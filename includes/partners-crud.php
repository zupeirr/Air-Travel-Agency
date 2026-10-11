<?php
/**
 * partners-crud.php — View/HTML portion only (no redirects)
 * POST actions are handled in partners-actions.php (included before header.php)
 */

// ── Fetch List ────────────────────────────────────────────────
$search  = trim($_GET['q'] ?? '');
$statusF = trim($_GET['status'] ?? '');

$where  = ['p.type = ?'];
$params = [$partnerType];

if ($search) {
    $where[]  = '(p.name LIKE ? OR p.contact_name LIKE ? OR p.country LIKE ?)';
    $s = "%$search%";
    $params   = array_merge($params, [$s, $s, $s]);
}
if ($statusF) {
    $where[]  = 'p.status = ?';
    $params[] = $statusF;
}

$whereSQL = implode(' AND ', $where);
$partners = db_select("SELECT p.* FROM partners p WHERE $whereSQL ORDER BY p.name", $params);

// Edit pre-load
$editPartner = null;
if (isset($_GET['edit'])) {
    $editPartner = db_row("SELECT * FROM partners WHERE id=? AND type=?", [(int)$_GET['edit'], $partnerType]);
}

function pBadge($s) {
    $m = ['active' => 'badge-approved', 'inactive' => 'badge-inactive', 'pending' => 'badge-pending'];
    return '<span class="badge '.($m[$s] ?? 'badge-draft').'">'.ucfirst($s).'</span>';
}
?>

<?php if (isset($_GET['saved'])): ?>
<div class="alert alert-success" style="margin-bottom:16px;"><i data-lucide="check-circle"></i> Partner saved successfully!</div>
<?php elseif (isset($_GET['deleted'])): ?>
<div class="alert alert-error" style="margin-bottom:16px;"><i data-lucide="trash-2"></i> Partner deleted.</div>
<?php endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div class="page-header-left">
        <h2><?= htmlspecialchars($pageHeading) ?></h2>
        <p><?= htmlspecialchars($pageDesc) ?></p>
    </div>
    <div class="page-header-actions">
        <button onclick="document.getElementById('add-modal').classList.add('show')" class="btn btn-primary">
            <i data-lucide="plus"></i> Add Partner
        </button>
    </div>
</div>

<!-- Search Bar -->
<form method="GET">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, contact, country…">
    </div>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['active','inactive','pending'] as $st): ?>
        <option value="<?= $st ?>" <?= $statusF===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($search || $statusF): ?>
    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
    <span class="sfbar-count"><?= count($partners) ?> partner<?= count($partners)!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<!-- Partners Table -->
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($partners)): ?>
        <div class="empty-state" style="padding:48px;">
            <i data-lucide="users" style="width:40px;height:40px;opacity:0.3;margin-bottom:12px;"></i>
            <div>No <?= strtolower($pageHeading) ?> found.</div>
            <button onclick="document.getElementById('add-modal').classList.add('show')" class="btn btn-primary btn-sm" style="margin-top:12px;">Add First Partner</button>
        </div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Country</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Commission %</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($partners as $p): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if($p['city']): ?><div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($p['city']) ?></div><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($p['country'] ?? '—') ?></td>
                <td><?= htmlspecialchars($p['contact_name'] ?? '—') ?></td>
                <td><?= $p['contact_email'] ? '<a href="mailto:'.htmlspecialchars($p['contact_email']).'" style="color:var(--navy-800);">'.htmlspecialchars($p['contact_email']).'</a>' : '—' ?></td>
                <td><?= htmlspecialchars($p['contact_phone'] ?? '—') ?></td>
                <td><?= $p['commission_rate'] > 0 ? number_format($p['commission_rate'],2).'%' : '—' ?></td>
                <td><?= pBadge($p['status']) ?></td>
                <td>
                    <div class="tbl-actions">
                        <!-- Edit -->
                        <button type="button" class="tbl-btn" title="Edit"
                            onclick="openEdit(<?= htmlspecialchars(json_encode($p)) ?>)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        </button>
                        <!-- Delete -->
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete partner: <?= htmlspecialchars(addslashes($p['name'])) ?>?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button type="submit" class="tbl-btn danger" title="Delete">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6l-1 14H6L5 6"></path><path d="M10 11v6M14 11v6"></path><path d="M9 6V4h6v2"></path></svg>
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

