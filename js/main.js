/* 
   Handles all API calls, rendering, modals, toasts, pagination
    */

'use strict';

/* ---- Cross-tab logout detection ---- */
(function() {
    function performLogout() {
        console.log('[Concurrency] Logout signal detected - redirecting to login');
        window.location.href = '../index.php';
    }
    
    // Use BroadcastChannel for modern browsers (most reliable)
    try {
        if (typeof BroadcastChannel !== 'undefined') {
            const logoutChannel = new BroadcastChannel('peakbook_logout');
            logoutChannel.onmessage = (event) => {
                if (event.data === 'logout') {
                    performLogout();
                }
            };
            console.log('[Concurrency] BroadcastChannel listener activated');
        }
    } catch (e) {
        console.error('[Concurrency] BroadcastChannel error:', e);
    }
    
    // Fallback: Listen for localStorage changes (older browsers)
    window.addEventListener('storage', (event) => {
        if (event.key === 'peakbook_logout_signal' && event.newValue === 'true') {
            performLogout();
        }
    });
    
    // Periodic session check - detect logout without refresh
    setInterval(async () => {
        try {
            const response = await fetch('../php/api.php?entity=session&action=check');
            const data = await response.json();
            if (!data.valid) {
                console.log('[Concurrency] Session invalidated - redirecting to login');
                performLogout();
            }
        } catch (e) {
            console.error('[Concurrency] Session check error:', e);
        }
    }, 2000); // Check every 2 seconds
    
    console.log('[Concurrency] Cross-tab logout detection initialized');
})();

/* ---- Cross-tab data synchronization ---- */
const Sync = {
    channel: null,

    init() {
        try {
            if (typeof BroadcastChannel !== 'undefined') {
                this.channel = new BroadcastChannel('peakbook_sync');
                this.channel.onmessage = (event) => this.handleMessage(event.data);
                console.log('[Concurrency] BroadcastChannel sync listener activated');
            }
        } catch (e) {
            console.error('[Concurrency] BroadcastChannel sync error:', e);
        }

        window.addEventListener('storage', (event) => {
            if (event.key === 'peakbook_sync_signal' && event.newValue) {
                try {
                    this.handleMessage(JSON.parse(event.newValue));
                } catch (err) {
                    console.error('[Concurrency] Invalid sync payload', err);
                }
            }
        });
    },

    broadcast(entity, action = 'refresh') {
        const payload = { entity, action, ts: Date.now() };

        try {
            if (this.channel) {
                this.channel.postMessage(payload);
            }
        } catch (e) {
            console.error('[Concurrency] BroadcastChannel sync send error:', e);
        }

        try {
            localStorage.setItem('peakbook_sync_signal', JSON.stringify(payload));
        } catch (e) {
            console.error('[Concurrency] localStorage sync send error:', e);
        }
    },

    handleMessage(data) {
        if (!data || !data.entity) return;

        const manager = window[`_tm_${data.entity}`];
        if (manager && typeof manager.loadData === 'function') {
            manager.loadData();
        }

        if (typeof refreshStats === 'function') {
            refreshStats();
        }
    },
};

Sync.init();

/* ---- Cross-tab auth synchronization ---- */
const AuthSync = {
    channel: null,

    init() {
        try {
            if (typeof BroadcastChannel !== 'undefined') {
                this.channel = new BroadcastChannel('peakbook_auth');
                this.channel.onmessage = (event) => {
                    if (event.data && event.data.action === 'login') {
                        this.handleLogin();
                    }
                };
                console.log('[Concurrency] BroadcastChannel auth listener activated');
            }
        } catch (e) {
            console.error('[Concurrency] BroadcastChannel auth error:', e);
        }

        window.addEventListener('storage', (event) => {
            if (event.key === 'peakbook_login_signal' && event.newValue) {
                try {
                    const payload = JSON.parse(event.newValue);
                    if (payload && payload.action === 'login') {
                        this.handleLogin();
                    }
                } catch (err) {
                    console.error('[Concurrency] Invalid auth payload', err);
                }
            }
        });
    },

    broadcastLogin() {
        const payload = { action: 'login', ts: Date.now() };

        try {
            if (this.channel) {
                this.channel.postMessage(payload);
            }
        } catch (e) {
            console.error('[Concurrency] BroadcastChannel auth send error:', e);
        }

        try {
            localStorage.setItem('peakbook_login_signal', JSON.stringify(payload));
        } catch (e) {
            console.error('[Concurrency] localStorage auth send error:', e);
        }
    },

    handleLogin() {
        const path = window.location.pathname;
        const isOnLoginPage = path.endsWith('/index.php') || path === '/' || path === '' || document.getElementById('loginForm');

        if (isOnLoginPage) {
            window.location.href = 'pages/dashboard.php';
        } else if (path.includes('/pages/') && !document.getElementById('loginForm')) {
            window.location.reload();
        }
    },
};

