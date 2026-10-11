<?php
$pageTitle = 'Campaigns';
include 'includes/header.php';
$campaigns = db_select("SELECT * FROM campaigns ORDER BY created_at DESC");
function campBadge($s) { $m=['draft'=>'badge-draft','active'=>'badge-approved','paused'=>'badge-pending','completed'=>'badge-submitted']; return '<span class="badge '.($m[$s]??'badge-draft').'">'.ucfirst($s).'</span>'; }
?>
<div class="page-header">
    <div class="page-header-left"><h2>Campaigns</h2><p>Email and SMS marketing campaigns</p></div>
    <div class="page-header-actions"><button class="btn btn-primary"><i data-lucide="plus"></i> New Campaign</button></div>
</div>
<div class="panel"><div class="panel-body table-wrap" style="padding:0;">
<?php if (empty($campaigns)): ?><div class="empty-state" style="padding:48px;">No campaigns created yet.</div>
<?php else: ?>
<table class="data-table"><thead><tr><th>Campaign</th><th>Type</th><th>Sent To</th><th>Open Rate</th><th>Started</th><th>Status</th></tr></thead>
<tbody><?php foreach ($campaigns as $c): ?>
<tr>
    <td style="font-weight:600;"><?= htmlspecialchars($c['name']) ?></td>
    <td><span class="badge badge-draft"><?= ucfirst($c['type'] ?? 'email') ?></span></td>
    <td><?= number_format($c['recipients_count'] ?? 0) ?></td>
    <td><?= isset($c['open_rate']) ? $c['open_rate'].'%' : '—' ?></td>
    <td style="font-size:0.8rem;color:var(--ink-soft);"><?= $c['started_at'] ? date('d M Y', strtotime($c['started_at'])) : '—' ?></td>
    <td><?= campBadge($c['status'] ?? 'draft') ?></td>
</tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div></div>
<?php include 'includes/footer.php'; ?>
