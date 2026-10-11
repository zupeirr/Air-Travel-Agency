<?php
$pageTitle = 'Users & Roles';
require_once __DIR__ . '/config/db.php';

$errors  = [];
$success = false;

// Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'add_user') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $roleId   = (int)($_POST['role_id'] ?? 0);
    $password = trim($_POST['password'] ?? '');

    if (!$name)     $errors[] = 'Name is required.';
    if (!$email)    $errors[] = 'Email is required.';
    if (!$roleId)   $errors[] = 'Role is required.';
    if (!$password) $errors[] = 'Password is required.';

    if (empty($errors)) {
        $existing = db_row("SELECT id FROM users WHERE email=?", [$email]);
        if ($existing) {
            $errors[] = 'Email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            db_insert("INSERT INTO users (name, email, password_hash, role_id, status) VALUES (?,?,?,?,'active')",
                [$name, $email, $hash, $roleId]);
            header("Location: settings-users.php?added=1");
            exit;
        }
    }
}

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action']??'') === 'toggle_status') {
    $uid    = (int)($_POST['user_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    if ($uid && in_array($status, ['active','inactive'])) {
        db_execute("UPDATE users SET status=? WHERE id=?", [$status, $uid]);
        header("Location: settings-users.php");
        exit;
    }
}

include 'includes/header.php';

$users = db_select("
    SELECT u.*, r.name AS role_name
    FROM users u
    LEFT JOIN roles r ON r.id = u.role_id
    ORDER BY u.created_at DESC");

$roles = db_select("SELECT * FROM roles ORDER BY name");
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Users & Roles</h2>
        <p>Manage staff accounts and access permissions</p>
    </div>
    <div class="page-header-actions">
        <button onclick="document.getElementById('add-user-modal').classList.add('show')" class="btn btn-primary" id="btn-add-user">
            <i data-lucide="user-plus"></i> Add User
        </button>
    </div>
</div>

<!-- Settings Tab Nav -->
<div class="settings-tabs">
    <a href="settings-general.php"       class="stab"><i data-lucide="settings"></i> General</a>
    <a href="settings-users.php"         class="stab active"><i data-lucide="users"></i> Users & Roles</a>
    <a href="settings-api.php"           class="stab"><i data-lucide="code"></i> API Integrations</a>
    <a href="settings-notifications.php" class="stab"><i data-lucide="bell"></i> Notifications</a>
    <a href="settings-security.php"      class="stab"><i data-lucide="shield"></i> Security</a>
</div>

<?php if (isset($_GET['added'])): ?>
<div class="alert alert-success" style="margin-bottom:20px;">✅ User added successfully.</div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="alert alert-error" style="margin-bottom:20px;"><?php foreach ($errors as $e) echo "<div>".htmlspecialchars($e)."</div>"; ?></div>
<?php endif; ?>

<!-- Roles Overview -->
<div class="form-card" style="margin-bottom:20px;">
    <div class="form-card-head"><h3>System Roles</h3></div>
    <div class="form-card-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
            <?php foreach ($roles as $r): ?>
            <div style="padding:14px;background:#f7f6f3;border-radius:8px;border:1px solid var(--border);">
                <div style="font-weight:700;font-size:0.9rem;margin-bottom:4px;"><?= htmlspecialchars($r['name']) ?></div>
                <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($r['description'] ?? '') ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="panel">
    <div class="panel-head"><h2>Staff Accounts (<?= count($users) ?>)</h2></div>
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($users)): ?>
        <div class="empty-state" style="padding:40px;">No users found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:34px;height:34px;border-radius:50%;background:var(--navy-800);display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.8rem;font-weight:700;">
                            <?= strtoupper(substr($u['name'],0,1)) ?>
                        </div>
                        <strong><?= htmlspecialchars($u['name']) ?></strong>
                    </div>
                </td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge badge-review"><?= htmlspecialchars($u['role_name'] ?? 'N/A') ?></span></td>
                <td>
                    <?php if ($u['status']==='active'): ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.8rem;font-weight:600;color:var(--success);"><span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span> Active</span>
                    <?php else: ?>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.8rem;font-weight:600;color:var(--ink-soft);"><span style="width:7px;height:7px;border-radius:50%;background:currentColor;"></span> Inactive</span>
                    <?php endif; ?>
                </td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <div class="tbl-actions">
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <input type="hidden" name="status" value="<?= $u['status']==='active'?'inactive':'active' ?>">
                            <button type="submit" class="tbl-btn" title="Toggle Status">
                                <?php if ($u['status']==='active'): ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="5" width="22" height="14" rx="7" ry="7"/><circle cx="16" cy="12" r="3" fill="currentColor"/></svg>
                                <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="5" width="22" height="14" rx="7" ry="7"/><circle cx="8" cy="12" r="3" fill="currentColor"/></svg>
                                <?php endif; ?>
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

<!-- Add User Modal -->
<div class="modal-overlay" id="add-user-modal">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>Add New User</h3>
            <button class="modal-close" onclick="document.getElementById('add-user-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="settings-users.php">
                <input type="hidden" name="action" value="add_user">
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-input" required placeholder="John Doe">
                    </div>
                    <div class="field-block">
                        <label>Email <span class="req">*</span></label>
                        <input type="email" name="email" class="form-input" required placeholder="john@airtravel.com">
                    </div>
                    <div class="field-block">
                        <label>Role <span class="req">*</span></label>
                        <select name="role_id" class="form-input" required>
                            <option value="">Select Role...</option>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-block">
                        <label>Password <span class="req">*</span></label>
                        <input type="password" name="password" class="form-input" required placeholder="Min 8 characters">
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                    <button type="button" onclick="document.getElementById('add-user-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.settings-tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid var(--border); padding-bottom:0; flex-wrap:wrap; }
.stab { display:flex; align-items:center; gap:7px; padding:10px 16px; font-size:0.85rem; font-weight:600; color:var(--ink-soft); text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.15s; }
.stab svg { width:14px; height:14px; }
.stab:hover { color:var(--navy-800); }
.stab.active { color:var(--navy-800); border-bottom-color:var(--navy-800); }
</style>

<?php include 'includes/footer.php'; ?>
