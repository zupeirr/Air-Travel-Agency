<?php
$pageTitle = 'Custom Itineraries';
include 'includes/header.php';

$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['p'] ?? 1));
$perPage= 25;
$offset = ($page-1) * $perPage;

// Since we don't have a standalone 'custom_itineraries' table yet, 
// let's display package itineraries grouped by package, 
// or create a mock view of custom customer itineraries.

$itineraries = db_select("
    SELECT pi.*, tp.name AS package_name, tp.package_code 
    FROM package_itineraries pi
    JOIN travel_packages tp ON tp.id = pi.package_id
    ORDER BY tp.name, pi.day_number
    LIMIT $perPage OFFSET $offset
");

$totalFiltered = db_count("SELECT COUNT(*) FROM package_itineraries");
$totalPages = max(1, ceil($totalFiltered/$perPage));

?>

<div class="page-header">
    <div class="page-header-left">
        <h2>Custom Itineraries</h2>
        <p>Manage daily plans for packages and custom trips</p>
    </div>
    <div class="page-header-actions">
        <a href="package-create.php" class="btn btn-primary"><i data-lucide="plus"></i> New Itinerary</a>
    </div>
</div>

<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($itineraries)): ?>
        <div class="empty-state" style="padding:48px;">No itineraries found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Package</th>
                    <th>Day</th>
                    <th>Title</th>
                    <th>Accommodation</th>
                    <th>Meals</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($itineraries as $it): ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><a href="package-detail.php?id=<?= $it['package_id'] ?>"><?= htmlspecialchars($it['package_name']) ?></a></div>
                    <code style="font-size:0.75rem;"><?= htmlspecialchars($it['package_code']) ?></code>
                </td>
                <td><span class="badge badge-navy">Day <?= $it['day_number'] ?></span></td>
                <td style="font-weight:500;"><?= htmlspecialchars($it['title']) ?></td>
                <td style="font-size:0.85rem;color:var(--ink-soft);"><?= htmlspecialchars($it['accommodation'] ?? '—') ?></td>
                <td style="font-size:0.85rem;color:var(--ink-soft);"><?= htmlspecialchars($it['meals_included'] ?? '—') ?></td>
                <td>
                    <div class="tbl-actions">
                        <a href="#" class="tbl-btn" title="Edit"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg></a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page>1): ?><a href="?q=<?= urlencode($search) ?>&p=<?= $page-1 ?>">‹ Prev</a><?php endif; ?>
            <?php for ($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++): ?>
            <?= $i===$page ? "<span class='current'>$i</span>" : "<a href='?q=".urlencode($search)."&p=$i'>$i</a>" ?>
            <?php endfor; ?>
            <?php if ($page<$totalPages): ?><a href="?q=<?= urlencode($search) ?>&p=<?= $page+1 ?>">Next ›</a><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
