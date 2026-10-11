<?php
$pageTitle = 'All Bookings';
include 'includes/header.php';

// ── Filters ──────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$type   = trim($_GET['type'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage= 25;
$offset = ($page-1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(b.booking_code LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR tp.name LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s]);
}
if ($status) { $where[] = 'b.status = ?'; $params[] = $status; }
if ($type)   { $where[] = 'b.booking_type = ?'; $params[] = $type; }

// If filtering by package specifically
$pkgId = (int)($_GET['package_id'] ?? 0);
if ($pkgId) {
    $where[] = 'b.package_id = ?';
    $params[] = $pkgId;
}

$whereSQL = implode(' AND ', $where);

// Stats
$stats = db_row("SELECT
    COUNT(*) AS total,
    SUM(status='pending') AS pending,
    SUM(status='confirmed') AS confirmed,
    SUM(status='cancelled') AS cancelled
FROM bookings b");

$totalFiltered = db_count("
    SELECT COUNT(*) FROM bookings b
    JOIN customers c ON c.id = b.customer_id
    LEFT JOIN travel_packages tp ON tp.id = b.package_id
    WHERE $whereSQL", $params);
$totalPages = max(1, ceil($totalFiltered/$perPage));

$bookings = db_select("
    SELECT b.*, c.first_name, c.last_name, c.customer_code, c.nationality,
           tp.name AS package_name
    FROM bookings b
    JOIN customers c ON c.id = b.customer_id
    LEFT JOIN travel_packages tp ON tp.id = b.package_id
    WHERE $whereSQL
    ORDER BY b.created_at DESC
    LIMIT $perPage OFFSET $offset", $params);

function bBadge($s) {
    $m=['pending'=>'badge-pending','confirmed'=>'badge-confirmed','cancelled'=>'badge-cancelled','completed'=>'badge-approved'];
    $cls = $m[$s] ?? 'badge-draft';
    return '<span class="badge '.$cls.'">'.ucfirst($s).'</span>';
}
function tIcon($t) {
    $m=['flight'=>'plane','hotel'=>'hotel','car_rental'=>'car','tour'=>'map','package'=>'package'];
    return $m[$t] ?? 'briefcase';
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>All Bookings</h2>
        <p>Manage flights, hotels, tours, and packages</p>
    </div>
    <div class="page-header-actions">
        <a href="booking-create.php" class="btn btn-primary"><i data-lucide="plus"></i> New Booking</a>
    </div>
</div>

<div class="stats-row" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-mini border-navy">
        <div class="stat-mini-val"><?= $stats['total'] ?></div>
        <div class="stat-mini-label">Total Bookings</div>
    </div>
    <div class="stat-mini border-gold">
        <div class="stat-mini-val"><?= $stats['pending'] ?></div>
        <div class="stat-mini-label">Pending</div>
    </div>
    <div class="stat-mini border-green">
        <div class="stat-mini-val"><?= $stats['confirmed'] ?></div>
        <div class="stat-mini-label">Confirmed</div>
    </div>
    <div class="stat-mini border-red">
        <div class="stat-mini-val"><?= $stats['cancelled'] ?></div>
        <div class="stat-mini-label">Cancelled</div>
    </div>
</div>

<form method="GET" action="bookings-all.php">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search code, customer, package…">
    </div>
    <select name="type" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Types</option>
        <?php foreach (['flight','hotel','car_rental','tour','package'] as $t): ?>
        <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= ucwords(str_replace('_',' ',$t)) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['pending','confirmed','completed','cancelled'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($search||$status||$type||$pkgId): ?>
    <a href="bookings-all.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
    <span class="sfbar-count"><?= number_format($totalFiltered) ?> result<?= $totalFiltered!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($bookings)): ?>
        <div class="empty-state" style="padding:48px;">No bookings found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th>Type / Item</th>
                    <th>Travel Date</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($bookings as $b): 
                $outstanding = $b['total_amount'] - $b['paid_amount'];
            ?>
            <tr>
                <td><code style="font-size:0.78rem;font-family:monospace;"><?= htmlspecialchars($b['booking_code']) ?></code></td>
                <td>
                    <div style="font-weight:600;"><a href="customer-profile.php?id=<?= $b['customer_id'] ?>&tab=bookings"><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></a></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($b['customer_code']) ?></div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;font-weight:500;font-size:0.88rem;">
                        <i data-lucide="<?= tIcon($b['booking_type']) ?>" style="width:14px;height:14px;color:var(--navy-800);"></i>
                        <?= ucwords(str_replace('_',' ',$b['booking_type'])) ?>
                    </div>
                    <?php if ($b['booking_type']==='package' && $b['package_name']): ?>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($b['package_name']) ?></div>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.85rem;"><?= $b['travel_date'] ? date('d M Y', strtotime($b['travel_date'])) : '—' ?></td>
                <td><strong style="color:var(--navy-900);">$<?= number_format($b['total_amount'],2) ?></strong></td>
                <td>
                    <?php if ($outstanding > 0): ?>
                    <span style="color:var(--danger);font-weight:600;" title="Outstanding: $<?= number_format($outstanding,2) ?>">$<?= number_format($b['paid_amount'],2) ?></span>
                    <?php else: ?>
                    <span style="color:var(--success);font-weight:600;">$<?= number_format($b['paid_amount'],2) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= bBadge($b['status']) ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                <td>
                    <div class="tbl-actions">
                        <a href="booking-detail.php?id=<?= $b['id'] ?>" class="tbl-btn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page>1): ?><a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&p=<?= $page-1 ?>">‹ Prev</a><?php endif; ?>
            <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
            <?= $i===$page ? "<span class='current'>$i</span>" : "<a href='?q=".urlencode($search)."&status=".urlencode($status)."&type=".urlencode($type)."&p=$i'>$i</a>" ?>
            <?php endfor; ?>
            <?php if ($page<$totalPages): ?><a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&p=<?= $page+1 ?>">Next ›</a><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
