<?php
$pageTitle = 'Travel Packages';
include 'includes/header.php';

$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage= 24;
$offset = ($page-1) * $perPage;

$where  = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(tp.name LIKE ? OR tp.package_code LIKE ? OR d.name LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s,$s,$s]);
}
if ($status) { $where[] = 'tp.status = ?'; $params[] = $status; }

$whereSQL = implode(' AND ', $where);

$totalFiltered = db_count("
    SELECT COUNT(*) FROM travel_packages tp
    LEFT JOIN destinations d ON d.id = tp.destination_id
    WHERE $whereSQL", $params);
$totalPages = max(1, ceil($totalFiltered/$perPage));

$packages = db_select("
    SELECT tp.*, d.name AS destination_name, d.country
    FROM travel_packages tp
    LEFT JOIN destinations d ON d.id = tp.destination_id
    WHERE $whereSQL
    ORDER BY tp.created_at DESC
    LIMIT $perPage OFFSET $offset", $params);

if (!function_exists('pImg')) {
    function pImg($url, $id) {
        if ($url) return htmlspecialchars($url);
        $imgs = [
            'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1506929562872-bb421503ef21?auto=format&fit=crop&w=600&q=80',
            'https://images.unsplash.com/photo-1500835556837-99ac94a94552?auto=format&fit=crop&w=600&q=80'
        ];
        return $imgs[$id % count($imgs)];
    }
}
?>

<style>
.pkg-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
.pkg-card {
    background: #fff;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
}
.pkg-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); border-color: var(--navy-800); }
.pkg-img {
    height: 180px;
    background-size: cover;
    background-position: center;
    position: relative;
}
.pkg-badge {
    position: absolute;
    top: 12px; right: 12px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    backdrop-filter: blur(4px);
}
.pkg-content { padding: 18px; flex: 1; display: flex; flex-direction: column; }
.pkg-title { font-size: 1.05rem; font-weight: 700; color: var(--navy-900); margin-bottom: 6px; }
.pkg-dest  { font-size: 0.8rem; color: var(--ink-soft); display: flex; align-items: center; gap: 4px; margin-bottom: 12px; }
.pkg-dest svg { width: 14px; height: 14px; }
.pkg-meta  { display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 16px; color: var(--ink); font-weight: 500; }
.pkg-price { font-size: 1.25rem; font-weight: 800; color: var(--gold-600); }
.pkg-footer{
    padding: 14px 18px;
    border-top: 1px solid var(--border);
    background: #fafaf8;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pkg-status.active { color: var(--success); font-weight: 600; font-size: 0.75rem; display: flex; align-items: center; gap:4px; }
.pkg-status.draft  { color: var(--ink-soft); font-weight: 600; font-size: 0.75rem; display: flex; align-items: center; gap:4px; }
</style>

<div class="page-header">
    <div class="page-header-left">
        <h2>Travel Packages</h2>
        <p>Manage curated holiday packages and tours</p>
    </div>
    <div class="page-header-actions">
        <a href="destinations.php" class="btn btn-ghost"><i data-lucide="map"></i> Destinations</a>
        <a href="package-create.php" class="btn btn-primary"><i data-lucide="plus"></i> Create Package</a>
    </div>
</div>

<form method="GET" action="travel-packages.php">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search packages, destinations…">
    </div>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
        <option value="draft"  <?= $status==='draft'?'selected':'' ?>>Draft</option>
        <option value="archived" <?= $status==='archived'?'selected':'' ?>>Archived</option>
    </select>
    <?php if ($search||$status): ?>
    <a href="travel-packages.php" class="btn btn-ghost btn-sm">Clear</a>
    <?php endif; ?>
    <span class="sfbar-count"><?= number_format($totalFiltered) ?> result<?= $totalFiltered!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>

<?php if (empty($packages)): ?>
<div class="panel">
    <div class="panel-body"><div class="empty-state" style="padding:40px;">No travel packages found.</div></div>
</div>
<?php else: ?>
<div class="pkg-grid">
    <?php foreach ($packages as $pkg): ?>
    <div class="pkg-card">
        <div class="pkg-img" style="background-image:url('<?= pImg($pkg['image_url'], $pkg['id']) ?>');">
            <div class="pkg-badge"><?= $pkg['duration_days'] ?> Days / <?= $pkg['duration_nights'] ?> Nights</div>
        </div>
        <div class="pkg-content">
            <div class="pkg-title"><a href="package-detail.php?id=<?= $pkg['id'] ?>" style="color:inherit;text-decoration:none;"><?= htmlspecialchars($pkg['name']) ?></a></div>
            <div class="pkg-dest"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg> <?= htmlspecialchars($pkg['destination_name'] ?? 'Multiple') ?></div>
            <div style="flex:1;"></div>
            <div class="pkg-meta">
                <div>From <span class="pkg-price">$<?= number_format($pkg['base_price']) ?></span></div>
                <div style="text-align:right;color:var(--ink-soft);font-size:0.75rem;">
                    Max: <?= $pkg['max_pax'] ?> Pax<br>
                    <code style="color:var(--navy-800);"><?= $pkg['package_code'] ?></code>
                </div>
            </div>
        </div>
        <div class="pkg-footer">
            <div class="pkg-status <?= $pkg['status'] ?>">
                <span style="width:8px;height:8px;border-radius:50%;background:currentColor;"></span>
                <?= ucfirst($pkg['status']) ?>
            </div>
            <a href="package-detail.php?id=<?= $pkg['id'] ?>" class="btn btn-ghost btn-sm">View Details</a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
