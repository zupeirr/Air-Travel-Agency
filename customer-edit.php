<?php
$pageTitle = 'Edit Customer';

require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) { header('Location: customers-directory.php'); exit; }

// Fetch existing customer
$c = db_row("SELECT * FROM customers WHERE id = ?", [$id]);
if (!$c) { header('Location: customers-directory.php'); exit; }

$agents = db_select("SELECT id, name FROM users WHERE status='active' ORDER BY name");

$errors = [];
$success = false;

// Initial data from DB
$data = $c;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize
    $fields = ['first_name','last_name','email','phone','date_of_birth',
    'nationality','national_id','passport_number','passport_issue_date',
    'passport_expiry_date','gender','marital_status','occupation',
    'employer','address_line1','address_line2','city','state',
    'postal_code','country','emergency_contact_name','emergency_contact_phone',
    'notes','assigned_agent_id'];
    
    foreach ($fields as $k) {
        $data[$k] = trim($_POST[$k] ?? '');
    }

    // Validate
    if (!$data['first_name']) $errors['first_name'] = 'First name is required.';
    if (!$data['last_name'])  $errors['last_name']  = 'Last name is required.';
    if (!$data['email'])      $errors['email']      = 'Email is required.';
    elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
    else {
        // Check email uniqueness excluding current customer
        $exists = db_count("SELECT COUNT(*) FROM customers WHERE email = ? AND id != ?", [$data['email'], $id]);
        if ($exists) $errors['email'] = 'This email is already registered by another customer.';
    }

    if (empty($errors)) {
        db_execute("UPDATE customers SET
             first_name=?, last_name=?, email=?, phone=?, date_of_birth=?, nationality=?,
             national_id=?, passport_number=?, passport_issue_date=?, passport_expiry_date=?,
             gender=?, marital_status=?, occupation=?, employer=?, address_line1=?, address_line2=?,
             city=?, state=?, postal_code=?, country=?, emergency_contact_name=?, emergency_contact_phone=?,
             notes=?, assigned_agent_id=?
            WHERE id=?",
            [
                $data['first_name'], $data['last_name'], $data['email'], $data['phone'],
                $data['date_of_birth']?:null, $data['nationality'], $data['national_id'],
                $data['passport_number'], $data['passport_issue_date']?:null,
                $data['passport_expiry_date']?:null, $data['gender']?:null,
                $data['marital_status']?:null, $data['occupation'], $data['employer'],
                $data['address_line1'], $data['address_line2'], $data['city'], $data['state'],
                $data['postal_code'], $data['country'], $data['emergency_contact_name'],
                $data['emergency_contact_phone'], $data['notes'],
                $data['assigned_agent_id']?:null,
                $id
            ]);

        header("Location: customer-profile.php?id=$id&updated=1");
        exit;
    }
}

// Now that all redirects are done, include the header (which starts HTML output)
include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Edit Customer: <?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></h2>
        <p>Update the customer's details below.</p>
    </div>
    <div class="page-header-actions">
        <a href="customer-profile.php?id=<?= $id ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back to Profile</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <strong>Please fix the following errors:</strong>
    <ul style="margin:6px 0 0 20px;font-size:0.88rem;">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" action="customer-edit.php?id=<?= $id ?>" id="edit-customer-form">
<input type="hidden" name="id" value="<?= $id ?>">

