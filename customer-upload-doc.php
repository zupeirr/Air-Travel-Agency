<?php
$pageTitle = 'Upload Document';
require_once __DIR__ . '/config/db.php';

$customerId = (int)($_GET['customer_id'] ?? 0);
$visaId     = (int)($_GET['visa_id'] ?? 0); // Optional: Link directly to a visa app requirement

if (!$customerId) { header('Location: customers-directory.php'); exit; }

$customer = db_row("SELECT id, customer_code, first_name, last_name FROM customers WHERE id=?", [$customerId]);
if (!$customer) { header('Location: customers-directory.php'); exit; }

$errors = [];
$success = false;

// If a specific visa application is passed, let's load its missing requirements
$requirements = [];
if ($visaId) {
    $va = db_row("SELECT id, visa_type_id FROM visa_applications WHERE id=? AND customer_id=?", [$visaId, $customerId]);
    if ($va) {
        $requirements = db_select("
            SELECT vr.* 
            FROM visa_requirements vr
            LEFT JOIN visa_application_documents vad ON vad.requirement_id = vr.id AND vad.visa_application_id = ?
            WHERE vr.visa_type_id = ? AND vad.id IS NULL
            ORDER BY vr.sort_order
        ", [$visaId, $va['visa_type_id']]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $docType = trim($_POST['document_type'] ?? '');
    $expiry  = trim($_POST['expiry_date'] ?? '');
    $reqId   = (int)($_POST['requirement_id'] ?? 0); // For specific visa requirement
    
    if (!$docType) $errors[] = 'Please select a document type.';
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a valid file to upload.';
    }

    if (empty($errors)) {
        // Handle file upload
        $file = $_FILES['document'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','jpg','jpeg','png','doc','docx'];
        
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Invalid file format. Allowed: ' . implode(', ', $allowed);
        } else {
            // Ensure uploads directory exists
            $uploadDir = __DIR__ . '/uploads/documents/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $filename = uniqid('DOC_') . '_' . $customerId . '.' . $ext;
            $destination = $uploadDir . $filename;
            
            // Check file size limit (10MB)
            if ($file['size'] > 10 * 1024 * 1024) {
                $errors[] = 'File is too large. Maximum allowed size is 10 MB.';
            } else {
                move_uploaded_file($file['tmp_name'], $destination);
                $filePath = 'uploads/documents/' . $filename; // Relative path for DB

            // Insert into customer_documents
            $docId = db_insert("INSERT INTO customer_documents 
                (customer_id, document_type, file_name, file_path, expiry_date, status)
                VALUES (?, ?, ?, ?, ?, 'pending')",
                [$customerId, $docType, $file['name'], $filePath, $expiry?:null]);
            
            // If linked to a visa requirement, map it
            if ($visaId && $reqId) {
                db_insert("INSERT INTO visa_application_documents (visa_application_id, document_id, requirement_id) VALUES (?,?,?)",
                    [$visaId, $docId, $reqId]);
            }

            // Log activity
            db_insert("INSERT INTO activity_logs (entity_type, entity_id, action, description, user_id) VALUES (?,?,?,?,?)",
                ['customers', $customerId, 'document_uploaded', "Uploaded document: {$file['name']} ({$docType})", 1]);

            if ($visaId) {
                header("Location: visa-detail.php?id=$visaId");
            } else {
                header("Location: customer-profile.php?id=$customerId&tab=documents");
            }
            exit;
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Upload Document</h2>
        <p>For: <?= htmlspecialchars($customer['first_name'].' '.$customer['last_name']) ?> (<?= htmlspecialchars($customer['customer_code']) ?>)</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= $visaId ? "visa-detail.php?id=$visaId" : "customer-profile.php?id=$customerId&tab=documents" ?>" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i> Back
        </a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" action="customer-upload-doc.php?customer_id=<?= $customerId ?><?= $visaId?"&visa_id=$visaId":'' ?>">

<div class="form-card" style="max-width:600px;">
    <div class="form-card-head">
        <i data-lucide="upload" style="width:16px;height:16px;color:var(--navy-800);"></i>
        <h3>Document Details</h3>
    </div>
    <div class="form-card-body">
        
        <?php if (!empty($requirements)): ?>
        <div class="field-block">
            <label>Satisfy Visa Requirement (Optional)</label>
            <select name="requirement_id" class="form-input">
                <option value="">-- Standalone Document --</option>
                <?php foreach ($requirements as $req): ?>
                <option value="<?= $req['id'] ?>"><?= htmlspecialchars($req['document_name']) ?> <?= $req['is_mandatory']?'(Required)':'' ?></option>
                <?php endforeach; ?>
            </select>
            <div class="field-hint">Select a requirement to automatically link this document to the visa application.</div>
        </div>
        <?php endif; ?>

        <div class="field-block">
            <label>Document Type <span class="req">*</span></label>
            <select name="document_type" class="form-input" required>
                <option value="">Select type...</option>
                <option value="passport">Passport</option>
                <option value="national_id">National ID</option>
                <option value="photo">Photograph</option>
                <option value="bank_statement">Bank Statement</option>
                <option value="admission_letter">Admission Letter</option>
                <option value="medical_report">Medical Report</option>
                <option value="other">Other Document</option>
            </select>
        </div>

        <div class="field-block">
            <label>Select File <span class="req">*</span></label>
            <!-- Custom stylized file input -->
            <label class="upload-zone" style="display:block;margin-top:8px;">
                <input type="file" name="document" style="display:none;" id="file-input" required accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                <div class="upload-zone-icon"><i data-lucide="upload-cloud" style="width:48px;height:48px;"></i></div>
                <p><strong>Click to browse</strong> or drag file here</p>
                <p style="font-size:0.75rem;margin-top:8px;">PDF, JPG, PNG, DOC (Max 5MB)</p>
                <div id="file-name-display" style="margin-top:12px;font-weight:600;color:var(--navy-800);display:none;"></div>
            </label>
        </div>

        <div class="field-block">
            <label>Expiry Date (Optional)</label>
            <input type="date" name="expiry_date" class="form-input">
            <div class="field-hint">Useful for passports, ID cards, or time-sensitive bank statements.</div>
        </div>

    </div>
</div>

<div style="display:flex;gap:12px;max-width:600px;justify-content:flex-end;margin-bottom:32px;">
    <a href="<?= $visaId ? "visa-detail.php?id=$visaId" : "customer-profile.php?id=$customerId&tab=documents" ?>" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> Upload Document</button>
</div>

</form>

<script>
document.getElementById('file-input').addEventListener('change', function(e) {
    const display = document.getElementById('file-name-display');
    if (this.files && this.files.length > 0) {
        display.textContent = 'Selected: ' + this.files[0].name;
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
});
</script>

<?php include 'includes/footer.php'; ?>
