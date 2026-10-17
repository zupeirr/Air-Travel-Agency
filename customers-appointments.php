<?php
ob_start();
$pageTitle = 'Customer Appointments';
require_once __DIR__ . '/config/db.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'create_appointment') {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $staffId    = (int)($_POST['staff_id'] ?? 0);
        $type       = trim($_POST['appointment_type'] ?? 'other');
        $date       = trim($_POST['appointment_date'] ?? '');
        $time       = trim($_POST['appointment_time'] ?? '');
        $duration   = (int)($_POST['duration_mins'] ?? 30);
        $status     = trim($_POST['status'] ?? 'scheduled');
        $notes      = trim($_POST['notes'] ?? '');

        $validTypes = ['visa_consult','education_consult','travel_planning','document_review','follow_up','other'];
        $validStatuses = ['scheduled','confirmed','completed','cancelled','no_show','rescheduled'];

        if (!in_array($type, $validTypes)) $type = 'other';
        if (!in_array($status, $validStatuses)) $status = 'scheduled';
        if ($duration <= 0) $duration = 30;

        if ($customerId > 0 && !empty($date) && !empty($time)) {
            $ref = generate_code('appointments', 'appointment_ref', 'APT');
            $newId = db_insert("INSERT INTO appointments 
                (appointment_ref, customer_id, staff_id, appointment_type, appointment_date, appointment_time, duration_mins, status, notes)
                VALUES (?,?,?,?,?,?,?,?,?)",
                [$ref, $customerId, $staffId ?: null, $type, $date, $time, $duration, $status, $notes]);

            $c = db_row("SELECT first_name, last_name FROM customers WHERE id = ?", [$customerId]);
            $cName = $c ? ($c['first_name'] . ' ' . $c['last_name']) : "Customer #$customerId";
            
            db_insert("INSERT INTO activity_logs (entity_type, entity_id, action, description, user_id) VALUES (?,?,?,?,?)",
                ['appointments', (int)$newId, 'appointment_created', "Scheduled " . str_replace('_', ' ', $type) . " for $cName on $date at $time", $_SESSION['user_id'] ?? 1]);

            header("Location: customers-appointments.php?success=created");
            exit;
        } else {
            header("Location: customers-appointments.php?error=missing_fields");
            exit;
        }
    }

    if ($action === 'update_status') {
        $id     = (int)($_POST['appointment_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $validStatuses = ['scheduled','confirmed','completed','cancelled','no_show','rescheduled'];

        if ($id > 0 && in_array($status, $validStatuses)) {
            db_execute("UPDATE appointments SET status = ? WHERE id = ?", [$status, $id]);
            db_insert("INSERT INTO activity_logs (entity_type, entity_id, action, description, user_id) VALUES (?,?,?,?,?)",
                ['appointments', $id, 'status_updated', "Appointment status updated to " . str_replace('_', ' ', $status), $_SESSION['user_id'] ?? 1]);

            header("Location: customers-appointments.php?success=updated");
            exit;
        }
    }

    if ($action === 'edit_appointment') {
        $id         = (int)($_POST['appointment_id'] ?? 0);
        $staffId    = (int)($_POST['staff_id'] ?? 0);
        $type       = trim($_POST['appointment_type'] ?? 'other');
        $date       = trim($_POST['appointment_date'] ?? '');
        $time       = trim($_POST['appointment_time'] ?? '');
        $duration   = (int)($_POST['duration_mins'] ?? 30);
        $status     = trim($_POST['status'] ?? 'scheduled');
        $notes      = trim($_POST['notes'] ?? '');

        $validTypes = ['visa_consult','education_consult','travel_planning','document_review','follow_up','other'];
        $validStatuses = ['scheduled','confirmed','completed','cancelled','no_show','rescheduled'];

        if (!in_array($type, $validTypes)) $type = 'other';
        if (!in_array($status, $validStatuses)) $status = 'scheduled';
        if ($duration <= 0) $duration = 30;

        if ($id > 0 && !empty($date) && !empty($time)) {
            db_execute("UPDATE appointments SET 
                staff_id = ?,
                appointment_type = ?,
                appointment_date = ?,
                appointment_time = ?,
                duration_mins = ?,
                status = ?,
                notes = ?
                WHERE id = ?",
                [$staffId ?: null, $type, $date, $time, $duration, $status, $notes, $id]);

            header("Location: customers-appointments.php?success=updated");
            exit;
        }
    }

    if ($action === 'delete_appointment') {
        $id = (int)($_POST['appointment_id'] ?? 0);
        if ($id > 0) {
            db_execute("DELETE FROM appointments WHERE id = ?", [$id]);
            header("Location: customers-appointments.php?success=deleted");
            exit;
        }
    }
}

// Filters & Search
$q          = trim($_GET['q'] ?? '');
$status     = trim($_GET['status'] ?? '');
$type       = trim($_GET['type'] ?? '');
$staffId    = (int)($_GET['staff_id'] ?? 0);
$dateFilter = trim($_GET['date_filter'] ?? '');
$preCustomerId = (int)($_GET['customer_id'] ?? 0);

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR c.customer_code LIKE ? OR a.appointment_ref LIKE ? OR a.notes LIKE ?)";
    $term = "%$q%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