<!-- ── ADD Modal ──────────────────────────────────────────── -->
<div class="modal-overlay" id="add-modal">
    <div class="modal-content" style="max-width:640px;">
        <div class="modal-header">
            <h3>Add Partner</h3>
            <button class="modal-close" onclick="document.getElementById('add-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <?= partnerFormFields(null) ?>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="document.getElementById('add-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── EDIT Modal ─────────────────────────────────────────── -->
<div class="modal-overlay" id="edit-modal">
    <div class="modal-content" style="max-width:640px;">
        <div class="modal-header">
            <h3>Edit Partner</h3>
            <button class="modal-close" onclick="document.getElementById('edit-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="edit-form">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="form-grid-2">
                    <div class="field-block" style="grid-column:span 2;">
                        <label>Partner Name <span class="req">*</span></label>
                        <input type="text" name="name" id="edit-name" class="form-input" required>
                    </div>
                    <div class="field-block">
                        <label>Country</label>
                        <input type="text" name="country" id="edit-country" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>City</label>
                        <input type="text" name="city" id="edit-city" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>Contact Person</label>
                        <input type="text" name="contact_name" id="edit-contact_name" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>Contact Email</label>
                        <input type="email" name="contact_email" id="edit-contact_email" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>Contact Phone</label>
                        <input type="text" name="contact_phone" id="edit-contact_phone" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>Website</label>
                        <input type="url" name="website" id="edit-website" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>Commission Rate (%)</label>
                        <input type="number" step="0.01" name="commission_rate" id="edit-commission_rate" class="form-input" min="0" max="100">
                    </div>
                    <div class="field-block">
                        <label>Contract Start</label>
                        <input type="date" name="contract_start" id="edit-contract_start" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>Contract End</label>
                        <input type="date" name="contract_end" id="edit-contract_end" class="form-input">
                    </div>
                    <div class="field-block">
                        <label>Status</label>
                        <select name="status" id="edit-status" class="form-input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="field-block" style="grid-column:span 2;">
                        <label>Notes</label>
                        <textarea name="notes" id="edit-notes" class="form-input" rows="2"></textarea>
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="document.getElementById('edit-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Update Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEdit(p) {
    document.getElementById('edit-id').value          = p.id;
    document.getElementById('edit-name').value        = p.name || '';
    document.getElementById('edit-country').value     = p.country || '';
    document.getElementById('edit-city').value        = p.city || '';
    document.getElementById('edit-contact_name').value  = p.contact_name || '';
    document.getElementById('edit-contact_email').value = p.contact_email || '';
    document.getElementById('edit-contact_phone').value = p.contact_phone || '';
    document.getElementById('edit-website').value     = p.website || '';
    document.getElementById('edit-commission_rate').value = p.commission_rate || '0';
    document.getElementById('edit-contract_start').value  = p.contract_start || '';
    document.getElementById('edit-contract_end').value    = p.contract_end || '';
    document.getElementById('edit-status').value      = p.status || 'pending';
    document.getElementById('edit-notes').value       = p.notes || '';
    document.getElementById('edit-modal').classList.add('show');
    if (window.lucide) lucide.createIcons();
}
</script>

<?php
// ── Shared add-form helper ────────────────────────────────────
function partnerFormFields($p) {
    ob_start(); ?>
    <div class="form-grid-2">
        <div class="field-block" style="grid-column:span 2;">
            <label>Partner Name <span class="req">*</span></label>
            <input type="text" name="name" class="form-input" required value="<?= htmlspecialchars($p['name'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Country</label>
            <input type="text" name="country" class="form-input" value="<?= htmlspecialchars($p['country'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>City</label>
            <input type="text" name="city" class="form-input" value="<?= htmlspecialchars($p['city'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Contact Person</label>
            <input type="text" name="contact_name" class="form-input" value="<?= htmlspecialchars($p['contact_name'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Contact Email</label>
            <input type="email" name="contact_email" class="form-input" value="<?= htmlspecialchars($p['contact_email'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Contact Phone</label>
            <input type="text" name="contact_phone" class="form-input" value="<?= htmlspecialchars($p['contact_phone'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Website</label>
            <input type="url" name="website" class="form-input" value="<?= htmlspecialchars($p['website'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Commission Rate (%)</label>
            <input type="number" step="0.01" name="commission_rate" class="form-input" min="0" max="100" value="<?= $p['commission_rate'] ?? '0' ?>">
        </div>
        <div class="field-block">
            <label>Contract Start</label>
            <input type="date" name="contract_start" class="form-input" value="<?= htmlspecialchars($p['contract_start'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Contract End</label>
            <input type="date" name="contract_end" class="form-input" value="<?= htmlspecialchars($p['contract_end'] ?? '') ?>">
        </div>
        <div class="field-block">
            <label>Status</label>
            <select name="status" class="form-input">
                <option value="active" <?= ($p['status']??'')==='active'?'selected':'' ?>>Active</option>
                <option value="inactive" <?= ($p['status']??'')==='inactive'?'selected':'' ?>>Inactive</option>
                <option value="pending" <?= ($p['status']??'pending')==='pending'?'selected':'' ?>>Pending</option>
            </select>
        </div>
        <div class="field-block" style="grid-column:span 2;">
            <label>Notes</label>
            <textarea name="notes" class="form-input" rows="2"><?= htmlspecialchars($p['notes'] ?? '') ?></textarea>
        </div>
    </div>
    <?php return ob_get_clean();
}
?>
