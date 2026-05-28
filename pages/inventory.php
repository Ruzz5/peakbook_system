<?php
// Manages: Books, Customers, Orders, Suppliers, Couriers, Payments
require_once '../php/auth.php';
requireLogin();

$activePage = 'inventory';
$user       = currentUser();
$initials   = strtoupper(substr($user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeakBook – Inventory</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<div class="app-layout">
    <?php include 'layout.php'; ?>

    <div class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <h1 class="topbar-title">Inventory</h1>

            <div class="topbar-right">
                <div class="topbar-user">
                    <div class="topbar-avatar"><?= $initials ?></div>
                    <span><?= htmlspecialchars($user) ?></span>
                </div>
            </div>
        </header>

        <div class="page-body">

            <!-- Stats cards -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-icon books-icon">
                        <img src="../images/book.png" alt="">
                    </div>
                    <div>
                        <div class="stat-number" id="stat-books">—</div>
                        <div class="stat-label">Total Books</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon cust-icon">
                        <img src="../images/update.png" alt="">
                    </div>
                    <div>
                        <div class="stat-number" id="stat-customers">—</div>
                        <div class="stat-label">Customers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon income-icon">
                        <img src="../images/money.png" alt="">
                    </div>
                    <div>
                        <div class="stat-number" id="stat-income">—</div>
                        <div class="stat-label">Total Income</div>
                    </div>
                </div>
            </div>

            <!-- Entity tabs -->
            <div class="table-controls" style="margin-bottom:0.8rem;">
                <div class="entity-tabs">
                    <button class="entity-tab active" data-entity="orders"    onclick="switchEntity('orders')">
                         <span>Orders</span>
                    </button>
                    <button class="entity-tab"        data-entity="customers" onclick="switchEntity('customers')">
                         <span>Customers</span>
                    </button>
                    <button class="entity-tab"        data-entity="books"     onclick="switchEntity('books')">
                        <span>Books</span>
                    </button>
                    <button class="entity-tab"        data-entity="suppliers" onclick="switchEntity('suppliers')">
                        <span>Suppliers</span>
                    </button>
                    <button class="entity-tab"        data-entity="couriers"  onclick="switchEntity('couriers')">
                         <span>Couriers</span>
                    </button>
                    <button class="entity-tab"        data-entity="payments"  onclick="switchEntity('payments')">
                         <span>Payments</span>
                    </button>
                </div>
            </div>

            <!-- Table containers (only the active one is shown) -->
            <div id="view-orders"></div>
            <div id="view-customers"  style="display:none"></div>
            <div id="view-books"      style="display:none"></div>
            <div id="view-suppliers"  style="display:none"></div>
            <div id="view-couriers"   style="display:none"></div>
            <div id="view-payments"   style="display:none"></div>

        </div><!-- /page-body -->
    </div><!-- /main-content -->
</div>

<!-- Orders Modal -->
<div class="modal-backdrop" id="orders-modal">
    <div class="modal">
        <button class="modal-close" onclick="Modal.close('orders-modal')">✕</button>
        <h2 class="modal-title">Add Order</h2>
        <form class="modal-form" id="orders-form">
            <div class="form-group">
                <label class="form-label">Book</label>
                <select name="book_id" class="form-control" required></select>
            </div>
            <div class="modal-row">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Total Amount (₱)</label>
                    <input type="number" name="total_amount" class="form-control" step="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control" required></select>
            </div>
            <div class="form-group">
                <label class="form-label">Courier</label>
                <select name="courier_id" class="form-control"></select>
            </div>
            <div class="modal-row">
                <div class="form-group">
                    <label class="form-label">Order Date</label>
                    <input type="date" name="order_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="order_status" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="Modal.close('orders-modal')">Back</button>
                <button type="button" class="btn btn-primary"
                        onclick="window._tm_orders.submitForm()">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Customers Modal -->
<div class="modal-backdrop" id="customers-modal">
    <div class="modal">
        <button class="modal-close" onclick="Modal.close('customers-modal')">✕</button>
        <h2 class="modal-title">Add Customer</h2>
        <form class="modal-form" id="customers-form">
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="Modal.close('customers-modal')">Back</button>
                <button type="button" class="btn btn-primary"
                        onclick="window._tm_customers.submitForm()">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Books Modal -->
<div class="modal-backdrop" id="books-modal">
    <div class="modal">
        <button class="modal-close" onclick="Modal.close('books-modal')">✕</button>
        <h2 class="modal-title">Add Book</h2>
        <form class="modal-form" id="books-form">
            <div class="form-group">
                <label class="form-label">Book Title</label>
                <input type="text" name="book_title" class="form-control" required>
            </div>
            <div class="modal-row">
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Price (₱)</label>
                    <input type="number" name="price" class="form-control" step="0.01" required>
                </div>
            </div>
            <div class="modal-row">
                <div class="form-group">
                    <label class="form-label">Condition</label>
                    <select name="book_condition" class="form-control">
                        <option value="New">New</option>
                        <option value="Good">Good</option>
                        <option value="Fair">Fair</option>
                        <option value="Poor">Poor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Supplier</label>
                    <select name="supplier_id" class="form-control"></select>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="Modal.close('books-modal')">Back</button>
                <button type="button" class="btn btn-primary"
                        onclick="window._tm_books.submitForm()">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Suppliers Modal -->
<div class="modal-backdrop" id="suppliers-modal">
    <div class="modal">
        <button class="modal-close" onclick="Modal.close('suppliers-modal')">✕</button>
        <h2 class="modal-title">Add Supplier</h2>
        <form class="modal-form" id="suppliers-form">
            <div class="form-group">
                <label class="form-label">Supplier Name</label>
                <input type="text" name="supplier_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <input type="text" name="address" class="form-control">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="Modal.close('suppliers-modal')">Back</button>
                <button type="button" class="btn btn-primary"
                        onclick="window._tm_suppliers.submitForm()">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Couriers Modal -->
<div class="modal-backdrop" id="couriers-modal">
    <div class="modal">
        <button class="modal-close" onclick="Modal.close('couriers-modal')">✕</button>
        <h2 class="modal-title">Add Courier</h2>
        <form class="modal-form" id="couriers-form">
            <div class="form-group">
                <label class="form-label">Courier Name</label>
                <input type="text" name="courier_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="Modal.close('couriers-modal')">Back</button>
                <button type="button" class="btn btn-primary"
                        onclick="window._tm_couriers.submitForm()">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Payments Modal -->
<div class="modal-backdrop" id="payments-modal">
    <div class="modal">
        <button class="modal-close" onclick="Modal.close('payments-modal')">✕</button>
        <h2 class="modal-title">Add Payment</h2>
        <form class="modal-form" id="payments-form">
            <div class="form-group">
                <label class="form-label">Order</label>
                <select name="order_id" class="form-control" required></select>
            </div>
            <div class="form-group">
                <label class="form-label">Customer</label>
                <select name="customer_id" class="form-control" required></select>
            </div>
            <div class="modal-row">
                <div class="form-group">
                    <label class="form-label">Payment Date</label>
                    <input type="date" name="payment_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Method</label>
                    <select name="payment_method" class="form-control">
                        <option value="Cash">Cash</option>
                        <option value="GCash">GCash</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Card">Card</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Amount Paid (₱)</label>
                <input type="number" name="amount_paid" class="form-control" step="0.01" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="Modal.close('payments-modal')">Back</button>
                <button type="button" class="btn btn-primary"
                        onclick="window._tm_payments.submitForm()">Add</button>
            </div>
        </form>
    </div>
</div>

<!-- Confirm delete modal -->
<div class="modal-backdrop" id="confirmModal">
    <div class="modal modal-sm">
        <div class="confirm-icon">⚠</div>
        <p class="confirm-text" id="confirmMessage">Are You Sure You Want To Permanently Delete This Record?</p>
        <p class="confirm-sub"  id="confirmSub">This action cannot be undone.</p>
        <div class="modal-actions" style="justify-content:center;gap:1rem;">
            <button class="btn btn-outline" onclick="Modal.close('confirmModal')">NO</button>
            <button class="btn btn-danger"  id="confirmYes">YES</button>
        </div>
    </div>
</div>

<script src="../js/main.js"></script>
<script>
/* ---- Column definitions for each entity ---- */
const ENTITY_COLS = {
    orders: [
        { key: 'order_id',       label: 'Order ID' },
        { key: 'customer_name',  label: 'Customer' },
        { key: 'book_title',     label: 'Book' },
        { key: 'total_amount',   label: 'Total',   currency: true },
        { key: 'order_date',     label: 'Date' },
        { key: 'order_status',   label: 'Status',  badge: true },
    ],
    customers: [
        { key: 'customer_id',    label: 'ID' },
        { key: 'fullname',       label: 'Full Name' },
        { key: 'address',        label: 'Address' },
        { key: 'contact_number', label: 'Contact' },
    ],
    books: [
        { key: 'book_id',        label: 'ID' },
        { key: 'book_title',     label: 'Title' },
        { key: 'quantity',       label: 'Qty' },
        { key: 'price',          label: 'Price',   currency: true },
        { key: 'book_condition', label: 'Condition', badge: true },
        { key: 'supplier_name',  label: 'Supplier' },
    ],
    suppliers: [
        { key: 'supplier_id',    label: 'ID' },
        { key: 'supplier_name',  label: 'Supplier Name' },
        { key: 'contact_number', label: 'Contact' },
        { key: 'address',        label: 'Address' },
    ],
    couriers: [
        { key: 'courier_id',     label: 'ID' },
        { key: 'courier_name',   label: 'Courier Name' },
        { key: 'contact_number', label: 'Contact' },
    ],
    payments: [
        { key: 'payment_id',     label: 'ID' },
        { key: 'customer_name',  label: 'Customer' },
        { key: 'order_date',     label: 'Order Date' },
        { key: 'payment_date',   label: 'Payment Date' },
        { key: 'payment_method', label: 'Method' },
        { key: 'amount_paid',    label: 'Amount', currency: true },
    ],
};

/* ---- Form field configs (for dropdowns) ---- */
const ENTITY_FORM = {
    orders:    [
        { name: 'book_id',      dropdown: 'books' },
        { name: 'quantity' },
        { name: 'total_amount' },
        { name: 'customer_id', dropdown: 'customers' },
        { name: 'courier_id',  dropdown: 'couriers' },
        { name: 'order_date' },
        { name: 'order_status' },
    ],
    customers: [{ name: 'fullname' }, { name: 'address' }, { name: 'contact_number' }],
    books:     [{ name: 'book_title' }, { name: 'quantity' }, { name: 'price' },
                { name: 'book_condition' }, { name: 'supplier_id', dropdown: 'suppliers' }],
    suppliers: [{ name: 'supplier_name' }, { name: 'contact_number' }, { name: 'address' }],
    couriers:  [{ name: 'courier_name' }, { name: 'contact_number' }],
    payments:  [{ name: 'order_id', dropdown: 'orders' },
                { name: 'customer_id', dropdown: 'customers' },
                { name: 'payment_date' }, { name: 'payment_method' }, { name: 'amount_paid' }],
};

/* ---- Initialize all TableManagers ---- */
const entities = ['orders', 'customers', 'books', 'suppliers', 'couriers', 'payments'];
entities.forEach(e => {
    window[`_tm_${e}`] = new TableManager({
        entity:     e,
        container:  document.getElementById(`view-${e}`),
        columns:    ENTITY_COLS[e],
        formConfig: ENTITY_FORM[e],
    });
});

/* ---- Switch active entity tab ---- */
let currentEntity = 'orders';

function switchEntity(entity) {
    // Hide all views
    entities.forEach(e => {
        document.getElementById(`view-${e}`).style.display = 'none';
        document.querySelector(`[data-entity="${e}"]`).classList.remove('active');
    });
    // Show selected
    document.getElementById(`view-${entity}`).style.display = 'block';
    document.querySelector(`[data-entity="${entity}"]`).classList.add('active');
    currentEntity = entity;
}

function showView(v) {
    if (v === 'books') switchEntity('books');
}

/* ---- Load dashboard stats ---- */
async function refreshStats() {
    const data = await API.stats();
    document.getElementById('stat-books').textContent     = data.books     || 0;
    document.getElementById('stat-customers').textContent = data.customers || 0;
    document.getElementById('stat-income').textContent    = '₱' + (data.income || '0.00');
}

refreshStats();

/* ---- Pre-fill today's date in order/payment forms ---- */
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date().toISOString().slice(0, 10);
    document.querySelectorAll('input[type="date"]').forEach(el => {
        if (!el.value) el.value = today;
    });
});
</script>
</body>
</html>