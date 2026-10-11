<?php
$pageTitle = 'New Visa Application';
require_once __DIR__ . '/config/db.php';

$step = (int)($_GET['step'] ?? 1);
$step = max(1, min(4, $step));

// Pre-filled customer
$preCustomerId = (int)($_GET['customer_id'] ?? 0);
$customer = $preCustomerId ? db_row("SELECT id, customer_code, first_name, last_name, email, nationality, passport_number, passport_expiry_date FROM customers WHERE id=?", [$preCustomerId]) : null;

// Load data for steps
$visaTypes   = db_select("SELECT * FROM visa_types WHERE is_active=1 ORDER BY country, name");
$destinations= db_select("SELECT * FROM destinations WHERE is_active=1 ORDER BY country, name");
$officers    = db_select("SELECT id, name FROM users WHERE role_id IN (1,2,3) AND status='active' ORDER BY name");
$customers   = db_select("SELECT id, customer_code, first_name, last_name, nationality FROM customers WHERE status='active' ORDER BY first_name");

// Group visa types by country
$vtByCountry = [];
foreach ($visaTypes as $vt) {
    $vtByCountry[$vt['country']][] = $vt;
}

$steps = ['Customer','Visa Type & Destination','Details & Priority','Review & Submit'];

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formStep = (int)($_POST['form_step'] ?? 1);

    if ($formStep === 4) {
        // Final submit
        $customerId   = (int)($_POST['customer_id'] ?? 0);
        $visaTypeId   = (int)($_POST['visa_type_id'] ?? 0);
        $destCountry  = trim($_POST['destination_country'] ?? '');
        $purpose      = trim($_POST['purpose'] ?? '');
        $travelDate   = trim($_POST['travel_date'] ?? '');
        $returnDate   = trim($_POST['return_date'] ?? '');
        $priority     = trim($_POST['priority'] ?? 'normal');
        $officerId    = (int)($_POST['assigned_officer_id'] ?? 0);
        $notes        = trim($_POST['notes'] ?? '');

        if (!$customerId) $errors[] = 'Please select a customer.';
        if (!$visaTypeId) $errors[] = 'Please select a visa type.';
        if (!$destCountry) $errors[] = 'Destination country is required.';

        if (empty($errors)) {
            $vt = db_row("SELECT fee, service_fee FROM visa_types WHERE id=?", [$visaTypeId]);
            $code = generate_code('visa_applications','application_code','VA');
            $appId = db_insert("INSERT INTO visa_applications
                (application_code, customer_id, visa_type_id, destination_country, purpose,
                 travel_date, return_date, status, priority, visa_fee, service_fee,
                 assigned_officer_id, notes)
                VALUES (?,?,?,?,?,?,?,'documents_pending',?,?,?,?,?)",
                [
                    $code, $customerId, $visaTypeId, $destCountry, $purpose,
                    $travelDate?:null, $returnDate?:null, $priority,
                    $vt['fee']??0, $vt['service_fee']??0,
                    $officerId?:null, $notes
                ]);
            // Log status history
            db_insert("INSERT INTO visa_application_status_history (visa_application_id, new_status, notes) VALUES (?,?,?)",
                [$appId, 'documents_pending', 'Application created']);
            header("Location: visa-detail.php?id=$appId&created=1");
            exit;
        }
    }
}

include 'includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h2>New Visa Application</h2>
        <p>Complete all steps to create the application</p>
    </div>
    <div class="page-header-actions">
        <a href="visa-all.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
    </div>
</div>

<!-- Workflow Steps Indicator -->
<div class="workflow-steps" style="margin-bottom:32px;">
    <?php foreach ($steps as $i => $label):
        $num = $i+1;
        $cls = $num < $step ? 'done' : ($num === $step ? 'active' : '');
        $connCls = $num < $step ? 'done' : '';
    ?>
    <div class="wf-step">
        <div class="wf-step-inner">
            <div class="wf-step-circle <?= $cls ?>">
                <?php if ($num < $step): ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <?php else: ?><?= $num ?><?php endif; ?>
            </div>
            <div class="wf-step-label <?= $cls === 'active' ? 'active' : '' ?>"><?= $label ?></div>
        </div>
    </div>
    <?php if ($num < count($steps)): ?>
    <div class="wf-connector <?= $connCls ?>"></div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="visa-create.php?step=<?= $step ?>" id="visa-form">
<input type="hidden" name="form_step" value="<?= $step ?>">

<!-- Carry forward hidden fields -->
<?php if ($step > 1): foreach (['customer_id','visa_type_id','destination_country','purpose','travel_date','return_date','priority','assigned_officer_id','notes'] as $f): ?>
<input type="hidden" name="<?= $f ?>" id="hf_<?= $f ?>" value="<?= htmlspecialchars($_POST[$f] ?? '') ?>">
<?php endforeach; endif; ?>

