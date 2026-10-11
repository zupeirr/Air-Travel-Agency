<?php
$pageTitle = 'Security Settings';
include 'includes/header.php';

$errors  = [];
$success = false;

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'change_password') {
    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if (!$current) $errors[] = 'Current password is required.';
    if (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
    if ($new !== $confirm) $errors[] = 'New passwords do not match.';

    if (empty($errors)) {
        // In production: verify $current against stored hash, then update.
        $success = true;
    }
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Security Settings</h2>
        <p>Password, session, and access control configuration</p>
    </div>
</div>

<div class="settings-tabs">
    <a href="settings-general.php"       class="stab"><i data-lucide="settings"></i> General</a>
    <a href="settings-users.php"         class="stab"><i data-lucide="users"></i> Users & Roles</a>
    <a href="settings-api.php"           class="stab"><i data-lucide="code"></i> API Integrations</a>
    <a href="settings-notifications.php" class="stab"><i data-lucide="bell"></i> Notifications</a>
    <a href="settings-security.php"      class="stab active"><i data-lucide="shield"></i> Security</a>
</div>

<style>
.settings-tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid var(--border); padding-bottom:0; flex-wrap:wrap; }
.stab { display:flex; align-items:center; gap:7px; padding:10px 16px; font-size:0.85rem; font-weight:600; color:var(--ink-soft); text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.15s; }
.stab svg { width:14px; height:14px; }
.stab:hover { color:var(--navy-800); }
.stab.active { color:var(--navy-800); border-bottom-color:var(--navy-800); }
.pwd-strength { height:4px; border-radius:4px; margin-top:6px; background:#e5e7eb; overflow:hidden; }
.pwd-strength-fill { height:100%; border-radius:4px; width:0%; transition:width 0.3s, background 0.3s; }
.toggle { position:relative; display:inline-block; width:42px; height:24px; }
.toggle input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; top:0;left:0;right:0;bottom:0; background:#d1d5db; border-radius:24px; transition:0.2s; }
.slider:before { position:absolute; content:""; height:18px; width:18px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:0.2s; }
input:checked + .slider { background:var(--navy-800); }
input:checked + .slider:before { transform:translateX(18px); }
</style>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px;">✅ Password changed successfully.</div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="alert alert-error" style="margin-bottom:20px;"><?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
<?php endif; ?>

<!-- Change Password -->
<div class="form-card" style="max-width:500px;margin-bottom:20px;">
    <div class="form-card-head">
        <i data-lucide="lock" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Change Admin Password</h3>
    </div>
    <div class="form-card-body">
        <form method="POST" action="settings-security.php">
            <input type="hidden" name="action" value="change_password">
            <div class="field-block">
                <label>Current Password <span class="req">*</span></label>
                <input type="password" name="current_password" class="form-input" required>
            </div>
            <div class="field-block">
                <label>New Password <span class="req">*</span></label>
                <input type="password" name="new_password" id="new_password" class="form-input" required minlength="8" oninput="checkStrength(this.value)">
                <div class="pwd-strength"><div class="pwd-strength-fill" id="strength-bar"></div></div>
                <div class="field-hint" id="strength-text">Enter a password</div>
            </div>
            <div class="field-block">
                <label>Confirm New Password <span class="req">*</span></label>
                <input type="password" name="confirm_password" class="form-input" required minlength="8">
            </div>
            <div style="margin-top:8px;">
                <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> Change Password</button>
            </div>
        </form>
    </div>
</div>

<!-- Session & Access -->
<div class="form-card" style="max-width:600px;margin-bottom:20px;">
    <div class="form-card-head">
        <i data-lucide="shield-check" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Session & Access Control</h3>
    </div>
    <div class="form-card-body">
        <form method="POST" action="settings-security.php">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Session Timeout</label>
                <select name="session_timeout" class="form-input">
                    <option value="30">30 minutes</option>
                    <option value="60" selected>1 hour</option>
                    <option value="240">4 hours</option>
                    <option value="480">8 hours</option>
                    <option value="0">Never</option>
                </select>
            </div>
            <div class="field-block">
                <label>Max Login Attempts</label>
                <select name="max_attempts" class="form-input">
                    <option value="3">3 attempts</option>
                    <option value="5" selected>5 attempts</option>
                    <option value="10">10 attempts</option>
                </select>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;gap:14px;margin-top:4px;">
            <?php $secOptions = [
                ['name'=>'force_https',    'label'=>'Force HTTPS',             'desc'=>'Redirect all traffic to HTTPS',    'default'=>true],
                ['name'=>'enable_2fa',     'label'=>'Enable Two-Factor Auth',  'desc'=>'Require OTP for admin login',      'default'=>false],
                ['name'=>'log_activity',   'label'=>'Log All Activity',        'desc'=>'Track all changes in activity log','default'=>true],
                ['name'=>'ip_whitelist',   'label'=>'IP Whitelist Mode',       'desc'=>'Only allow specific IP addresses', 'default'=>false],
            ]; foreach ($secOptions as $opt): ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;background:#f7f6f3;border-radius:8px;">
                <div>
                    <div style="font-weight:600;font-size:0.88rem;"><?= $opt['label'] ?></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= $opt['desc'] ?></div>
                </div>
                <label class="toggle">
                    <input type="checkbox" name="<?= $opt['name'] ?>" <?= $opt['default']?'checked':'' ?>>
                    <span class="slider"></span>
                </label>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Security Settings</button>
        </div>
        </form>
    </div>
</div>

<!-- Recent Login Activity -->
<div class="panel" style="max-width:700px;margin-bottom:32px;">
    <div class="panel-head"><h2>Recent Login Activity</h2></div>
    <div class="panel-body table-wrap" style="padding:0;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Device</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <!-- Placeholder rows -->
                <?php
                $fakeLogins = [
                    ['Admin','192.168.1.10','Chrome / Windows','3 mins ago','success'],
                    ['Admin','192.168.1.10','Chrome / Windows','2 hours ago','success'],
                    ['Jane Smith','203.0.113.5','Safari / macOS','Yesterday 2:14 PM','success'],
                    ['Unknown','45.33.32.156','Unknown','Yesterday 11:02 PM','failed'],
                ];
                foreach ($fakeLogins as $l): ?>
                <tr>
                    <td style="font-weight:600;font-size:0.88rem;"><?= $l[0] ?></td>
                    <td><code style="font-size:0.75rem;"><?= $l[1] ?></code></td>
                    <td style="font-size:0.8rem;color:var(--ink-soft);"><?= $l[2] ?></td>
                    <td style="font-size:0.8rem;color:var(--ink-soft);"><?= $l[3] ?></td>
                    <td>
                        <?php if ($l[4]==='success'): ?>
                        <span style="font-size:0.75rem;font-weight:700;color:var(--success);">✓ Success</span>
                        <?php else: ?>
                        <span style="font-size:0.75rem;font-weight:700;color:var(--danger);">✗ Failed</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function checkStrength(val) {
    const bar = document.getElementById('strength-bar');
    const txt = document.getElementById('strength-text');
    let score = 0;
    if (val.length >= 8) score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        {w:'0%',  bg:'#e5e7eb', t:'Enter a password'},
        {w:'25%', bg:'#ef4444', t:'Weak'},
        {w:'50%', bg:'#f59e0b', t:'Fair'},
        {w:'75%', bg:'#3b82f6', t:'Good'},
        {w:'100%',bg:'#22c55e', t:'Strong'},
    ];
    const lv = levels[Math.min(score, 4)];
    bar.style.width = lv.w;
    bar.style.background = lv.bg;
    txt.textContent = lv.t;
    txt.style.color = lv.bg;
}
</script>

<?php include 'includes/footer.php'; ?>
