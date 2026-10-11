<?php
$pageTitle = 'Reports & Analytics';
include 'includes/header.php';

// Generate some report data
$totalCustomers = db_row("SELECT COUNT(*) AS c FROM customers")['c'];
$totalBookings  = db_row("SELECT COUNT(*) AS c FROM bookings")['c'];
$totalVisa      = db_row("SELECT COUNT(*) AS c FROM visa_applications")['c'];
$totalRevenue   = db_row("SELECT SUM(amount) AS total FROM payments")['total'] ?: 0;

// Monthly Revenue for the chart (last 6 months)
$months = [];
$revenueData = [];
for ($i=5; $i>=0; $i--) {
    $date = date('Y-m', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($date));
    // Query payments for this month
    $rev = db_row("SELECT SUM(amount) AS s FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?", [$date])['s'];
    $revenueData[] = (float)$rev;
}

// Bookings by Type
$bookingTypes = db_select("SELECT booking_type, COUNT(*) AS count FROM bookings GROUP BY booking_type");
$btLabels = [];
$btData = [];
foreach ($bookingTypes as $bt) {
    $btLabels[] = ucwords(str_replace('_',' ',$bt['booking_type']));
    $btData[] = (int)$bt['count'];
}

// Recent Invoices Overdue
$overdueInvoices = db_select("
    SELECT i.invoice_number, c.first_name, c.last_name, (i.total - i.paid_amount) AS balance
    FROM invoices i
    JOIN customers c ON c.id = i.customer_id
    WHERE i.status = 'overdue'
    ORDER BY i.due_date ASC LIMIT 5");

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-header">
    <div class="page-header-left">
        <h2>Reports & Analytics</h2>
        <p>Agency performance and financial overview</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-ghost" onclick="window.print()"><i data-lucide="printer"></i> Print Report</button>
    </div>
</div>

<!-- Key Metrics -->
<div class="stats-row">
    <div class="stat-mini border-navy">
        <div class="stat-mini-val"><?= number_format($totalCustomers) ?></div>
        <div class="stat-mini-label">Total Customers</div>
    </div>
    <div class="stat-mini border-gold">
        <div class="stat-mini-val"><?= number_format($totalBookings) ?></div>
        <div class="stat-mini-label">Total Bookings</div>
    </div>
    <div class="stat-mini border-green">
        <div class="stat-mini-val"><?= number_format($totalVisa) ?></div>
        <div class="stat-mini-label">Visa Applications</div>
    </div>
    <div class="stat-mini border-red">
        <div class="stat-mini-val">$<?= number_format($totalRevenue,2) ?></div>
        <div class="stat-mini-label">Total Collected</div>
    </div>
</div>

<div class="dash-grid-2" style="margin-top:24px;">
    <!-- Revenue Chart -->
    <div class="panel">
        <div class="panel-head"><h2>Revenue Overview (Last 6 Months)</h2></div>
        <div class="panel-body">
            <canvas id="revenueChart" height="250"></canvas>
        </div>
    </div>
    
    <!-- Booking Distribution -->
    <div class="panel">
        <div class="panel-head"><h2>Bookings Distribution</h2></div>
        <div class="panel-body" style="display:flex;justify-content:center;align-items:center;height:282px;">
            <?php if(empty($btData)): ?>
            <div class="empty-state">No booking data available.</div>
            <?php else: ?>
            <canvas id="bookingsChart"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="dash-grid-2" style="margin-top:24px;">
    <!-- Overdue Invoices -->
    <div class="panel">
        <div class="panel-head"><h2>Overdue Invoices Alert</h2></div>
        <div class="panel-body" style="padding:0;">
            <?php if(empty($overdueInvoices)): ?>
            <div class="empty-state" style="padding:24px;">No overdue invoices. Great job!</div>
            <?php else: ?>
            <table class="data-table">
                <thead><tr><th>Invoice</th><th>Customer</th><th>Amount Due</th></tr></thead>
                <tbody>
                    <?php foreach($overdueInvoices as $oi): ?>
                    <tr>
                        <td><code style="font-size:0.75rem;"><?= htmlspecialchars($oi['invoice_number']) ?></code></td>
                        <td style="font-weight:600;"><?= htmlspecialchars($oi['first_name'].' '.$oi['last_name']) ?></td>
                        <td style="color:var(--danger);font-weight:700;">$<?= number_format($oi['balance'],2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="panel">
        <div class="panel-head"><h2>Export Data</h2></div>
        <div class="panel-body">
            <p style="font-size:0.85rem;color:var(--ink-soft);margin-bottom:16px;">Download your data for accounting and external analysis.</p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button class="btn btn-ghost" style="justify-content:flex-start;"><i data-lucide="download"></i> Export Customers (CSV)</button>
                <button class="btn btn-ghost" style="justify-content:flex-start;"><i data-lucide="download"></i> Export Bookings (CSV)</button>
                <button class="btn btn-ghost" style="justify-content:flex-start;"><i data-lucide="download"></i> Export Payments (Excel)</button>
            </div>
        </div>
    </div>
</div>

<script>
// Chart configs
window.addEventListener('DOMContentLoaded', () => {
    // Revenue Chart
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
        new Chart(revCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($months) ?>,
                datasets: [{
                    label: 'Revenue ($)',
                    data: <?= json_encode($revenueData) ?>,
                    backgroundColor: '#1d4ed8',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Bookings Chart
    const bkCtx = document.getElementById('bookingsChart');
    if (bkCtx) {
        new Chart(bkCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($btLabels) ?>,
                datasets: [{
                    data: <?= json_encode($btData) ?>,
                    backgroundColor: ['#1d4ed8','#047857','#b45309','#6d28d9','#be123c'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right' } },
                cutout: '70%'
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>
