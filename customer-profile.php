<?php
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: customers-directory.php'); exit; }

$c = db_row("
    SELECT c.*, u.name AS agent_name
    FROM customers c
    LEFT JOIN users u ON u.id = c.assigned_agent_id
    WHERE c.id = ?", [$id]);

if (!$c) { header('Location: customers-directory.php'); exit; }

$pageTitle = htmlspecialchars($c['first_name'].' '.$c['last_name']);

// ── Related data ──────────────────────────────────────────────
$documents    = db_select("SELECT * FROM customer_documents WHERE customer_id = ? ORDER BY uploaded_at DESC", [$id]);
$visaApps     = db_select("
    SELECT va.*, vt.name AS visa_type_name, vt.country
    FROM visa_applications va
    JOIN visa_types vt ON vt.id = va.visa_type_id
    WHERE va.customer_id = ?
    ORDER BY va.created_at DESC", [$id]);
$eduApps      = db_select("
    SELECT ea.*, ei.name AS institution_name, ep.name AS program_name, ep.degree_level
    FROM education_applications ea
    JOIN education_institutions ei ON ei.id = ea.institution_id
    JOIN education_programs ep     ON ep.id = ea.program_id
    WHERE ea.customer_id = ?
    ORDER BY ea.created_at DESC", [$id]);
$bookings     = db_select("
    SELECT b.*, tp.name AS package_name
    FROM bookings b
    LEFT JOIN travel_packages tp ON tp.id = b.package_id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC", [$id]);
$invoices     = db_select("
    SELECT * FROM invoices WHERE customer_id = ? ORDER BY issue_date DESC", [$id]);
$payments     = db_select("
    SELECT p.*, i.invoice_number
    FROM payments p
    JOIN invoices i ON i.id = p.invoice_id
    WHERE p.customer_id = ?
    ORDER BY p.payment_date DESC", [$id]);
$appointments = db_select("
    SELECT a.*, u.name AS staff_name
    FROM appointments a
    LEFT JOIN users u ON u.id = a.staff_id
    WHERE a.customer_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC", [$id]);
$activityLogs = db_select("
    SELECT al.*, u.name AS user_name
    FROM activity_logs al
    LEFT JOIN users u ON u.id = al.user_id
    WHERE al.entity_type = 'customers' AND al.entity_id = ?
    ORDER BY al.created_at DESC
    LIMIT 50", [$id]);

// Passport expiry days
$passExpDays = null;
if ($c['passport_expiry_date']) {
    $now = new DateTime();
    $exp = new DateTime($c['passport_expiry_date']);
    $passExpDays = $now <= $exp ? $now->diff($exp)->days : -1;
}

$avatarColors = ['#101d2c','#2563eb','#7c3aed','#047857','#d97706','#b0413e','#0e7490','#9d174d'];
$avatarColor = $avatarColors[$id % count($avatarColors)];
$initials = strtoupper(substr($c['first_name'],0,1).substr($c['last_name'],0,1));

function fmtDate(?string $d, string $fmt='d M Y'): string {
    return $d ? date($fmt, strtotime($d)) : '—';
}
function badge(string $val, string $cls=''): string {
    if (!$val) return '<span style="color:var(--ink-soft);">—</span>';
    if (!$cls) {
        $map = [
            'active'=>'badge-active','inactive'=>'badge-inactive','blacklisted'=>'badge-blacklisted',
            'confirmed'=>'badge-confirmed','pending'=>'badge-pending','cancelled'=>'badge-cancelled',
            'approved'=>'badge-approved','rejected'=>'badge-rejected','draft'=>'badge-draft',
            'submitted'=>'badge-submitted','under_review'=>'badge-review','processing'=>'badge-processing',
            'new'=>'badge-new','enrolled'=>'badge-enrolled','accepted'=>'badge-accepted',
            'paid'=>'badge-paid','overdue'=>'badge-overdue','completed'=>'badge-confirmed',
            'documents_pending'=>'badge-pending','ready_for_submission'=>'badge-review',
            'appointment_scheduled'=>'badge-review','documents_pending'=>'badge-pending',
        ];
        $cls = $map[strtolower($val)] ?? 'badge-draft';
    }
    return '<span class="badge '.$cls.'">'.htmlspecialchars(ucwords(str_replace('_',' ',$val))).'</span>';
}

include 'includes/header.php';
?>

<!-- Profile Hero -->
<div class="profile-hero">
    <div class="cus-avatar-xl" style="background:<?= $avatarColor ?>;"><?= $initials ?></div>
    <div class="profile-hero-info">
        <div class="profile-hero-name"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></div>
        <div class="profile-hero-code"><?= htmlspecialchars($c['customer_code']) ?></div>
        <div class="profile-hero-meta">
            <?php if ($c['email']): ?>
            <span class="profile-hero-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <?= htmlspecialchars($c['email']) ?>
            </span>
            <?php endif; ?>
            <?php if ($c['phone']): ?>
            <span class="profile-hero-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.62 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <?= htmlspecialchars($c['phone']) ?>
            </span>
            <?php endif; ?>
            <?php if ($c['nationality']): ?>
            <span class="profile-hero-meta-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                <?= htmlspecialchars($c['nationality']) ?>
            </span>
            <?php endif; ?>
            <span class="profile-hero-meta-item">Customer since <?= fmtDate($c['created_at'], 'M Y') ?></span>
        </div>
    </div>
    <div class="profile-hero-actions">
        <?= badge($c['status']) ?>
        <a href="customer-edit.php?id=<?= $id ?>" class="btn btn-white-outline btn-sm">
            <i data-lucide="edit-2"></i> Edit
        </a>
        <a href="visa-create.php?customer_id=<?= $id ?>" class="btn btn-gold btn-sm">
            <i data-lucide="file-plus"></i> New Visa App
        </a>
        <a href="customer-upload-doc.php?customer_id=<?= $id ?>" class="btn btn-white-outline btn-sm">
            <i data-lucide="upload"></i> Upload Doc
        </a>
    </div>
</div>

<!-- Profile Tabs -->
<div class="profile-tabs" role="tablist">
    <button class="profile-tab active" data-tab="personal" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Personal Info
    </button>
    <button class="profile-tab" data-tab="passport" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="2" y="3" width="20" height="18" rx="2"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="14" y2="14"/></svg>
        Passport
    </button>
    <button class="profile-tab" data-tab="documents" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        Documents <span class="tab-count"><?= count($documents) ?></span>
    </button>
    <button class="profile-tab" data-tab="visa" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Visa Apps <span class="tab-count"><?= count($visaApps) ?></span>
    </button>
    <button class="profile-tab" data-tab="education" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
        Education <span class="tab-count"><?= count($eduApps) ?></span>
    </button>
    <button class="profile-tab" data-tab="bookings" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Bookings <span class="tab-count"><?= count($bookings) ?></span>
    </button>
    <button class="profile-tab" data-tab="payments" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Payments <span class="tab-count"><?= count($payments) ?></span>
    </button>
    <button class="profile-tab" data-tab="appointments" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Appointments <span class="tab-count"><?= count($appointments) ?></span>
    </button>
    <button class="profile-tab" data-tab="activity" role="tab">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Activity
    </button>
</div>

<!-- ══ TAB 1: Personal Info ══════════════════════════════════ -->
<div class="tab-panel active" id="tab-personal">
    <div class="panel">
        <div class="panel-head">
            <h2>Personal Information</h2>
            <a href="customer-edit.php?id=<?= $id ?>&tab=personal" class="btn btn-ghost btn-sm"><i data-lucide="edit-2"></i> Edit</a>
        </div>
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-row"><div class="info-label">First Name</div><div class="info-value"><?= htmlspecialchars($c['first_name']) ?></div></div>
                <div class="info-row"><div class="info-label">Last Name</div><div class="info-value"><?= htmlspecialchars($c['last_name']) ?></div></div>
                <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($c['email']) ?></div></div>
                <div class="info-row"><div class="info-label">Phone</div><div class="info-value"><?= htmlspecialchars($c['phone'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Date of Birth</div><div class="info-value"><?= fmtDate($c['date_of_birth']) ?></div></div>
                <div class="info-row"><div class="info-label">Nationality</div><div class="info-value"><?= htmlspecialchars($c['nationality'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Gender</div><div class="info-value"><?= ucfirst($c['gender'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Marital Status</div><div class="info-value"><?= ucfirst($c['marital_status'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Occupation</div><div class="info-value"><?= htmlspecialchars($c['occupation'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Employer</div><div class="info-value"><?= htmlspecialchars($c['employer'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Address</div><div class="info-value"><?= htmlspecialchars(implode(', ', array_filter([$c['address_line1'],$c['address_line2'],$c['city'],$c['state'],$c['postal_code'],$c['country']]))) ?: '—' ?></div></div>
                <div class="info-row"><div class="info-label">Assigned Agent</div><div class="info-value"><?= htmlspecialchars($c['agent_name'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Emergency Contact</div><div class="info-value"><?= htmlspecialchars($c['emergency_contact_name'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Emergency Phone</div><div class="info-value"><?= htmlspecialchars($c['emergency_contact_phone'] ?? '—') ?></div></div>
            </div>
            <?php if ($c['notes']): ?>
            <div style="margin-top:20px;padding:14px;background:#fafaf8;border-radius:8px;border:1px solid var(--border);">
                <div class="info-label">Notes</div>
                <p style="font-size:0.88rem;margin-top:6px;"><?= nl2br(htmlspecialchars($c['notes'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══ TAB 2: Passport ══════════════════════════════════════ -->
<div class="tab-panel" id="tab-passport">
    <div class="panel">
        <div class="panel-head">
            <h2>Passport Information</h2>
            <a href="customer-edit.php?id=<?= $id ?>&tab=passport" class="btn btn-ghost btn-sm"><i data-lucide="edit-2"></i> Edit</a>
        </div>
        <div class="panel-body">
            <?php if ($passExpDays !== null && $passExpDays < 180 && $passExpDays >= 0): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">
                <strong>⚠️ Passport Expiring Soon:</strong>
                Expires in <strong><?= $passExpDays ?> days</strong> on <?= fmtDate($c['passport_expiry_date']) ?>.
                Many visa applications require at least 6 months validity.
            </div>
            <?php elseif ($passExpDays !== null && $passExpDays < 0): ?>
            <div class="alert alert-error" style="margin-bottom:20px;">
                <strong>🚨 Passport Expired!</strong> Please update the customer's passport information.
            </div>
            <?php endif; ?>
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Passport Number</div><div class="info-value mono"><?= htmlspecialchars($c['passport_number'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">National ID</div><div class="info-value mono"><?= htmlspecialchars($c['national_id'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Issue Date</div><div class="info-value"><?= fmtDate($c['passport_issue_date']) ?></div></div>
                <div class="info-row">
                    <div class="info-label">Expiry Date</div>
                    <div class="info-value">
                        <?php if ($c['passport_expiry_date']): ?>
                            <?php if ($passExpDays < 0): ?>
                                <span class="expiry-crit"><?= fmtDate($c['passport_expiry_date']) ?> (Expired)</span>
                            <?php elseif ($passExpDays < 180): ?>
                                <span class="expiry-warn"><?= fmtDate($c['passport_expiry_date']) ?> (<?= $passExpDays ?> days left)</span>
                            <?php else: ?>
                                <?= fmtDate($c['passport_expiry_date']) ?> (<?= $passExpDays ?> days left)
                            <?php endif; ?>
                        <?php else: ?>—<?php endif; ?>
                    </div>
                </div>
                <div class="info-row"><div class="info-label">Nationality</div><div class="info-value"><?= htmlspecialchars($c['nationality'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Country</div><div class="info-value"><?= htmlspecialchars($c['country'] ?? '—') ?></div></div>
            </div>
        </div>
    </div>
</div>

<!-- ══ TAB 3: Documents ════════════════════════════════════ -->
<div class="tab-panel" id="tab-documents">
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <a href="customer-upload-doc.php?customer_id=<?= $id ?>" class="btn btn-primary btn-sm" id="btn-upload-doc">
            <i data-lucide="upload"></i> Upload Document
        </a>
    </div>
    <?php if (empty($documents)): ?>
    <div class="panel"><div class="panel-body"><div class="empty-state">No documents uploaded yet.</div></div></div>
    <?php else: ?>
    <div class="doc-grid">
        <?php foreach ($documents as $doc):
            $docIcons = ['passport'=>'🛂','national_id'=>'🪪','bank_statement'=>'🏦','photo'=>'📷','admission_letter'=>'🎓','other'=>'📄'];
            $icon = $docIcons[$doc['document_type']] ?? '📄';
            $statusCls = ['verified'=>'badge-approved','rejected'=>'badge-rejected','pending'=>'badge-pending','expired'=>'badge-blacklisted'][$doc['status']] ?? 'badge-draft';
        ?>
        <div class="doc-card">
            <div class="doc-card-icon" style="font-size:1.4rem;"><?= $icon ?></div>
            <div class="doc-card-name"><?= htmlspecialchars($doc['file_name']) ?></div>
            <div class="doc-card-type"><?= str_replace('_',' ', $doc['document_type']) ?></div>
            <span class="badge <?= $statusCls ?>"><?= ucfirst($doc['status']) ?></span>
            <?php if ($doc['expiry_date']): ?>
            <div style="font-size:0.73rem;color:var(--ink-soft);margin-top:6px;">Expires: <?= fmtDate($doc['expiry_date']) ?></div>
            <?php endif; ?>
            <div class="doc-card-actions">
                <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-ghost btn-sm">View</a>
                <a href="document-verify.php?id=<?= $doc['id'] ?>" class="btn btn-ghost btn-sm">Verify</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- ══ TAB 4: Visa Applications ════════════════════════════ -->
<div class="tab-panel" id="tab-visa">
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <a href="visa-create.php?customer_id=<?= $id ?>" class="btn btn-primary btn-sm">
            <i data-lucide="file-plus"></i> New Visa Application
        </a>
    </div>
    <?php if (empty($visaApps)): ?>
    <div class="panel"><div class="panel-body"><div class="empty-state">No visa applications yet.</div></div></div>
    <?php else: ?>
    <div class="panel">
        <div class="panel-body table-wrap" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Visa Type</th><th>Country</th><th>Travel Date</th><th>Status</th><th>Priority</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($visaApps as $va): ?>
                <tr>
                    <td><code style="font-size:0.8rem;"><?= htmlspecialchars($va['application_code']) ?></code></td>
                    <td><?= htmlspecialchars($va['visa_type_name']) ?></td>
                    <td><?= htmlspecialchars($va['country']) ?></td>
                    <td><?= fmtDate($va['travel_date']) ?></td>
                    <td><?= badge($va['status']) ?></td>
                    <td><?= badge($va['priority']) ?></td>
                    <td><?= fmtDate($va['created_at']) ?></td>
                    <td>
                        <div class="tbl-actions">
                            <a href="visa-detail.php?id=<?= $va['id'] ?>" class="tbl-btn" title="View">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══ TAB 5: Education Applications ═══════════════════════ -->
<div class="tab-panel" id="tab-education">
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <a href="education-create.php?customer_id=<?= $id ?>" class="btn btn-primary btn-sm">
            <i data-lucide="graduation-cap"></i> New Education Application
        </a>
    </div>
    <?php if (empty($eduApps)): ?>
    <div class="panel"><div class="panel-body"><div class="empty-state">No education applications yet.</div></div></div>
    <?php else: ?>
    <div class="panel">
        <div class="panel-body table-wrap" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Institution</th><th>Program</th><th>Level</th><th>Intake</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($eduApps as $ea): ?>
                <tr>
                    <td><code style="font-size:0.8rem;"><?= htmlspecialchars($ea['application_code']) ?></code></td>
                    <td><?= htmlspecialchars($ea['institution_name']) ?></td>
                    <td><?= htmlspecialchars($ea['program_name']) ?></td>
                    <td><?= ucfirst($ea['degree_level']) ?></td>
                    <td><?= htmlspecialchars($ea['intake'] ?? '—') ?></td>
                    <td><?= badge($ea['status']) ?></td>
                    <td>
                        <div class="tbl-actions">
                            <a href="education-detail.php?id=<?= $ea['id'] ?>" class="tbl-btn" title="View">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══ TAB 6: Bookings ══════════════════════════════════════ -->
<div class="tab-panel" id="tab-bookings">
    <?php if (empty($bookings)): ?>
    <div class="panel"><div class="panel-body"><div class="empty-state">No bookings yet.</div></div></div>
    <?php else: ?>
    <div class="panel">
        <div class="panel-body table-wrap" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Type</th><th>Package</th><th>Travel Date</th><th>Amount</th><th>Paid</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><code style="font-size:0.8rem;"><?= htmlspecialchars($b['booking_code']) ?></code></td>
                    <td><?= ucwords(str_replace('_',' ',$b['booking_type'])) ?></td>
                    <td><?= htmlspecialchars($b['package_name'] ?? '—') ?></td>
                    <td><?= fmtDate($b['travel_date']) ?></td>
                    <td><strong>$<?= number_format($b['total_amount'],2) ?></strong></td>
                    <td>$<?= number_format($b['paid_amount'],2) ?></td>
                    <td><?= badge($b['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══ TAB 7: Payments ══════════════════════════════════════ -->
<div class="tab-panel" id="tab-payments">
    <?php if (empty($invoices) && empty($payments)): ?>
    <div class="panel"><div class="panel-body"><div class="empty-state">No payment records yet.</div></div></div>
    <?php else: ?>

    <!-- Outstanding invoices -->
    <?php $outstanding = array_filter($invoices, fn($i)=>in_array($i['status'],['sent','overdue','partially_paid'])); ?>
    <?php if ($outstanding): ?>
    <div class="panel" style="border-top:3px solid var(--danger);">
        <div class="panel-head"><h2>⚠️ Outstanding Invoices</h2></div>
        <div class="panel-body table-wrap" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Invoice #</th><th>Due Date</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($outstanding as $inv): ?>
                <tr>
                    <td><code><?= htmlspecialchars($inv['invoice_number']) ?></code></td>
                    <td><?= fmtDate($inv['due_date']) ?></td>
                    <td><strong>$<?= number_format($inv['total'],2) ?></strong></td>
                    <td><?= badge($inv['status']) ?></td>
                    <td><a href="finance-invoices.php?id=<?= $inv['id'] ?>" class="btn btn-ghost btn-sm">View</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Payment history -->
    <div class="panel">
        <div class="panel-head"><h2>Payment History</h2></div>
        <div class="panel-body table-wrap" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Ref</th><th>Invoice</th><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><code style="font-size:0.8rem;"><?= htmlspecialchars($p['payment_ref']) ?></code></td>
                    <td><?= htmlspecialchars($p['invoice_number']) ?></td>
                    <td><?= fmtDate($p['payment_date']) ?></td>
                    <td><strong>$<?= number_format($p['amount'],2) ?></strong></td>
                    <td><?= ucwords(str_replace('_',' ',$p['method'])) ?></td>
                    <td><?= badge($p['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($payments)): ?>
                <tr><td colspan="6" class="empty-state">No payments recorded.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══ TAB 8: Appointments ══════════════════════════════════ -->
<div class="tab-panel" id="tab-appointments">
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <a href="customers-appointments.php?customer_id=<?= $id ?>" class="btn btn-primary btn-sm">
            <i data-lucide="calendar-plus"></i> Schedule Appointment
        </a>
    </div>
    <?php if (empty($appointments)): ?>
    <div class="panel"><div class="panel-body"><div class="empty-state">No appointments scheduled.</div></div></div>
    <?php else: ?>
    <div class="panel">
        <div class="panel-body table-wrap" style="padding:0;">
            <table class="data-table">
                <thead><tr><th>Ref</th><th>Type</th><th>Date</th><th>Time</th><th>Duration</th><th>Staff</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><code style="font-size:0.8rem;"><?= htmlspecialchars($a['appointment_ref']) ?></code></td>
                    <td><span class="appt-type-pill <?= explode('_',$a['appointment_type'])[0] ?>"><?= ucwords(str_replace('_',' ',$a['appointment_type'])) ?></span></td>
                    <td><?= fmtDate($a['appointment_date']) ?></td>
                    <td><?= date('g:i A', strtotime($a['appointment_time'])) ?></td>
                    <td><?= $a['duration_mins'] ?> min</td>
                    <td><?= htmlspecialchars($a['staff_name'] ?? '—') ?></td>
                    <td><?= badge($a['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- ══ TAB 9: Activity ══════════════════════════════════════ -->
<div class="tab-panel" id="tab-activity">
    <div class="panel">
        <div class="panel-head"><h2>Activity History</h2><span class="panel-head-stat">Last 50 events</span></div>
        <div class="panel-body">
            <?php if (empty($activityLogs)): ?>
            <div class="empty-state">No activity logged yet.</div>
            <?php else: ?>
            <ul class="activity-feed">
                <?php foreach ($activityLogs as $log): ?>
                <li>
                    <div class="activity-icon blue"><i data-lucide="activity" style="width:13px;height:13px;"></i></div>
                    <div class="activity-text">
                        <?= htmlspecialchars($log['description'] ?: $log['action']) ?>
                        <?php if ($log['user_name']): ?>
                        · <span style="color:var(--ink-soft);">by <?= htmlspecialchars($log['user_name']) ?></span>
                        <?php endif; ?>
                        <div class="activity-time"><?= date('d M Y, g:i A', strtotime($log['created_at'])) ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Tab switching
document.querySelectorAll('.profile-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
        // Update URL without reload
        const url = new URL(window.location);
        url.searchParams.set('tab', this.dataset.tab);
        history.replaceState({}, '', url);
    });
});
// Restore active tab from URL
const urlTab = new URL(window.location).searchParams.get('tab');
if (urlTab) {
    const tabBtn = document.querySelector('[data-tab="'+urlTab+'"]');
    if (tabBtn) tabBtn.click();
}
</script>

<?php include 'includes/footer.php'; ?>
