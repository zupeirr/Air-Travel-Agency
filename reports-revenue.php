<?php
$pageTitle = 'Revenue Reports';
include 'includes/header.php';
$monthly   = db_select("SELECT DATE_FORMAT(payment_date,'%b %Y') AS month, SUM(amount) AS total FROM payments GROUP BY DATE_FORMAT(payment_date,'%Y-%m') ORDER BY MIN(payment_date) DESC LIMIT 12");
$byMethod  = db_select("SELECT payment_method, SUM(amount) AS total, COUNT(*) AS cnt FROM payments GROUP BY payment_method ORDER BY total DESC");
$outstanding = db_row("SELECT SUM(total-paid_amount) AS total FROM invoices WHERE status IN ('sent','overdue','partially_paid')")['total'] ?? 0;
$collected   = db_row("SELECT SUM(amount) AS total FROM payments")['total'] ?? 0;
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="page-header"><div class="page-header-left"><h2>Revenue Reports</h2><p>Collection analysis, payment methods, and outstanding receivables</p></div></div>
<div class="stats-row" style="grid-template-columns:repeat(2,1fr);">
    <div class="stat-mini border-green"><div class="stat-mini-val">$<?= number_format($collected,2) ?></div><div class="stat-mini-label">Total Collected</div></div>
    <div class="stat-mini border-red"><div class="stat-mini-val">$<?= number_format($outstanding,2) ?></div><div class="stat-mini-label">Total Outstanding</div></div>
</div>
<div class="dash-grid-2" style="margin-top:20px;">
    <div class="panel"><div class="panel-head"><h2>Monthly Revenue</h2></div><div class="panel-body"><canvas id="revChart" height="200"></canvas></div></div>
    <div class="panel"><div class="panel-head"><h2>By Payment Method</h2></div><div class="panel-body table-wrap" style="padding:0;">
        <table class="data-table"><thead><tr><th>Method</th><th>Transactions</th><th>Total</th></tr></thead><tbody>
        <?php foreach ($byMethod as $b): ?><tr><td style="font-weight:600;"><?= ucwords(str_replace('_',' ',$b['payment_method'])) ?></td><td><?= $b['cnt'] ?></td><td style="font-weight:700;color:var(--success);">$<?= number_format($b['total'],2) ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
</div>
<script>
new Chart(document.getElementById('revChart'),{type:'bar',data:{labels:<?= json_encode(array_column(array_reverse($monthly),'month')) ?>,datasets:[{label:'Revenue ($)',data:<?= json_encode(array_column(array_reverse($monthly),'total')) ?>,backgroundColor:'#1d4ed8',borderRadius:4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
</script>
<?php include 'includes/footer.php'; ?>
