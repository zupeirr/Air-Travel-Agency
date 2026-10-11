<?php
$pageTitle = 'Create Travel Package';
require_once __DIR__ . '/config/db.php';

$destinations = db_select("SELECT * FROM destinations WHERE is_active=1 ORDER BY country, name");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $destId       = (int)($_POST['destination_id'] ?? 0);
    $durDays      = (int)($_POST['duration_days'] ?? 0);
    $durNights    = (int)($_POST['duration_nights'] ?? 0);
    $basePrice    = (float)($_POST['base_price'] ?? 0);
    $maxPax       = (int)($_POST['max_pax'] ?? 0);
    $status       = trim($_POST['status'] ?? 'draft');
    $description  = trim($_POST['description'] ?? '');
    $inclusions   = trim($_POST['inclusions'] ?? '');
    $exclusions   = trim($_POST['exclusions'] ?? '');

    if (!$name) $errors[] = 'Package name is required.';
    if (!$destId) $errors[] = 'Please select a destination.';
    if ($durDays <= 0) $errors[] = 'Duration (Days) must be greater than 0.';
    if ($basePrice <= 0) $errors[] = 'Base price must be greater than 0.';

    if (empty($errors)) {
        $code = generate_code('travel_packages','package_code','PKG');
        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $slug = ($rawSlug ?: 'pkg') . '-' . strtolower($code);
        $userId = $_SESSION['user_id'] ?? null;

        $pkgId = db_insert("INSERT INTO travel_packages 
            (package_code, name, slug, destination_id, description, duration_days, duration_nights, price, inclusions, exclusions, max_pax, status, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
            [$code, $name, $slug, $destId, $description, $durDays, $durNights, $basePrice, $inclusions, $exclusions, $maxPax, $status, $userId]);
        
        header("Location: package-detail.php?id=$pkgId&created=1");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Create Travel Package</h2>
        <p>Design a new holiday package or tour</p>
    </div>
    <div class="page-header-actions">
        <a href="travel-packages.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back to Packages</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="package-create.php">

<div class="dash-grid-2">
    <!-- Left Column -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="form-card">
            <div class="form-card-head"><h3>Basic Information</h3></div>
            <div class="form-card-body">
                <div class="field-block">
                    <label>Package Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required placeholder="e.g. 7 Days Alpine Adventure">
                </div>
                <div class="field-block">
                    <label>Primary Destination <span class="req">*</span></label>
                    <select name="destination_id" class="form-input" required>
                        <option value="">Select Destination...</option>
                        <?php foreach ($destinations as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (isset($_POST['destination_id']) && $_POST['destination_id']==$d['id'])?'selected':'' ?>>
                            <?= htmlspecialchars($d['name']) ?> (<?= htmlspecialchars($d['country']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-block">
                    <label>Description</label>
                    <textarea name="description" class="form-input" rows="5" placeholder="Engaging description of the package…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="form-card">
            <div class="form-card-head"><h3>Package Details</h3></div>
            <div class="form-card-body">
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Duration (Days) <span class="req">*</span></label>
                        <input type="number" name="duration_days" class="form-input" value="<?= htmlspecialchars($_POST['duration_days'] ?? '') ?>" required min="1">
                    </div>
                    <div class="field-block">
                        <label>Duration (Nights)</label>
                        <input type="number" name="duration_nights" class="form-input" value="<?= htmlspecialchars($_POST['duration_nights'] ?? '') ?>" min="0">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Base Price ($) <span class="req">*</span></label>
                        <input type="number" step="0.01" name="base_price" class="form-input" value="<?= htmlspecialchars($_POST['base_price'] ?? '') ?>" required min="0">
                    </div>
                    <div class="field-block">
                        <label>Max Pax</label>
                        <input type="number" name="max_pax" class="form-input" value="<?= htmlspecialchars($_POST['max_pax'] ?? '10') ?>" min="1">
                        <div class="field-hint">Maximum group size</div>
                    </div>
                </div>
                <div class="field-block">
                    <label>Status</label>
                    <select name="status" class="form-input">
                        <option value="draft" <?= (isset($_POST['status']) && $_POST['status']==='draft')?'selected':'' ?>>Draft</option>
                        <option value="active" <?= (isset($_POST['status']) && $_POST['status']==='active')?'selected':'' ?>>Active</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-card">
            <div class="form-card-head"><h3>Inclusions & Exclusions</h3></div>
            <div class="form-card-body">
                <div class="field-block">
                    <label>Inclusions (JSON format or bullet points)</label>
                    <textarea name="inclusions" class="form-input" rows="3" placeholder='["Hotel Accommodation", "Breakfast", "Airport Transfers"]'><?= htmlspecialchars($_POST['inclusions'] ?? '') ?></textarea>
                </div>
                <div class="field-block">
                    <label>Exclusions (JSON format or bullet points)</label>
                    <textarea name="exclusions" class="form-input" rows="3" placeholder='["Flights", "Personal Expenses", "Visa Fees"]'><?= htmlspecialchars($_POST['exclusions'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;margin-bottom:32px;">
    <a href="travel-packages.php" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> Create Package</button>
</div>

</form>

<?php include 'includes/footer.php'; ?>