<!-- Personal Information -->
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="user" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Personal Information</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-3">
            <div class="field-block">
                <label>First Name <span class="req">*</span></label>
                <input type="text" name="first_name" class="form-input <?= isset($errors['first_name'])?'error':'' ?>" value="<?= htmlspecialchars($data['first_name']) ?>" required placeholder="Ahmed">
                <?php if (isset($errors['first_name'])): ?><div class="field-error"><?= $errors['first_name'] ?></div><?php endif; ?>
            </div>
            <div class="field-block">
                <label>Last Name <span class="req">*</span></label>
                <input type="text" name="last_name" class="form-input <?= isset($errors['last_name'])?'error':'' ?>" value="<?= htmlspecialchars($data['last_name']) ?>" required placeholder="Al-Rashid">
                <?php if (isset($errors['last_name'])): ?><div class="field-error"><?= $errors['last_name'] ?></div><?php endif; ?>
            </div>
            <div class="field-block">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-input" value="<?= htmlspecialchars($data['date_of_birth']) ?>">
            </div>
            <div class="field-block">
                <label>Email Address <span class="req">*</span></label>
                <input type="email" name="email" class="form-input <?= isset($errors['email'])?'error':'' ?>" value="<?= htmlspecialchars($data['email']) ?>" required placeholder="ahmed@email.com">
                <?php if (isset($errors['email'])): ?><div class="field-error"><?= $errors['email'] ?></div><?php endif; ?>
            </div>
            <div class="field-block">
                <label>Phone Number</label>
                <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($data['phone']) ?>" placeholder="+971 55 123 4567">
            </div>
            <div class="field-block">
                <label>Gender</label>
                <select name="gender" class="form-input">
                    <option value="">Select gender</option>
                    <option value="male"   <?= $data['gender']==='male'?'selected':'' ?>>Male</option>
                    <option value="female" <?= $data['gender']==='female'?'selected':'' ?>>Female</option>
                    <option value="other"  <?= $data['gender']==='other'?'selected':'' ?>>Other</option>
                </select>
            </div>
            <div class="field-block">
                <label>Marital Status</label>
                <select name="marital_status" class="form-input">
                    <option value="">Select status</option>
                    <option value="single"   <?= $data['marital_status']==='single'?'selected':'' ?>>Single</option>
                    <option value="married"  <?= $data['marital_status']==='married'?'selected':'' ?>>Married</option>
                    <option value="divorced" <?= $data['marital_status']==='divorced'?'selected':'' ?>>Divorced</option>
                    <option value="widowed"  <?= $data['marital_status']==='widowed'?'selected':'' ?>>Widowed</option>
                </select>
            </div>
            <div class="field-block">
                <label>Nationality</label>
                <input type="text" name="nationality" class="form-input" value="<?= htmlspecialchars($data['nationality']) ?>" placeholder="Nigerian">
            </div>
            <div class="field-block">
                <label>Assigned Agent</label>
                <select name="assigned_agent_id" class="form-input">
                    <option value="">No agent assigned</option>
                    <?php foreach ($agents as $agent): ?>
                    <option value="<?= $agent['id'] ?>" <?= $data['assigned_agent_id']==$agent['id']?'selected':'' ?>>
                        <?= htmlspecialchars($agent['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-grid-2" style="margin-top:0;">
            <div class="field-block">
                <label>Occupation</label>
                <input type="text" name="occupation" class="form-input" value="<?= htmlspecialchars($data['occupation']) ?>" placeholder="Software Engineer">
            </div>
            <div class="field-block">
                <label>Employer / Company</label>
                <input type="text" name="employer" class="form-input" value="<?= htmlspecialchars($data['employer']) ?>" placeholder="ABC Corp">
            </div>
        </div>
    </div>
</div>

<!-- Passport Information -->
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="credit-card" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Passport & Identity</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-3">
            <div class="field-block">
                <label>Passport Number</label>
                <input type="text" name="passport_number" class="form-input" value="<?= htmlspecialchars($data['passport_number']) ?>" placeholder="A12345678" style="font-family:monospace;">
            </div>
            <div class="field-block">
                <label>National ID Number</label>
                <input type="text" name="national_id" class="form-input" value="<?= htmlspecialchars($data['national_id']) ?>" placeholder="Optional" style="font-family:monospace;">
            </div>
            <div class="field-block">
                <label>Passport Issue Date</label>
                <input type="date" name="passport_issue_date" class="form-input" value="<?= htmlspecialchars($data['passport_issue_date']) ?>">
            </div>
            <div class="field-block">
                <label>Passport Expiry Date</label>
                <input type="date" name="passport_expiry_date" class="form-input" value="<?= htmlspecialchars($data['passport_expiry_date']) ?>" id="passport-expiry">
                <div class="field-hint" id="passport-expiry-hint"></div>
            </div>
        </div>
    </div>
</div>

<!-- Address -->
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="map-pin" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Address</h3>
    </div>
    <div class="form-card-body">
        <div class="field-block">
            <label>Address Line 1</label>
            <input type="text" name="address_line1" class="form-input" value="<?= htmlspecialchars($data['address_line1']) ?>" placeholder="123 Main Street">
        </div>
        <div class="field-block">
            <label>Address Line 2</label>
            <input type="text" name="address_line2" class="form-input" value="<?= htmlspecialchars($data['address_line2']) ?>" placeholder="Apt, Suite, etc.">
        </div>
        <div class="form-grid-3">
            <div class="field-block">
                <label>City</label>
                <input type="text" name="city" class="form-input" value="<?= htmlspecialchars($data['city']) ?>" placeholder="Lagos">
            </div>
            <div class="field-block">
                <label>State / Province</label>
                <input type="text" name="state" class="form-input" value="<?= htmlspecialchars($data['state']) ?>">
            </div>
            <div class="field-block">
                <label>Postal Code</label>
                <input type="text" name="postal_code" class="form-input" value="<?= htmlspecialchars($data['postal_code']) ?>">
            </div>
            <div class="field-block">
                <label>Country</label>
                <input type="text" name="country" class="form-input" value="<?= htmlspecialchars($data['country']) ?>" placeholder="Nigeria">
            </div>
        </div>
    </div>
</div>

<!-- Emergency Contact -->
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="phone" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Emergency Contact</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Contact Name</label>
                <input type="text" name="emergency_contact_name" class="form-input" value="<?= htmlspecialchars($data['emergency_contact_name']) ?>" placeholder="Jane Doe">
            </div>
            <div class="field-block">
                <label>Contact Phone</label>
                <input type="tel" name="emergency_contact_phone" class="form-input" value="<?= htmlspecialchars($data['emergency_contact_phone']) ?>" placeholder="+1 555 0000">
            </div>
        </div>
    </div>
</div>

<!-- Notes -->
<div class="form-card">
    <div class="form-card-body">
        <div class="field-block" style="margin:0;">
            <label>Internal Notes</label>
            <textarea name="notes" class="form-input" rows="3" placeholder="Any additional notes about this customer…"><?= htmlspecialchars($data['notes']) ?></textarea>
        </div>
    </div>
</div>

<!-- Actions -->
<div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;margin-bottom:32px;">
    <a href="customer-profile.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary" id="btn-save-customer">
        <i data-lucide="save"></i> Update Customer
    </button>
</div>

</form>

<script>
// Live passport expiry hint
document.getElementById('passport-expiry')?.addEventListener('change', function() {
    const hint = document.getElementById('passport-expiry-hint');
    if (!this.value) { hint.textContent = ''; return; }
    const exp = new Date(this.value), now = new Date();
    const days = Math.round((exp - now) / 86400000);
    if (days < 0) {
        hint.textContent = '⚠️ This passport has already expired.';
        hint.style.color = 'var(--danger)';
    } else if (days < 180) {
        hint.textContent = `⚠️ Expiring in ${days} days — may not meet visa requirements.`;
        hint.style.color = '#d97706';
    } else {
        hint.textContent = `✓ Valid for ${days} more days.`;
        hint.style.color = 'var(--success)';
    }
});
// Trigger once on load
document.getElementById('passport-expiry')?.dispatchEvent(new Event('change'));
</script>

<?php include 'includes/footer.php'; ?>
