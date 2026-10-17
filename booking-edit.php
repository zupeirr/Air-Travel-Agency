<?php
$pageTitle = 'Edit Booking';
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id) {
    header('Location: bookings-all.php');
    exit;
}

$b = db_row("
    SELECT b.*, c.first_name, c.last_name, c.customer_code
    FROM bookings b
    JOIN customers c ON c.id = b.customer_id
    WHERE b.id = ?", [$id]);

if (!$b) {
    header('Location: bookings-all.php');
    exit;
}

$customers = db_select("SELECT id, customer_code, first_name, last_name FROM customers WHERE status='active' ORDER BY first_name");
$packages  = db_select("SELECT id, package_code, name, base_price FROM travel_packages WHERE status='active' ORDER BY name");
$officers  = db_select("SELECT id, name FROM users WHERE status='active' ORDER BY name");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $type       = trim($_POST['booking_type'] ?? '');
    $pkgId      = (int)($_POST['package_id'] ?? 0);
    $travelDate = trim($_POST['travel_date'] ?? '');
    $returnDate = trim($_POST['return_date'] ?? '');
    $totalAmt   = (float)($_POST['total_amount'] ?? 0);
    $paidAmt    = (float)($_POST['paid_amount'] ?? 0);
    $status     = trim($_POST['status'] ?? 'pending');
    $officerId  = (int)($_POST['agent_id'] ?? 0);
    $notes      = trim($_POST['notes'] ?? '');

    if (!$customerId) $errors[] = 'Please select a customer.';
    if (!$type)       $errors[] = 'Please select a booking type.';
    if ($type === 'package' && !$pkgId) $errors[] = 'Please select a travel package.';
    if (!in_array($status, ['pending','confirmed','completed','cancelled'])) $errors[] = 'Invalid status selected.';

    if (empty($errors)) {
        db_execute("UPDATE bookings SET 
            customer_id = ?,
            booking_type = ?,
            package_id = ?,
            travel_date = ?,
            return_date = ?,
            total_amount = ?,
            paid_amount = ?,
            status = ?,
            agent_id = ?,
            notes = ?,
            updated_at = NOW()
            WHERE id = ?",
            [$customerId, $type, $pkgId?:null, $travelDate?:null, $returnDate?:null, $totalAmt, $paidAmt, $status, $officerId?:null, $notes, $id]);

        header("Location: booking-detail.php?id=$id&updated=1");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
            <code style="font-size:0.95rem;font-weight:700;"><?= htmlspecialchars($b['booking_code']) ?></code>
        </div>
        <h2>Edit Booking</h2>
        <p>Update reservation parameters, financial totals, schedule, and internal notes</p>
    </div>
    <div class="page-header-actions">
        <a href="booking-detail.php?id=<?= $id ?>" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back to Detail</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="booking-edit.php?id=<?= $id ?>">
<input type="hidden" name="id" value="<?= $id ?>">

<div class="form-card" style="max-width:840px;">
    <div class="form-card-head">
        <i data-lucide="edit-3" style="width:18px;height:18px;color:var(--navy-800);"></i>
        <h3>Booking Details</h3>
    </div>
    <div class="form-card-body">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Customer <span class="req">*</span></label>
                <select name="customer_id" class="form-input" required>
                    <option value="">Select Customer...</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= (int)$b['customer_id']===(int)$c['id']?'selected':'' ?>>
                        <?= htmlspecialchars($c['first_name'].' '.$c['last_name'].' ('.$c['customer_code'].')') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-block">
                <label>Booking Type <span class="req">*</span></label>
                <select name="booking_type" id="booking_type" class="form-input" required onchange="togglePkg()">
                    <option value="flight"     <?= $b['booking_type']==='flight'?'selected':'' ?>>Flight</option>
                    <option value="hotel"      <?= $b['booking_type']==='hotel'?'selected':'' ?>>Hotel & Stays</option>
                    <option value="car_rental" <?= $b['booking_type']==='car_rental'?'selected':'' ?>>Car Rental</option>
                    <option value="tour"       <?= $b['booking_type']==='tour'?'selected':'' ?>>Tour & Activity</option>
                    <option value="package"    <?= $b['booking_type']==='package'?'selected':'' ?>>Travel Package</option>
                </select>
            </div>
        </div>

        <div class="field-block" id="pkg-select-blk" style="display:none;background:#f7f6f3;padding:16px;border-radius:8px;">
            <label>Select Travel Package <span class="req">*</span></label>
            <select name="package_id" id="package_id" class="form-input" onchange="setPkgPrice()">
                <option value="" data-price="0">Select Package...</option>
                <?php foreach ($packages as $p): ?>
                <option value="<?= $p['id'] ?>" data-price="<?= $p['base_price'] ?>" <?= (int)$b['package_id']===(int)$p['id']?'selected':'' ?>>
                    <?= htmlspecialchars($p['name'].' — $'.number_format($p['base_price'])) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid-2">
            <div class="field-block">
                <label>Travel Date / Start Date</label>
                <input type="date" name="travel_date" class="form-input" value="<?= htmlspecialchars($b['travel_date'] ?? '') ?>">
            </div>
            <div class="field-block">
                <label>Return Date / End Date</label>
                <input type="date" name="return_date" class="form-input" value="<?= htmlspecialchars($b['return_date'] ?? '') ?>">
            </div>
        </div>

        <div class="form-grid-3">
            <div class="field-block">
                <label>Total Amount ($) <span class="req">*</span></label>
                <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-input" required value="<?= htmlspecialchars($b['total_amount']) ?>">
            </div>
            <div class="field-block">
                <label>Amount Paid So Far ($)</label>
                <input type="number" step="0.01" name="paid_amount" class="form-input" value="<?= htmlspecialchars($b['paid_amount']) ?>">
            </div>
            <div class="field-block">
                <label>Booking Status <span class="req">*</span></label>
                <select name="status" class="form-input" required>
                    <option value="pending"   <?= $b['status']==='pending'?'selected':'' ?>>Pending</option>
                    <option value="confirmed" <?= $b['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                    <option value="completed" <?= $b['status']==='completed'?'selected':'' ?>>Completed</option>
                    <option value="cancelled" <?= $b['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="field-block">
                <label>Agent / Booking Officer</label>
                <select name="agent_id" class="form-input">
                    <option value="">Select Agent...</option>
                    <?php foreach ($officers as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= (int)$b['agent_id']===(int)$o['id']?'selected':'' ?>>
                        <?= htmlspecialchars($o['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field-block">
            <label>Internal Notes</label>
            <textarea name="notes" class="form-input" rows="3" placeholder="Additional details, voucher numbers, special requests..."><?= htmlspecialchars($b['notes'] ?? '') ?></textarea>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;max-width:840px;margin-bottom:32px;">
    <a href="booking-detail.php?id=<?= $id ?>" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> Save Changes</button>
</div>

</form>

<script>
function togglePkg() {
    const t = document.getElementById('booking_type').value;
    const b = document.getElementById('pkg-select-blk');
    const s = document.getElementById('package_id');
    if (t === 'package') {
        b.style.display = 'block';
        s.required = true;
    } else {
        b.style.display = 'none';
        s.required = false;
    }
}
function setPkgPrice() {
    const s = document.getElementById('package_id');
    const opt = s.options[s.selectedIndex];
    if (opt && opt.dataset.price > 0) {
        document.getElementById('total_amount').value = opt.dataset.price;
    }
}
togglePkg();
</script>

<?php include 'includes/footer.php'; ?>
