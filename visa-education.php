<?php
$pageTitle = 'Education Applications';
include 'includes/header.php';

// ── Filters ──────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1,(int)($_GET['p'] ?? 1));
$perPage= 25;
$offset = ($page-1) * $perPage;

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(c.first_name LIKE ? OR c.last_name LIKE ? OR ea.application_code LIKE ? OR ei.name LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params,[$s,$s,$s,$s]);
}
if ($status) { $where[] = 'ea.status = ?'; $params[] = $status; }
$whereSQL = implode(' AND ', $where);

$totalFiltered = db_count("
    SELECT COUNT(*) FROM education_applications ea
    JOIN customers c ON c.id = ea.customer_id
    JOIN education_institutions ei ON ei.id = ea.institution_id
    WHERE $whereSQL", $params);
$totalPages = max(1, ceil($totalFiltered/$perPage));

$applications = db_select("
    SELECT ea.*, ei.name AS institution_name, ei.country,
           ep.name AS program_name, ep.degree_level,
           c.first_name, c.last_name, c.customer_code, c.nationality
    FROM education_applications ea
    JOIN customers c ON c.id = ea.customer_id
    JOIN education_institutions ei ON ei.id = ea.institution_id
    JOIN education_programs ep ON ep.id = ea.program_id
    WHERE $whereSQL
    ORDER BY ea.created_at DESC
    LIMIT $perPage OFFSET $offset", $params);

function eBadge(string $s): string {
    $m=['new'=>'badge-new','documents_pending'=>'badge-pending','submitted_to_institution'=>'badge-submitted',
        'under_review'=>'badge-review','interview_scheduled'=>'badge-review','offer_received'=>'badge-approved',
        'conditional_offer'=>'badge-pending','accepted'=>'badge-approved','rejected'=>'badge-rejected',
        'deferred'=>'badge-draft','withdrawn'=>'badge-inactive'];
    $cls=$m[$s]??'badge-draft';
    return '<span class="badge '.$cls.'">'.ucwords(str_replace('_',' ',$s)).'</span>';
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Education Applications</h2>
        <p>Manage all university and school applications</p>
    </div>
    <div class="page-header-actions">
        <a href="education-create.php" class="btn btn-primary">
            <i data-lucide="graduation-cap"></i> New Application
        </a>
    </div>
</div>

<form method="GET" action="visa-education.php">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search student, code, or institution...">
    </div>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['new','documents_pending','submitted_to_institution','under_review','interview_scheduled','offer_received','conditional_offer','accepted','rejected','deferred','withdrawn'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($search||$status): ?>
    <a href="visa-education.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
    <span class="sfbar-count"><?= number_format($totalFiltered) ?> result<?= $totalFiltered!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($applications)): ?>
        <div class="empty-state" style="padding:48px;">No education applications found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Applicant</th>
                    <th>Institution</th>
                    <th>Program</th>
                    <th>Intake</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($applications as $ea): ?>
            <tr>
                <td><code style="font-size:0.78rem;"><?= htmlspecialchars($ea['application_code']) ?></code></td>
                <td>
                    <div style="font-weight:600;"><a href="customer-profile.php?id=<?= $ea['customer_id'] ?>&tab=education"><?= htmlspecialchars($ea['first_name'].' '.$ea['last_name']) ?></a></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($ea['nationality'] ?? '') ?></div>
                </td>
                <td>
                    <div style="font-weight:500;"><?= htmlspecialchars($ea['institution_name']) ?></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($ea['country']) ?></div>
                </td>
                <td>
                    <div style="font-weight:500;"><?= htmlspecialchars($ea['program_name']) ?></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= ucfirst($ea['degree_level']) ?></div>
                </td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($ea['intake'] ?? '—') ?></td>
                <td><?= eBadge($ea['status']) ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($ea['created_at'])) ?></td>
                <td>
                    <div class="tbl-actions">
                        <a href="education-detail.php?id=<?= $ea['id'] ?>" class="tbl-btn" title="View"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