<!-- ── STEP 1: Select Customer ───────────────────────────── -->
<?php if ($step === 1): ?>
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="users" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Select Customer</h3>
    </div>
    <div class="form-card-body">
        <?php if ($customer): ?>
        <!-- Pre-selected customer -->
        <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
        <div style="display:flex;align-items:center;gap:16px;padding:18px;background:#f7f6f3;border-radius:var(--radius);border:1px solid var(--border);">
            <div class="cus-avatar-xl" style="background:var(--navy-800);width:52px;height:52px;font-size:1.1rem;">
                <?= strtoupper(substr($customer['first_name'],0,1).substr($customer['last_name'],0,1)) ?>
            </div>
            <div>
                <div style="font-size:1.1rem;font-weight:700;"><?= htmlspecialchars($customer['first_name'].' '.$customer['last_name']) ?></div>
                <div style="font-size:0.83rem;color:var(--ink-soft);font-family:monospace;"><?= htmlspecialchars($customer['customer_code']) ?></div>
                <div style="font-size:0.83rem;color:var(--ink-soft);"><?= htmlspecialchars($customer['email']) ?> · <?= htmlspecialchars($customer['nationality'] ?? '') ?></div>
                <?php if ($customer['passport_expiry_date']): ?>
                <div style="font-size:0.78rem;color:var(--ink-soft);">Passport Exp: <?= date('d M Y', strtotime($customer['passport_expiry_date'])) ?></div>
                <?php endif; ?>
            </div>
            <div style="margin-left:auto;">
                <a href="visa-create.php" class="btn btn-ghost btn-sm">Change Customer</a>
            </div>
        </div>
        <?php else: ?>
        <!-- Search & select customer -->
        <div class="field-block">
            <label>Search Customer <span class="req">*</span></label>
            <div class="sfbar-search" style="margin-bottom:12px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="cust-search" placeholder="Type name or code to filter…">
            </div>
            <input type="hidden" name="customer_id" id="selected-customer-id" required>
            <div id="customer-list" style="max-height:340px;overflow-y:auto;border:1px solid var(--border);border-radius:8px;">
                <?php foreach ($customers as $cu): ?>
                <div class="customer-select-row" data-name="<?= htmlspecialchars(strtolower($cu['first_name'].' '.$cu['last_name'].' '.$cu['customer_code'])) ?>"
                     data-id="<?= $cu['id'] ?>"
                     style="display:flex;align-items:center;gap:12px;padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border);transition:background 0.1s;"
                     onmouseover="this.style.background='#f7f6f3'" onmouseout="this.style.background=''"
                     onclick="selectCustomer(this)">
                    <div class="cus-avatar" style="background:<?= ['#101d2c','#2563eb','#7c3aed','#047857','#d97706','#b0413e'][$cu['id']%6] ?>;width:36px;height:36px;font-size:0.78rem;flex-shrink:0;">
                        <?= strtoupper(substr($cu['first_name'],0,1).substr($cu['last_name'],0,1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($cu['first_name'].' '.$cu['last_name']) ?></div>
                        <div style="font-size:0.75rem;color:var(--ink-soft);font-family:monospace;"><?= htmlspecialchars($cu['customer_code']) ?> · <?= htmlspecialchars($cu['nationality'] ?? '') ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                <div class="empty-state" style="padding:24px;">No customers found. <a href="customer-add.php">Add one first.</a></div>
                <?php endif; ?>
            </div>
            <div id="selected-customer-display" style="display:none;margin-top:10px;padding:12px;background:#d1fae5;border-radius:8px;border:1px solid #a7f3d0;font-size:0.88rem;color:#065f46;font-weight:600;"></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── STEP 2: Visa Type & Destination ─────────────────────── -->
<?php elseif ($step === 2): ?>
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="file-text" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Select Visa Type</h3>
    </div>
    <div class="form-card-body">
        <div class="field-block">
            <label>Filter by Country</label>
            <select id="country-filter" class="form-input" style="max-width:300px;" onchange="filterVisaTypes()">
                <option value="">All Countries</option>
                <?php foreach (array_keys($vtByCountry) as $country): ?>
                <option value="<?= htmlspecialchars($country) ?>"><?= htmlspecialchars($country) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <input type="hidden" name="visa_type_id" id="visa_type_id" required>
        <div id="visa-type-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-top:12px;">
            <?php foreach ($visaTypes as $vt): ?>
            <div class="visa-type-card" data-country="<?= htmlspecialchars($vt['country']) ?>" data-id="<?= $vt['id'] ?>"
                 onclick="selectVisaType(this)"
                 style="border:2px solid var(--border);border-radius:var(--radius);padding:16px;cursor:pointer;transition:all 0.15s;background:#fff;">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                    <div style="font-weight:700;font-size:0.9rem;"><?= htmlspecialchars($vt['name']) ?></div>
                    <span class="badge badge-draft"><?= ucfirst($vt['category']) ?></span>
                </div>
                <div style="font-size:0.78rem;color:var(--ink-soft);margin-bottom:8px;"><?= htmlspecialchars($vt['country']) ?></div>
                <div style="display:flex;gap:16px;font-size:0.78rem;">
                    <div><strong style="color:var(--navy-800);">$<?= number_format($vt['fee']+$vt['service_fee'],0) ?></strong> total</div>
                    <div style="color:var(--ink-soft);"><?= htmlspecialchars($vt['processing_time'] ?? '—') ?></div>
                </div>
                <?php if ($vt['validity']): ?>
                <div style="font-size:0.72rem;color:var(--ink-soft);margin-top:4px;">Valid: <?= htmlspecialchars($vt['validity']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="selected-visa-display" style="display:none;margin-top:14px;padding:12px;background:#d1fae5;border-radius:8px;border:1px solid #a7f3d0;font-size:0.88rem;color:#065f46;font-weight:600;"></div>
    </div>
</div>

<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="map-pin" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Destination</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Destination Country <span class="req">*</span></label>
                <input type="text" name="destination_country" id="destination_country" class="form-input" value="<?= htmlspecialchars($_POST['destination_country'] ?? '') ?>" required placeholder="United Kingdom">
            </div>
        </div>
    </div>
</div>

<!-- ── STEP 3: Details & Priority ──────────────────────────── -->
<?php elseif ($step === 3): ?>
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="settings" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Application Details</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-3">
            <div class="field-block">
                <label>Travel Date</label>
                <input type="date" name="travel_date" class="form-input" value="<?= htmlspecialchars($_POST['travel_date'] ?? '') ?>">
            </div>
            <div class="field-block">
                <label>Return Date</label>
                <input type="date" name="return_date" class="form-input" value="<?= htmlspecialchars($_POST['return_date'] ?? '') ?>">
            </div>
            <div class="field-block">
                <label>Priority</label>
                <select name="priority" class="form-input">
                    <option value="normal"  <?= ($_POST['priority']??'normal')==='normal' ?'selected':'' ?>>Normal</option>
                    <option value="urgent"  <?= ($_POST['priority']??'')==='urgent'  ?'selected':'' ?>>Urgent (+fee)</option>
                    <option value="express" <?= ($_POST['priority']??'')==='express' ?'selected':'' ?>>Express (+fee)</option>
                </select>
            </div>
            <div class="field-block">
                <label>Assigned Officer</label>
                <select name="assigned_officer_id" class="form-input">
                    <option value="">Auto-assign</option>
                    <?php foreach ($officers as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= ($_POST['assigned_officer_id']??'')==$o['id']?'selected':'' ?>><?= htmlspecialchars($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field-block">
            <label>Purpose of Travel</label>
            <textarea name="purpose" class="form-input" rows="3" placeholder="Describe the purpose of the visa application…"><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
        </div>
        <div class="field-block">
            <label>Internal Notes</label>
            <textarea name="notes" class="form-input" rows="2" placeholder="Any internal notes…"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>
    </div>
</div>

<!-- ── STEP 4: Review & Submit ─────────────────────────────── -->
<?php elseif ($step === 4): ?>
<?php
$revCustomer = db_row("SELECT * FROM customers WHERE id=?", [$_POST['customer_id']??0]);
$revVisa     = db_row("SELECT * FROM visa_types WHERE id=?", [$_POST['visa_type_id']??0]);
$revOfficer  = $_POST['assigned_officer_id'] ? db_row("SELECT name FROM users WHERE id=?", [$_POST['assigned_officer_id']]) : null;
?>
<div class="panel" style="margin-bottom:20px;">
    <div class="panel-head"><h2>Review Application</h2></div>
    <div class="panel-body">
        <div class="info-grid">
            <div class="info-row"><div class="info-label">Customer</div><div class="info-value"><?= $revCustomer ? htmlspecialchars($revCustomer['first_name'].' '.$revCustomer['last_name'].' ('.$revCustomer['customer_code'].')') : '—' ?></div></div>
            <div class="info-row"><div class="info-label">Visa Type</div><div class="info-value"><?= $revVisa ? htmlspecialchars($revVisa['name']) : '—' ?></div></div>
            <div class="info-row"><div class="info-label">Destination</div><div class="info-value"><?= htmlspecialchars($_POST['destination_country']??'—') ?></div></div>
            <div class="info-row"><div class="info-label">Category</div><div class="info-value"><?= $revVisa ? ucfirst($revVisa['category']) : '—' ?></div></div>
            <div class="info-row"><div class="info-label">Travel Date</div><div class="info-value"><?= $_POST['travel_date'] ? date('d M Y', strtotime($_POST['travel_date'])) : '—' ?></div></div>
            <div class="info-row"><div class="info-label">Return Date</div><div class="info-value"><?= $_POST['return_date'] ? date('d M Y', strtotime($_POST['return_date'])) : '—' ?></div></div>
            <div class="info-row"><div class="info-label">Priority</div><div class="info-value"><?= ucfirst($_POST['priority']??'normal') ?></div></div>
            <div class="info-row"><div class="info-label">Assigned Officer</div><div class="info-value"><?= $revOfficer ? htmlspecialchars($revOfficer['name']) : 'Auto-assign' ?></div></div>
            <?php if ($revVisa): ?>
            <div class="info-row"><div class="info-label">Visa Fee</div><div class="info-value">$<?= number_format($revVisa['fee'],2) ?></div></div>
            <div class="info-row"><div class="info-label">Service Fee</div><div class="info-value">$<?= number_format($revVisa['service_fee'],2) ?></div></div>
            <?php endif; ?>
        </div>
        <?php if (!empty($_POST['purpose'])): ?>
        <div style="margin-top:16px;"><div class="info-label">Purpose</div><p style="font-size:0.88rem;margin-top:6px;"><?= htmlspecialchars($_POST['purpose']) ?></p></div>
        <?php endif; ?>
    </div>
</div>
<!-- Carry all POST values as hidden fields for final submit -->
<?php foreach (['customer_id','visa_type_id','destination_country','purpose','travel_date','return_date','priority','assigned_officer_id','notes'] as $f): ?>
<input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars($_POST[$f] ?? '') ?>">
<?php endforeach; ?>
<div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;">
    <strong>ℹ️ After creating this application</strong>, the system will set status to <strong>Documents Pending</strong>.
    You can then upload the required documents from the application detail page.
</div>
<?php endif; ?>

<!-- Navigation Buttons -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;margin-bottom:32px;">
    <div>
        <?php if ($step > 1): ?>
        <a href="visa-create.php?step=<?= $step-1 ?><?= $preCustomerId?"&customer_id=$preCustomerId":'' ?>" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i> Back
        </a>
        <?php endif; ?>
    </div>
    <div style="display:flex;gap:10px;">
        <?php if ($step < 4): ?>
        <button type="submit" formaction="visa-create.php?step=<?= $step+1 ?>" class="btn btn-primary" id="btn-next-step">
            Next <i data-lucide="arrow-right"></i>
        </button>
        <?php else: ?>
        <button type="submit" class="btn btn-primary" id="btn-submit-visa">
            <i data-lucide="check-circle"></i> Create Application
        </button>
        <?php endif; ?>
    </div>
</div>

</form>

<script>
// Customer selection
function selectCustomer(el) {
    document.querySelectorAll('.customer-select-row').forEach(r => r.style.background='');
    el.style.background = '#d1fae5';
    document.getElementById('selected-customer-id').value = el.dataset.id;
    const display = document.getElementById('selected-customer-display');
    display.style.display = 'block';
    display.textContent = '✓ Selected: ' + el.querySelector('[style*="font-weight:600"]').textContent.trim();
}
// Customer search filter
document.getElementById('cust-search')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.customer-select-row').forEach(r => {
        r.style.display = r.dataset.name.includes(q) ? '' : 'none';
    });
});

// Visa type selection
function selectVisaType(el) {
    document.querySelectorAll('.visa-type-card').forEach(c => {
        c.style.borderColor = 'var(--border)';
        c.style.background = '#fff';
    });
    el.style.borderColor = 'var(--navy-800)';
    el.style.background = '#eff6ff';
    document.getElementById('visa_type_id').value = el.dataset.id;
    const country = el.querySelector('[style*="color:var(--ink-soft)"]').textContent.trim();
    document.getElementById('destination_country').value = country;
    const display = document.getElementById('selected-visa-display');
    display.style.display = 'block';
    display.textContent = '✓ Selected: ' + el.querySelector('[style*="font-weight:700"]').textContent.trim() + ' — ' + country;
}
// Filter visa types by country
function filterVisaTypes() {
    const val = document.getElementById('country-filter').value.toLowerCase();
    document.querySelectorAll('.visa-type-card').forEach(c => {
        c.style.display = !val || c.dataset.country.toLowerCase() === val ? '' : 'none';
    });
}
</script>

<?php include 'includes/footer.php'; ?>
