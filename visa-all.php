<?php
$pageTitle = 'Visa Applications';
include 'includes/header.php';

// ── Filters ──────────────────────────────────────────────────
$search   = trim($_GET['q']      ?? '');
$status   = trim($_GET['status'] ?? '');
$type     = trim($_GET['type']   ?? '');
$priority = trim($_GET['priority']?? '');
$category = trim($_GET['category']?? ''); // For internal wrappers
$page     = max(1,(int)($_GET['p'] ?? 1));
$perPage  = 25;
$offset   = ($page-1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(c.first_name LIKE ? OR c.last_name LIKE ? OR va.application_code LIKE ? OR va.destination_country LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params,[$s,$s,$s,$s]);
}
if ($status)   { $where[] = 'va.status = ?';     $params[] = $status; }
if ($type)     { $where[] = 'vt.category = ?';   $params[] = $type; }
if ($priority) { $where[] = 'va.priority = ?';   $params[] = $priority; }
if ($category) { $where[] = 'vt.category = ?';   $params[] = $category; }
$whereSQL = implode(' AND ', $where);

// Stats
$stats = db_row("SELECT
    COUNT(*) AS total,
    SUM(status='draft') AS draft,
    SUM(status='documents_pending') AS docs_pending,
    SUM(status='under_review') AS review,
    SUM(status='submitted') AS submitted,
    SUM(status='appointment_scheduled') AS appt,
    SUM(status='processing') AS processing,
    SUM(status='approved') AS approved,
    SUM(status='rejected') AS rejected,
    SUM(status='cancelled') AS cancelled
FROM visa_applications");

$totalFiltered = db_count("
    SELECT COUNT(*) FROM visa_applications va
    JOIN customers c ON c.id = va.customer_id
    JOIN visa_types vt ON vt.id = va.visa_type_id
    WHERE $whereSQL", $params);
$totalPages = max(1, ceil($totalFiltered/$perPage));

$applications = db_select("
    SELECT va.*, vt.name AS visa_type_name, vt.category,
           c.first_name, c.last_name, c.customer_code, c.nationality,
           u.name AS officer_name
    FROM visa_applications va
    JOIN customers c  ON c.id  = va.customer_id
    JOIN visa_types vt ON vt.id = va.visa_type_id
    LEFT JOIN users u ON u.id  = va.assigned_officer_id
    WHERE $whereSQL
    ORDER BY va.created_at DESC
    LIMIT $perPage OFFSET $offset", $params);

$visaTypes = db_select("SELECT DISTINCT category FROM visa_types ORDER BY category");

$avatarColors = ['#101d2c','#2563eb','#7c3aed','#047857','#d97706','#b0413e','#0e7490','#9d174d'];
function avColor($id) { global $avatarColors; return $avatarColors[$id % count($avatarColors)]; }
function ini($f,$l) { return strtoupper(substr($f,0,1).substr($l,0,1)); }
function vBadge(string $s): string {
    $m=['draft'=>'badge-draft','documents_pending'=>'badge-pending','under_review'=>'badge-review',
        'ready_for_submission'=>'badge-review','submitted'=>'badge-submitted',
        'appointment_scheduled'=>'badge-review','processing'=>'badge-processing',
        'approved'=>'badge-approved','rejected'=>'badge-rejected','cancelled'=>'badge-inactive',
        'withdrawn'=>'badge-inactive'];
    $cls=$m[$s]??'badge-draft';
    return '<span class="badge '.$cls.'">'.ucwords(str_replace('_',' ',$s)).'</span>';
}
function pBadge(string $p): string {
    $m=['normal'=>'badge-draft','urgent'=>'badge-pending','express'=>'badge-rejected'];
    $cls=$m[$p]??'badge-draft';
    return '<span class="badge '.$cls.'">'.ucfirst($p).'</span>';
}
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h2>Visa Applications</h2>
        <p>Manage all visa applications across all customers</p>
    </div>
    <div class="page-header-actions">
        <a href="visa-create.php" class="btn btn-primary" id="btn-new-visa">
            <i data-lucide="file-plus"></i> New Application
        </a>
        <a href="#" class="btn btn-ghost"><i data-lucide="download"></i> Export</a>
    </div>
</div>

<!-- Stats Row -->
<div class="stats-row" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));">
    <div class="stat-mini border-navy" onclick="setFilter('')" style="cursor:pointer;" title="All">
        <div class="stat-mini-val"><?= $stats['total'] ?></div>
        <div class="stat-mini-label">Total</div>
    </div>
    <div class="stat-mini border-gold" onclick="setFilter('documents_pending')" style="cursor:pointer;" title="Docs Pending">
        <div class="stat-mini-val"><?= $stats['docs_pending'] ?></div>
        <div class="stat-mini-label">Docs Pending</div>
    </div>
    <div class="stat-mini border-blue" onclick="setFilter('under_review')" style="cursor:pointer;">
        <div class="stat-mini-val"><?= $stats['review'] ?></div>
        <div class="stat-mini-label">Under Review</div>
    </div>
    <div class="stat-mini border-purple" onclick="setFilter('submitted')" style="cursor:pointer;">
        <div class="stat-mini-val"><?= $stats['submitted'] ?></div>
        <div class="stat-mini-label">Submitted</div>
    </div>
    <div class="stat-mini border-gold" onclick="setFilter('appointment_scheduled')" style="cursor:pointer;">
        <div class="stat-mini-val"><?= $stats['appt'] ?></div>
        <div class="stat-mini-label">Appt. Scheduled</div>
    </div>
    <div class="stat-mini border-green" onclick="setFilter('approved')" style="cursor:pointer;">
        <div class="stat-mini-val"><?= $stats['approved'] ?></div>
        <div class="stat-mini-label">Approved</div>
    </div>
    <div class="stat-mini border-red" onclick="setFilter('rejected')" style="cursor:pointer;">
        <div class="stat-mini-val"><?= $stats['rejected'] ?></div>
        <div class="stat-mini-label">Rejected</div>
    </div>
</div>

<!-- Search Bar -->
<form method="GET" action="visa-all.php" id="visa-filter-form">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by customer, code, country…">
    </div>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['draft','documents_pending','under_review','ready_for_submission','submitted','appointment_scheduled','processing','approved','rejected','cancelled'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="type" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($visaTypes as $vt): ?>
        <option value="<?= htmlspecialchars($vt['category']) ?>" <?= $type===$vt['category']?'selected':'' ?>><?= ucfirst($vt['category']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="priority" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Priority</option>
        <option value="normal"  <?= $priority==='normal'?'selected':''  ?>>Normal</option>
        <option value="urgent"  <?= $priority==='urgent'?'selected':''  ?>>Urgent</option>
        <option value="express" <?= $priority==='express'?'selected':'' ?>>Express</option>
    </select>
    <?php if ($search||$status||$type||$priority): ?>
    <a href="visa-all.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
    <span class="sfbar-count"><?= number_format($totalFiltered) ?> result<?= $totalFiltered!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<!-- Table -->
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($applications)): ?>
        <div class="empty-state" style="padding:48px;">
            <p>No visa applications found.</p>
            <a href="visa-create.php" class="btn btn-primary btn-sm" style="margin-top:12px;">Create First Application</a>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th>Visa Type</th>
                    <th>Country</th>
                    <th>Travel Date</th>
                    <th>Officer</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $va): ?>
            <tr>
                <td><code style="font-size:0.78rem;font-family:monospace;"><?= htmlspecialchars($va['application_code']) ?></code></td>
                <td>
                    <div class="avatar-cell">
                        <div class="cus-avatar" style="background:<?= avColor($va['customer_id']) ?>;width:32px;height:32px;font-size:0.72rem;">
                            <?= ini($va['first_name'],$va['last_name']) ?>
                        </div>
                        <div>
                            <div class="avatar-cell-name">
                                <a href="customer-profile.php?id=<?= $va['customer_id'] ?>&tab=visa">
                                    <?= htmlspecialchars($va['first_name'].' '.$va['last_name']) ?>
                                </a>
                            </div>
                            <div class="avatar-cell-sub"><?= htmlspecialchars($va['nationality'] ?? '') ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:0.87rem;font-weight:500;"><?= htmlspecialchars($va['visa_type_name']) ?></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= ucfirst($va['category']) ?></div>
                </td>
                <td><?= htmlspecialchars($va['destination_country']) ?></td>
                <td style="font-size:0.85rem;"><?= $va['travel_date'] ? date('d M Y', strtotime($va['travel_date'])) : '—' ?></td>
                <td style="font-size:0.83rem;"><?= htmlspecialchars($va['officer_name'] ?? '—') ?></td>
                <td><?= pBadge($va['priority']) ?></td>
                <td><?= vBadge($va['status']) ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($va['created_at'])) ?></td>
                <td>
                    <div class="tbl-actions">
                        <a href="visa-detail.php?id=<?= $va['id'] ?>" class="tbl-btn" title="View Application">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </a>
                        <a href="visa-edit.php?id=<?= $va['id'] ?>" class="tbl-btn" title="Edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page>1): ?><a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&priority=<?= urlencode($priority) ?>&p=<?= $page-1 ?>">‹ Prev</a><?php endif; ?>
            <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
            <?= $i===$page ? "<span class='current'>$i</span>" : "<a href='?q=".urlencode($search)."&status=".urlencode($status)."&type=".urlencode($type)."&priority=".urlencode($priority)."&p=$i'>$i</a>" ?>
            <?php endfor; ?>
            <?php if ($page<$totalPages): ?><a href="?q=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>&type=<?= urlencode($type) ?>&priority=<?= urlencode($priority) ?>&p=<?= $page+1 ?>">Next ›</a><?php endif; ?>
            <span style="margin-left:auto;font-size:0.8rem;color:var(--ink-soft);">Page <?= $page ?> of <?= $totalPages ?></span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function setFilter(status) {
    const url = new URL(window.location);
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    window.location = url;
}
</script>

<?php include 'includes/footer.php'; ?>
