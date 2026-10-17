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

// Sub-items queries
$flightSubItems = db_select("SELECT * FROM flight_bookings WHERE booking_id = ? ORDER BY departure_at ASC", [$id]);
$hotelSubItems  = db_select("SELECT h.*, p.company_name AS partner_name FROM hotel_bookings h LEFT JOIN partners p ON p.id = h.partner_id WHERE h.booking_id = ? ORDER BY h.check_in ASC", [$id]);
$tourSubItems   = db_select("SELECT t.*, p.company_name AS partner_name FROM tour_bookings t LEFT JOIN partners p ON p.id = t.partner_id WHERE t.booking_id = ? ORDER BY t.start_date ASC", [$id]);
$carSubItems    = db_select("SELECT * FROM car_rentals WHERE booking_id = ? ORDER BY pickup_at ASC", [$id]);

// Partners for dropdowns
$hotelPartners  = db_select("SELECT id, company_name FROM partners WHERE partner_type='hotel' AND status='active' ORDER BY company_name");
$tourPartners   = db_select("SELECT id, company_name FROM partners WHERE partner_type='tour_operator' AND status='active' ORDER BY company_name");

// Handle adding sub-items
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_flight') {
        $airline    = trim($_POST['airline'] ?? '');
        $flightNo   = trim($_POST['flight_number'] ?? '');
        $origin     = trim($_POST['origin'] ?? '');
        $dest       = trim($_POST['destination'] ?? '');
        $depAt      = trim($_POST['departure_at'] ?? '');
        $arrAt      = trim($_POST['arrival_at'] ?? '');
        $cabin      = trim($_POST['cabin_class'] ?? 'economy');
        $pnr        = trim($_POST['pnr_code'] ?? '');
        $fare       = (float)($_POST['fare'] ?? 0);

        if ($origin && $dest && $depAt && $arrAt) {
            db_insert("INSERT INTO flight_bookings (booking_id, airline, flight_number, origin, destination, departure_at, arrival_at, cabin_class, pnr_code, fare) VALUES (?,?,?,?,?,?,?,?,?,?)",
                [$id, $airline?:null, $flightNo?:null, $origin, $dest, $depAt, $arrAt, $cabin, $pnr?:null, $fare]);
            header("Location: booking-detail.php?id=$id&added_sub=1");
            exit;
        }
    }
    if ($_POST['action'] === 'add_hotel') {
        $hotelName  = trim($_POST['hotel_name'] ?? '');
        $city       = trim($_POST['city'] ?? '');
        $country    = trim($_POST['country'] ?? '');
        $stars      = (int)($_POST['stars'] ?? 0);
        $roomType   = trim($_POST['room_type'] ?? '');
        $checkIn    = trim($_POST['check_in'] ?? '');
        $checkOut   = trim($_POST['check_out'] ?? '');
        $nights     = (int)($_POST['nights'] ?? 1);
        $rooms      = (int)($_POST['rooms'] ?? 1);
        $conf       = trim($_POST['confirmation'] ?? '');
        $totalFare  = (float)($_POST['total_fare'] ?? 0);
        $partnerId  = (int)($_POST['partner_id'] ?? 0);

        if ($hotelName && $checkIn && $checkOut) {
            db_insert("INSERT INTO hotel_bookings (booking_id, hotel_name, city, country, stars, room_type, check_in, check_out, nights, rooms, confirmation, total_fare, partner_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$id, $hotelName, $city?:null, $country?:null, $stars?:null, $roomType?:null, $checkIn, $checkOut, $nights, $rooms, $conf?:null, $totalFare, $partnerId?:null]);
            header("Location: booking-detail.php?id=$id&added_sub=1");
            exit;
        }
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
        <a href="booking-edit.php?id=<?= $id ?>" class="btn btn-ghost"><i data-lucide="edit-3"></i> Edit</a>
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

    <!-- Flight Breakdown -->
    <div class="panel">
        <div class="panel-head">
            <h2>Flight Segments</h2>
            <button type="button" onclick="document.getElementById('flight-modal').classList.add('show')" class="btn btn-ghost btn-sm">
                <i data-lucide="plus"></i> Add Flight
            </button>
        </div>
        <div class="panel-body" style="padding:0;">
            <?php if (empty($flightSubItems)): ?>
            <div class="empty-state" style="padding:20px;">No flight segments attached to this booking.</div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Flight / Airline</th><th>Route</th><th>Departure</th><th>Arrival</th><th>Class & PNR</th><th>Fare</th></tr>
                </thead>
                <tbody>
                <?php foreach ($flightSubItems as $f): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;"><?= htmlspecialchars($f['airline'] ?? 'Flight') ?></div>
                        <code style="font-size:0.75rem;"><?= htmlspecialchars($f['flight_number'] ?? '—') ?></code>
                    </td>
                    <td>
                        <strong style="color:var(--navy-900);"><?= htmlspecialchars($f['origin']) ?></strong> &rarr; <strong style="color:var(--navy-900);"><?= htmlspecialchars($f['destination']) ?></strong>
                    </td>
                    <td style="font-size:0.82rem;"><?= date('d M Y, g:i A', strtotime($f['departure_at'])) ?></td>
                    <td style="font-size:0.82rem;"><?= date('d M Y, g:i A', strtotime($f['arrival_at'])) ?></td>
                    <td>
                        <span class="badge badge-draft" style="font-size:0.72rem;"><?= ucfirst($f['cabin_class'] ?? 'economy') ?></span>
                        <?php if ($f['pnr_code']): ?><div style="font-size:0.75rem;font-family:monospace;margin-top:2px;">PNR: <?= htmlspecialchars($f['pnr_code']) ?></div><?php endif; ?>
                    </td>
                    <td style="font-weight:700;color:var(--navy-900);">$<?= number_format((float)$f['fare'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hotel Breakdown -->
    <div class="panel">
        <div class="panel-head">
            <h2>Hotel & Accommodation</h2>
            <button type="button" onclick="document.getElementById('hotel-modal').classList.add('show')" class="btn btn-ghost btn-sm">
                <i data-lucide="plus"></i> Add Hotel
            </button>
        </div>
        <div class="panel-body" style="padding:0;">
            <?php if (empty($hotelSubItems)): ?>
            <div class="empty-state" style="padding:20px;">No hotel reservations attached to this booking.</div>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Hotel</th><th>Destination</th><th>Dates</th><th>Rooms / Nights</th><th>Conf #</th><th>Total</th></tr>
                </thead>
                <tbody>
                <?php foreach ($hotelSubItems as $h): ?>
                <tr>
                    <td>
                        <div style="font-weight:700;"><?= htmlspecialchars($h['hotel_name']) ?></div>
                        <?php if ($h['partner_name']): ?><div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($h['partner_name']) ?></div><?php endif; ?>
                    </td>
                    <td style="font-size:0.85rem;"><?= htmlspecialchars($h['city'].', '.$h['country']) ?></td>
                    <td style="font-size:0.82rem;"><?= date('d M', strtotime($h['check_in'])) ?> &rarr; <?= date('d M Y', strtotime($h['check_out'])) ?></td>
                    <td><?= (int)$h['rooms'] ?> rm · <?= (int)$h['nights'] ?> nts</td>
                    <td><code style="font-size:0.75rem;"><?= htmlspecialchars($h['confirmation'] ?? '—') ?></code></td>
                    <td style="font-weight:700;color:var(--navy-900);">$<?= number_format((float)$h['total_fare'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
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

<!-- Flight Modal -->
<div class="modal-overlay" id="flight-modal">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3>Add Flight Segment</h3>
            <button class="modal-close" onclick="document.getElementById('flight-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="booking-detail.php?id=<?= $id ?>">
                <input type="hidden" name="action" value="add_flight">
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Airline Name</label>
                        <input type="text" name="airline" class="form-input" placeholder="e.g. Emirates">
                    </div>
                    <div class="field-block">
                        <label>Flight Number</label>
                        <input type="text" name="flight_number" class="form-input" placeholder="e.g. EK 202">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Origin Airport / City <span class="req">*</span></label>
                        <input type="text" name="origin" class="form-input" placeholder="e.g. DXB (Dubai)" required>
                    </div>
                    <div class="field-block">
                        <label>Destination <span class="req">*</span></label>
                        <input type="text" name="destination" class="form-input" placeholder="e.g. LHR (London)" required>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Departure Date & Time <span class="req">*</span></label>
                        <input type="datetime-local" name="departure_at" class="form-input" required>
                    </div>
                    <div class="field-block">
                        <label>Arrival Date & Time <span class="req">*</span></label>
                        <input type="datetime-local" name="arrival_at" class="form-input" required>
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="field-block">
                        <label>Cabin Class</label>
                        <select name="cabin_class" class="form-input">
                            <option value="economy">Economy</option>
                            <option value="premium_economy">Premium Econ</option>
                            <option value="business">Business</option>
                            <option value="first">First Class</option>
                        </select>
                    </div>
                    <div class="field-block">
                        <label>PNR / Booking Ref</label>
                        <input type="text" name="pnr_code" class="form-input" placeholder="e.g. 6XYZ9P">
                    </div>
                    <div class="field-block">
                        <label>Flight Fare ($)</label>
                        <input type="number" step="0.01" name="fare" class="form-input" value="0.00">
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                    <button type="button" onclick="document.getElementById('flight-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> Save Flight</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hotel Modal -->
<div class="modal-overlay" id="hotel-modal">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3>Add Hotel Accommodation</h3>
            <button class="modal-close" onclick="document.getElementById('hotel-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="booking-detail.php?id=<?= $id ?>">
                <input type="hidden" name="action" value="add_hotel">
                <div class="field-block">
                    <label>Hotel Name <span class="req">*</span></label>
                    <input type="text" name="hotel_name" class="form-input" placeholder="e.g. Marriott Resort & Spa" required>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>City</label>
                        <input type="text" name="city" class="form-input" placeholder="e.g. Paris">
                    </div>
                    <div class="field-block">
                        <label>Country</label>
                        <input type="text" name="country" class="form-input" placeholder="e.g. France">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Check-in Date <span class="req">*</span></label>
                        <input type="date" name="check_in" class="form-input" required>
                    </div>
                    <div class="field-block">
                        <label>Check-out Date <span class="req">*</span></label>
                        <input type="date" name="check_out" class="form-input" required>
                    </div>
                </div>
                <div class="form-grid-3">
                    <div class="field-block">
                        <label>Nights</label>
                        <input type="number" name="nights" class="form-input" value="1" min="1">
                    </div>
                    <div class="field-block">
                        <label>Rooms</label>
                        <input type="number" name="rooms" class="form-input" value="1" min="1">
                    </div>
                    <div class="field-block">
                        <label>Star Rating</label>
                        <select name="stars" class="form-input">
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5" selected>5 Stars</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Room Type</label>
                        <input type="text" name="room_type" class="form-input" placeholder="e.g. Deluxe King Suite">
                    </div>
                    <div class="field-block">
                        <label>Total Fare ($)</label>
                        <input type="number" step="0.01" name="total_fare" class="form-input" value="0.00">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Confirmation Number</label>
                        <input type="text" name="confirmation" class="form-input" placeholder="e.g. HTL-882194">
                    </div>
                    <div class="field-block">
                        <label>Partner / Supplier</label>
                        <select name="partner_id" class="form-input">
                            <option value="">Direct / None</option>
                            <?php foreach ($hotelPartners as $hp): ?>
                            <option value="<?= $hp['id'] ?>"><?= htmlspecialchars($hp['company_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                    <button type="button" onclick="document.getElementById('hotel-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="check-circle"></i> Save Hotel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
