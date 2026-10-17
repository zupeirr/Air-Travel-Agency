<?php
$pageTitle = 'Invoices';
include 'includes/header.php';

$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage= 25;
$offset = ($page-1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(i.invoice_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s]);
}
if ($status) { $where[] = 'i.status = ?'; $params[] = $status; }

$whereSQL = implode(' AND ', $where);

// Stats
$stats = db_row("SELECT
    COUNT(*) AS total,
    SUM(status='draft') AS draft,
    SUM(status IN ('sent','partially_paid')) AS unpaid,
    SUM(status='overdue') AS overdue,
    SUM(status='paid') AS paid,
    SUM(total) AS total_val,
    SUM(CASE WHEN status IN ('sent','overdue','partially_paid') THEN (total - paid_amount) ELSE 0 END) AS total_outstanding
FROM invoices");

$totalFiltered = db_count("
    SELECT COUNT(*) FROM invoices i
    JOIN customers c ON c.id = i.customer_id
    WHERE $whereSQL", $params);
$totalPages = max(1, ceil($totalFiltered/$perPage));

$invoices = db_select("
    SELECT i.*, c.first_name, c.last_name, c.customer_code
    FROM invoices i
    JOIN customers c ON c.id = i.customer_id
    WHERE $whereSQL
    ORDER BY i.issue_date DESC
    LIMIT $perPage OFFSET $offset", $params);

function iBadge($s) {
    $m=['draft'=>'badge-draft','sent'=>'badge-review','partially_paid'=>'badge-pending','paid'=>'badge-approved','overdue'=>'badge-rejected','cancelled'=>'badge-inactive'];
    $cls = $m[$s] ?? 'badge-draft';
    return '<span class="badge '.$cls.'">'.ucwords(str_replace('_',' ',$s)).'</span>';
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Invoices</h2>
        <p>Manage all customer billing and outstanding balances</p>
    </div>
    <div class="page-header-actions">
        <a href="finance-payments.php" class="btn btn-ghost"><i data-lucide="credit-card"></i> Payments</a>
        <a href="finance-invoice-create.php" class="btn btn-primary"><i data-lucide="plus"></i> Create Invoice</a>
    </div>
</div>

<div class="stats-row">
    <div class="stat-mini border-navy">
        <div class="stat-mini-val">$<?= number_format($stats['total_val']?:0) ?></div>
        <div class="stat-mini-label">Total Invoiced</div>
    </div>
    <div class="stat-mini border-red">
        <div class="stat-mini-val">$<?= number_format($stats['total_outstanding']?:0) ?></div>
        <div class="stat-mini-label">Total Outstanding</div>
    </div>
    <div class="stat-mini border-gold">
        <div class="stat-mini-val"><?= $stats['unpaid']?:0 ?></div>
        <div class="stat-mini-label">Unpaid Invoices</div>
    </div>
    <div class="stat-mini border-green">
        <div class="stat-mini-val"><?= $stats['paid']?:0 ?></div>
        <div class="stat-mini-label">Paid Invoices</div>
    </div>
</div>

<form method="GET" action="finance-invoices.php">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search invoice #, customer…">
    </div>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['draft','sent','partially_paid','paid','overdue','cancelled'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($search||$status): ?>
    <a href="finance-invoices.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
    <span class="sfbar-count"><?= number_format($totalFiltered) ?> result<?= $totalFiltered!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($invoices)): ?>
        <div class="empty-state" style="padding:48px;">No invoices found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Issue Date</th>
                    <th>Due Date</th>
                    <th>Total</th>
                    <th>Balance Due</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $inv): 
                $balance = $inv['total'] - $inv['paid_amount'];
            ?>
            <tr>
                <td><code style="font-size:0.78rem;font-family:monospace;"><?= htmlspecialchars($inv['invoice_number']) ?></code></td>
                <td>
                    <div style="font-weight:600;"><a href="customer-profile.php?id=<?= $inv['customer_id'] ?>&tab=payments"><?= htmlspecialchars($inv['first_name'].' '.$inv['last_name']) ?></a></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($inv['customer_code']) ?></div>
                </td>
                <td style="font-size:0.85rem;"><?= date('d M Y', strtotime($inv['issue_date'])) ?></td>
                <td style="font-size:0.85rem;"><?= $inv['due_date'] ? date('d M Y', strtotime($inv['due_date'])) : '—' ?></td>
                <td><strong style="color:var(--navy-900);">$<?= number_format($inv['total'],2) ?></strong></td>
                <td>
                    <?php if ($balance > 0): ?>
                    <span style="color:var(--danger);font-weight:700;">$<?= number_format($balance,2) ?></span>
                    <?php else: ?>
                    <span style="color:var(--ink-soft);">Paid</span>
                    <?php endif; ?>
                </td>
                <td><?= iBadge($inv['status']) ?></td>
                <td>
                    <div class="tbl-actions">
                        <a href="finance-invoice-edit.php?id=<?= $inv['id'] ?>" class="tbl-btn" title="Edit Invoice"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></a>
                        <a href="finance-receipt.php?id=<?= $inv['id'] ?>" class="tbl-btn" title="View / Print Receipt" target="_blank"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                        <?php if ($balance > 0 && !in_array($inv['status'], ['draft','cancelled'])): ?>
                        <a href="finance-payment-add.php?invoice_id=<?= $inv['id'] ?>" class="tbl-btn success" title="Add Payment"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page>1): ?><a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&p=<?= $page-1 ?>">‹ Prev</a><?php endif; ?>
            <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
            <?= $i===$page ? "<span class='current'>$i</span>" : "<a href='?q=".urlencode($search)."&status=".urlencode($status)."&p=$i'>$i</a>" ?>
            <?php endfor; ?>
            <?php if ($page<$totalPages): ?><a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&p=<?= $page+1 ?>">Next ›</a><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
