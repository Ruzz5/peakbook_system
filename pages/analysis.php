<?php
// ============================================================
// PeakBook - Analysis Page
// ============================================================
require_once '../php/auth.php';
requireLogin();

$activePage = 'analysis';
$user       = currentUser();
$initials   = strtoupper(substr($user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeakBook – Analysis</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="app-layout">
    <?php include 'layout.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <h1 class="topbar-title">Analysis</h1>
            <div class="topbar-right">
                <div class="topbar-user">
                    <div class="topbar-avatar"><?= $initials ?></div>
                    <span><?= htmlspecialchars($user) ?></span>
                </div>
            </div>
        </header>

        <div class="page-body">

            <!-- Stats summary -->
            <div class="stats-row" style="margin-bottom:1.5rem;">
                <div class="stat-card">
                    <div><div class="stat-number" id="s-books">—</div><div class="stat-label">Total Books</div></div>
                </div>
                <div class="stat-card">
                    <div><div class="stat-number" id="s-orders">—</div><div class="stat-label">Total Orders</div></div>
                </div>
                <div class="stat-card">
                    <div><div class="stat-number" id="s-income">—</div><div class="stat-label">Total Income</div></div>
                </div>
            </div>

            <div class="reports-grid">
                <!-- Weekly Income Bar Chart -->
                <div class="chart-card">
                    <p class="chart-title">Weekly Income (Last 7 Days)</p>
                    <canvas id="weeklyBar"></canvas>
                </div>

                <!-- Order Status Pie -->
                <div class="chart-card">
                    <p class="chart-title">Order Status Distribution</p>
                    <canvas id="statusPie"></canvas>
                </div>

                <!-- Book Condition Bar -->
                <div class="chart-card">
                    <p class="chart-title">Book Inventory by Condition</p>
                    <canvas id="conditionBar"></canvas>
                </div>

                <!-- Top Books by Quantity -->
                <div class="chart-card">
                    <p class="chart-title">Top 5 Books by Stock</p>
                    <canvas id="topBooks"></canvas>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/main.js"></script>
<script>
    const CHART_COLORS = {
        blue:   'rgba(78, 78, 78, 0.7)',
        green:  'rgba(0, 0, 0, 0.7)',
        amber:  'rgba(134, 134, 134, 0.7)',
        red:    'rgba(49, 49, 49, 0.7)',
        purple: 'rgba(211, 204, 208, 0.7)',
        teal:   'rgba(77, 74, 75, 0.7)',
    };

    const GRID_COLOR = 'rgba(255,255,255,0.05)';
    const TICK_COLOR = '#7a7a8e';

    /* ---- Load all chart data ---- */
    async function loadAll() {
        // Stats
        const stats = await API.stats();
        document.getElementById('s-books').textContent  = stats.books   || 0;
        document.getElementById('s-orders').textContent = stats.orders  || 0;
        document.getElementById('s-income').textContent = '₱' + (stats.income || '0.00');
        renderWeekly(stats.weekly || []);

        // Orders list for status pie (fetch up to 1000 records for chart accuracy)
        const orders = await API.list('orders', { page: 1, search: '', per_page: 1000 });
        renderStatusPie(orders.data || []);

        // Books for condition and top books (fetch up to 1000 records)
        const books  = await API.list('books', { page: 1, search: '', per_page: 1000 });
        renderConditionBar(books.data || []);
        renderTopBooks(books.data || []);
    }

    /* Weekly income bar */
    function renderWeekly(weekly) {
        const days = [], totals = [];
        for (let i = 6; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            const key = d.toISOString().slice(0, 10);
            days.push(d.toLocaleDateString('en-PH', { weekday: 'short' }));
            const m = weekly.find(w => w.day === key);
            totals.push(m ? parseFloat(m.total) : 0);
        }
        new Chart(document.getElementById('weeklyBar').getContext('2d'), {
            type: 'bar',
            data: {
                labels: days,
                datasets: [{ label: '₱', data: totals,
                    backgroundColor: CHART_COLORS.blue, borderRadius: 4 }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } },
                    y: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR }, beginAtZero: true },
                }
            }
        });
    }

    /* Order status pie */
    function renderStatusPie(orders) {
        const counts = {};
        orders.forEach(o => {
            const s = o.order_status || 'Unknown';
            counts[s] = (counts[s] || 0) + 1;
        });
        const labels = Object.keys(counts);
        const values = Object.values(counts);
        const colors = [CHART_COLORS.amber, CHART_COLORS.green, CHART_COLORS.red,
                        CHART_COLORS.blue, CHART_COLORS.purple];

        new Chart(document.getElementById('statusPie').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: TICK_COLOR, font: { size: 12 } } }
                }
            }
        });
    }

    /* Book condition bar */
    function renderConditionBar(books) {
        const counts = {};
        books.forEach(b => {
            const c = b.book_condition || 'Unknown';
            counts[c] = (counts[c] || 0) + parseInt(b.quantity || 0);
        });
        new Chart(document.getElementById('conditionBar').getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(counts),
                datasets: [{
                    label: 'Qty',
                    data: Object.values(counts),
                    backgroundColor: [CHART_COLORS.green, CHART_COLORS.blue,
                                      CHART_COLORS.amber, CHART_COLORS.red],
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } },
                    y: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR }, beginAtZero: true },
                }
            }
        });
    }

    /* Top 5 books by stock qty */
    function renderTopBooks(books) {
        const sorted = [...books].sort((a, b) => b.quantity - a.quantity).slice(0, 5);
        new Chart(document.getElementById('topBooks').getContext('2d'), {
            type: 'bar',
            data: {
                labels: sorted.map(b => b.book_title.length > 20 ? b.book_title.slice(0, 18) + '…' : b.book_title),
                datasets: [{
                    label: 'Stock',
                    data: sorted.map(b => b.quantity),
                    backgroundColor: CHART_COLORS.teal,
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR }, beginAtZero: true },
                    y: { ticks: { color: TICK_COLOR }, grid: { color: GRID_COLOR } },
                }
            }
        });
    }

    loadAll();
</script>
</body>
</html>