AuthSync.init();

/* ---- Toast notifications ---- */
const Toast = {
    container: null,

    init() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        document.body.appendChild(this.container);
    },

    show(message, type = 'success') {
        const t = document.createElement('div');
        t.className = `toast toast-${type}`;
        t.innerHTML = `
            <span>${type === 'success' ? '[OK]' : '[ERR]'}</span>
            <span>${message}</span>`;
        this.container.appendChild(t);

        // Auto-remove after 3s
        setTimeout(() => {
            t.style.opacity = '0';
            t.style.transition = 'opacity 0.3s';
            setTimeout(() => t.remove(), 300);
        }, 3000);
    },
};

/* ---- Generic API wrapper ---- */
const API = {
    base: '../php/api.php',

    async request(entity, action, params = {}, body = null) {
        const url = new URL(this.base, location.href);
        url.searchParams.set('entity', entity);
        url.searchParams.set('action', action);
        Object.entries(params).forEach(([k, v]) => url.searchParams.set(k, v));

        const opts = { method: body ? 'POST' : 'GET' };
        if (body) {
            const fd = new FormData();
            Object.entries(body).forEach(([k, v]) => v !== undefined && fd.append(k, v));
            opts.body = fd;
        }

        const res  = await fetch(url, opts);
        return res.json();
    },

    list  (entity, params)   { return this.request(entity, 'list',   params); },
    get   (entity, id)       { return this.request(entity, 'get',    { id }); },
    create(entity, data)     { return this.request(entity, 'create', {}, data); },
    update(entity, data)     { return this.request(entity, 'update', {}, data); },
    delete(entity, id)       { return this.request(entity, 'delete', {}, { id }); },
    stats ()                 { return this.request('books', 'stats'); },
    dropdown(entity)         { return this.request(entity, 'dropdown'); },
};

/* ---- Modal helper ---- */
const Modal = {
    open(id)  { document.getElementById(id).classList.add('open'); },
    close(id) { document.getElementById(id).classList.remove('open'); },

    confirm(message, sub, onYes) {
        document.getElementById('confirmMessage').textContent = message;
        document.getElementById('confirmSub').textContent     = sub || '';

        const yesBtn = document.getElementById('confirmYes');
        const clone  = yesBtn.cloneNode(true);
        yesBtn.parentNode.replaceChild(clone, yesBtn);
        clone.addEventListener('click', () => {
            this.close('confirmModal');
            onYes();
        });

        this.open('confirmModal');
    },
};

/* ---- Render a badge based on value ---- */
function badge(value) {
    if (!value) return '<span class="text-muted">—</span>';
    const map = {
        'new': 'badge-new', 'good': 'badge-good', 'fair': 'badge-fair',
        'poor': 'badge-poor', 'pending': 'badge-pending', 'paid': 'badge-paid',
        'delivered': 'badge-new', 'cancelled': 'badge-poor',
    };
    const cls = map[value.toLowerCase()] || 'badge-good';
    return `<span class="badge ${cls}">${value}</span>`;
}

