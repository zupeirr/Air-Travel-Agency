<?php
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: bookings-all.php'); exit; }

$b = db_row("
    SELECT b.*, c.first_name, c.last_name, c.customer_code, c.email, c.phone,
           tp.name AS package_name, tp.package_code, d.name AS destination_name,
           u.name AS agent_name
    FROM bookings b
    JOIN customers c ON c.id = b.customer_id
    LEFT JOIN travel_packages tp ON tp.id = b.package_id
    LEFT JOIN destinations d ON d.id = tp.destination_id
    LEFT JOIN users u ON u.id = b.agent_id
    WHERE b.id = ?", [$id]);

if (!$b) { header('Location: bookings-all.php'); exit; }

$pageTitle = 'Booking: '.$b['booking_code'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='update_status') {
    $newStatus = trim($_POST['new_status'] ?? '');
    if (in_array($newStatus, ['pending','confirmed','completed','cancelled'])) {
        db_execute("UPDATE bookings SET status=? WHERE id=?", [$newStatus, $id]);
        header("Location: booking-detail.php?id=$id&updated=1");
        exit;
    }
}

include 'includes/header.php';

// Invoices related to this booking
$invoices = db_select("SELECT * FROM invoices WHERE booking_id = ? ORDER BY issue_date DESC", [$id]);
$outstanding = $b['total_amount'] - $b['paid_amount'];

function bBadge($s) {
    $m=['pending'=>'badge-pending','confirmed'=>'badge-confirmed','cancelled'=>'badge-cancelled','completed'=>'badge-approved'];
    $cls = $m[$s] ?? 'badge-draft';
    return '<span class="badge '.$cls.'">'.ucfirst($s).'</span>';
}
function tIcon($t) {
    $m=['flight'=>'plane','hotel'=>'hotel','car_rental'=>'car','tour'=>'map','package'=>'package'];
    return $m[$t] ?? 'briefcase';
}
?>

<div class="page-header">
    <div class="page-header-left">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
            <code style="font-size:1rem;font-family:monospace;font-weight:700;color:var(--navy-800);"><?= htmlspecialchars($b['booking_code']) ?></code>
            <?= bBadge($b['status']) ?>
        </div>
        <h2><?= ucwords(str_replace('_',' ',$b['booking_type'])) ?> Booking</h2>
        <p>Customer: <a href="customer-profile.php?id=<?= $b['customer_id'] ?>&tab=bookings" style="color:var(--gold-600);font-weight:600;"><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></a></p>
    </div>
    <div class="page-header-actions">
        <a href="bookings-all.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> All Bookings</a>
        <button onclick="document.getElementById('status-modal').classList.add('show')" class="btn btn-primary btn-sm">
            <i data-lucide="refresh-cw"></i> Update Status
        </button>
    </div>
</div>

<div class="dash-grid-3">

<!-- LEFT COL -->
<div style="display:flex;flex-direction:column;gap:20px;grid-column:span 2;">
    <div class="panel">
        <div class="panel-head"><h2>Booking Details</h2></div>
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Type</div><div class="info-value" style="display:flex;align-items:center;gap:6px;"><i data-lucide="<?= tIcon($b['booking_type']) ?>" style="width:16px;height:16px;color:var(--navy-800);"></i> <?= ucwords(str_replace('_',' ',$b['booking_type'])) ?></div></div>
                <div class="info-row"><div class="info-label">Created</div><div class="info-value"><?= date('d M Y, g:i A', strtotime($b['created_at'])) ?></div></div>
                <div class="info-row"><div class="info-label">Travel / Start Date</div><div class="info-value"><?= $b['travel_date'] ? date('d M Y', strtotime($b['travel_date'])) : '—' ?></div></div>
                <div class="info-row"><div class="info-label">Return / End Date</div><div class="info-value"><?= $b['return_date'] ? date('d M Y', strtotime($b['return_date'])) : '—' ?></div></div>
                <div class="info-row"><div class="info-label">Total Amount</div><div class="info-value" style="font-weight:700;color:var(--navy-900);">$<?= number_format($b['total_amount'],2) ?></div></div>
                <div class="info-row"><div class="info-label">Paid Amount</div><div class="info-value" style="font-weight:700;color:<?= $outstanding>0?'var(--danger)':'var(--success)' ?>;">$<?= number_format($b['paid_amount'],2) ?></div></div>
                <div class="info-row"><div class="info-label">Agent</div><div class="info-value"><?= htmlspecialchars($b['agent_name'] ?? '—') ?></div></div>
            </div>
            
            <?php if ($b['booking_type'] === 'package' && $b['package_id']): ?>
            <div style="margin-top:20px;padding:16px;background:#f7f6f3;border-radius:8px;border:1px solid var(--border);">
                <div class="info-label" style="margin-bottom:8px;">Travel Package Included</div>
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <div style="font-weight:700;font-size:1.05rem;"><a href="package-detail.php?id=<?= $b['package_id'] ?>" style="color:var(--navy-900);"><?= htmlspecialchars($b['package_name']) ?></a></div>
                        <div style="font-size:0.8rem;color:var(--ink-soft);"><?= htmlspecialchars($b['destination_name']) ?></div>
                    </div>
                    <code style="font-size:0.8rem;"><?= htmlspecialchars($b['package_code']) ?></code>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($b['notes']): ?>
            <div style="margin-top:20px;">
                <div class="info-label">Internal Notes</div>
                <p style="font-size:0.88rem;margin-top:6px;"><?= nl2br(htmlspecialchars($b['notes'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- RIGHT COL -->
<div style="display:flex;flex-direction:column;gap:20px;">
    <!-- Customer Summary -->
    <div class="panel">
        <div class="panel-head"><h2>Customer</h2></div>
        <div class="panel-body">
            <div style="font-weight:700;font-size:1.05rem;margin-bottom:4px;"><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></div>
            <code style="font-size:0.8rem;color:var(--ink-soft);margin-bottom:12px;display:inline-block;"><?= htmlspecialchars($b['customer_code']) ?></code>
            <div style="font-size:0.85rem;margin-bottom:6px;"><i data-lucide="mail" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> <?= htmlspecialchars($b['email']) ?></div>
            <div style="font-size:0.85rem;"><i data-lucide="phone" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;"></i> <?= htmlspecialchars($b['phone'] ?? '—') ?></div>
            <div style="margin-top:16px;">
                <a href="customer-profile.php?id=<?= $b['customer_id'] ?>" class="btn btn-ghost btn-sm" style="width:100%;">View Full Profile</a>
            </div>
        </div>
    </div>

    <!-- Related Invoices -->
    <div class="panel">
        <div class="panel-head">
            <h2>Invoices</h2>
            <a href="finance-invoice-create.php?booking_id=<?= $id ?>&customer_id=<?= $b['customer_id'] ?>" class="btn btn-ghost btn-sm"><i data-lucide="plus"></i> Create</a>
        </div>
        <div class="panel-body" style="padding-top:8px;">
            <?php if (empty($invoices)): ?>
            <div class="empty-state">No invoices for this booking.</div>
            <?php else: ?>
            <?php foreach ($invoices as $inv): ?>
            <div style="padding:10px 0;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <code style="font-size:0.75rem;"><?= htmlspecialchars($inv['invoice_number']) ?></code>
                    <div style="font-weight:700;font-size:0.9rem;">$<?= number_format($inv['total'],2) ?></div>
                </div>
                <span class="badge <?= ['draft'=>'badge-draft','sent'=>'badge-review','paid'=>'badge-approved','overdue'=>'badge-rejected'][$inv['status']]??'badge-draft' ?>"><?= ucfirst($inv['status']) ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>

<!-- Status Modal -->
<div class="modal-overlay" id="status-modal">
    <div class="modal-content" style="max-width:400px;">
        <div class="modal-header">
            <h3>Update Booking Status</h3>
            <button class="modal-close" onclick="document.getElementById('status-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="booking-detail.php?id=<?= $id ?>">
                <input type="hidden" name="action" value="update_status">
                <div class="field-block">
                    <label>New Status</label>
                    <select name="new_status" class="form-input" required>
                        <?php foreach (['pending','confirmed','completed','cancelled'] as $st): ?>
                        <option value="<?= $st ?>" <?= $b['status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                    <button type="button" onclick="document.getElementById('status-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
