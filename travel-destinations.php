<?php
$pageTitle = 'Travel Destinations';
include 'includes/header.php';
$dests = db_select("SELECT d.*, COUNT(tp.id) AS pkg_count FROM destinations d LEFT JOIN travel_packages tp ON tp.destination_id = d.id GROUP BY d.id ORDER BY d.name");
?>
<div class="page-header">
    <div class="page-header-left"><h2>Destinations</h2><p>All managed travel destinations and their packages</p></div>
    <div class="page-header-actions"><button class="btn btn-primary" onclick="alert('Add destination form coming soon.')"><i data-lucide="plus"></i> Add Destination</button></div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
    <?php if (empty($dests)): ?>
    <div class="panel" style="grid-column:1/-1;"><div class="empty-state" style="padding:48px;">No destinations configured yet.</div></div>
    <?php else: ?>
    <?php foreach ($dests as $d): ?>
    <div class="panel" style="overflow:hidden;">
        <?php if ($d['image_url'] ?? ''): ?><div style="height:160px;background:url('<?= htmlspecialchars($d['image_url']) ?>') center/cover no-repeat;"></div><?php else: ?><div style="height:160px;background:linear-gradient(135deg,var(--navy-800),var(--navy-900));display:flex;align-items:center;justify-content:center;font-size:3rem;">🌍</div><?php endif; ?>
        <div class="panel-body" style="padding:16px 20px;">
            <div style="font-size:1.05rem;font-weight:700;"><?= htmlspecialchars($d['name']) ?></div>
            <div style="font-size:0.82rem;color:var(--ink-soft);margin-top:2px;"><?= htmlspecialchars($d['country'] ?? '') ?></div>
            <div style="margin-top:10px;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:0.8rem;color:var(--ink-soft);"><?= $d['pkg_count'] ?> package<?= $d['pkg_count']!=1?'s':'' ?></span>
                <a href="travel-packages.php?dest=<?= $d['id'] ?>" class="btn btn-ghost btn-sm">View Packages</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
