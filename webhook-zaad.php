<?php
/**
 * Telesom Zaad Service API Webhook Callback Handler
 * Receives incoming mobile payment confirmation notifications from Zaad Merchant API
 */
require_once __DIR__ . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');
$payload  = json_decode($rawInput, true);

// Accept JSON payload or standard POST fields
if (!$payload && !empty($_POST)) {
    $payload = $_POST;
}

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Empty payload received']);
    exit;
}

// Typical Zaad Merchant API callback fields:
// transactionId / txId, referenceNo / invoiceNumber, amount, senderPhone, status
$txId          = trim($payload['transactionId'] ?? ($payload['tx_id'] ?? ($payload['TransactionId'] ?? '')));
$invoiceNumber = trim($payload['referenceNo'] ?? ($payload['invoice_number'] ?? ($payload['InvoiceNo'] ?? '')));
$amount        = (float)($payload['amount'] ?? ($payload['Amount'] ?? 0));
$senderPhone   = trim($payload['senderPhone'] ?? ($payload['phone'] ?? ($payload['MobileNo'] ?? '')));
$paymentStatus = strtolower(trim($payload['status'] ?? ($payload['Status'] ?? 'completed')));

if (!$invoiceNumber || $amount <= 0) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Missing invoice reference or invalid amount']);
    exit;
}

// Find invoice
$inv = db_row("SELECT * FROM invoices WHERE invoice_number = ?", [$invoiceNumber]);

if (!$inv) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Invoice not found: ' . $invoiceNumber]);
    exit;
}

// Verify transaction ID uniqueness
$existingPay = null;
if ($txId) {
    $existingPay = db_row("SELECT id FROM payments WHERE transaction_id = ?", [$txId]);
}

if ($existingPay) {
    // Already recorded (idempotent response)
    echo json_encode(['status' => 'success', 'message' => 'Transaction already processed']);
    exit;
}

$genTxId = $txId ?: generate_code('payments', 'transaction_id', 'ZD');
$notes = 'Zaad Mobile Money payment received via Merchant Webhook';
if ($senderPhone) {
    $notes .= ' from ' . $senderPhone;
}

// Insert Payment
$payId = db_insert(
    "INSERT INTO payments (invoice_id, customer_id, amount, currency, method, status, transaction_id, gateway, payment_date, notes) 
     VALUES (?, ?, ?, 'USD', 'mobile_money', 'completed', ?, 'Zaad Service', CURDATE(), ?)",
    [$inv['id'], $inv['customer_id'], $amount, $genTxId, $notes]
);

// Update Invoice Paid Amount and Status
$newPaidAmount = (float)$inv['paid_amount'] + $amount;
$newInvStatus  = ($newPaidAmount >= (float)$inv['total']) ? 'paid' : 'partially_paid';

db_execute("UPDATE invoices SET paid_amount = ?, status = ? WHERE id = ?", [$newPaidAmount, $newInvStatus, $inv['id']]);

// Synchronize connected Booking if applicable
if ($inv['booking_id']) {
    $bk = db_row("SELECT total_amount, paid_amount FROM bookings WHERE id = ?", [$inv['booking_id']]);
    if ($bk) {
        $bkNewPaid = (float)$bk['paid_amount'] + $amount;
        $bkStatus  = ($bkNewPaid >= (float)$bk['total_amount']) ? 'confirmed' : 'pending';
        db_execute("UPDATE bookings SET paid_amount = ?, status = ? WHERE id = ?", [$bkNewPaid, $bkStatus, $inv['booking_id']]);
    }
}

// Audit Log
db_insert(
    "INSERT INTO activity_logs (entity_type, entity_id, action, description) VALUES (?, ?, ?, ?)",
    ['payments', $payId, 'zaad_payment_received', "Automated Zaad payment of \${$amount} received for invoice {$invoiceNumber} (Tx: {$genTxId})"]
);

echo json_encode([
    'status'         => 'success',
    'message'        => 'Zaad payment successfully credited',
    'transaction_id' => $genTxId,
    'invoice_status' => $newInvStatus,
    'paid_amount'    => $newPaidAmount
]);
exit;
