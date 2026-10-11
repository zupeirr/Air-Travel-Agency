<?php
$pageTitle = 'Traveler Inquiries';
include 'includes/header.php';
$status = trim($_GET['status'] ?? '');
$where = ['1=1']; $params = [];
if ($status) { $where[] = 'i.status = ?'; $params[] = $status; }
$inquiries = db_select("SELECT i.*, c.first_name, c.last_name FROM inquiries i LEFT JOIN customers c ON c.id = i.customer_id WHERE ".implode(' AND ',$where)." ORDER BY i.created_at DESC", $params);
function iqBadge($s) { $m=['new'=>'badge-pending','in_progress'=>'badge-review','resolved'=>'badge-approved','closed'=>'badge-inactive']; return '<span class="badge '.($m[$s]??'badge-draft').'">'.ucwords(str_replace('_',' ',$s)).'</span>'; }
?>
<div class="page-header">
    <div class="page-header-left"><h2>Traveler Inquiries</h2><p>Manage incoming customer requests and inquiries</p></div>
</div>
<form method="GET" action="customers-inquiries.php">
<div class="sfbar">
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All</option>
        <?php foreach (['new','in_progress','resolved','closed'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($status): ?><a href="customers-inquiries.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    <span class="sfbar-count"><?= count($inquiries) ?> inquir<?= count($inquiries)!==1?'ies':'y' ?></span>
</div>
</form>
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($inquiries)): ?><div class="empty-state" style="padding:48px;">No inquiries found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>From</th><th>Subject</th><th>Type</th><th>Received</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($inquiries as $i): ?>
            <tr>
                <td><div style="font-weight:600;"><?= $i['first_name'] ? htmlspecialchars($i['first_name'].' '.$i['last_name']) : htmlspecialchars($i['name'] ?? 'Unknown') ?></div><div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($i['email'] ?? '—') ?></div></td>
                <td style="font-weight:500;"><?= htmlspecialchars($i['subject'] ?? '—') ?></td>
                <td style="font-size:0.83rem;color:var(--ink-soft);"><?= ucfirst($i['inquiry_type'] ?? '—') ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y, g:i A', strtotime($i['created_at'])) ?></td>
                <td><?= iqBadge($i['status'] ?? 'new') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
