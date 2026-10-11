<?php
$pageTitle = 'Edit Invoice';
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: finance-invoices.php");
    exit;
}

$inv = db_row("SELECT i.*, c.first_name, c.last_name, c.customer_code FROM invoices i JOIN customers c ON c.id = i.customer_id WHERE i.id = ?", [$id]);

if (!$inv) {
    header("Location: finance-invoices.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? '');
    
    // In a real scenario, you might also want to update paid_amount based on status, but we will leave paid_amount alone
    // or set paid_amount = total if marked 'paid', and 0 if marked 'draft' / 'unpaid'.
    $paidAmount = $inv['paid_amount'];
    if ($status === 'paid' && $paidAmount < $inv['total']) {
        $paidAmount = $inv['total'];
    } elseif ($status === 'draft' || $status === 'sent') {
        // user might be resetting to unpaid
        if (isset($_POST['reset_paid'])) {
            $paidAmount = 0;
            // Also delete related payments? For simplicity, we just update the invoice total.
            db_execute("DELETE FROM payments WHERE invoice_id = ?", [$id]);
        }
    }
    
    if (empty($errors)) {
        db_execute("UPDATE invoices SET status = ?, paid_amount = ? WHERE id = ?", [$status, $paidAmount, $id]);
        header("Location: finance-invoices.php?updated=1");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Edit Invoice: <?= htmlspecialchars($inv['invoice_number']) ?></h2>
        <p>Customer: <?= htmlspecialchars($inv['first_name'].' '.$inv['last_name']) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="finance-invoices.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back to Invoices</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="finance-invoice-edit.php?id=<?= $id ?>">

<div class="form-card" style="max-width:500px;">
    <div class="form-card-head"><h3>Invoice Status</h3></div>
    <div class="form-card-body">
        
        <div class="info-row" style="margin-bottom:20px;">
            <div class="info-label">Total Amount:</div>
            <div class="info-value"><strong>$<?= number_format($inv['total'], 2) ?></strong></div>
        </div>
        
        <div class="field-block">
            <label>Status</label>
            <select name="status" class="form-input" required>
                <?php foreach (['draft' => 'Draft (Unpaid)', 'sent' => 'Sent (Unpaid)', 'partially_paid' => 'Partially Paid', 'paid' => 'Paid', 'overdue' => 'Overdue', 'cancelled' => 'Cancelled'] as $val => $label): ?>
                <option value="<?= $val ?>" <?= $inv['status']===$val?'selected':'' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <div class="field-hint">Marking as 'Paid' will automatically update the paid amount to the full total.</div>
        </div>
        
        <div class="field-block">
            <label style="display:flex; align-items:center; gap:8px;">
                <input type="checkbox" name="reset_paid" value="1">
                Reset paid amount to $0 (if changing back to unpaid)
            </label>
            <div class="field-hint" style="color:var(--danger);">This will also delete all payment records for this invoice!</div>
        </div>

    </div>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;max-width:500px;margin-bottom:32px;">
    <a href="finance-invoices.php" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save Changes</button>
</div>

</form>

<?php include 'includes/footer.php'; ?>
