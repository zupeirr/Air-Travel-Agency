<?php
$pageTitle = 'Record Payment';
require_once __DIR__ . '/config/db.php';

$invoices = db_select("
    SELECT i.id, i.invoice_number, i.total, i.paid_amount, c.first_name, c.last_name 
    FROM invoices i 
    JOIN customers c ON c.id = i.customer_id 
    WHERE i.status IN ('draft','sent','partially_paid','overdue') 
    ORDER BY i.issue_date DESC");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoiceId = (int)($_POST['invoice_id'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $method    = trim($_POST['payment_method'] ?? '');
    $date      = trim($_POST['payment_date'] ?? date('Y-m-d H:i:s'));
    $notes     = trim($_POST['notes'] ?? '');

    if (!$invoiceId) $errors[] = 'Please select an invoice.';
    if ($amount <= 0) $errors[] = 'Amount must be greater than 0.';
    if (!$method) $errors[] = 'Please select a payment method.';

    if (empty($errors)) {
        // Validate amount against invoice balance
        $inv = db_row("SELECT total, paid_amount, booking_id FROM invoices WHERE id=?", [$invoiceId]);
        if ($inv) {
            $balance = $inv['total'] - $inv['paid_amount'];
            if ($amount > $balance) {
                $amount = $balance; // Don't overpay
            }
            
            $txId = generate_code('payments', 'transaction_id', 'TX');
            db_insert("INSERT INTO payments (invoice_id, transaction_id, amount, payment_date, payment_method, notes) VALUES (?,?,?,?,?,?)",
                [$invoiceId, $txId, $amount, $date, $method, $notes]);
            
            // Update Invoice
            $newPaid = $inv['paid_amount'] + $amount;
            $newStatus = ($newPaid >= $inv['total']) ? 'paid' : 'partially_paid';
            db_execute("UPDATE invoices SET paid_amount = ?, status = ? WHERE id = ?", [$newPaid, $newStatus, $invoiceId]);
            
            // Sync with Booking if exists
            if ($inv['booking_id']) {
                $bk = db_row("SELECT total_amount, paid_amount FROM bookings WHERE id=?", [$inv['booking_id']]);
                if ($bk) {
                    $bNewPaid = $bk['paid_amount'] + $amount;
                    db_execute("UPDATE bookings SET paid_amount = ? WHERE id = ?", [$bNewPaid, $inv['booking_id']]);
                }
            }

            header("Location: finance-payments.php");
            exit;
        } else {
            $errors[] = 'Invalid invoice selected.';
        }
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Record Payment</h2>
        <p>Log a received payment against an outstanding invoice</p>
    </div>
    <div class="page-header-actions">
        <a href="finance-payments.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back to Payments</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="finance-payment-add.php">

<div class="form-card" style="max-width:600px;">
    <div class="form-card-head"><h3>Payment Details</h3></div>
    <div class="form-card-body">
        
        <div class="field-block">
            <label>Select Invoice <span class="req">*</span></label>
            <select name="invoice_id" id="invoice_id" class="form-input" required onchange="autoFillAmount()">
                <option value="" data-bal="0">-- Select --</option>
                <?php foreach ($invoices as $i): 
                    $bal = $i['total'] - $i['paid_amount'];
                ?>
                <option value="<?= $i['id'] ?>" data-bal="<?= $bal ?>">
                    <?= htmlspecialchars($i['invoice_number'].' - '.$i['first_name'].' '.$i['last_name']) ?> (Due: $<?= number_format($bal,2) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid-2">
            <div class="field-block">
                <label>Amount Received ($) <span class="req">*</span></label>
                <input type="number" step="0.01" name="amount" id="pay_amount" class="form-input" required min="0.01" style="font-size:1.1rem;font-weight:700;color:var(--success);">
            </div>
            <div class="field-block">
                <label>Payment Date</label>
                <input type="datetime-local" name="payment_date" class="form-input" required value="<?= date('Y-m-d\TH:i') ?>">
            </div>
        </div>

        <div class="field-block">
            <label>Payment Method <span class="req">*</span></label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px;">
                <label style="display:flex;align-items:center;gap:8px;padding:12px;border:1px solid var(--border);border-radius:6px;cursor:pointer;">
                    <input type="radio" name="payment_method" value="bank_transfer" required> Bank Transfer
                </label>
                <label style="display:flex;align-items:center;gap:8px;padding:12px;border:1px solid var(--border);border-radius:6px;cursor:pointer;">
                    <input type="radio" name="payment_method" value="credit_card"> Credit Card
                </label>
                <label style="display:flex;align-items:center;gap:8px;padding:12px;border:1px solid var(--border);border-radius:6px;cursor:pointer;">
                    <input type="radio" name="payment_method" value="cash"> Cash
                </label>
                <label style="display:flex;align-items:center;gap:8px;padding:12px;border:1px solid var(--border);border-radius:6px;cursor:pointer;">
                    <input type="radio" name="payment_method" value="online_gateway"> Online Gateway
                </label>
            </div>
        </div>

        <div class="field-block">
            <label>Notes / Reference</label>
            <textarea name="notes" class="form-input" rows="2" placeholder="e.g. Bank Ref #123456"></textarea>
        </div>

    </div>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;max-width:600px;margin-bottom:32px;">
    <a href="finance-payments.php" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary" style="background:var(--success);border-color:var(--success);"><i data-lucide="check"></i> Record Payment</button>
</div>

</form>

<script>
function autoFillAmount() {
    const s = document.getElementById('invoice_id');
    const opt = s.options[s.selectedIndex];
    if (opt && parseFloat(opt.dataset.bal) > 0) {
        document.getElementById('pay_amount').value = parseFloat(opt.dataset.bal).toFixed(2);
    }
}
</script>

<?php include 'includes/footer.php'; ?>