/* ---- Format currency ---- */
function formatCurrency(v) {
    return '₱' + parseFloat(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}

/* TABLE MANAGER */

class TableManager {
    constructor({ entity, container, columns, formConfig }) {
        this.entity     = entity;
        this.container  = container;
        this.columns    = columns;
        this.formConfig = formConfig;
        this.page       = 1;
        this.search     = '';
        this.editId     = null;

        this.render();
        this.loadData();
    }

    /* Build the table HTML skeleton */
    render() {
        this.container.innerHTML = `
            <div class="table-controls">
                <div class="search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input type="text" placeholder="Search…" id="${this.entity}-search"/>
                </div>
                <button class="btn btn-primary btn-sm" id="${this.entity}-add-btn">
                    + Add
                </button>
            </div>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>${this.columns.map(c => `<th>${c.label}</th>`).join('')}<th>Actions</th></tr>
                    </thead>
                    <tbody id="${this.entity}-tbody">
                        <tr><td colspan="${this.columns.length + 1}" class="table-empty">Loading…</td></tr>
                    </tbody>
                </table>
                <div class="pagination" id="${this.entity}-pagination"></div>
            </div>`;

        // Search handler with debounce
        let timer;
        this.container.querySelector(`#${this.entity}-search`)
            .addEventListener('input', e => {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    this.search = e.target.value;
                    this.page   = 1;
                    this.loadData();
                }, 350);
            });

        // Add button
        this.container.querySelector(`#${this.entity}-add-btn`)
            .addEventListener('click', () => this.openForm());
    }

    /* Fetch data from API and render rows */
    async loadData() {
        const tbody = document.getElementById(`${this.entity}-tbody`);
        tbody.innerHTML = `<tr><td colspan="${this.columns.length + 1}" class="table-empty">Loading…</td></tr>`;

        const data = await API.list(this.entity, { page: this.page, search: this.search });
        this.lastData = data;

        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="${this.columns.length + 1}" class="table-empty">No records found.</td></tr>`;
            document.getElementById(`${this.entity}-pagination`).innerHTML = '';
            return;
        }

        tbody.innerHTML = data.data.map(row => `
            <tr>
                ${this.columns.map(c => `<td>${this.renderCell(c, row)}</td>`).join('')}
                <td>
                    <button class="btn-icon" title="Edit"
                            onclick="window._tm_${this.entity}.openForm(${row[Object.keys(row)[0]]})">
                        ✎
                    </button>
                    <button class="btn-icon danger" title="Delete"
                            onclick="window._tm_${this.entity}.confirmDelete(${row[Object.keys(row)[0]]})">
                        ✕
                    </button>
                </td>
            </tr>`).join('');

        this.renderPagination(data);
    }

    /* Render an individual cell based on column config */
    renderCell(col, row) {
        const val = row[col.key];
        if (col.badge)     return badge(val);
        if (col.currency)  return formatCurrency(val);
        if (col.alt)       return val || row[col.alt] || '—';
        return val !== null && val !== undefined && val !== '' ? val : '—';
    }

    /* Pagination bar */
    renderPagination(data) {
        const pg = document.getElementById(`${this.entity}-pagination`);
        if (data.last_page <= 1) { pg.innerHTML = ''; return; }

        const pages = Array.from({ length: data.last_page }, (_, i) => i + 1);
        pg.innerHTML = `
            <span class="page-info">
                ${(data.page - 1) * data.per_page + 1}–${Math.min(data.page * data.per_page, data.total)}
                of ${data.total}
            </span>
            <button class="page-btn" ${data.page === 1 ? 'disabled' : ''}
                    onclick="window._tm_${this.entity}.goPage(${data.page - 1})">‹</button>
            ${pages.map(p => `
                <button class="page-btn ${p === data.page ? 'current' : ''}"
                        onclick="window._tm_${this.entity}.goPage(${p})">${p}</button>`).join('')}
            <button class="page-btn" ${data.page === data.last_page ? 'disabled' : ''}
                    onclick="window._tm_${this.entity}.goPage(${data.page + 1})">›</button>`;
    }

    goPage(p) { this.page = p; this.loadData(); }

    /* Open the add/edit modal and populate dropdowns */
    async openForm(id = null) {
        this.editId = id;
        const modal = document.getElementById(`${this.entity}-modal`);
        const form  = document.getElementById(`${this.entity}-form`);

        if (!modal || !form) return;

        // Reset form
        form.reset();
        modal.querySelector('.modal-title').textContent = id ? `Edit ${this.entityLabel()}` : `Add ${this.entityLabel()}`;

        // Load dropdown options if needed
        for (const field of this.formConfig) {
            if (field.dropdown) {
                const select = form.querySelector(`[name="${field.name}"]`);
                if (!select) continue;
                const opts = await API.dropdown(field.dropdown);
                select.innerHTML = `<option value="">— Select —</option>` +
                    opts.map(o => `<option value="${o.id}" data-price="${o.price || ''}">${o.label}</option>`).join('');

                // Auto-fill price when book is selected in orders form
                if (field.dropdown === 'books') {
                    select.addEventListener('change', () => {
                        const opt   = select.options[select.selectedIndex];
                        const price = parseFloat(opt.dataset.price || 0);
                        const qtyEl = form.querySelector('[name="quantity"]');
                        if (qtyEl) {
                            qtyEl.addEventListener('input', () => {
                                const total = form.querySelector('[name="total_amount"]');
                                if (total) total.value = (price * parseInt(qtyEl.value || 1)).toFixed(2);
                            });
                            const total = form.querySelector('[name="total_amount"]');
                            if (total) total.value = (price * parseInt(qtyEl.value || 1)).toFixed(2);
                        }
                    });
                }
            }
        }

        // Prefill values when editing
        if (id) {
            const rec = await API.get(this.entity, id);
            this.formConfig.forEach(f => {
                const el = form.querySelector(`[name="${f.name}"]`);
                if (el && rec[f.name] !== undefined) el.value = rec[f.name];
            });
        }

        Modal.open(`${this.entity}-modal`);

        // Update submit button label
        const submitBtn = modal.querySelector('.btn-primary[onclick*="submitForm"]');
        if (submitBtn) submitBtn.textContent = id ? 'Save Changes' : 'Add';
    }

    /* Submit the form (create or update) */
    async submitForm() {
        const form = document.getElementById(`${this.entity}-form`);
        const fd   = new FormData(form);
        const data = Object.fromEntries(fd.entries());

        if (this.editId) data.id = this.editId;

        const res = this.editId
            ? await API.update(this.entity, data)
            : await API.create(this.entity, data);

        if (res.success) {
            Modal.close(`${this.entity}-modal`);
            Toast.show(this.editId ? 'Record updated successfully!' : 'Added Successfully!');
            this.loadData();
            Sync.broadcast(this.entity, this.editId ? 'update' : 'create');
            if (typeof refreshStats === 'function') refreshStats();
        } else {
            Toast.show(res.error || 'An error occurred.', 'error');
        }
    }

    /* Confirm before deleting */
    confirmDelete(id) {
        Modal.confirm(
            'Are You Sure You Want To Permanently Delete This Record?',
            'This action cannot be undone.',
            async () => {
                const res = await API.delete(this.entity, id);
                if (res.success) {
                    Toast.show('Record deleted.');
                    this.loadData();
                    Sync.broadcast(this.entity, 'delete');
                    if (typeof refreshStats === 'function') refreshStats();
                } else {
                    Toast.show(res.error || 'Delete failed.', 'error');
                }
            }
        );
    }

    entityLabel() {
        return this.entity.charAt(0).toUpperCase() + this.entity.slice(1, -1); // e.g. "books" -> "Book"
    }
}

/* Lightweight fetch -> JSON helper with graceful fallback */
async function fetchJson(url, options = {}) {
    const res = await fetch(url, options);
    const text = await res.text();

    try {
        return JSON.parse(text);
    } catch (err) {
        console.error('Invalid JSON response from', url, text);
        return { success: false, error: 'Invalid server response.' };
    }
}

/* ---- Auth form helpers ---- */

function initLoginForm() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const btn   = form.querySelector('[type=submit]');
        const alert = document.getElementById('loginAlert');
        btn.textContent = 'Logging in…';
        btn.disabled    = true;

        const fd = new FormData(form);
        const data = await fetchJson('php/login.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        });

        if (data.success) {
            AuthSync.broadcastLogin();
            window.location.href = 'pages/dashboard.php';
        } else {
            alert.textContent = data.error || 'Login failed.';
            alert.classList.add('show');
            btn.textContent = 'Log In';
            btn.disabled = false;
        }
    });
}

function initRegisterForm() {
    const form = document.getElementById('registerForm');
    if (!form) return;

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const btn   = form.querySelector('[type=submit]');
        const alert = document.getElementById('registerAlert');
        btn.textContent = 'Creating account…';
        btn.disabled    = true;

        const fd = new FormData(form);
        const data = await fetchJson('../php/register.php', { method: 'POST', body: fd });

        if (data.success) {
            alert.textContent = 'Account created! Redirecting to login…';
            alert.className = 'alert alert-success show';
            setTimeout(() => window.location.href = '../index.php', 1500);
        } else {
            alert.textContent = data.error || 'Account creation failed.';
            alert.className = 'alert alert-error show';
            btn.textContent = 'Sign Up';
            btn.disabled = false;
        }
    });
}

/* ---- DOMContentLoaded entry point ---- */
document.addEventListener('DOMContentLoaded', () => {
    Toast.init();
    initLoginForm();
    initRegisterForm();

    // Close modals when clicking backdrop
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', e => {
            if (e.target === backdrop) backdrop.classList.remove('open');
        });
    });
});