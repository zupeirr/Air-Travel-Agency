<?php
$pageTitle = 'Refunds';
include 'includes/header.php';
$refunds = db_select("SELECT r.*, c.first_name, c.last_name, c.customer_code, i.invoice_number FROM refunds r JOIN customers c ON c.id = r.customer_id LEFT JOIN invoices i ON i.id = r.invoice_id ORDER BY r.created_at DESC");
function rfBadge($s) { $m=['pending'=>'badge-pending','approved'=>'badge-approved','rejected'=>'badge-rejected','processed'=>'badge-submitted']; return '<span class="badge '.($m[$s]??'badge-draft').'">'.ucfirst($s).'</span>'; }
?>
<div class="page-header">
    <div class="page-header-left"><h2>Refunds</h2><p>Customer refund requests and processing status</p></div>
</div>
<div class="panel"><div class="panel-body table-wrap" style="padding:0;">
<?php if (empty($refunds)): ?><div class="empty-state" style="padding:48px;">No refunds on record.</div>
<?php else: ?>
<table class="data-table"><thead><tr><th>Customer</th><th>Invoice</th><th>Amount</th><th>Reason</th><th>Requested</th><th>Status</th></tr></thead>
<tbody><?php foreach ($refunds as $r): ?>
<tr>
    <td><div style="font-weight:600;"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div><div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($r['customer_code']) ?></div></td>
    <td><code style="font-size:0.78rem;"><?= htmlspecialchars($r['invoice_number'] ?? '—') ?></code></td>
    <td style="font-weight:700;color:var(--danger);">$<?= number_format($r['amount'],2) ?></td>
    <td style="font-size:0.83rem;max-width:200px;"><?= htmlspecialchars($r['reason'] ?? '—') ?></td>
    <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
    <td><?= rfBadge($r['status'] ?? 'pending') ?></td>
</tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div></div>
<?php include 'includes/footer.php'; ?>
