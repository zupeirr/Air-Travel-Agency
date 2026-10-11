<?php
$pageTitle = 'Visa Requirements';
include 'includes/header.php';

$visaTypes = db_select("SELECT * FROM visa_types ORDER BY country, name");
$countries = [];
foreach ($visaTypes as $vt) {
    $countries[$vt['country']][] = $vt;
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Visa Requirements</h2>
        <p>Country-specific documentation and visa eligibility rules</p>
    </div>
    <div class="page-header-actions">
        <a href="visa-create.php" class="btn btn-primary"><i data-lucide="plus"></i> New Application</a>
    </div>
</div>
<?php if (empty($countries)): ?>
<div class="panel"><div class="empty-state" style="padding:48px;">No visa types configured yet.</div></div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;">
<?php foreach ($countries as $country => $types): ?>
<div class="panel">
    <div class="panel-head">
        <h2 style="display:flex;align-items:center;gap:8px;"><i data-lucide="map-pin" style="width:16px;height:16px;color:var(--gold-600);"></i><?= htmlspecialchars($country) ?></h2>
    </div>
    <div class="panel-body" style="padding:0;">
        <?php foreach ($types as $vt): ?>
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
            <div>
                <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($vt['name']) ?></div>
                <div style="font-size:0.75rem;color:var(--ink-soft);"><?= ucfirst($vt['category']) ?> · <?= $vt['processing_time_days'] ?? '—' ?> days · $<?= number_format($vt['fee'] ?? 0) ?></div>
            </div>
            <span class="badge <?= $vt['status']==='active'?'badge-approved':'badge-inactive' ?>"><?= ucfirst($vt['status']) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
