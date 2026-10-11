<?php
$pageTitle = 'New Booking';

require_once __DIR__ . '/config/db.php';

$customers = db_select("SELECT id, customer_code, first_name, last_name FROM customers WHERE status='active' ORDER BY first_name");
$packages  = db_select("SELECT id, package_code, name, base_price FROM travel_packages WHERE status='active' ORDER BY name");
$officers  = db_select("SELECT id, name FROM users WHERE status='active' ORDER BY name");

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId  = (int)($_POST['customer_id'] ?? 0);
    $type        = trim($_POST['booking_type'] ?? '');
    $pkgId       = (int)($_POST['package_id'] ?? 0);
    $travelDate  = trim($_POST['travel_date'] ?? '');
    $returnDate  = trim($_POST['return_date'] ?? '');
    $totalAmt    = (float)($_POST['total_amount'] ?? 0);
    $paidAmt     = (float)($_POST['paid_amount'] ?? 0);
    $officerId   = (int)($_POST['agent_id'] ?? 0);
    $notes       = trim($_POST['notes'] ?? '');

    if (!$customerId) $errors[] = 'Please select a customer.';
    if (!$type) $errors[] = 'Please select a booking type.';
    if ($type === 'package' && !$pkgId) $errors[] = 'Please select a travel package.';

    if (empty($errors)) {
        $code = generate_code('bookings','booking_code','BK');
        $bId = db_insert("INSERT INTO bookings 
            (booking_code, customer_id, booking_type, package_id, travel_date, return_date, total_amount, paid_amount, status, agent_id, notes)
            VALUES (?,?,?,?,?,?,?,?,'pending',?,?)",
            [$code, $customerId, $type, $pkgId?:null, $travelDate?:null, $returnDate?:null, $totalAmt, $paidAmt, $officerId?:null, $notes]);
        
        header("Location: booking-detail.php?id=$bId&created=1");
        exit;
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>New Booking</h2>
        <p>Record a new flight, hotel, tour, or package booking</p>
    </div>
    <div class="page-header-actions">
        <a href="bookings-all.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> Back to Bookings</a>
    </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
    <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" action="booking-create.php">

<div class="form-card" style="max-width:800px;">
    <div class="form-card-head"><h3>Booking Details</h3></div>
    <div class="form-card-body">
        <div class="form-grid-2">
            <div class="field-block">
                <label>Customer <span class="req">*</span></label>
                <select name="customer_id" class="form-input" required>
                    <option value="">Select Customer...</option>
                    <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['first_name'].' '.$c['last_name'].' ('.$c['customer_code'].')') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field-block">
                <label>Booking Type <span class="req">*</span></label>
                <select name="booking_type" id="booking_type" class="form-input" required onchange="togglePkg()">
                    <option value="">Select Type...</option>
                    <option value="flight">Flight</option>
                    <option value="hotel">Hotel & Stays</option>
                    <option value="car_rental">Car Rental</option>
                    <option value="tour">Tour & Activity</option>
                    <option value="package">Travel Package</option>
                </select>
            </div>
        </div>

        <div class="field-block" id="pkg-select-blk" style="display:none;background:#f7f6f3;padding:16px;border-radius:8px;">
            <label>Select Travel Package <span class="req">*</span></label>
            <select name="package_id" id="package_id" class="form-input" onchange="setPkgPrice()">
                <option value="" data-price="0">Select Package...</option>
                <?php foreach ($packages as $p): ?>
                <option value="<?= $p['id'] ?>" data-price="<?= $p['base_price'] ?>"><?= htmlspecialchars($p['name'].' — $'.number_format($p['base_price'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-grid-2">
            <div class="field-block">
                <label>Travel Date / Start Date</label>
                <input type="date" name="travel_date" class="form-input">
            </div>
            <div class="field-block">
                <label>Return Date / End Date</label>
                <input type="date" name="return_date" class="form-input">
            </div>
        </div>
        
        <div class="form-grid-2">
            <div class="field-block">
                <label>Total Amount ($) <span class="req">*</span></label>
                <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-input" required value="0">
            </div>
            <div class="field-block">
                <label>Amount Paid So Far ($)</label>
                <input type="number" step="0.01" name="paid_amount" class="form-input" value="0">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="field-block">
                <label>Agent / Booking Officer</label>
                <select name="agent_id" class="form-input">
                    <option value="">Select Agent...</option>
                    <?php foreach ($officers as $o): ?>
                    <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="field-block">
            <label>Notes</label>
            <textarea name="notes" class="form-input" rows="3" placeholder="Additional details..."></textarea>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;justify-content:flex-end;max-width:800px;margin-bottom:32px;">
    <a href="bookings-all.php" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> Create Booking</button>
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
        s.value = '';
    }
}
function setPkgPrice() {
    const s = document.getElementById('package_id');
    const opt = s.options[s.selectedIndex];
    if (opt && opt.dataset.price > 0) {
        document.getElementById('total_amount').value = opt.dataset.price;
    }
}
// Init
togglePkg();
</script>

<?php include 'includes/footer.php'; ?>
