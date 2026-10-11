<?php
$pageTitle = 'New Education Application';
require_once __DIR__ . '/config/db.php';

$preCustomerId = (int)($_GET['customer_id'] ?? 0);
$customer = $preCustomerId ? db_row("SELECT id, customer_code, first_name, last_name, email, nationality FROM customers WHERE id=?", [$preCustomerId]) : null;

$institutions = db_select("SELECT * FROM education_institutions WHERE status='active' ORDER BY country, name");
$programs     = db_select("SELECT ep.*, ei.name AS institution_name, ei.country FROM education_programs ep JOIN education_institutions ei ON ei.id=ep.institution_id WHERE ep.status='active' ORDER BY ei.name, ep.name");
$officers     = db_select("SELECT id, name FROM users WHERE role_id IN (1,2,4) AND status='active' ORDER BY name");
$customers    = db_select("SELECT id, customer_code, first_name, last_name, nationality FROM customers WHERE status='active' ORDER BY first_name");

// Group programs by institution
$progByInst = [];
foreach ($programs as $p) {
    $progByInst[$p['institution_id']][] = $p;
}

$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId    = (int)($_POST['customer_id'] ?? 0);
    $institutionId = (int)($_POST['institution_id'] ?? 0);
    $programId     = (int)($_POST['program_id'] ?? 0);
    $intake        = trim($_POST['intake'] ?? '');
    $appDate       = trim($_POST['application_date'] ?? '');
    $officerId     = (int)($_POST['assigned_officer_id'] ?? 0);
    $notes         = trim($_POST['notes'] ?? '');

    if (!$customerId)    $errors[] = 'Please select a customer.';
    if (!$institutionId) $errors[] = 'Please select an institution.';
    if (!$programId)     $errors[] = 'Please select a program.';

    if (empty($errors)) {
        $code = generate_code('education_applications','application_code','EA');
        $appId = db_insert("INSERT INTO education_applications
            (application_code, customer_id, institution_id, program_id, intake, application_date,
             status, assigned_officer_id, notes)
            VALUES (?,?,?,?,?,?,'new',?,?)",
            [$code, $customerId, $institutionId, $programId,
             $intake?:null, $appDate?:null, $officerId?:null, $notes]);
        header("Location: education-detail.php?id=$appId&created=1");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>New Education Application</h2>
        <p>Register a new education/university application</p>
    </div>
    <div class="page-header-actions">
        <a href="visa-education.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="education-create.php<?= $preCustomerId?"?customer_id=$preCustomerId":'' ?>" id="edu-form">

<!-- Customer -->
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="user" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Select Customer</h3>
    </div>
    <div class="form-card-body">
        <?php if ($customer): ?>
        <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
        <div style="display:flex;align-items:center;gap:16px;padding:16px;background:#f7f6f3;border-radius:var(--radius);border:1px solid var(--border);">
            <div class="cus-avatar" style="background:var(--navy-800);width:46px;height:46px;font-size:1rem;">
                <?= strtoupper(substr($customer['first_name'],0,1).substr($customer['last_name'],0,1)) ?>
            </div>
            <div>
                <div style="font-weight:700;"><?= htmlspecialchars($customer['first_name'].' '.$customer['last_name']) ?></div>
                <div style="font-size:0.8rem;color:var(--ink-soft);"><?= htmlspecialchars($customer['customer_code']) ?> · <?= htmlspecialchars($customer['nationality'] ?? '') ?></div>
            </div>
            <a href="education-create.php" class="btn btn-ghost btn-sm" style="margin-left:auto;">Change</a>
        </div>
        <?php else: ?>
        <div class="field-block">
            <label>Customer <span class="req">*</span></label>
            <select name="customer_id" class="form-input" required>
                <option value="">-- Select Customer --</option>
                <?php foreach ($customers as $cu): ?>
                <option value="<?= $cu['id'] ?>" <?= (isset($_POST['customer_id']) && $_POST['customer_id']==$cu['id'])?'selected':'' ?>>
                    <?= htmlspecialchars($cu['first_name'].' '.$cu['last_name'].' ('.$cu['customer_code'].')') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Institution & Program -->
<div class="form-card">
    <div class="form-card-head">
        <i data-lucide="graduation-cap" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Institution & Program</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Institution <span class="req">*</span></label>
                <select name="institution_id" id="institution-select" class="form-input" required onchange="loadPrograms()">
                    <option value="">-- Select Institution --</option>
                    <?php foreach ($institutions as $inst): ?>
                    <option value="<?= $inst['id'] ?>" data-country="<?= htmlspecialchars($inst['country']) ?>"
                        <?= (isset($_POST['institution_id']) && $_POST['institution_id']==$inst['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($inst['name']) ?> (<?= htmlspecialchars($inst['country']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-block">
                <label>Program <span class="req">*</span></label>
                <select name="program_id" id="program-select" class="form-input" required>
                    <option value="">-- Select Institution First --</option>
                    <?php foreach ($programs as $p): ?>
                    <option value="<?= $p['id'] ?>" data-inst="<?= $p['institution_id'] ?>"
                        data-level="<?= htmlspecialchars($p['degree_level']) ?>"
                        data-fee="<?= $p['tuition_fee'] ?>"
                        data-currency="<?= htmlspecialchars($p['currency']) ?>"
                        style="display:none;"
                        <?= (isset($_POST['program_id']) && $_POST['program_id']==$p['id'])?'selected':'' ?>>
                        <?= htmlspecialchars($p['name']) ?> — <?= ucfirst($p['degree_level']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <!-- Program Info Card (dynamic) -->
        <div id="program-info" style="display:none;padding:14px;background:#eff6ff;border-radius:8px;border:1px solid #bfdbfe;font-size:0.87rem;margin-top:4px;">
            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <div><span style="color:var(--ink-soft);">Level:</span> <strong id="pi-level"></strong></div>
                <div><span style="color:var(--ink-soft);">Duration:</span> <strong id="pi-duration"></strong></div>
                <div><span style="color:var(--ink-soft);">Tuition:</span> <strong id="pi-fee"></strong></div>
            </div>
        </div>
        <div class="form-grid-3" style="margin-top:16px;">
            <div class="field-block">
                <label>Intake (Start)</label>
                <input type="text" name="intake" class="form-input" value="<?= htmlspecialchars($_POST['intake'] ?? '') ?>" placeholder="e.g. September 2027">
            </div>
            <div class="field-block">
                <label>Application Date</label>
                <input type="date" name="application_date" class="form-input" value="<?= htmlspecialchars($_POST['application_date'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="field-block">
                <label>Assigned Officer</label>
                <select name="assigned_officer_id" class="form-input">
                    <option value="">Auto-assign</option>
                    <?php foreach ($officers as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="field-block">
            <label>Notes</label>
            <textarea name="notes" class="form-input" rows="2" placeholder="Any notes…"><?= htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
        </div>
    </div>
</div>

<!-- Program data for JS -->
<script>
const progData = <?php
    $pData = [];
    foreach ($programs as $p) {
        $pData[$p['id']] = [
            'inst_id'  => $p['institution_id'],
            'name'     => $p['name'],
            'level'    => $p['degree_level'],
            'duration' => $p['duration_months'] ? $p['duration_months'].' months' : '—',
            'fee'      => $p['tuition_fee'] ? number_format((float)$p['tuition_fee'],0,'.',',').' '.$p['currency'] : '—'
        ];
    }
    echo json_encode($pData);
?>;

function loadPrograms() {
    const instId = document.getElementById('institution-select').value;
    const progSel = document.getElementById('program-select');
    progSel.innerHTML = '<option value="">-- Select Program --</option>';
    document.getElementById('program-info').style.display = 'none';
    if (!instId) return;
    
    Object.entries(progData).forEach(([id, p]) => {
        if (p.inst_id == instId) {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = p.name + ' — ' + p.level.charAt(0).toUpperCase() + p.level.slice(1);
            progSel.appendChild(opt);
        }
    });
}
document.getElementById('program-select').addEventListener('change', function() {
    const p = progData[this.value];
    if (p) {
        document.getElementById('pi-level').textContent    = p.level;
        document.getElementById('pi-duration').textContent = p.duration;
        document.getElementById('pi-fee').textContent      = p.fee;
        document.getElementById('program-info').style.display = '';
    } else {
        document.getElementById('program-info').style.display = 'none';
    }
});
// Init if already selected
window.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('institution-select').value) loadPrograms();
});
</script>

<div style="display:flex;gap:12px;justify-content:flex-end;margin-bottom:32px;">
    <a href="visa-education.php" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="graduation-cap"></i> Create Application</button>
</div>

</form>
<?php include 'includes/footer.php'; ?>
