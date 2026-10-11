<?php
$pageTitle = 'General Settings';
include 'includes/header.php';

$success = false;
$errors  = [];

// Load current settings (or use defaults)
$settings = [
    'agency_name'     => 'Air Travel Online Agency',
    'agency_email'    => 'info@airtravel.com',
    'agency_phone'    => '+1 (555) 000-0000',
    'agency_address'  => '',
    'agency_currency' => 'USD',
    'agency_timezone' => 'America/New_York',
    'agency_language' => 'en',
    'date_format'     => 'd M Y',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In production, store these in a `settings` table via key/value pairs.
    // For now, just show success.
    $success = true;
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>General Settings</h2>
        <p>Core agency information and regional preferences</p>
    </div>
</div>

<!-- Settings Tab Nav -->
<div class="settings-tabs">
    <a href="settings-general.php"       class="stab active"><i data-lucide="settings"></i> General</a>
    <a href="settings-users.php"         class="stab"><i data-lucide="users"></i> Users & Roles</a>
    <a href="settings-api.php"           class="stab"><i data-lucide="code"></i> API Integrations</a>
    <a href="settings-notifications.php" class="stab"><i data-lucide="bell"></i> Notifications</a>
    <a href="settings-security.php"      class="stab"><i data-lucide="shield"></i> Security</a>
</div>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px;">✅ Settings saved successfully.</div>
<?php endif; ?>

<form method="POST" action="settings-general.php">

<div class="form-card" style="max-width:700px;">
    <div class="form-card-head">
        <i data-lucide="building" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Agency Information</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Agency Name</label>
                <input type="text" name="agency_name" class="form-input" value="<?= htmlspecialchars($settings['agency_name']) ?>">
            </div>
            <div class="field-block">
                <label>Contact Email</label>
                <input type="email" name="agency_email" class="form-input" value="<?= htmlspecialchars($settings['agency_email']) ?>">
            </div>
            <div class="field-block">
                <label>Phone Number</label>
                <input type="text" name="agency_phone" class="form-input" value="<?= htmlspecialchars($settings['agency_phone']) ?>">
            </div>
            <div class="field-block">
                <label>Currency</label>
                <select name="agency_currency" class="form-input">
                    <?php foreach (['USD'=>'US Dollar ($)','EUR'=>'Euro (€)','GBP'=>'British Pound (£)','AED'=>'UAE Dirham (AED)','SAR'=>'Saudi Riyal (SAR)','CAD'=>'Canadian Dollar (CAD)'] as $code=>$label): ?>
                    <option value="<?= $code ?>" <?= $settings['agency_currency']===$code?'selected':'' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field-block">
            <label>Office Address</label>
            <textarea name="agency_address" class="form-input" rows="2" placeholder="123 Main St, City, Country"><?= htmlspecialchars($settings['agency_address']) ?></textarea>
        </div>
    </div>
</div>

<div class="form-card" style="max-width:700px;">
    <div class="form-card-head">
        <i data-lucide="globe" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Regional Preferences</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Timezone</label>
                <select name="agency_timezone" class="form-input">
                    <?php foreach (['America/New_York'=>'Eastern (UTC-5)','America/Chicago'=>'Central (UTC-6)','America/Los_Angeles'=>'Pacific (UTC-8)','Europe/London'=>'London (UTC+0)','Europe/Paris'=>'Paris (UTC+1)','Asia/Dubai'=>'Dubai (UTC+4)','Asia/Riyadh'=>'Riyadh (UTC+3)','Asia/Karachi'=>'Karachi (UTC+5)','Asia/Manila'=>'Manila (UTC+8)'] as $tz=>$label): ?>
                    <option value="<?= $tz ?>" <?= $settings['agency_timezone']===$tz?'selected':'' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-block">
                <label>Date Format</label>
                <select name="date_format" class="form-input">
                    <option value="d M Y"   <?= $settings['date_format']==='d M Y'?'selected':'' ?>>14 Oct 2026</option>
                    <option value="d/m/Y"   <?= $settings['date_format']==='d/m/Y'?'selected':'' ?>>14/10/2026</option>
                    <option value="m/d/Y"   <?= $settings['date_format']==='m/d/Y'?'selected':'' ?>>10/14/2026</option>
                    <option value="Y-m-d"   <?= $settings['date_format']==='Y-m-d'?'selected':'' ?>>2026-10-14</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;max-width:700px;justify-content:flex-end;margin-bottom:32px;">
    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Changes</button>
</div>

</form>

<style>
.settings-tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid var(--border); padding-bottom:0; flex-wrap:wrap; }
.stab {
    display:flex; align-items:center; gap:7px; padding:10px 16px;
    font-size:0.85rem; font-weight:600; color:var(--ink-soft); text-decoration:none;
    border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.15s;
}
.stab svg { width:14px; height:14px; }
.stab:hover { color:var(--navy-800); }
.stab.active { color:var(--navy-800); border-bottom-color:var(--navy-800); }
</style>

<?php include 'includes/footer.php'; ?>
