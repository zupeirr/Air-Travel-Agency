<?php
$pageTitle = 'Notification Settings';
include 'includes/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
}

// Notification rules
$notifRules = [
    ['key'=>'notif_booking_confirmed',   'label'=>'Booking Confirmed',        'desc'=>'When a booking status changes to confirmed',        'email'=>true,'sms'=>false,'app'=>true],
    ['key'=>'notif_visa_status_changed', 'label'=>'Visa Status Updated',      'desc'=>'When a visa application status is updated',         'email'=>true,'sms'=>true, 'app'=>true],
    ['key'=>'notif_payment_received',    'label'=>'Payment Received',          'desc'=>'When a payment is logged against an invoice',       'email'=>true,'sms'=>false,'app'=>true],
    ['key'=>'notif_invoice_overdue',     'label'=>'Invoice Overdue Alert',     'desc'=>'When an invoice passes its due date unpaid',        'email'=>true,'sms'=>false,'app'=>true],
    ['key'=>'notif_doc_uploaded',        'label'=>'Document Uploaded',         'desc'=>'When a customer uploads a new document',           'email'=>false,'sms'=>false,'app'=>true],
    ['key'=>'notif_passport_expiry',     'label'=>'Passport Expiry Warning',   'desc'=>'30 days before a customer\'s passport expires',    'email'=>true,'sms'=>false,'app'=>true],
    ['key'=>'notif_appointment_due',     'label'=>'Appointment Reminder',      'desc'=>'24 hours before a scheduled embassy appointment',  'email'=>true,'sms'=>true, 'app'=>true],
    ['key'=>'notif_new_inquiry',         'label'=>'New Customer Inquiry',      'desc'=>'When a traveler submits a new inquiry',            'email'=>true,'sms'=>false,'app'=>true],
];
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Notification Settings</h2>
        <p>Control when and how the system alerts you and your customers</p>
    </div>
</div>

<div class="settings-tabs">
    <a href="settings-general.php"       class="stab"><i data-lucide="settings"></i> General</a>
    <a href="settings-users.php"         class="stab"><i data-lucide="users"></i> Users & Roles</a>
    <a href="settings-api.php"           class="stab"><i data-lucide="code"></i> API Integrations</a>
    <a href="settings-notifications.php" class="stab active"><i data-lucide="bell"></i> Notifications</a>
    <a href="settings-security.php"      class="stab"><i data-lucide="shield"></i> Security</a>
</div>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px;">✅ Notification preferences saved.</div>
<?php endif; ?>

<style>
.settings-tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid var(--border); padding-bottom:0; flex-wrap:wrap; }
.stab { display:flex; align-items:center; gap:7px; padding:10px 16px; font-size:0.85rem; font-weight:600; color:var(--ink-soft); text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.15s; }
.stab svg { width:14px; height:14px; }
.stab:hover { color:var(--navy-800); }
.stab.active { color:var(--navy-800); border-bottom-color:var(--navy-800); }
/* Toggle switch */
.toggle-wrap { display:flex; align-items:center; gap:8px; }
.toggle { position:relative; display:inline-block; width:42px; height:24px; }
.toggle input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; top:0;left:0;right:0;bottom:0; background:#d1d5db; border-radius:24px; transition:0.2s; }
.slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:0.2s; }
input:checked + .slider { background:var(--navy-800); }
input:checked + .slider:before { transform:translateX(18px); }
.notif-row { display:grid; grid-template-columns:1fr 90px 70px 70px; gap:16px; align-items:center; padding:16px 24px; border-bottom:1px solid var(--border); }
.notif-row:last-child { border-bottom:none; }
.notif-row-head { grid-template-columns:1fr 90px 70px 70px; background:#f7f6f3; font-size:0.75rem; font-weight:700; text-transform:uppercase; color:var(--ink-soft); letter-spacing:0.05em; }
</style>

<form method="POST" action="settings-notifications.php">

<div class="panel" style="max-width:800px;margin-bottom:20px;">
    <div class="panel-head"><h2>Notification Rules</h2></div>
    <!-- Header row -->
    <div class="notif-row notif-row-head">
        <div>Event</div>
        <div style="text-align:center;">Email</div>
        <div style="text-align:center;">SMS</div>
        <div style="text-align:center;">In-App</div>
    </div>
    <?php foreach ($notifRules as $rule): ?>
    <div class="notif-row">
        <div>
            <div style="font-weight:600;font-size:0.9rem;"><?= $rule['label'] ?></div>
            <div style="font-size:0.75rem;color:var(--ink-soft);"><?= $rule['desc'] ?></div>
        </div>
        <!-- Email toggle -->
        <div style="text-align:center;">
            <label class="toggle"><input type="checkbox" name="<?= $rule['key'] ?>_email" <?= $rule['email']?'checked':'' ?>><span class="slider"></span></label>
        </div>
        <!-- SMS toggle -->
        <div style="text-align:center;">
            <label class="toggle"><input type="checkbox" name="<?= $rule['key'] ?>_sms" <?= $rule['sms']?'checked':'' ?>><span class="slider"></span></label>
        </div>
        <!-- In-App toggle -->
        <div style="text-align:center;">
            <label class="toggle"><input type="checkbox" name="<?= $rule['key'] ?>_app" <?= $rule['app']?'checked':'' ?>><span class="slider"></span></label>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="form-card" style="max-width:800px;">
    <div class="form-card-head"><h3>Email Digest</h3></div>
    <div class="form-card-body">
        <div class="field-block">
            <label>Send Daily Summary Report To</label>
            <input type="email" name="digest_email" class="form-input" value="" placeholder="manager@airtravel.com" style="max-width:350px;">
            <div class="field-hint">A daily digest of new applications, payments received, and overdue items.</div>
        </div>
        <div class="field-block">
            <label>Send At</label>
            <select name="digest_time" class="form-input" style="max-width:200px;">
                <option value="08:00">8:00 AM</option>
                <option value="09:00" selected>9:00 AM</option>
                <option value="18:00">6:00 PM</option>
                <option value="disabled">Disabled</option>
            </select>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;max-width:800px;justify-content:flex-end;margin-bottom:32px;margin-top:20px;">
    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Preferences</button>
</div>

</form>

<?php include 'includes/footer.php'; ?>
