<?php
$pageTitle = 'Application Tracking';
include 'includes/header.php';
$apps = db_select("
    SELECT va.*, vt.name AS visa_type_name, c.first_name, c.last_name, c.customer_code
    FROM visa_applications va
    JOIN visa_types vt ON vt.id = va.visa_type_id
    JOIN customers c ON c.id = va.customer_id
    ORDER BY va.updated_at DESC");
$stages = ['draft'=>0,'documents_pending'=>1,'under_review'=>2,'ready_for_submission'=>3,'submitted'=>4,'appointment_scheduled'=>5,'processing'=>6,'approved'=>7];
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Application Tracking</h2>
        <p>Live pipeline view of all visa applications in progress</p>
    </div>
</div>
<style>
.track-row { background:#fff; border:1px solid var(--border); border-radius:12px; padding:20px 24px; margin-bottom:12px; }
.track-bar-wrap { display:flex; gap:2px; margin-top:12px; }
.track-seg { flex:1; height:6px; border-radius:3px; background:#e5e7eb; }
.track-seg.done { background:var(--navy-800); }
.track-seg.current { background:var(--gold-500); }
.track-meta { display:flex; justify-content:space-between; align-items:flex-start; }
</style>
<?php if (empty($apps)): ?>
<div class="panel"><div class="empty-state" style="padding:48px;">No applications to track.</div></div>
<?php else: ?>
<?php foreach ($apps as $app):
    if (in_array($app['status'], ['approved','rejected','cancelled'])) continue;
    $stage = $stages[$app['status']] ?? 0;
    $total = count($stages);
?>
<div class="track-row">
    <div class="track-meta">
        <div>
            <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($app['first_name'].' '.$app['last_name']) ?> <span style="color:var(--ink-soft);font-size:0.8rem;font-weight:400;"><?= htmlspecialchars($app['customer_code']) ?></span></div>
            <div style="font-size:0.85rem;color:var(--ink-soft);margin-top:2px;"><?= htmlspecialchars($app['visa_type_name']) ?> → <?= htmlspecialchars($app['destination_country']) ?> · <code style="font-size:0.78rem;"><?= $app['application_code'] ?></code></div>
        </div>
        <div style="text-align:right;">
            <span class="badge badge-pending" style="font-size:0.75rem;"><?= ucwords(str_replace('_',' ',$app['status'])) ?></span>
            <?php if ($app['travel_date']): ?><div style="font-size:0.75rem;color:var(--ink-soft);margin-top:4px;">Travel: <?= date('d M Y', strtotime($app['travel_date'])) ?></div><?php endif; ?>
        </div>
    </div>
    <div class="track-bar-wrap">
        <?php foreach (array_values($stages) as $i => $s): ?>
        <div class="track-seg <?= $i < $stage ? 'done' : ($i === $stage ? 'current' : '') ?>"></div>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:4px;">
        <span style="font-size:0.65rem;color:var(--ink-soft);">Draft</span>
        <span style="font-size:0.65rem;color:var(--ink-soft);">Approved</span>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
