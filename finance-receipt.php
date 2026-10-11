<?php
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die("Invoice ID required.");
}

$inv = db_row("
    SELECT i.*, c.first_name, c.last_name, c.email, c.phone, c.address_line1, c.city, c.country
    FROM invoices i
    JOIN customers c ON c.id = i.customer_id
    WHERE i.id = ?", [$id]);

if (!$inv) {
    die("Invoice not found.");
}

$payments = db_select("SELECT * FROM payments WHERE invoice_id = ? ORDER BY payment_date ASC", [$id]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= htmlspecialchars($inv['invoice_number']) ?></title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #333; margin: 0; padding: 40px; background: #f5f6fa; }
        .receipt-container { max-width: 800px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .logo-area h1 { margin: 0 0 5px 0; color: #1e3a8a; font-size: 28px; }
        .logo-area p { margin: 0; color: #64748b; font-size: 14px; }
        .receipt-title { text-align: right; }
        .receipt-title h2 { margin: 0 0 5px 0; font-size: 32px; color: #334155; text-transform: uppercase; letter-spacing: 2px; }
        .receipt-title p { margin: 0; color: #64748b; font-size: 14px; }
        .info-section { display: flex; justify-content: space-between; margin-bottom: 40px; }
        .info-block h3 { margin: 0 0 10px 0; font-size: 14px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        .info-block p { margin: 0 0 5px 0; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th, td { padding: 12px 15px; text-align: left; }
        th { background: #f8fafc; color: #475569; font-weight: 600; font-size: 14px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        td { border-bottom: 1px solid #e2e8f0; font-size: 15px; }
        .text-right { text-align: right; }
        .totals-section { width: 300px; margin-left: auto; }
        .total-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-size: 15px; }
        .total-row.grand-total { font-size: 18px; font-weight: 700; color: #1e3a8a; border-bottom: none; border-top: 2px solid #1e3a8a; margin-top: 5px; padding-top: 15px; }
        .status-badge { display: inline-block; padding: 6px 12px; border-radius: 4px; font-size: 14px; font-weight: 600; text-transform: uppercase; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-partial { background: #fef08a; color: #854d0e; }
        .footer { margin-top: 50px; text-align: center; color: #94a3b8; font-size: 14px; border-top: 1px solid #eee; padding-top: 20px; }
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-container">
        <div class="header">
            <div class="logo-area">
                <h1>Air Travel Agency</h1>
                <p>123 Aviation Blvd, Suite 400<br>Metropolis, NY 10001<br>billing@airtravelagency.com</p>
            </div>
            <div class="receipt-title">
                <h2>RECEIPT</h2>
                <p>Invoice # <?= htmlspecialchars($inv['invoice_number']) ?></p>
                <p>Date: <?= date('M d, Y') ?></p>
            </div>
        </div>

        <div class="info-section">
            <div class="info-block">
                <h3>Billed To</h3>
                <p><strong><?= htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']) ?></strong></p>
                <?php if($inv['address_line1']): ?><p><?= htmlspecialchars($inv['address_line1']) ?></p><?php endif; ?>
                <?php if($inv['city'] || $inv['country']): ?><p><?= htmlspecialchars($inv['city'] . ', ' . $inv['country']) ?></p><?php endif; ?>
                <p><?= htmlspecialchars($inv['email']) ?></p>
            </div>
            <div class="info-block" style="text-align: right;">
                <h3>Payment Status</h3>
                <?php
                if ($inv['paid_amount'] >= $inv['total']) {
                    $statusClass = 'status-paid';
                    $statusText = 'FULLY PAID';
                } elseif ($inv['paid_amount'] > 0) {
                    $statusClass = 'status-partial';
                    $statusText = 'PARTIAL PAYMENT';
                } else {
                    $statusClass = 'status-partial';
                    $statusText = 'UNPAID';
                }
                ?>
                <div class="status-badge <?= $statusClass ?>"><?= $statusText ?></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Travel Services & Bookings</td>
                    <td class="text-right">$<?= number_format($inv['total'], 2) ?></td>
                </tr>
            </tbody>
        </table>

        <div style="display:flex; justify-content: space-between;">
            <div style="flex:1; padding-right:40px;">
                <h3 style="margin-top:0; font-size:14px; color:#94a3b8; text-transform:uppercase; letter-spacing:1px;">Payment History</h3>
                <?php if (empty($payments)): ?>
                    <p style="font-size:14px; color:#64748b;">No payments recorded.</p>
                <?php else: ?>
                    <table style="margin-bottom:0;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                                <td><?= htmlspecialchars(ucfirst($p['payment_method'])) ?></td>
                                <td class="text-right">$<?= number_format($p['amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="totals-section">
                <div class="total-row">
                    <span>Total Amount</span>
                    <span>$<?= number_format($inv['total'], 2) ?></span>
                </div>
                <div class="total-row">
                    <span>Amount Paid</span>
                    <span style="color:#166534;">-$<?= number_format($inv['paid_amount'], 2) ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Balance Due</span>
                    <span>$<?= number_format($inv['total'] - $inv['paid_amount'], 2) ?></span>
                </div>
            </div>
        </div>

        <?php if ($inv['notes']): ?>
        <div style="margin-top: 40px; padding: 20px; background: #f8fafc; border-radius: 6px;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #94a3b8; text-transform: uppercase;">Notes</h3>
            <p style="margin: 0; font-size: 14px; line-height: 1.5; color: #475569;"><?= nl2br(htmlspecialchars($inv['notes'])) ?></p>
        </div>
        <?php endif; ?>

        <div class="footer">
            Thank you for choosing Air Travel Agency. We appreciate your business!
        </div>
    </div>
</body>
</html>
