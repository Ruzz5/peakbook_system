<?php
require_once '../php/auth.php';
requireLogin();

$activePage = 'dashboard';
$user       = currentUser();
$initials   = strtoupper(substr($user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeakBook – Dashboard</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- Chart.js for the income graph -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="app-layout">
    <?php include 'layout.php'; ?>

    <div class="main-content">

        <!-- Topbar -->
        <header class="topbar">
            <h1 class="topbar-title">Dashboard</h1>
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
                        <img src="../images/book.png" alt="books">
                    </div>
                    <div>
                        <div class="stat-number" id="stat-books">—</div>
                        <div class="stat-label">Total Books</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon cust-icon">
                        <img src="../images/update.png" alt="customers">
                    </div>
                    <div>
                        <div class="stat-number" id="stat-customers">—</div>
                        <div class="stat-label">Customers</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon income-icon">
                        <img src="../images/money.png" alt="income">
                    </div>
                    <div>
                        <div class="stat-number" id="stat-income">—</div>
                        <div class="stat-label">Total Income</div>
                    </div>
                </div>
            </div>

            <!-- Weekly income chart -->
            <div class="chart-card">
                <p class="chart-title">Weekly Income</p>
                <canvas id="weeklyChart"></canvas>
            </div>

            <!-- Quick links -->
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-top:1rem;">
                <a href="inventory.php" class="stat-card" style="text-decoration:none;cursor:pointer;">
                    <div>
                        <div class="stat-label" style="margin-bottom:0.3rem;"> Inventory</div>
                        <div style="font-size:0.82rem;color:var(--text-faint);">Manage books, orders, customers…</div>
                    </div>
                </a>
                <a href="analysis.php" class="stat-card" style="text-decoration:none;cursor:pointer;">
                    <div>
                        <div class="stat-label" style="margin-bottom:0.3rem;"> Analysis</div>
                        <div style="font-size:0.82rem;color:var(--text-faint);">View sales trends and charts</div>
                    </div>
                </a>
                <a href="reports.php" class="stat-card" style="text-decoration:none;cursor:pointer;">
                    <div>
                        <div class="stat-label" style="margin-bottom:0.3rem;"> Reports</div>
                        <div style="font-size:0.82rem;color:var(--text-faint);">Weekly income summaries</div>
                    </div>
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Confirm delete modal (shared across all pages) -->
<div class="modal-backdrop" id="confirmModal">
    <div class="modal modal-sm">
        <div class="confirm-icon">⚠</div>
        <p class="confirm-text" id="confirmMessage">Are you sure?</p>
        <p class="confirm-sub"  id="confirmSub"></p>
        <div class="modal-actions" style="justify-content:center;gap:1rem;">
            <button class="btn btn-outline" onclick="Modal.close('confirmModal')">NO</button>
            <button class="btn btn-danger"  id="confirmYes">YES</button>
        </div>
    </div>
</div>

<script src="../js/main.js"></script>
<script>
    /* ---- Load stats and render weekly chart ---- */
    async function refreshStats() {
        const data = await API.stats();

        document.getElementById('stat-books').textContent     = data.books     || 0;
        document.getElementById('stat-customers').textContent = data.customers || 0;
        document.getElementById('stat-income').textContent    = '₱' + (data.income || '0.00');

        renderWeeklyChart(data.weekly || []);
    }

    let weeklyChart = null;

    function renderWeeklyChart(weekly) {
        // Build last 7 days labels
        const days   = [];
        const totals = [];

        for (let i = 6; i >= 0; i--) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            const key = d.toISOString().slice(0, 10);
            const lbl = d.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' });
            days.push(lbl);
            const match = weekly.find(w => w.day === key);
            totals.push(match ? parseFloat(match.total) : 0);
        }

        const ctx = document.getElementById('weeklyChart').getContext('2d');
        if (weeklyChart) weeklyChart.destroy();

        weeklyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: days,
                datasets: [{
                    label: 'Income (₱)',
                    data: totals,
                    backgroundColor: 'rgba(45,93,181,0.5)',
                    borderColor:     'rgba(45,93,181,1)',
                    borderWidth: 1,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => '₱' + ctx.raw.toLocaleString('en-PH', { minimumFractionDigits: 2 })
                        }
                    }
                },
                scales: {
                    x: { ticks: { color: '#7a7a8e' }, grid: { color: 'rgba(255,255,255,0.04)' } },
                    y: { ticks: { color: '#7a7a8e' }, grid: { color: 'rgba(255,255,255,0.04)' },
                         beginAtZero: true }
                }
            }
        });
    }

    refreshStats();
</script>
</body>
</html>