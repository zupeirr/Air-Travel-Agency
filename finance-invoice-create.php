<?php
$pageTitle = 'Create Invoice';

require_once __DIR__ . '/config/db.php';

$customers = db_select("SELECT id, customer_code, first_name, last_name FROM customers WHERE status='active' ORDER BY first_name");
$bookings  = db_select("SELECT id, booking_code, booking_type, total_amount, paid_amount FROM bookings ORDER BY created_at DESC LIMIT 50"); // Simplification for UI

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $bookingId  = (int)($_POST['booking_id'] ?? 0);
    $issueDate  = trim($_POST['issue_date'] ?? date('Y-m-d'));
    $dueDate    = trim($_POST['due_date'] ?? '');
    $total      = (float)($_POST['total'] ?? 0);
    $notes      = trim($_POST['notes'] ?? '');

    if (!$customerId) $errors[] = 'Please select a customer.';
    if ($total <= 0)  $errors[] = 'Total amount must be greater than 0.';

    if (empty($errors)) {
        $invNum = generate_code('invoices', 'invoice_number', 'INV');
        $invId = db_insert("INSERT INTO invoices 
            (invoice_number, customer_id, booking_id, issue_date, due_date, total, paid_amount, status, notes)
            VALUES (?,?,?,?,?,?,0,'draft',?)",
            [$invNum, $customerId, $bookingId?:null, $issueDate, $dueDate ?: $issueDate, $total, $notes]);
        
        header("Location: finance-invoices.php");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Create Invoice</h2>
        <p>Generate a new bill for a customer</p>
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

<form method="POST" action="finance-invoice-create.php">

<div class="form-card" style="max-width:600px;">
    <div class="form-card-head"><h3>Invoice Details</h3></div>
    <div class="form-card-body">
        
        <div class="field-block">
            <label>Customer <span class="req">*</span></label>
            <select name="customer_id" class="form-input" required>
                <option value="">Select Customer...</option>
                <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ((int)($_GET['customer_id']??0) === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['first_name'].' '.$c['last_name'].' ('.$c['customer_code'].')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field-block">
            <label>Link to Booking (Optional)</label>
            <select name="booking_id" id="booking_id" class="form-input" onchange="autoFillTotal()">
                <option value="" data-bal="0">No related booking</option>
                <?php foreach ($bookings as $b): 
                    $bal = $b['total_amount'] - $b['paid_amount'];
                ?>
                <option value="<?= $b['id'] ?>" data-bal="<?= $bal ?>" <?= ((int)($_GET['booking_id']??0) === $b['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['booking_code'].' - '.ucfirst($b['booking_type'])) ?> (Bal: $<?= number_format($bal,2) ?>)
                </option>
                <?php endforeach; ?>
            </select>
            <div class="field-hint">Links this invoice to a specific booking.</div>
        </div>

        <div class="form-grid-2">
            <div class="field-block">
                <label>Issue Date <span class="req">*</span></label>
                <input type="date" name="issue_date" class="form-input" required value="<?= date('Y-m-d') ?>">
            </div>
            <div class="field-block">
                <label>Due Date</label>
                <input type="date" name="due_date" class="form-input">
            </div>
        </div>
        
        <div class="field-block">
            <label>Total Amount ($) <span class="req">*</span></label>
            <input type="number" step="0.01" name="total" id="inv_total" class="form-input" required min="0.01" style="font-size:1.1rem;font-weight:700;">
        </div>

        <div class="field-block">
            <label>Notes / Terms</label>
            <textarea name="notes" class="form-input" rows="3" placeholder="Thank you for your business…"></textarea>
        </div>

    </div>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;max-width:600px;margin-bottom:32px;">
    <a href="finance-invoices.php" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> Create Invoice</button>
</div>

</form>

<script>
function autoFillTotal() {
    const s = document.getElementById('booking_id');
    const opt = s.options[s.selectedIndex];
    if (opt && parseFloat(opt.dataset.bal) > 0) {
        document.getElementById('inv_total').value = parseFloat(opt.dataset.bal).toFixed(2);
    }
}
window.onload = function() {
    <?php if(isset($_GET['booking_id'])): ?>
    autoFillTotal();
    <?php endif; ?>
};
</script>

<?php include 'includes/footer.php'; ?>
