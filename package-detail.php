<?php
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: travel-packages.php'); exit; }

$pkg = db_row("
    SELECT tp.*, d.name AS destination_name, d.country, d.region
    FROM travel_packages tp
    LEFT JOIN destinations d ON d.id = tp.destination_id
    WHERE tp.id = ?", [$id]);

if (!$pkg) { header('Location: travel-packages.php'); exit; }

$pageTitle = htmlspecialchars($pkg['name']);

// Decode inclusions/exclusions if JSON
function decInc($str) {
    if (!$str) return [];
    $arr = json_decode($str, true);
    return is_array($arr) ? $arr : explode("\n", $str);
}
$inclusions = decInc($pkg['inclusions']);
$exclusions = decInc($pkg['exclusions']);

$itineraries = db_select("SELECT * FROM package_itineraries WHERE package_id = ? ORDER BY day_number", [$id]);

// Get related bookings
$bookings = db_select("
    SELECT b.*, c.first_name, c.last_name, c.customer_code
    FROM bookings b
    JOIN customers c ON c.id = b.customer_id
    WHERE b.package_id = ?
    ORDER BY b.created_at DESC", [$id]);

if (!function_exists('pImg')) {
    function pImg($url, $id) {
        if ($url) return htmlspecialchars($url);
        $imgs = ['https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=1200&q=80',
                 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1200&q=80'];
        return $imgs[$id % count($imgs)];
    }
}

include 'includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
            <code style="font-size:1rem;font-family:monospace;font-weight:700;color:var(--navy-800);"><?= htmlspecialchars($pkg['package_code']) ?></code>
            <span class="badge <?= $pkg['status']==='active'?'badge-active':($pkg['status']==='archived'?'badge-inactive':'badge-draft') ?>"><?= ucfirst($pkg['status']) ?></span>
        </div>
        <h2><?= htmlspecialchars($pkg['name']) ?></h2>
        <p><?= htmlspecialchars($pkg['destination_name']) ?>, <?= htmlspecialchars($pkg['country']) ?> · Created <?= date('d M Y', strtotime($pkg['created_at'])) ?></p>
    </div>
    <div class="page-header-actions">
        <a href="travel-packages.php" class="btn btn-ghost"><i data-lucide="arrow-left"></i> All Packages</a>
        <a href="#" class="btn btn-primary"><i data-lucide="edit"></i> Edit Package</a>
    </div>
</div>

<div class="dash-grid-3">

<!-- LEFT COLUMN (Details & Itinerary) -->
<div style="grid-column: span 2; display:flex;flex-direction:column;gap:20px;">
    
    <!-- Hero Image -->
    <div style="height:250px;border-radius:var(--radius);background:url('<?= pImg($pkg['image_url'], $pkg['id']) ?>') center/cover;box-shadow:var(--shadow);"></div>

    <!-- Details -->
    <div class="panel">
        <div class="panel-head"><h2>Package Overview</h2></div>
        <div class="panel-body">
            <div class="info-grid">
                <div class="info-row"><div class="info-label">Duration</div><div class="info-value"><?= $pkg['duration_days'] ?> Days, <?= $pkg['duration_nights'] ?> Nights</div></div>
                <div class="info-row"><div class="info-label">Base Price</div><div class="info-value" style="font-weight:700;color:var(--gold-600);">$<?= number_format($pkg['base_price'],2) ?></div></div>
                <div class="info-row"><div class="info-label">Destination</div><div class="info-value"><?= htmlspecialchars($pkg['destination_name']) ?></div></div>
                <div class="info-row"><div class="info-label">Max Group Size</div><div class="info-value"><?= $pkg['max_pax'] ?> Pax</div></div>
            </div>
            
            <div style="margin-top:20px;">
                <div class="info-label">Description</div>
                <p style="font-size:0.9rem;margin-top:6px;line-height:1.6;"><?= nl2br(htmlspecialchars($pkg['description'] ?: 'No description provided.')) ?></p>
            </div>
        </div>
    </div>

    <!-- Itinerary -->
    <div class="panel">
        <div class="panel-head">
            <h2>Itinerary</h2>
            <a href="#" class="btn btn-ghost btn-sm"><i data-lucide="plus"></i> Add Day</a>
        </div>
        <div class="panel-body">
            <?php if (empty($itineraries)): ?>
            <div class="empty-state" style="padding:24px;">No itinerary set up yet.</div>
            <?php else: ?>
            <div class="visa-timeline">
                <?php foreach ($itineraries as $it): ?>
                <div class="visa-step">
                    <div class="visa-step-dot done" style="font-weight:700;font-size:0.7rem;color:#fff;border:none;">
                        <?= $it['day_number'] ?>
                    </div>
                    <div class="visa-step-title"><?= htmlspecialchars($it['title']) ?></div>
                    <div class="visa-step-desc" style="color:var(--ink);"><?= nl2br(htmlspecialchars($it['description'])) ?></div>
                    <?php if ($it['accommodation']): ?><div class="visa-step-date">🏨 <?= htmlspecialchars($it['accommodation']) ?></div><?php endif; ?>
                    <?php if ($it['meals_included']): ?><div class="visa-step-date">🍽️ Meals: <?= htmlspecialchars($it['meals_included']) ?></div><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div> <!-- End Left Col -->

<!-- RIGHT COLUMN (Inc/Exc & Bookings) -->
<div style="display:flex;flex-direction:column;gap:20px;">
    
    <div class="panel">
        <div class="panel-head"><h2>Inclusions & Exclusions</h2></div>
        <div class="panel-body">
            <h4 style="font-size:0.85rem;color:var(--success);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg> Inclusions
            </h4>
            <?php if (empty($inclusions)): ?>
            <p style="font-size:0.8rem;color:var(--ink-soft);margin-bottom:16px;">Not specified</p>
            <?php else: ?>
            <ul style="padding-left:20px;font-size:0.85rem;margin-bottom:20px;color:var(--ink);">
                <?php foreach ($inclusions as $inc): if (trim($inc)) echo "<li>".htmlspecialchars($inc)."</li>"; endforeach; ?>
            </ul>
            <?php endif; ?>

            <h4 style="font-size:0.85rem;color:var(--danger);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg> Exclusions
            </h4>
            <?php if (empty($exclusions)): ?>
            <p style="font-size:0.8rem;color:var(--ink-soft);">Not specified</p>
            <?php else: ?>
            <ul style="padding-left:20px;font-size:0.85rem;color:var(--ink);">
                <?php foreach ($exclusions as $exc): if (trim($exc)) echo "<li>".htmlspecialchars($exc)."</li>"; endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Bookings -->
    <div class="panel">
        <div class="panel-head"><h2>Recent Bookings</h2></div>
        <div class="panel-body" style="padding-top:8px;">
            <?php if (empty($bookings)): ?>
            <div class="empty-state">No bookings for this package yet.</div>
            <?php else: ?>
            <?php foreach (array_slice($bookings,0,5) as $b): ?>
            <div style="padding:12px 0;border-bottom:1px solid var(--border);">
                <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                    <a href="customer-profile.php?id=<?= $b['customer_id'] ?>" style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($b['first_name'].' '.$b['last_name']) ?></a>
                    <span class="badge <?= ['confirmed'=>'badge-confirmed','pending'=>'badge-pending','cancelled'=>'badge-cancelled'][$b['status']]??'badge-draft' ?>"><?= ucfirst($b['status']) ?></span>
                </div>
                <div style="font-size:0.75rem;color:var(--ink-soft);display:flex;justify-content:space-between;">
                    <span>Code: <code style="font-size:0.7rem;"><?= htmlspecialchars($b['booking_code']) ?></code></span>
                    <span>Date: <?= date('d M Y', strtotime($b['travel_date'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (count($bookings)>5): ?>
            <div style="text-align:center;margin-top:12px;"><a href="bookings-all.php?package_id=<?= $id ?>" class="btn btn-ghost btn-sm">View All <?= count($bookings) ?></a></div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div> <!-- End Right Col -->

</div>

<?php include 'includes/footer.php'; ?>
