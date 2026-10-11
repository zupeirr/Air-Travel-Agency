<?php
$pageTitle = 'Booking Reports';
include 'includes/header.php';
$byType   = db_select("SELECT booking_type, COUNT(*) AS cnt, SUM(total_amount) AS revenue FROM bookings GROUP BY booking_type ORDER BY cnt DESC");
$byStatus = db_select("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status");
$monthly  = db_select("SELECT DATE_FORMAT(created_at,'%b %Y') AS month, COUNT(*) AS cnt, SUM(total_amount) AS revenue FROM bookings GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 6");
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="page-header"><div class="page-header-left"><h2>Booking Reports</h2><p>Booking volume, revenue, and status breakdown</p></div></div>
<div class="dash-grid-2">
    <div class="panel"><div class="panel-head"><h2>Bookings by Type</h2></div><div class="panel-body table-wrap" style="padding:0;">
        <table class="data-table"><thead><tr><th>Type</th><th>Count</th><th>Revenue</th></tr></thead><tbody>
        <?php foreach ($byType as $b): ?><tr><td style="font-weight:600;"><?= ucwords(str_replace('_',' ',$b['booking_type'])) ?></td><td><?= $b['cnt'] ?></td><td style="color:var(--success);font-weight:700;">$<?= number_format($b['revenue'],2) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    <div class="panel"><div class="panel-head"><h2>Bookings by Status</h2></div><div class="panel-body table-wrap" style="padding:0;">
        <table class="data-table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
        <?php foreach ($byStatus as $b): ?><tr><td><?= ucfirst($b['status']) ?></td><td><?= $b['cnt'] ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
</div>
<div class="panel" style="margin-top:20px;"><div class="panel-head"><h2>Monthly Bookings (Last 6 Months)</h2></div><div class="panel-body"><canvas id="mChart" height="120"></canvas></div></div>
<script>
new Chart(document.getElementById('mChart'),{type:'line',data:{labels:<?= json_encode(array_column(array_reverse($monthly),'month')) ?>,datasets:[{label:'Bookings',data:<?= json_encode(array_column(array_reverse($monthly),'cnt')) ?>,borderColor:'#1d4ed8',fill:false,tension:0.3}]},options:{responsive:true,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
</script>
<?php include 'includes/footer.php'; ?>
