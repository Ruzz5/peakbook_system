<?php
// e Shows weekly income summary with printable table

require_once '../php/auth.php';
requireLogin();

$activePage = 'reports';
$user       = currentUser();
$initials   = strtoupper(substr($user, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PeakBook – Reports</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        @media print {
            .sidebar, .topbar, .no-print { display: none !important; }
            .main-content { margin: 0 !important; }
        }
        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
        }
        .report-period {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .report-period input[type="date"] {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 0.4rem 0.7rem;
            color: var(--text-primary);
            font-size: 0.84rem;
        }
        .summary-box {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .summary-item {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1rem;
            text-align: center;
        }
        .summary-value {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        .summary-lbl { font-size: 0.77rem; color: var(--text-muted); margin-top: 0.3rem; }
    </style>
</head>
<body>

<div class="app-layout">
    <?php include 'layout.php'; ?>

    <div class="main-content">
        <header class="topbar">
            <h1 class="topbar-title">Reports</h1>
            <div class="topbar-right no-print">
                <button class="btn btn-outline btn-sm" onclick="window.print()">🖨 Print</button>
                <div class="topbar-user">
                    <div class="topbar-avatar"><?= $initials ?></div>
                    <span><?= htmlspecialchars($user) ?></span>
                </div>
            </div>
        </header>

        <div class="page-body">

            <!-- Date range picker -->
            <div class="report-header no-print">
                <h2 class="section-title" style="margin:0;">Income Summary Report</h2>
                <div class="report-period">
                    <input type="date" id="from-date">
                    <span style="color:var(--text-muted);">to</span>
                    <input type="date" id="to-date">
                    <button class="btn btn-primary btn-sm" onclick="loadReport()">Generate</button>
                </div>
            </div>

            <!-- Summary boxes -->
            <div class="summary-box">
                <div class="summary-item">
                    <div class="summary-value" id="r-orders">—</div>
                    <div class="summary-lbl">Orders</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="r-income">—</div>
                    <div class="summary-lbl">Total Income</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="r-avg">—</div>
                    <div class="summary-lbl">Avg. Per Order</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value" id="r-customers">—</div>
                    <div class="summary-lbl">Unique Customers</div>
                </div>
            </div>

            <!-- Income Line Chart -->
            <div class="chart-card" style="margin-bottom:1.2rem;">
                <p class="chart-title">Daily Income</p>
                <canvas id="reportLine"></canvas>
            </div>

            <!-- Payments table -->
            <div class="table-wrap">
                <table class="data-table" id="report-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Customer</th>
                            <th>Order Date</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="report-tbody">
                        <tr><td colspan="6" class="table-empty">Select a date range and click Generate.</td></tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script src="../js/main.js"></script>
<script>
    let lineChart = null;

    /* Set default date range to current week */
    function initDates() {
        const today  = new Date();
        const monday = new Date(today);
        monday.setDate(today.getDate() - today.getDay() + 1);

        document.getElementById('from-date').value = monday.toISOString().slice(0, 10);
        document.getElementById('to-date').value   = today.toISOString().slice(0, 10);
    }

    /* Load and render the report */
    async function loadReport() {
        const from = document.getElementById('from-date').value;
        const to   = document.getElementById('to-date').value;

        if (!from || !to) { alert('Please select a valid date range.'); return; }

        // Fetch all payments and filter client-side by date range
        // (For a production app you'd add date filtering to the API)
        const data = await API.list('payments', { page: 1, search: '' });
        const rows = (data.data || []).filter(p => p.payment_date >= from && p.payment_date <= to);

        // Aggregate
        const totalIncome = rows.reduce((s, r) => s + parseFloat(r.amount_paid || 0), 0);
        const customers   = new Set(rows.map(r => r.customer_id)).size;
        const avgOrder    = rows.length ? totalIncome / rows.length : 0;

        document.getElementById('r-orders').textContent    = rows.length;
        document.getElementById('r-income').textContent    = '₱' + totalIncome.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        document.getElementById('r-avg').textContent       = '₱' + avgOrder.toLocaleString('en-PH', { minimumFractionDigits: 2 });
        document.getElementById('r-customers').textContent = customers;

        // Render table
        const tbody = document.getElementById('report-tbody');
        if (rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="table-empty">No payments found in this period.</td></tr>';
        } else {
            tbody.innerHTML = rows.map(r => `
                <tr>
                    <td>${r.payment_id}</td>
                    <td>${r.customer_name || '—'}</td>
                    <td>${r.order_date    || '—'}</td>
                    <td>${r.payment_date  || '—'}</td>
                    <td>${r.payment_method || '—'}</td>
                    <td>₱${parseFloat(r.amount_paid||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
                </tr>`).join('');
        }

        renderLineChart(rows);
    }

    /* Daily income line chart */
    function renderLineChart(rows) {
        // Group by payment_date
        const byDay = {};
        rows.forEach(r => {
            const d = r.payment_date;
            if (d) byDay[d] = (byDay[d] || 0) + parseFloat(r.amount_paid || 0);
        });

        const labels = Object.keys(byDay).sort();
        const values = labels.map(d => byDay[d]);

        const ctx = document.getElementById('reportLine').getContext('2d');
        if (lineChart) lineChart.destroy();

        lineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Income (₱)',
                    data: values,
                    borderColor: 'rgba(45,93,181,1)',
                    backgroundColor: 'rgba(45,93,181,0.15)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(45,93,181,1)',
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#7a7a8e' }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: { ticks: { color: '#7a7a8e' }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true },
                }
            }
        });
    }

    initDates();
</script>
</body>
</html>