<?php
$pageTitle = 'Discount Codes';
include 'includes/header.php';
$codes = db_select("SELECT * FROM discount_codes ORDER BY created_at DESC");
?>
<div class="page-header">
    <div class="page-header-left"><h2>Discount Codes</h2><p>Promotional codes and their usage statistics</p></div>
    <div class="page-header-actions"><button class="btn btn-primary"><i data-lucide="plus"></i> Create Code</button></div>
</div>
<div class="panel"><div class="panel-body table-wrap" style="padding:0;">
<?php if (empty($codes)): ?><div class="empty-state" style="padding:48px;">No discount codes yet.</div>
<?php else: ?>
<table class="data-table"><thead><tr><th>Code</th><th>Discount</th><th>Min Order</th><th>Used / Max</th><th>Expires</th><th>Status</th></tr></thead>
<tbody><?php foreach ($codes as $c): ?>
<tr>
    <td><code style="font-size:0.9rem;font-weight:800;color:var(--navy-800);letter-spacing:0.05em;"><?= htmlspecialchars($c['code']) ?></code></td>
    <td style="font-weight:700;color:var(--gold-600);"><?= $c['discount_type']==='percent'?$c['discount_value'].'%':'$'.$c['discount_value'] ?></td>
    <td>$<?= number_format($c['min_order_amount'] ?? 0) ?></td>
    <td><?= $c['used_count'] ?? 0 ?> / <?= $c['max_uses'] ?? '∞' ?></td>
    <td style="font-size:0.83rem;"><?= $c['expires_at'] ? date('d M Y', strtotime($c['expires_at'])) : 'Never' ?></td>
    <td><span class="badge <?= $c['status']==='active'?'badge-approved':'badge-inactive' ?>"><?= ucfirst($c['status']) ?></span></td>
</tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div></div>
<?php include 'includes/footer.php'; ?>
