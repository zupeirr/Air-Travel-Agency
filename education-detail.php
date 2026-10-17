<?php
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: visa-education.php'); exit; }

$ea = db_row("
    SELECT ea.*, ei.name AS institution_name, ei.country, ei.website AS institution_website,
           ep.name AS program_name, ep.degree_level, ep.duration_months, ep.tuition_fee, ep.currency,
           c.id AS cust_id, c.first_name, c.last_name, c.customer_code, c.email, c.phone,
           c.nationality, c.passport_number,
           u.name AS officer_name
    FROM education_applications ea
    JOIN education_institutions ei ON ei.id = ea.institution_id
    JOIN education_programs ep     ON ep.id = ea.program_id
    JOIN customers c               ON c.id  = ea.customer_id
    LEFT JOIN users u              ON u.id  = ea.assigned_officer_id
    WHERE ea.id = ?", [$id]);

if (!$ea) { header('Location: visa-education.php'); exit; }

$pageTitle = 'Edu App: '.$ea['application_code'];

// Related
$documents = db_select("
    SELECT cd.*
    FROM customer_documents cd
    WHERE cd.customer_id = ? AND cd.document_type IN ('passport', 'national_id', 'photo', 'bank_statement', 'admission_letter', 'other')
    ORDER BY cd.uploaded_at DESC", [$ea['cust_id']]);

$linkedVisas = db_select("
    SELECT va.*, vt.name AS visa_type_name
    FROM visa_applications va
    JOIN visa_types vt ON vt.id = va.visa_type_id
    WHERE va.customer_id = ?
    ORDER BY va.created_at DESC", [$ea['cust_id']]);

// Status handling
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $newStatus = $_POST['new_status'] ?? '';
        $notes     = trim($_POST['status_notes'] ?? '');
        $allowed   = ['new','documents_pending','submitted_to_institution','under_review','interview_scheduled',
                      'offer_received','conditional_offer','accepted','rejected','deferred','withdrawn'];
        if (in_array($newStatus, $allowed)) {
            $old = $ea['status'];
            db_execute("UPDATE education_applications SET status=?, updated_at=NOW() WHERE id=?", [$newStatus, $id]);
            // Log to activity log
            db_insert("INSERT INTO activity_logs (entity_type, entity_id, action, description, user_id) VALUES (?,?,?,?,?)",
                ['education_applications', $id, 'status_updated', "Status changed from {$old} to {$newStatus}. " . $notes, 1]); // Assuming user_id 1 for admin
            header("Location: education-detail.php?id=$id&updated=1");
            exit;
        }
    }
}

// Track step order
$statusOrder = ['new','documents_pending','submitted_to_institution','under_review','offer_received','accepted'];
$currentStep = array_search($ea['status'], $statusOrder);
// Map other statuses to positions
if ($ea['status'] === 'interview_scheduled') $currentStep = 3;
if ($ea['status'] === 'conditional_offer') $currentStep = 4;

$trackSteps = [
    ['key'=>'new',                      'label'=>'New',       'icon'=>'file'],
    ['key'=>'documents_pending',        'label'=>'Documents', 'icon'=>'upload'],
    ['key'=>'submitted_to_institution', 'label'=>'Submitted', 'icon'=>'send'],
    ['key'=>'under_review',             'label'=>'Review',    'icon'=>'search'],
    ['key'=>'offer_received',           'label'=>'Offer',     'icon'=>'award'],
    ['key'=>'accepted',                 'label'=>'Accepted',  'icon'=>'check-circle'],
];

function statusClass(string $s): string {
    $m=['new'=>'badge-new','documents_pending'=>'badge-pending','submitted_to_institution'=>'badge-submitted',
        'under_review'=>'badge-review','interview_scheduled'=>'badge-review','offer_received'=>'badge-approved',
        'conditional_offer'=>'badge-pending','accepted'=>'badge-approved','rejected'=>'badge-rejected',
        'deferred'=>'badge-draft','withdrawn'=>'badge-inactive'];
    return $m[$s]??'badge-draft';
}
$statusLabel = ucwords(str_replace('_',' ',$ea['status']));
$avatarColors = ['#101d2c','#2563eb','#7c3aed','#047857','#d97706','#b0413e'];
$avatarColor = $avatarColors[$ea['cust_id'] % count($avatarColors)];
$initials = strtoupper(substr($ea['first_name'],0,1).substr($ea['last_name'],0,1));
function fmtD(?string $d): string { return $d ? date('d M Y', strtotime($d)) : '—'; }

include 'includes/header.php';
?>

<?php if (isset($_GET['created'])): ?>
<div class="alert alert-success" style="margin-bottom:20px;">
    ✅ <strong>Education Application <?= htmlspecialchars($ea['application_code']) ?> created!</strong>
</div>
<?php elseif (isset($_GET['updated'])): ?>
<div class="alert alert-success" style="margin-bottom:20px;">✅ Status updated successfully.</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
            <code style="font-size:1rem;font-family:monospace;font-weight:700;color:var(--navy-800);"><?= htmlspecialchars($ea['application_code']) ?></code>
            <span class="badge <?= statusClass($ea['status']) ?>"><?= $statusLabel ?></span>
        </div>
        <h2><?= htmlspecialchars($ea['program_name']) ?></h2>
        <p><?= htmlspecialchars($ea['institution_name']) ?>, <?= htmlspecialchars($ea['country']) ?> · Created <?= fmtD($ea['created_at']) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="visa-education.php" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> All Applications</a>
        <button onclick="document.getElementById('status-modal').classList.add('show')" class="btn btn-primary btn-sm" id="btn-update-status">
            <i data-lucide="refresh-cw"></i> Update Status
        </button>
    </div>
</div>

<!-- Application Tracking Bar -->
<?php if (!in_array($ea['status'], ['rejected','cancelled','withdrawn'])): ?>
<div class="panel" style="margin-bottom:24px;">
    <div class="panel-head"><h2>Application Progress</h2></div>
    <div style="padding:20px 24px;">
        <div class="track-bar">
            <?php foreach ($trackSteps as $i => $ts):
                $stepIdx = array_search($ts['key'], $statusOrder);
                $cls = '';
                if ($currentStep === false || $ts['key']==='accepted' && $ea['status']==='accepted') {
                    $cls = $ea['status']==='accepted' ? 'done' : '';
                } elseif ($stepIdx < $currentStep) $cls = 'done';
                elseif ($stepIdx === $currentStep) $cls = 'active';
            ?>
            <div class="track-step <?= $cls ?>">
                <div class="track-dot">
                    <?php if ($cls==='done'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php elseif ($cls==='active'): ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3" fill="currentColor"/></svg>
                    <?php endif; ?>
                </div>
                <div class="track-label"><?= $ts['label'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php elseif ($ea['status'] === 'rejected'): ?>
<div class="alert alert-error" style="margin-bottom:20px;">
    🚫 <strong>Application Rejected</strong>
</div>
<?php else: ?>
<div class="alert" style="background:#f3f4f6;border:1px solid var(--border);color:var(--ink-soft);margin-bottom:20px;">
    ⊘ <strong>Application <?= ucfirst($ea['status']) ?></strong>
</div>
<?php endif; ?>

<div class="dash-grid-3">

<!-- LEFT: Details -->
<div style="display:flex;flex-direction:column;gap:20px;">
    
    <!-- Application Info -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head"><h2>Application Details</h2></div>
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Institution</div><div class="info-value"><?= htmlspecialchars($ea['institution_name']) ?></div></div>
                <div class="info-row"><div class="info-label">Program</div><div class="info-value"><?= htmlspecialchars($ea['program_name']) ?></div></div>
                <div class="info-row"><div class="info-label">Degree Level</div><div class="info-value"><?= ucfirst($ea['degree_level']) ?></div></div>
                <div class="info-row"><div class="info-label">Intake</div><div class="info-value"><?= htmlspecialchars($ea['intake'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Duration</div><div class="info-value"><?= $ea['duration_months'] ? $ea['duration_months'].' months' : '—' ?></div></div>
                <div class="info-row"><div class="info-label">Tuition Fee</div><div class="info-value"><?= $ea['tuition_fee'] ? number_format($ea['tuition_fee']).' '.$ea['currency'] : '—' ?></div></div>
                <div class="info-row"><div class="info-label">App Date</div><div class="info-value"><?= fmtD($ea['application_date']) ?></div></div>
                <div class="info-row"><div class="info-label">Officer</div><div class="info-value"><?= htmlspecialchars($ea['officer_name'] ?? '—') ?></div></div>
            </div>
            <?php if ($ea['notes']): ?>
            <div style="margin-top:16px;"><div class="info-label">Notes</div><p style="font-size:0.87rem;margin-top:4px;"><?= nl2br(htmlspecialchars($ea['notes'])) ?></p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head"><h2>Applicant</h2><a href="customer-profile.php?id=<?= $ea['cust_id'] ?>&tab=education" class="btn btn-ghost btn-sm">View Profile</a></div>
        <div class="panel-body">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                <div class="cus-avatar-xl" style="background:<?= $avatarColor ?>;width:50px;height:50px;font-size:1rem;"><?= $initials ?></div>
                <div>
                    <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($ea['first_name'].' '.$ea['last_name']) ?></div>
                    <div style="font-size:0.8rem;color:var(--ink-soft);font-family:monospace;"><?= htmlspecialchars($ea['customer_code']) ?></div>
                </div>
            </div>
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($ea['email']) ?></div></div>
                <div class="info-row"><div class="info-label">Phone</div><div class="info-value"><?= htmlspecialchars($ea['phone'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Nationality</div><div class="info-value"><?= htmlspecialchars($ea['nationality'] ?? '—') ?></div></div>
            </div>
        </div>
    </div>

</div>

<!-- RIGHT: Documents -->
<div style="display:flex;flex-direction:column;gap:20px;">
    
    <!-- Uploaded Documents -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head">
            <h2>Applicant Documents</h2>
            <a href="customer-upload-doc.php?customer_id=<?= $ea['cust_id'] ?>" class="btn btn-primary btn-sm"><i data-lucide="upload"></i> Upload</a>
        </div>
        <div class="panel-body" style="padding-top:8px;">
            <?php if (empty($documents)): ?>
            <div class="empty-state">No documents found for this applicant.</div>
            <?php else: ?>
            <?php foreach ($documents as $doc): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;background:#f7f6f3;border-radius:8px;display:flex;align-items:center;justify-content:center;">📄</div>
                    <div>
                        <div style="font-size:0.87rem;font-weight:600;"><?= htmlspecialchars($doc['file_name']) ?></div>
                        <div style="font-size:0.74rem;color:var(--ink-soft);"><?= ucwords(str_replace('_',' ',$doc['document_type'])) ?></div>
                    </div>
                </div>
                <span class="badge <?= ['pending'=>'badge-pending','submitted'=>'badge-submitted','verified'=>'badge-approved','rejected'=>'badge-rejected'][$doc['status']]??'badge-draft' ?>">
                    <?= ucfirst($doc['status']) ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Student Visa Applications -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head">
            <h2>Student Visa Applications</h2>
            <a href="visa-create.php?customer_id=<?= $ea['cust_id'] ?>&purpose=Education+at+<?= urlencode($ea['institution_name']) ?>" class="btn btn-ghost btn-sm">
                <i data-lucide="plus"></i> New Visa App
            </a>
        </div>
        <div class="panel-body" style="padding-top:8px;">
            <?php if (empty($linkedVisas)): ?>
            <div class="empty-state" style="padding:16px;">
                No visa applications linked yet.<br>
                <a href="visa-create.php?customer_id=<?= $ea['cust_id'] ?>&purpose=Education+at+<?= urlencode($ea['institution_name']) ?>" style="color:var(--navy-800);font-weight:600;font-size:0.8rem;margin-top:6px;display:inline-block;">
                    Create student visa application &rarr;
                </a>
            </div>
            <?php else: ?>
            <?php foreach ($linkedVisas as $lv): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                <div>
                    <a href="visa-detail.php?id=<?= $lv['id'] ?>" style="font-weight:700;font-size:0.85rem;color:var(--navy-900);text-decoration:none;">
                        <code><?= htmlspecialchars($lv['application_code']) ?></code>
                    </a>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($lv['destination_country']) ?> · <?= htmlspecialchars($lv['visa_type_name']) ?></div>
                </div>
                <span class="badge <?= ['approved'=>'badge-approved','rejected'=>'badge-rejected','cancelled'=>'badge-rejected'][$lv['status']]??'badge-review' ?>" style="font-size:0.72rem;">
                    <?= ucwords(str_replace('_',' ',$lv['status'])) ?>
                </span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

</div>

<!-- Status Modal -->
<div class="modal-overlay" id="status-modal">
    <div class="modal-content" style="max-width:460px;">
        <div class="modal-header">
            <h3>Update Status</h3>
            <button class="modal-close" onclick="document.getElementById('status-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="education-detail.php?id=<?= $id ?>">
                <input type="hidden" name="action" value="update_status">
                <div class="field-block">
                    <label>New Status <span class="req">*</span></label>
                    <select name="new_status" class="form-input" required>
                        <?php foreach (['new','documents_pending','submitted_to_institution','under_review','interview_scheduled','offer_received','conditional_offer','accepted','rejected','deferred','withdrawn'] as $st): ?>
                        <option value="<?= $st ?>" <?= $ea['status']===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-block">
                    <label>Notes (optional)</label>
                    <textarea name="status_notes" class="form-input" rows="3" placeholder="Additional info..."></textarea>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                    <button type="button" onclick="document.getElementById('status-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
