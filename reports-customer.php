<?php
$pageTitle = 'Customer Reports';
include 'includes/header.php';
$byCountry     = db_select("SELECT nationality, COUNT(*) AS cnt FROM customers GROUP BY nationality ORDER BY cnt DESC LIMIT 10");
$byStatus      = db_select("SELECT status, COUNT(*) AS cnt FROM customers GROUP BY status");
$byMonth       = db_select("SELECT DATE_FORMAT(created_at,'%b %Y') AS month, COUNT(*) AS cnt FROM customers GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY MIN(created_at) DESC LIMIT 6");
$total         = db_row("SELECT COUNT(*) AS t FROM customers")['t'];
$activeCount   = db_row("SELECT COUNT(*) AS t FROM customers WHERE status='active'")['t'];
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="page-header"><div class="page-header-left"><h2>Customer Reports</h2><p>Demographics, growth trends, and nationality breakdown</p></div></div>
<div class="stats-row" style="grid-template-columns:repeat(2,1fr);">
    <div class="stat-mini border-navy"><div class="stat-mini-val"><?= $total ?></div><div class="stat-mini-label">Total Customers</div></div>
    <div class="stat-mini border-green"><div class="stat-mini-val"><?= $activeCount ?></div><div class="stat-mini-label">Active</div></div>
</div>
<div class="dash-grid-2" style="margin-top:20px;">
    <div class="panel"><div class="panel-head"><h2>By Nationality (Top 10)</h2></div><div class="panel-body table-wrap" style="padding:0;">
        <table class="data-table"><thead><tr><th>Nationality</th><th>Count</th></tr></thead><tbody>
        <?php foreach ($byCountry as $b): ?><tr><td><?= htmlspecialchars($b['nationality'] ?? 'Unknown') ?></td><td style="font-weight:700;"><?= $b['cnt'] ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    <div class="panel"><div class="panel-head"><h2>Monthly New Customers</h2></div><div class="panel-body"><canvas id="growChart" height="200"></canvas></div></div>
</div>
<script>
new Chart(document.getElementById('growChart'),{type:'bar',data:{labels:<?= json_encode(array_column(array_reverse($byMonth),'month')) ?>,datasets:[{label:'New Customers',data:<?= json_encode(array_column(array_reverse($byMonth),'cnt')) ?>,backgroundColor:'#047857',borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
</script>
<?php include 'includes/footer.php'; ?>
