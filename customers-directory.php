<?php
$pageTitle = 'Customer Directory';
include 'includes/header.php';

// ── Filters ──────────────────────────────────────────────────
$search      = trim($_GET['q']     ?? '');
$status      = trim($_GET['status']?? '');
$nationality = trim($_GET['nat']   ?? '');
$page        = max(1, (int)($_GET['p'] ?? 1));
$perPage     = 20;
$offset      = ($page - 1) * $perPage;

// ── Build query ───────────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($search) {
    $where[]  = '(c.first_name LIKE ? OR c.last_name LIKE ? OR c.email LIKE ? OR c.customer_code LIKE ? OR c.phone LIKE ? OR c.passport_number LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s,$s,$s,$s]);
}
if ($status) {
    $where[] = 'c.status = ?';
    $params[] = $status;
}
if ($nationality) {
    $where[] = 'c.nationality = ?';
    $params[] = $nationality;
}

$whereSQL = implode(' AND ', $where);

// Totals for stats
$totalAll         = db_count("SELECT COUNT(*) FROM customers");
$totalActive      = db_count("SELECT COUNT(*) FROM customers WHERE status='active'");
$totalPassExpiring= db_count("SELECT COUNT(*) FROM customers WHERE passport_expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 MONTH)");

// Count for pagination
$totalFiltered = db_count("SELECT COUNT(*) FROM customers c WHERE $whereSQL", $params);
$totalPages    = max(1, ceil($totalFiltered / $perPage));

// Nationality list for filter
$nationalities = db_select("SELECT DISTINCT nationality FROM customers WHERE nationality IS NOT NULL ORDER BY nationality");

