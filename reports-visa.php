<?php
$pageTitle = 'Visa Reports';
include 'includes/header.php';
$byStatus   = db_select("SELECT status, COUNT(*) AS cnt FROM visa_applications GROUP BY status ORDER BY cnt DESC");
$byCountry  = db_select("SELECT destination_country, COUNT(*) AS cnt FROM visa_applications GROUP BY destination_country ORDER BY cnt DESC LIMIT 10");
$byCategory = db_select("SELECT vt.category, COUNT(*) AS cnt FROM visa_applications va JOIN visa_types vt ON vt.id=va.visa_type_id GROUP BY vt.category ORDER BY cnt DESC");
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<div class="page-header"><div class="page-header-left"><h2>Visa Reports</h2><p>Application volumes, outcomes, and destination analysis</p></div></div>
<div class="dash-grid-2">
    <div class="panel"><div class="panel-head"><h2>Applications by Status</h2></div><div class="panel-body table-wrap" style="padding:0;">
        <table class="data-table"><thead><tr><th>Status</th><th>Count</th></tr></thead><tbody>
        <?php foreach ($byStatus as $b): ?><tr><td><?= ucwords(str_replace('_',' ',$b['status'])) ?></td><td style="font-weight:700;"><?= $b['cnt'] ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
    <div class="panel"><div class="panel-head"><h2>Top Destinations</h2></div><div class="panel-body table-wrap" style="padding:0;">
        <table class="data-table"><thead><tr><th>Country</th><th>Applications</th></tr></thead><tbody>
        <?php foreach ($byCountry as $b): ?><tr><td style="font-weight:600;"><?= htmlspecialchars($b['destination_country']) ?></td><td><?= $b['cnt'] ?></td></tr><?php endforeach; ?>
        </tbody></table></div></div>
</div>
<div class="panel" style="margin-top:20px;"><div class="panel-head"><h2>By Visa Category</h2></div><div class="panel-body" style="height:220px;display:flex;justify-content:center;"><canvas id="catChart"></canvas></div></div>
<script>
new Chart(document.getElementById('catChart'),{type:'pie',data:{labels:<?= json_encode(array_map(fn($b)=>ucfirst($b['category']),$byCategory)) ?>,datasets:[{data:<?= json_encode(array_column($byCategory,'cnt')) ?>,backgroundColor:['#1d4ed8','#047857','#b45309','#6d28d9','#be123c'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'right'}}}});
</script>
<?php include 'includes/footer.php'; ?>
