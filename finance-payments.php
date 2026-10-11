<?php
$pageTitle = 'Payments';
include 'includes/header.php';

$search = trim($_GET['q'] ?? '');
$method = trim($_GET['method'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage= 25;
$offset = ($page-1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(p.transaction_id LIKE ? OR i.invoice_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s]);
}
if ($method) { $where[] = 'p.payment_method = ?'; $params[] = $method; }

$whereSQL = implode(' AND ', $where);

// Stats
$stats = db_row("SELECT
    COUNT(*) AS total_tx,
    SUM(amount) AS total_val,
    SUM(CASE WHEN payment_method='cash' THEN amount ELSE 0 END) AS cash_val,
    SUM(CASE WHEN payment_method='bank_transfer' THEN amount ELSE 0 END) AS bank_val
FROM payments p");

$totalFiltered = db_count("
    SELECT COUNT(*) FROM payments p
    LEFT JOIN invoices i ON i.id = p.invoice_id
    LEFT JOIN customers c ON c.id = i.customer_id
    WHERE $whereSQL", $params);
$totalPages = max(1, ceil($totalFiltered/$perPage));

$payments = db_select("
    SELECT p.*, i.invoice_number, c.first_name, c.last_name, c.customer_code
    FROM payments p
    LEFT JOIN invoices i ON i.id = p.invoice_id
    LEFT JOIN customers c ON c.id = i.customer_id
    WHERE $whereSQL
    ORDER BY p.payment_date DESC
    LIMIT $perPage OFFSET $offset", $params);
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Payments</h2>
        <p>Log of all received payments and transactions</p>
    </div>
    <div class="page-header-actions">
        <a href="finance-invoices.php" class="btn btn-ghost"><i data-lucide="file-text"></i> Invoices</a>
    </div>
</div>

<div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-mini border-green">
        <div class="stat-mini-val">$<?= number_format($stats['total_val']?:0,2) ?></div>
        <div class="stat-mini-label">Total Collected</div>
    </div>
    <div class="stat-mini border-navy">
        <div class="stat-mini-val">$<?= number_format($stats['bank_val']?:0,2) ?></div>
        <div class="stat-mini-label">Via Bank Transfer</div>
    </div>
    <div class="stat-mini border-gold">
        <div class="stat-mini-val">$<?= number_format($stats['cash_val']?:0,2) ?></div>
        <div class="stat-mini-label">Via Cash</div>
    </div>
</div>

<form method="GET" action="finance-payments.php">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search transaction, invoice, customer…">
    </div>
    <select name="method" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Methods</option>
        <?php foreach (['cash','credit_card','bank_transfer','cheque','online_gateway'] as $m): ?>
        <option value="<?= $m ?>" <?= $method===$m?'selected':'' ?>><?= ucwords(str_replace('_',' ',$m)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($search||$method): ?>
    <a href="finance-payments.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
    <span class="sfbar-count"><?= number_format($totalFiltered) ?> result<?= $totalFiltered!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($payments)): ?>
        <div class="empty-state" style="padding:48px;">No payments found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Transaction ID</th>
                    <th>Customer</th>
                    <th>Invoice #</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $pay): ?>
            <tr>
                <td style="font-size:0.85rem;"><?= date('d M Y, g:i A', strtotime($pay['payment_date'])) ?></td>
                <td><code style="font-size:0.75rem;"><?= htmlspecialchars($pay['transaction_id']) ?></code></td>
                <td>
                    <?php if ($pay['customer_code']): ?>
                    <div style="font-weight:600;"><a href="customer-profile.php?id=<?= $pay['customer_id'] ?>&tab=payments"><?= htmlspecialchars($pay['first_name'].' '.$pay['last_name']) ?></a></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($pay['customer_code']) ?></div>
                    <?php else: ?>
                    <span style="color:var(--ink-soft);">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($pay['invoice_number']): ?>
                    <code style="font-size:0.75rem;color:var(--navy-800);"><?= htmlspecialchars($pay['invoice_number']) ?></code>
                    <?php else: ?>
                    <span style="color:var(--ink-soft);">—</span>
                    <?php endif; ?>
                </td>
                <td><strong style="color:var(--success);">$<?= number_format($pay['amount'],2) ?></strong></td>
                <td style="font-size:0.85rem;color:var(--ink-soft);"><?= ucwords(str_replace('_',' ',$pay['payment_method'])) ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($pay['notes']??'') ?>"><?= htmlspecialchars($pay['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page>1): ?><a href="?q=<?= urlencode($search) ?>&method=<?= urlencode($method) ?>&p=<?= $page-1 ?>">‹ Prev</a><?php endif; ?>
            <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
            <?= $i===$page ? "<span class='current'>$i</span>" : "<a href='?q=".urlencode($search)."&method=".urlencode($method)."&p=$i'>$i</a>" ?>
            <?php endfor; ?>
            <?php if ($page<$totalPages): ?><a href="?q=<?= urlencode($search) ?>&method=<?= urlencode($method) ?>&p=<?= $page+1 ?>">Next ›</a><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
