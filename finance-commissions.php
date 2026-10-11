<?php
$pageTitle = 'Agent Commissions';
include 'includes/header.php';
$commissions = db_select("
    SELECT c.*, u.name AS agent_name, i.invoice_number, cu.first_name, cu.last_name
    FROM commissions c
    JOIN users u ON u.id = c.agent_id
    LEFT JOIN invoices i ON i.id = c.invoice_id
    LEFT JOIN customers cu ON cu.id = i.customer_id
    ORDER BY c.created_at DESC");
$stats = db_row("SELECT SUM(amount) AS total, SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) AS paid, SUM(CASE WHEN status='pending' THEN amount ELSE 0 END) AS pending FROM commissions");
?>
<div class="page-header">
    <div class="page-header-left"><h2>Agent Commissions</h2><p>Track earned and paid commissions per booking agent</p></div>
</div>
<div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-mini border-navy"><div class="stat-mini-val">$<?= number_format($stats['total']??0,2) ?></div><div class="stat-mini-label">Total Earned</div></div>
    <div class="stat-mini border-green"><div class="stat-mini-val">$<?= number_format($stats['paid']??0,2) ?></div><div class="stat-mini-label">Paid Out</div></div>
    <div class="stat-mini border-gold"><div class="stat-mini-val">$<?= number_format($stats['pending']??0,2) ?></div><div class="stat-mini-label">Pending Payout</div></div>
</div>
<div class="panel" style="margin-top:20px;"><div class="panel-body table-wrap" style="padding:0;">
<?php if (empty($commissions)): ?><div class="empty-state" style="padding:48px;">No commissions recorded yet.</div>
<?php else: ?>
<table class="data-table"><thead><tr><th>Agent</th><th>Customer</th><th>Invoice</th><th>Commission</th><th>Status</th><th>Date</th></tr></thead>
<tbody><?php foreach ($commissions as $c): ?>
<tr>
    <td style="font-weight:600;"><?= htmlspecialchars($c['agent_name']) ?></td>
    <td><?= htmlspecialchars(($c['first_name']??'').' '.($c['last_name']??'')) ?></td>
    <td><code style="font-size:0.75rem;"><?= htmlspecialchars($c['invoice_number'] ?? '—') ?></code></td>
    <td style="font-weight:700;color:var(--success);">$<?= number_format($c['amount'],2) ?></td>
    <td><span class="badge <?= $c['status']==='paid'?'badge-approved':'badge-pending' ?>"><?= ucfirst($c['status']) ?></span></td>
    <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
</tr>
<?php endforeach; ?></tbody></table>
<?php endif; ?>
</div></div>
<?php include 'includes/footer.php'; ?>
