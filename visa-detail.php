<?php
$pageTitle = 'Visa Application';

require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: visa-all.php'); exit; }

$va = db_row("
    SELECT va.*, vt.name AS visa_type_name, vt.category, vt.processing_time, vt.validity,
           vt.fee AS visa_fee_rate, vt.service_fee AS service_fee_rate, vt.embassy_website,
           c.id AS cust_id, c.first_name, c.last_name, c.customer_code, c.email, c.phone,
           c.nationality, c.passport_number, c.passport_expiry_date,
           u.name AS officer_name
    FROM visa_applications va
    JOIN visa_types vt   ON vt.id = va.visa_type_id
    JOIN customers c     ON c.id  = va.customer_id
    LEFT JOIN users u    ON u.id  = va.assigned_officer_id
    WHERE va.id = ?", [$id]);

if (!$va) { header('Location: visa-all.php'); exit; }

$pageTitle = 'Visa: '.$va['application_code'];

// Related
$documents  = db_select("
    SELECT vad.*, cd.document_type, cd.file_name, cd.file_path, cd.status AS doc_status,
           vr.document_name AS requirement_name
    FROM visa_application_documents vad
    JOIN customer_documents cd ON cd.id = vad.document_id
    LEFT JOIN visa_requirements vr ON vr.id = vad.requirement_id
    WHERE vad.visa_application_id = ?", [$id]);
$requirements = db_select("SELECT * FROM visa_requirements WHERE visa_type_id = ? ORDER BY sort_order", [$va['visa_type_id']]);
$appointments = db_select("SELECT * FROM visa_appointments WHERE visa_application_id = ? ORDER BY appointment_date DESC", [$id]);
$history      = db_select("
    SELECT vash.*, u.name AS changed_by_name
    FROM visa_application_status_history vash
    LEFT JOIN users u ON u.id = vash.changed_by
    WHERE vash.visa_application_id = ?
    ORDER BY vash.changed_at ASC", [$id]);

// ── Handle status update ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $newStatus = $_POST['new_status'] ?? '';
        $notes     = trim($_POST['status_notes'] ?? '');
        $allowed   = ['draft','documents_pending','under_review','ready_for_submission',
                      'submitted','appointment_scheduled','processing','approved','rejected','cancelled'];
        if (in_array($newStatus, $allowed)) {
            $old = $va['status'];
            db_execute("UPDATE visa_applications SET status=?, updated_at=NOW() WHERE id=?", [$newStatus, $id]);
            db_insert("INSERT INTO visa_application_status_history (visa_application_id, old_status, new_status, notes) VALUES (?,?,?,?)",
                [$id, $old, $newStatus, $notes]);
            header("Location: visa-detail.php?id=$id&updated=1");
            exit;
        }
    }
}

include 'includes/header.php';

// Track step order for timeline
$statusOrder = ['draft','documents_pending','under_review','ready_for_submission','submitted','appointment_scheduled','processing','approved'];
$currentStep = array_search($va['status'], $statusOrder);

$trackSteps = [
    ['key'=>'draft',                  'label'=>'Created',        'icon'=>'file'],
    ['key'=>'documents_pending',      'label'=>'Documents',      'icon'=>'upload'],
    ['key'=>'under_review',           'label'=>'Review',         'icon'=>'search'],
    ['key'=>'ready_for_submission',   'label'=>'Ready',          'icon'=>'check-square'],
    ['key'=>'submitted',              'label'=>'Submitted',      'icon'=>'send'],
    ['key'=>'appointment_scheduled',  'label'=>'Appointment',    'icon'=>'calendar'],
    ['key'=>'processing',             'label'=>'Processing',     'icon'=>'loader'],
    ['key'=>'approved',               'label'=>'Approved',       'icon'=>'check-circle'],
];

function statusClass(string $s): string {
    $m=['draft'=>'badge-draft','documents_pending'=>'badge-pending','under_review'=>'badge-review',
        'ready_for_submission'=>'badge-review','submitted'=>'badge-submitted',
        'appointment_scheduled'=>'badge-review','processing'=>'badge-processing',
        'approved'=>'badge-approved','rejected'=>'badge-rejected','cancelled'=>'badge-inactive'];
    return $m[$s]??'badge-draft';
}
$statusLabel = ucwords(str_replace('_',' ',$va['status']));
$avatarColors = ['#101d2c','#2563eb','#7c3aed','#047857','#d97706','#b0413e'];
$avatarColor = $avatarColors[$va['cust_id'] % count($avatarColors)];
$initials = strtoupper(substr($va['first_name'],0,1).substr($va['last_name'],0,1));
function fmtD(?string $d): string { return $d ? date('d M Y', strtotime($d)) : '—'; }
?>

<?php if (isset($_GET['created'])): ?>
<div class="alert alert-success" style="margin-bottom:20px;">
    ✅ <strong>Application <?= htmlspecialchars($va['application_code']) ?> created successfully!</strong>
    Next step: Upload the required documents below.
</div>
<?php elseif (isset($_GET['updated'])): ?>
<div class="alert alert-success" style="margin-bottom:20px;">✅ Status updated successfully.</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
            <code style="font-size:1rem;font-family:monospace;font-weight:700;color:var(--navy-800);"><?= htmlspecialchars($va['application_code']) ?></code>
            <span class="badge <?= statusClass($va['status']) ?>"><?= $statusLabel ?></span>
            <?php if ($va['priority'] !== 'normal'): ?>
            <span class="badge badge-pending"><?= ucfirst($va['priority']) ?></span>
            <?php endif; ?>
        </div>
        <h2><?= htmlspecialchars($va['visa_type_name']) ?> · <?= htmlspecialchars($va['destination_country']) ?></h2>
        <p>Customer: <a href="customer-profile.php?id=<?= $va['cust_id'] ?>&tab=visa" style="color:var(--gold-600);font-weight:600;"><?= htmlspecialchars($va['first_name'].' '.$va['last_name']) ?></a>
        · Created <?= fmtD($va['created_at']) ?>
        <?php if ($va['officer_name']): ?> · Officer: <?= htmlspecialchars($va['officer_name']) ?><?php endif; ?>
        </p>
    </div>
    <div class="page-header-actions">
        <a href="visa-all.php" class="btn btn-ghost btn-sm"><i data-lucide="arrow-left"></i> All Applications</a>
        <?php if ($va['embassy_website']): ?>
        <a href="<?= htmlspecialchars($va['embassy_website']) ?>" target="_blank" class="btn btn-ghost btn-sm"><i data-lucide="external-link"></i> Embassy</a>
        <?php endif; ?>
        <!-- Status Update -->
        <button onclick="document.getElementById('status-modal').classList.add('show')" class="btn btn-primary btn-sm" id="btn-update-status">
            <i data-lucide="refresh-cw"></i> Update Status
        </button>
    </div>
</div>

<!-- Application Tracking Bar -->
<?php if ($va['status'] !== 'rejected' && $va['status'] !== 'cancelled'): ?>
<div class="panel" style="margin-bottom:24px;">
    <div class="panel-head"><h2>Application Progress</h2></div>
    <div style="padding:20px 24px;">
        <div class="track-bar">
            <?php foreach ($trackSteps as $i => $ts):
                $stepIdx = array_search($ts['key'], $statusOrder);
                $cls = '';
                if ($currentStep === false || $ts['key']==='approved' && $va['status']==='approved') {
                    $cls = $va['status']==='approved' ? 'done' : '';
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
<?php elseif ($va['status'] === 'rejected'): ?>
<div class="alert alert-error" style="margin-bottom:20px;">
    🚫 <strong>Application Rejected</strong>
    <?php if ($va['rejection_reason']): ?> · <?= htmlspecialchars($va['rejection_reason']) ?><?php endif; ?>
</div>
<?php elseif ($va['status'] === 'cancelled'): ?>
<div class="alert" style="background:#f3f4f6;border:1px solid var(--border);color:var(--ink-soft);margin-bottom:20px;">
    ⊘ <strong>Application Cancelled</strong>
</div>
<?php endif; ?>

<div class="dash-grid-3">

<!-- LEFT: Details + Documents + Checklist -->
<div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Application Info -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head"><h2>Application Details</h2></div>
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Visa Type</div><div class="info-value"><?= htmlspecialchars($va['visa_type_name']) ?></div></div>
                <div class="info-row"><div class="info-label">Category</div><div class="info-value"><?= ucfirst($va['category']) ?></div></div>
                <div class="info-row"><div class="info-label">Destination</div><div class="info-value"><?= htmlspecialchars($va['destination_country']) ?></div></div>
                <div class="info-row"><div class="info-label">Processing Time</div><div class="info-value"><?= htmlspecialchars($va['processing_time'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Travel Date</div><div class="info-value"><?= fmtD($va['travel_date']) ?></div></div>
                <div class="info-row"><div class="info-label">Return Date</div><div class="info-value"><?= fmtD($va['return_date']) ?></div></div>
                <div class="info-row"><div class="info-label">Visa Fee</div><div class="info-value">$<?= number_format($va['visa_fee'],2) ?></div></div>
                <div class="info-row"><div class="info-label">Service Fee</div><div class="info-value">$<?= number_format($va['service_fee'],2) ?></div></div>
                <div class="info-row"><div class="info-label">Submitted</div><div class="info-value"><?= fmtD($va['submitted_at']) ?></div></div>
                <div class="info-row"><div class="info-label">Decision Date</div><div class="info-value"><?= fmtD($va['decision_date']) ?></div></div>
                <?php if ($va['embassy_ref']): ?>
                <div class="info-row"><div class="info-label">Embassy Ref</div><div class="info-value mono"><?= htmlspecialchars($va['embassy_ref']) ?></div></div>
                <?php endif; ?>
                <?php if ($va['status']==='approved' && $va['expiry_date']): ?>
                <div class="info-row"><div class="info-label">Visa Valid Until</div><div class="info-value" style="color:var(--success);font-weight:700;"><?= fmtD($va['expiry_date']) ?></div></div>
                <?php endif; ?>
            </div>
            <?php if ($va['purpose']): ?>
            <div style="margin-top:16px;"><div class="info-label">Purpose</div><p style="font-size:0.87rem;margin-top:4px;"><?= nl2br(htmlspecialchars($va['purpose'])) ?></p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head"><h2>Customer</h2><a href="customer-profile.php?id=<?= $va['cust_id'] ?>" class="btn btn-ghost btn-sm">View Profile</a></div>
        <div class="panel-body">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px;">
                <div class="cus-avatar-xl" style="background:<?= $avatarColor ?>;width:50px;height:50px;font-size:1rem;"><?= $initials ?></div>
                <div>
                    <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($va['first_name'].' '.$va['last_name']) ?></div>
                    <div style="font-size:0.8rem;color:var(--ink-soft);font-family:monospace;"><?= htmlspecialchars($va['customer_code']) ?></div>
                </div>
            </div>
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Email</div><div class="info-value"><?= htmlspecialchars($va['email']) ?></div></div>
                <div class="info-row"><div class="info-label">Phone</div><div class="info-value"><?= htmlspecialchars($va['phone'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Nationality</div><div class="info-value"><?= htmlspecialchars($va['nationality'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Passport #</div><div class="info-value mono"><?= htmlspecialchars($va['passport_number'] ?? '—') ?></div></div>
                <div class="info-row"><div class="info-label">Passport Expiry</div><div class="info-value"><?= fmtD($va['passport_expiry_date']) ?></div></div>
            </div>
        </div>
    </div>

    <!-- Visa Appointments -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head">
            <h2>Embassy Appointments</h2>
            <a href="visa-appointments.php?visa_id=<?= $id ?>" class="btn btn-ghost btn-sm"><i data-lucide="calendar-plus"></i> Schedule</a>
        </div>
        <div class="panel-body">
            <?php if (empty($appointments)): ?>
            <div class="empty-state" style="padding:16px;">No appointments scheduled.</div>
            <?php else: ?>
            <?php foreach ($appointments as $appt): ?>
            <div style="padding:12px;background:#f7f6f3;border-radius:8px;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                    <div>
                        <div style="font-weight:600;font-size:0.9rem;"><?= fmtD($appt['appointment_date']) ?> at <?= date('g:i A', strtotime($appt['appointment_time'])) ?></div>
                        <?php if ($appt['embassy_name']): ?><div style="font-size:0.8rem;color:var(--ink-soft);"><?= htmlspecialchars($appt['embassy_name']) ?></div><?php endif; ?>
                        <?php if ($appt['appointment_ref']): ?><div style="font-size:0.75rem;font-family:monospace;color:var(--ink-soft);">Ref: <?= htmlspecialchars($appt['appointment_ref']) ?></div><?php endif; ?>
                    </div>
                    <span class="badge <?= ['scheduled'=>'badge-review','completed'=>'badge-approved','cancelled'=>'badge-rejected','no_show'=>'badge-blacklisted','rescheduled'=>'badge-pending'][$appt['status']]??'badge-draft' ?>"><?= ucfirst($appt['status']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div><!-- end left col -->

<!-- RIGHT: Checklist + Documents + History -->
<div style="display:flex;flex-direction:column;gap:20px;">

    <!-- Document Checklist -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head">
            <h2>Document Checklist</h2>
            <a href="customer-upload-doc.php?customer_id=<?= $va['cust_id'] ?>&visa_id=<?= $id ?>" class="btn btn-primary btn-sm"><i data-lucide="upload"></i> Upload</a>
        </div>
        <div class="panel-body" style="padding-top:8px;">
            <?php if (empty($requirements)): ?>
            <div class="empty-state">No requirements defined for this visa type.</div>
            <?php else: ?>
            <ul class="doc-checklist">
                <?php foreach ($requirements as $req):
                    // Check if this requirement is satisfied
                    $uploaded = array_filter($documents, fn($d) => $d['requirement_id'] == $req['id']);
                    $satisfied = !empty($uploaded) && in_array(array_values($uploaded)[0]['status'], ['submitted','verified']);
                    $cls = $satisfied ? 'done' : ($req['is_mandatory'] ? 'missing' : 'pending');
                ?>
                <li>
                    <div class="check-icon <?= $cls ?>">
                        <?php if ($cls==='done'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                        <?php elseif ($cls==='missing'): ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <?php endif; ?>
                    </div>
                    <div class="check-label"><?= htmlspecialchars($req['document_name']) ?></div>
                    <?php if ($req['is_mandatory'] && !$satisfied): ?>
                    <span class="check-mandatory">REQUIRED</span>
                    <?php elseif (!$req['is_mandatory']): ?>
                    <span style="font-size:0.72rem;color:var(--ink-soft);">Optional</span>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php
            $totalReq  = count($requirements);
            $doneReq   = 0;
            foreach ($requirements as $req) {
                $uploaded = array_filter($documents, fn($d) => $d['requirement_id'] == $req['id']);
                if (!empty($uploaded) && in_array(array_values($uploaded)[0]['status'], ['submitted','verified'])) $doneReq++;
            }
            $pct = $totalReq ? round($doneReq/$totalReq*100) : 0;
            ?>
            <div style="margin-top:14px;">
                <div style="display:flex;justify-content:space-between;font-size:0.78rem;color:var(--ink-soft);margin-bottom:4px;">
                    <span>Progress</span><span><?= $doneReq ?>/<?= $totalReq ?> documents (<?= $pct ?>%)</span>
                </div>
                <div class="chart-bar-track"><div class="chart-bar-fill" style="width:<?= $pct ?>%;background:<?= $pct===100?'var(--success)':'var(--gold-500)' ?>;"></div></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Uploaded Documents -->
    <?php if (!empty($documents)): ?>
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head"><h2>Uploaded Documents</h2></div>
        <div class="panel-body" style="padding-top:8px;">
            <?php foreach ($documents as $doc): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:36px;height:36px;background:#f7f6f3;border-radius:8px;display:flex;align-items:center;justify-content:center;">📄</div>
                    <div>
                        <div style="font-size:0.87rem;font-weight:600;"><?= htmlspecialchars($doc['file_name']) ?></div>
                        <div style="font-size:0.74rem;color:var(--ink-soft);"><?= htmlspecialchars($doc['requirement_name'] ?? str_replace('_',' ',$doc['document_type'])) ?></div>
                    </div>
                </div>
                <span class="badge <?= ['pending'=>'badge-pending','submitted'=>'badge-submitted','verified'=>'badge-approved','rejected'=>'badge-rejected'][$doc['status']]??'badge-draft' ?>">
                    <?= ucfirst($doc['status']) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status History -->
    <div class="panel" style="margin-bottom:0;">
        <div class="panel-head"><h2>Status History</h2></div>
        <div class="panel-body">
            <?php if (empty($history)): ?>
            <div class="empty-state">No history yet.</div>
            <?php else: ?>
            <div class="visa-timeline">
                <?php foreach (array_reverse($history) as $h): ?>
                <div class="visa-step">
                    <div class="visa-step-dot done">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div class="visa-step-title"><?= ucwords(str_replace('_',' ',$h['new_status'])) ?></div>
                    <?php if ($h['old_status']): ?><div class="visa-step-desc">From: <?= ucwords(str_replace('_',' ',$h['old_status'])) ?></div><?php endif; ?>
                    <?php if ($h['notes']): ?><div class="visa-step-desc" style="color:var(--ink);"><?= htmlspecialchars($h['notes']) ?></div><?php endif; ?>
                    <div class="visa-step-date"><?= date('d M Y, g:i A', strtotime($h['changed_at'])) ?><?= $h['changed_by_name'] ? ' · '.$h['changed_by_name'] : '' ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- end right col -->
</div><!-- end dash-grid-3 -->

<!-- ── Status Update Modal ─────────────────────────────────── -->
<div class="modal-overlay" id="status-modal">
    <div class="modal-content" style="max-width:460px;">
        <div class="modal-header">
            <h3>Update Application Status</h3>
            <button class="modal-close" onclick="document.getElementById('status-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="visa-detail.php?id=<?= $id ?>">
                <input type="hidden" name="action" value="update_status">
                <div class="field-block">
                    <label>New Status <span class="req">*</span></label>
                    <select name="new_status" class="form-input" required>
                        <?php foreach (['draft','documents_pending','under_review','ready_for_submission','submitted','appointment_scheduled','processing','approved','rejected','cancelled','withdrawn'] as $st): ?>
                        <option value="<?= $st ?>" <?= $va['status']===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-block">
                    <label>Notes (optional)</label>
                    <textarea name="status_notes" class="form-input" rows="3" placeholder="Reason for status change, embassy ref, etc."></textarea>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:8px;">
                    <button type="button" onclick="document.getElementById('status-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