// Main query — join on counts of visa_applications, bookings, invoices
$customers = db_select("
    SELECT
        c.id, c.customer_code, c.first_name, c.last_name,
        c.email, c.phone, c.nationality, c.passport_number,
        c.passport_expiry_date, c.status, c.created_at,
        u.name AS agent_name,
        COUNT(DISTINCT va.id)  AS visa_count,
        COUNT(DISTINCT ea.id)  AS edu_count,
        COUNT(DISTINCT b.id)   AS booking_count,
        COALESCE(SUM(CASE WHEN inv.status IN ('sent','overdue') THEN inv.total ELSE 0 END), 0) AS outstanding
    FROM customers c
    LEFT JOIN users u              ON u.id = c.assigned_agent_id
    LEFT JOIN visa_applications va ON va.customer_id = c.id
    LEFT JOIN education_applications ea ON ea.customer_id = c.id
    LEFT JOIN bookings b           ON b.customer_id = c.id
    LEFT JOIN invoices inv         ON inv.customer_id = c.id
    WHERE $whereSQL
    GROUP BY c.id
    ORDER BY c.created_at DESC
    LIMIT $perPage OFFSET $offset
", $params);

// Avatar colours
$avatarColors = ['#101d2c','#2563eb','#7c3aed','#047857','#d97706','#b0413e','#0e7490','#9d174d'];
function avatarColor(int $id): string {
    global $avatarColors;
    return $avatarColors[$id % count($avatarColors)];
}
function initials(string $first, string $last): string {
    return strtoupper(substr($first,0,1) . substr($last,0,1));
}
function passportExpiry(?string $date): string {
    if (!$date) return '<span class="expiry-ok">—</span>';
    $diff = (new DateTime($date))->diff(new DateTime())->days * ((new DateTime($date)) >= new DateTime() ? 1 : -1);
    if ($diff < 0)  return '<span class="expiry-crit">Expired</span>';
    if ($diff < 90) return '<span class="expiry-warn">'.htmlspecialchars(date('d M Y', strtotime($date))).'</span>';
    return '<span class="expiry-ok">'.htmlspecialchars(date('d M Y', strtotime($date))).'</span>';
}
function badgeStatus(string $s): string {
    $map = ['active'=>'badge-active','inactive'=>'badge-inactive','blacklisted'=>'badge-blacklisted'];
    $cls = $map[$s] ?? 'badge-inactive';
    return '<span class="badge '.$cls.'">'.ucfirst($s).'</span>';
}
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header">
    <div class="page-header-left">
        <h2>Customer Directory</h2>
        <p>All registered customers · <?= number_format($totalAll) ?> total</p>
    </div>
    <div class="page-header-actions">
        <a href="customer-add.php" class="btn btn-primary" id="btn-add-customer">
            <i data-lucide="user-plus"></i> Add Customer
        </a>
        <a href="#" class="btn btn-ghost" id="btn-export-customers">
            <i data-lucide="download"></i> Export
        </a>
    </div>
</div>

<!-- ── Stats Row ───────────────────────────────────────────── -->
<div class="stats-row">
    <div class="stat-mini border-navy">
        <div class="stat-mini-val"><?= number_format($totalAll) ?></div>
        <div class="stat-mini-label">Total Customers</div>
    </div>
    <div class="stat-mini border-green">
        <div class="stat-mini-val"><?= number_format($totalActive) ?></div>
        <div class="stat-mini-label">Active</div>
    </div>
    <div class="stat-mini border-gold">
        <div class="stat-mini-val"><?= number_format(db_count("SELECT COUNT(*) FROM customers WHERE status='inactive'")) ?></div>
        <div class="stat-mini-label">Inactive</div>
    </div>
    <div class="stat-mini border-red">
        <div class="stat-mini-val"><?= number_format($totalPassExpiring) ?></div>
        <div class="stat-mini-label">Passport Expiring Soon</div>
        <div class="stat-mini-sub">Within 6 months</div>
    </div>
    <div class="stat-mini border-blue">
        <div class="stat-mini-val"><?= number_format(db_count("SELECT COUNT(*) FROM visa_applications WHERE status NOT IN ('approved','rejected','cancelled')")) ?></div>
        <div class="stat-mini-label">Active Visa Apps</div>
    </div>
</div>

<!-- ── Search & Filter Bar ─────────────────────────────────── -->
<form method="GET" action="customers-directory.php" id="filter-form">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, email, code, passport…" id="customer-search-input">
    </div>

    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Status</option>
        <option value="active"      <?= $status==='active'     ?'selected':'' ?>>Active</option>
        <option value="inactive"    <?= $status==='inactive'   ?'selected':'' ?>>Inactive</option>
        <option value="blacklisted" <?= $status==='blacklisted'?'selected':'' ?>>Blacklisted</option>
    </select>

    <select name="nat" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Nationalities</option>
        <?php foreach ($nationalities as $n): ?>
        <option value="<?= htmlspecialchars($n['nationality']) ?>" <?= $nationality===$n['nationality']?'selected':'' ?>>
            <?= htmlspecialchars($n['nationality']) ?>
        </option>
        <?php endforeach; ?>
    </select>

    <?php if ($search || $status || $nationality): ?>
    <a href="customers-directory.php" class="btn btn-ghost btn-sm">Clear Filters</a>
    <?php endif; ?>

    <span class="sfbar-count"><?= number_format($totalFiltered) ?> customer<?= $totalFiltered!==1?'s':'' ?></span>

    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<!-- ── Table ───────────────────────────────────────────────── -->
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($customers)): ?>
        <div class="empty-state">
            <i data-lucide="users" style="width:40px;height:40px;color:var(--border);margin-bottom:12px;"></i>
            <p>No customers found<?= $search ? ' for <strong>'.htmlspecialchars($search).'</strong>' : '' ?>.</p>
            <?php if ($search || $status || $nationality): ?>
            <a href="customers-directory.php" class="btn btn-ghost btn-sm" style="margin-top:12px;">Clear Filters</a>
            <?php else: ?>
            <a href="customer-add.php" class="btn btn-primary btn-sm" style="margin-top:12px;">Add First Customer</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <table class="data-table" id="customers-table">
            <thead>
                <tr>
                    <th style="width:36px;"><input type="checkbox" id="chk-all" title="Select all"></th>
                    <th>Customer</th>
                    <th>Contact</th>
                    <th>Nationality</th>
                    <th>Passport Expiry</th>
                    <th>Applications</th>
                    <th>Outstanding</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $c): ?>
            <tr>
                <td><input type="checkbox" class="chk-row" value="<?= $c['id'] ?>"></td>
                <td>
                    <div class="avatar-cell">
                        <div class="cus-avatar" style="background:<?= avatarColor($c['id']) ?>;">
                            <?= initials($c['first_name'], $c['last_name']) ?>
                        </div>
                        <div>
                            <div class="avatar-cell-name">
                                <a href="customer-profile.php?id=<?= $c['id'] ?>">
                                    <?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?>
                                </a>
                            </div>
                            <div class="avatar-cell-sub" style="font-family:monospace;"><?= htmlspecialchars($c['customer_code']) ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:0.87rem;"><?= htmlspecialchars($c['email']) ?></div>
                    <div style="font-size:0.78rem;color:var(--ink-soft);"><?= htmlspecialchars($c['phone'] ?? '—') ?></div>
                </td>
                <td><?= htmlspecialchars($c['nationality'] ?? '—') ?></td>
                <td><?= passportExpiry($c['passport_expiry_date']) ?></td>
                <td>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <?php if ($c['visa_count'] > 0): ?>
                        <span class="badge badge-review" title="Visa Applications">✈ <?= $c['visa_count'] ?></span>
                        <?php endif; ?>
                        <?php if ($c['edu_count'] > 0): ?>
                        <span class="badge badge-new" title="Education Applications">🎓 <?= $c['edu_count'] ?></span>
                        <?php endif; ?>
                        <?php if ($c['booking_count'] > 0): ?>
                        <span class="badge badge-confirmed" title="Bookings">📅 <?= $c['booking_count'] ?></span>
                        <?php endif; ?>
                        <?php if ($c['visa_count']+$c['edu_count']+$c['booking_count'] === 0): ?>
                        <span style="color:var(--ink-soft);font-size:0.8rem;">None</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td>
                    <?php if ($c['outstanding'] > 0): ?>
                    <span class="expiry-warn" style="font-weight:700;">$<?= number_format($c['outstanding'],2) ?></span>
                    <?php else: ?>
                    <span style="color:var(--ink-soft);font-size:0.85rem;">—</span>
                    <?php endif; ?>
                </td>
                <td><?= badgeStatus($c['status']) ?></td>
                <td>
                    <div class="tbl-actions">
                        <a href="customer-profile.php?id=<?= $c['id'] ?>" class="tbl-btn" title="View Profile">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <a href="customer-edit.php?id=<?= $c['id'] ?>" class="tbl-btn" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                        <a href="visa-create.php?customer_id=<?= $c['id'] ?>" class="tbl-btn success" title="New Visa Application">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        </a>
                        <a href="customer-delete.php?id=<?= $c['id'] ?>" class="tbl-btn danger" title="Delete"
                           onclick="return confirm('Delete <?= htmlspecialchars($c['first_name']) ?>? This cannot be undone.')">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&nat=<?= urlencode($nationality) ?>&p=<?= $page-1 ?>">‹ Prev</a>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
            <?php if ($i===$page): ?>
            <span class="current"><?= $i ?></span>
            <?php else: ?>
            <a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&nat=<?= urlencode($nationality) ?>&p=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&nat=<?= urlencode($nationality) ?>&p=<?= $page+1 ?>">Next ›</a>
            <?php endif; ?>
            <span style="margin-left:auto;font-size:0.8rem;color:var(--ink-soft);">
                Page <?= $page ?> of <?= $totalPages ?> · <?= number_format($totalFiltered) ?> results
            </span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Select all checkbox
document.getElementById('chk-all')?.addEventListener('change', function() {
    document.querySelectorAll('.chk-row').forEach(c => c.checked = this.checked);
});
// Live search on enter
document.getElementById('customer-search-input')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') this.closest('form').submit();
});
</script>

<?php include 'includes/footer.php'; ?>