if ($status !== '') {
    $where[] = "a.status = ?";
    $params[] = $status;
}

if ($type !== '') {
    $where[] = "a.appointment_type = ?";
    $params[] = $type;
}

if ($staffId > 0) {
    $where[] = "a.staff_id = ?";
    $params[] = $staffId;
}

if ($preCustomerId > 0) {
    $where[] = "a.customer_id = ?";
    $params[] = $preCustomerId;
}

if ($dateFilter === 'today') {
    $where[] = "a.appointment_date = CURDATE()";
} elseif ($dateFilter === 'upcoming') {
    $where[] = "a.appointment_date >= CURDATE()";
} elseif ($dateFilter === 'past') {
    $where[] = "a.appointment_date < CURDATE()";
}

$whereSQL = implode(' AND ', $where);

$appts = db_select("
    SELECT a.*, c.first_name, c.last_name, c.customer_code, c.email, c.phone, u.name AS staff_name
    FROM appointments a
    JOIN customers c ON c.id = a.customer_id
    LEFT JOIN users u ON u.id = a.staff_id
    WHERE $whereSQL
    ORDER BY a.appointment_date DESC, a.appointment_time DESC", $params);

// Overall KPIs
$kpiTotal     = db_count("SELECT COUNT(*) FROM appointments");
$kpiUpcoming  = db_count("SELECT COUNT(*) FROM appointments WHERE appointment_date >= CURDATE() AND status IN ('scheduled','confirmed')");
$kpiToday     = db_count("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
$kpiCompleted = db_count("SELECT COUNT(*) FROM appointments WHERE status = 'completed'");

// Data for dropdowns
$customers = db_select("SELECT id, customer_code, first_name, last_name, email FROM customers WHERE status != 'blacklisted' ORDER BY first_name, last_name");
$staffMembers = db_select("SELECT id, name FROM users WHERE status = 'active' ORDER BY name");

function aptBadge($s) {
    $map = [
        'scheduled'   => 'badge-review',
        'confirmed'   => 'badge-active',
        'completed'   => 'badge-approved',
        'cancelled'   => 'badge-cancelled',
        'no_show'     => 'badge-blacklisted',
        'rescheduled' => 'badge-pending'
    ];
    return '<span class="badge ' . ($map[$s] ?? 'badge-draft') . '">' . ucwords(str_replace('_', ' ', $s)) . '</span>';
}

function aptTypeBadge($t) {
    $colors = [
        'visa_consult'      => 'background:rgba(37,99,235,0.1);color:#1d4ed8;border:1px solid rgba(37,99,235,0.2);',
        'education_consult' => 'background:rgba(124,58,237,0.1);color:#6d28d9;border:1px solid rgba(124,58,237,0.2);',
        'travel_planning'   => 'background:rgba(16,185,129,0.1);color:#047857;border:1px solid rgba(16,185,129,0.2);',
        'document_review'   => 'background:rgba(217,119,6,0.1);color:#b45309;border:1px solid rgba(217,119,6,0.2);',
        'follow_up'         => 'background:rgba(14,116,144,0.1);color:#0e7490;border:1px solid rgba(14,116,144,0.2);',
        'other'             => 'background:rgba(100,116,139,0.1);color:#475569;border:1px solid rgba(100,116,139,0.2);'
    ];
    $style = $colors[$t] ?? 'background:#f1f5f9;color:#475569;';
    return '<span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:0.75rem;font-weight:600;' . $style . '">' . ucwords(str_replace('_', ' ', $t)) . '</span>';
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Customer Appointments</h2>
        <p>Schedule and manage client consultations, travel briefings, and visa advisories</p>
    </div>
    <div class="page-header-actions">
        <button type="button" class="btn btn-primary" id="btn-new-appointment" onclick="openNewModal()">
            <i data-lucide="plus"></i> New Appointment
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;">
        <?php if ($_GET['success'] === 'created'): ?>
            ✅ <strong>Appointment scheduled successfully!</strong>
        <?php elseif ($_GET['success'] === 'updated'): ?>
            ✅ <strong>Appointment updated successfully!</strong>
        <?php elseif ($_GET['success'] === 'deleted'): ?>
            🗑️ <strong>Appointment deleted successfully.</strong>
        <?php endif; ?>
    </div>
<?php elseif (isset($_GET['error'])): ?>
    <div class="alert alert-error" style="margin-bottom:20px;">
        ⚠️ <strong>Please complete all required fields (Customer, Date, and Time).</strong>
    </div>
<?php endif; ?>

<!-- KPI Stats Cards -->
<div class="dash-stats">
    <div class="dash-card">
        <div class="ic-wrap blue"><i data-lucide="calendar"></i></div>
        <div class="kpi-body">
            <h3><?= $kpiTotal ?></h3>
            <span class="kpi-label">Total Appointments</span>
        </div>
    </div>
    <div class="dash-card">
        <div class="ic-wrap gold"><i data-lucide="clock"></i></div>
        <div class="kpi-body">
            <h3><?= $kpiUpcoming ?></h3>
            <span class="kpi-label">Upcoming & Scheduled</span>
        </div>
    </div>
    <div class="dash-card">
        <div class="ic-wrap purple"><i data-lucide="sun"></i></div>
        <div class="kpi-body">
            <h3><?= $kpiToday ?></h3>
            <span class="kpi-label">Today's Schedule</span>
        </div>
    </div>
    <div class="dash-card">
        <div class="ic-wrap green"><i data-lucide="check-circle-2"></i></div>
        <div class="kpi-body">
            <h3><?= $kpiCompleted ?></h3>
            <span class="kpi-label">Completed Sessions</span>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="panel" style="margin-bottom:20px;padding:16px 20px;">
    <form method="GET" action="customers-appointments.php" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
        <?php if ($preCustomerId > 0): ?>
            <input type="hidden" name="customer_id" value="<?= $preCustomerId ?>">
        <?php endif; ?>

        <div style="flex:1;min-width:200px;position:relative;">
            <input type="text" name="q" class="form-input" placeholder="Search customer, ref, or notes..." value="<?= htmlspecialchars($q) ?>">
        </div>

        <select name="status" class="form-input" style="width:auto;min-width:140px;">
            <option value="">All Statuses</option>
            <option value="scheduled" <?= $status==='scheduled'?'selected':'' ?>>Scheduled</option>
            <option value="confirmed" <?= $status==='confirmed'?'selected':'' ?>>Confirmed</option>
            <option value="completed" <?= $status==='completed'?'selected':'' ?>>Completed</option>
            <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>Cancelled</option>
            <option value="rescheduled" <?= $status==='rescheduled'?'selected':'' ?>>Rescheduled</option>
            <option value="no_show" <?= $status==='no_show'?'selected':'' ?>>No Show</option>
        </select>

        <select name="type" class="form-input" style="width:auto;min-width:150px;">
            <option value="">All Types</option>
            <option value="visa_consult" <?= $type==='visa_consult'?'selected':'' ?>>Visa Consultation</option>
            <option value="education_consult" <?= $type==='education_consult'?'selected':'' ?>>Education Consultation</option>
            <option value="travel_planning" <?= $type==='travel_planning'?'selected':'' ?>>Travel Planning</option>
            <option value="document_review" <?= $type==='document_review'?'selected':'' ?>>Document Review</option>
            <option value="follow_up" <?= $type==='follow_up'?'selected':'' ?>>Follow Up</option>
            <option value="other" <?= $type==='other'?'selected':'' ?>>Other</option>
        </select>

        <select name="date_filter" class="form-input" style="width:auto;min-width:130px;">
            <option value="">Any Date</option>
            <option value="today" <?= $dateFilter==='today'?'selected':'' ?>>Today</option>
            <option value="upcoming" <?= $dateFilter==='upcoming'?'selected':'' ?>>Upcoming</option>
            <option value="past" <?= $dateFilter==='past'?'selected':'' ?>>Past</option>
        </select>

        <select name="staff_id" class="form-input" style="width:auto;min-width:140px;">
            <option value="">All Staff</option>
            <?php foreach ($staffMembers as $sm): ?>
            <option value="<?= $sm['id'] ?>" <?= $staffId===$sm['id']?'selected':'' ?>><?= htmlspecialchars($sm['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary btn-sm"><i data-lucide="filter"></i> Filter</button>
        <?php if ($q || $status || $type || $staffId || $dateFilter || $preCustomerId): ?>
            <a href="customers-appointments.php" class="btn btn-ghost btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Appointments Table -->
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($appts)): ?>
            <div class="empty-state" style="padding:48px;">
                <div style="font-size:2.5rem;margin-bottom:12px;">🗓️</div>
                <h3>No appointments found</h3>
                <p style="color:var(--ink-soft);margin-bottom:16px;">Try adjusting your filters or schedule a new customer appointment.</p>
                <button type="button" class="btn btn-primary btn-sm" onclick="openNewModal()"><i data-lucide="plus"></i> Schedule Appointment</button>
            </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Ref</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Date & Time</th>
                    <th>Duration</th>
                    <th>Assigned Staff</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($appts as $a): 
                $dateTimeStr = $a['appointment_date'] . ' ' . $a['appointment_time'];
                $isToday = ($a['appointment_date'] === date('Y-m-d'));
                $isPast  = ($a['appointment_date'] < date('Y-m-d'));
            ?>
            <tr>
                <td>
                    <code style="font-size:0.8rem;font-weight:700;color:var(--navy-800);"><?= htmlspecialchars($a['appointment_ref']) ?></code>
                </td>
                <td>
                    <div style="font-weight:600;">
                        <a href="customer-profile.php?id=<?= $a['customer_id'] ?>" style="color:inherit;text-decoration:none;" title="View Profile">
                            <?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?>
                        </a>
                    </div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);">
                        <code><?= htmlspecialchars($a['customer_code']) ?></code>
                        <?php if ($a['phone']): ?> · <?= htmlspecialchars($a['phone']) ?><?php endif; ?>
                    </div>
                </td>
                <td>
                    <?= aptTypeBadge($a['appointment_type']) ?>
                </td>
                <td>
                    <div style="font-size:0.88rem;font-weight:600;">
                        <?= date('d M Y', strtotime($a['appointment_date'])) ?>
                        <?php if ($isToday): ?>
                            <span style="font-size:0.7rem;background:#ecfdf5;color:#047857;padding:1px 6px;border-radius:4px;font-weight:700;">TODAY</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:0.8rem;color:var(--ink-soft);">
                        <?= date('g:i A', strtotime($a['appointment_time'])) ?>
                    </div>
                </td>
                <td>
                    <span style="font-size:0.82rem;color:var(--ink-mid);"><?= $a['duration_mins'] ?> mins</span>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:6px;font-size:0.85rem;">
                        <i data-lucide="user" style="width:14px;height:14px;color:var(--ink-soft);"></i>
                        <?= htmlspecialchars($a['staff_name'] ?? 'Unassigned') ?>
                    </div>
                </td>
                <td>
                    <form method="POST" action="customers-appointments.php" style="display:inline-block;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                        <select name="status" class="form-input" style="padding:3px 8px;font-size:0.78rem;height:auto;border-radius:6px;" onchange="this.form.submit()">
                            <option value="scheduled" <?= $a['status']==='scheduled'?'selected':'' ?>>Scheduled</option>
                            <option value="confirmed" <?= $a['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                            <option value="completed" <?= $a['status']==='completed'?'selected':'' ?>>Completed</option>
                            <option value="cancelled" <?= $a['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                            <option value="rescheduled" <?= $a['status']==='rescheduled'?'selected':'' ?>>Rescheduled</option>
                            <option value="no_show" <?= $a['status']==='no_show'?'selected':'' ?>>No Show</option>
                        </select>
                    </form>
                </td>
                <td style="font-size:0.8rem;color:var(--ink-soft);max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($a['notes'] ?? '') ?>">
                    <?= htmlspecialchars($a['notes'] ?: '—') ?>
                </td>
                <td style="text-align:right;">
                    <div style="display:inline-flex;gap:4px;">
                        <button type="button" class="btn btn-ghost btn-sm" title="Edit Appointment"
                            data-apt='<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>'
                            onclick="openEditModalFromEl(this)">
                            <i data-lucide="edit-2"></i>
                        </button>
                        <form method="POST" action="customers-appointments.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this appointment?');">
                            <input type="hidden" name="action" value="delete_appointment">
                            <input type="hidden" name="appointment_id" value="<?= $a['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);" title="Delete Appointment">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: New Appointment -->
<div class="modal-overlay" id="new-appointment-modal">
    <div class="modal-content" style="max-width:540px;">
        <div class="modal-header">
            <h3>Schedule New Appointment</h3>
            <button type="button" class="modal-close" onclick="closeNewModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="customers-appointments.php">
                <input type="hidden" name="action" value="create_appointment">

                <div class="field-block" style="margin-bottom:14px;">
                    <label>Select Customer <span class="req">*</span></label>
                    <select name="customer_id" class="form-input" required id="modal-customer-select">
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($preCustomerId == $c['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?> (<?= htmlspecialchars($c['customer_code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid-2" style="margin-bottom:14px;">
                    <div class="field-block">
                        <label>Appointment Type <span class="req">*</span></label>
                        <select name="appointment_type" class="form-input" required>
                            <option value="visa_consult">Visa Consultation</option>
                            <option value="education_consult">Education Consultation</option>
                            <option value="travel_planning">Travel Planning</option>
                            <option value="document_review">Document Review</option>
                            <option value="follow_up">Follow Up</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="field-block">
                        <label>Assigned Staff</label>
                        <select name="staff_id" class="form-input">
                            <option value="">Select Staff...</option>
                            <?php foreach ($staffMembers as $sm): ?>
                            <option value="<?= $sm['id'] ?>"><?= htmlspecialchars($sm['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:14px;">
                    <div class="field-block">
                        <label>Date <span class="req">*</span></label>
                        <input type="date" name="appointment_date" class="form-input" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="field-block">
                        <label>Time <span class="req">*</span></label>
                        <input type="time" name="appointment_time" class="form-input" required value="10:00">
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:14px;">
                    <div class="field-block">
                        <label>Duration</label>
                        <select name="duration_mins" class="form-input">
                            <option value="15">15 Minutes</option>
                            <option value="30" selected>30 Minutes</option>
                            <option value="45">45 Minutes</option>
                            <option value="60">60 Minutes</option>
                        </select>
                    </div>
                    <div class="field-block">
                        <label>Initial Status</label>
                        <select name="status" class="form-input">
                            <option value="scheduled" selected>Scheduled</option>
                            <option value="confirmed">Confirmed</option>
                        </select>
                    </div>
                </div>

                <div class="field-block" style="margin-bottom:20px;">
                    <label>Consultation Notes / Objectives</label>
                    <textarea name="notes" class="form-input" rows="3" placeholder="Client objectives, specific destination or questions to prepare for..."></textarea>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" onclick="closeNewModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> Confirm Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Appointment -->
<div class="modal-overlay" id="edit-appointment-modal">
    <div class="modal-content" style="max-width:540px;">
        <div class="modal-header">
            <h3>Edit Appointment <span id="edit-ref" style="font-family:monospace;color:var(--navy-800);font-size:0.95rem;"></span></h3>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="customers-appointments.php">
                <input type="hidden" name="action" value="edit_appointment">
                <input type="hidden" name="appointment_id" id="edit-id" value="">

                <div class="field-block" style="margin-bottom:14px;">
                    <label>Customer</label>
                    <input type="text" id="edit-customer-name" class="form-input" readonly style="background:#f8fafc;">
                </div>

                <div class="form-grid-2" style="margin-bottom:14px;">
                    <div class="field-block">
                        <label>Appointment Type <span class="req">*</span></label>
                        <select name="appointment_type" id="edit-type" class="form-input" required>
                            <option value="visa_consult">Visa Consultation</option>
                            <option value="education_consult">Education Consultation</option>
                            <option value="travel_planning">Travel Planning</option>
                            <option value="document_review">Document Review</option>
                            <option value="follow_up">Follow Up</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="field-block">
                        <label>Assigned Staff</label>
                        <select name="staff_id" id="edit-staff" class="form-input">
                            <option value="">Select Staff...</option>
                            <?php foreach ($staffMembers as $sm): ?>
                            <option value="<?= $sm['id'] ?>"><?= htmlspecialchars($sm['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:14px;">
                    <div class="field-block">
                        <label>Date <span class="req">*</span></label>
                        <input type="date" name="appointment_date" id="edit-date" class="form-input" required>
                    </div>
                    <div class="field-block">
                        <label>Time <span class="req">*</span></label>
                        <input type="time" name="appointment_time" id="edit-time" class="form-input" required>
                    </div>
                </div>

                <div class="form-grid-2" style="margin-bottom:14px;">
                    <div class="field-block">
                        <label>Duration</label>
                        <select name="duration_mins" id="edit-duration" class="form-input">
                            <option value="15">15 Minutes</option>
                            <option value="30">30 Minutes</option>
                            <option value="45">45 Minutes</option>
                            <option value="60">60 Minutes</option>
                        </select>
                    </div>
                    <div class="field-block">
                        <label>Status</label>
                        <select name="status" id="edit-status" class="form-input">
                            <option value="scheduled">Scheduled</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="rescheduled">Rescheduled</option>
                            <option value="no_show">No Show</option>
                        </select>
                    </div>
                </div>

                <div class="field-block" style="margin-bottom:20px;">
                    <label>Consultation Notes</label>
                    <textarea name="notes" id="edit-notes" class="form-input" rows="3"></textarea>
                </div>

                <div style="display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Appointment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.openNewModal = function() {
    var m = document.getElementById('new-appointment-modal');
    if (m) {
        m.classList.add('show');
    }
};

window.closeNewModal = function() {
    var m = document.getElementById('new-appointment-modal');
    if (m) {
        m.classList.remove('show');
    }
};

window.openEditModal = function(apt) {
    if (!apt) return;
    var setVal = function(id, val) {
        var el = document.getElementById(id);
        if (el) el.value = val || '';
    };
    setVal('edit-id', apt.id);
    var refEl = document.getElementById('edit-ref');
    if (refEl) refEl.textContent = apt.appointment_ref || '';
    setVal('edit-customer-name', (apt.first_name || '') + ' ' + (apt.last_name || '') + ' (' + (apt.customer_code || '') + ')');
    setVal('edit-type', apt.appointment_type || 'other');
    setVal('edit-staff', apt.staff_id || '');
    setVal('edit-date', apt.appointment_date || '');
    setVal('edit-time', apt.appointment_time || '');
    setVal('edit-duration', apt.duration_mins || '30');
    setVal('edit-status', apt.status || 'scheduled');
    setVal('edit-notes', apt.notes || '');

    var m = document.getElementById('edit-appointment-modal');
    if (m) m.classList.add('show');
};

window.openEditModalFromEl = function(btn) {
    try {
        var raw = btn.getAttribute('data-apt');
        if (raw) {
            var data = JSON.parse(raw);
            window.openEditModal(data);
        }
    } catch(e) {
        console.error('Error parsing appointment data:', e);
    }
};

window.closeEditModal = function() {
    var m = document.getElementById('edit-appointment-modal');
    if (m) {
        m.classList.remove('show');
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Direct click binding for New Appointment button
    var newBtn = document.getElementById('btn-new-appointment');
    if (newBtn) {
        newBtn.addEventListener('click', function(e) {
            e.preventDefault();
            window.openNewModal();
        });
    }

    // Close modal when clicking backdrop outside content
    document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('show');
            }
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.closeNewModal();
            window.closeEditModal();
        }
    });

    <?php if ($preCustomerId > 0 && !isset($_GET['success'])): ?>
    window.openNewModal();
    <?php endif; ?>
});
</script>

<?php include 'includes/footer.php'; ?>
