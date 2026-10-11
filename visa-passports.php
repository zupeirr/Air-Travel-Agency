<?php
$pageTitle = 'Passport Expirations';
include 'includes/header.php';
$filter = trim($_GET['f'] ?? '30');
$days = (int)$filter;
$customers = db_select("
    SELECT id, customer_code, first_name, last_name, nationality, passport_number, passport_expiry,
    DATEDIFF(passport_expiry, CURDATE()) AS days_left
    FROM customers
    WHERE passport_expiry IS NOT NULL
    ORDER BY passport_expiry ASC");
$expired = array_filter($customers, fn($c) => $c['days_left'] < 0);
$critical = array_filter($customers, fn($c) => $c['days_left'] >= 0 && $c['days_left'] <= 30);
$warning  = array_filter($customers, fn($c) => $c['days_left'] > 30 && $c['days_left'] <= 90);
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Passport Expirations</h2>
        <p>Customers whose passports are expiring soon or have expired</p>
    </div>
</div>
<div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-mini border-red"><div class="stat-mini-val"><?= count($expired) ?></div><div class="stat-mini-label">Expired</div></div>
    <div class="stat-mini border-gold"><div class="stat-mini-val"><?= count($critical) ?></div><div class="stat-mini-label">Expiring in 30 Days</div></div>
    <div class="stat-mini border-navy"><div class="stat-mini-val"><?= count($warning) ?></div><div class="stat-mini-label">Expiring in 90 Days</div></div>
</div>
<?php
function passportRows($list, $title, $color) {
    if (empty($list)) return;
    echo "<h3 style='margin:20px 0 10px;color:$color;font-size:0.9rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;'>$title</h3>";
    echo '<div class="panel"><div class="panel-body table-wrap" style="padding:0;"><table class="data-table"><thead><tr><th>Customer</th><th>Passport No.</th><th>Nationality</th><th>Expiry Date</th><th>Days Left</th><th>Action</th></tr></thead><tbody>';
    foreach ($list as $c) {
        $d = (int)$c['days_left'];
        $dStr = $d < 0 ? '<span style="color:var(--danger);font-weight:700;">Expired '.(abs($d)).' days ago</span>' : '<span style="color:'.($d<=30?'var(--danger)':'var(--gold-600)').';font-weight:700;">'.$d.' days</span>';
        echo '<tr><td><div style="font-weight:600;">'.htmlspecialchars($c['first_name'].' '.$c['last_name']).'</div><div style="font-size:0.75rem;color:var(--ink-soft);">'.htmlspecialchars($c['customer_code']).'</div></td><td><code>'.htmlspecialchars($c['passport_number'] ?? '—').'</code></td><td>'.htmlspecialchars($c['nationality'] ?? '—').'</td><td>'.($c['passport_expiry'] ? date('d M Y', strtotime($c['passport_expiry'])) : '—').'</td><td>'.$dStr.'</td><td><a href="customer-profile.php?id='.$c['id'].'" class="btn btn-ghost btn-sm">View</a></td></tr>';
    }
    echo '</tbody></table></div></div>';
}
passportRows($expired, '⚠ Expired Passports', 'var(--danger)');
passportRows($critical, '🔴 Expiring Within 30 Days', 'var(--danger)');
passportRows($warning,  '🟡 Expiring Within 90 Days', 'var(--gold-600)');
if (empty($expired) && empty($critical) && empty($warning)) {
    echo '<div class="panel"><div class="empty-state" style="padding:64px;">✅ No passport expiry alerts at this time.</div></div>';
}
?>
<?php include 'includes/footer.php'; ?>
