<?php
$pageTitle = 'Marketing — Promotions';
include 'includes/header.php';
$promos = db_select("SELECT * FROM promotions ORDER BY created_at DESC");
?>
<div class="page-header">
    <div class="page-header-left"><h2>Promotions</h2><p>Active and scheduled marketing promotions</p></div>
    <div class="page-header-actions"><button class="btn btn-primary"><i data-lucide="plus"></i> Create Promotion</button></div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
<?php if (empty($promos)): ?><div class="panel" style="grid-column:1/-1;"><div class="empty-state" style="padding:48px;">No promotions created yet.</div></div>
<?php else: foreach ($promos as $p): ?>
<div class="panel">
    <div class="panel-body">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
            <h3 style="font-size:1rem;font-weight:700;"><?= htmlspecialchars($p['title']) ?></h3>
            <span class="badge <?= $p['status']==='active'?'badge-approved':'badge-inactive' ?>"><?= ucfirst($p['status']) ?></span>
        </div>
        <p style="font-size:0.85rem;color:var(--ink-soft);margin-bottom:14px;"><?= htmlspecialchars($p['description'] ?? '') ?></p>
        <?php if ($p['discount_amount'] ?? ''): ?><div style="font-size:1.2rem;font-weight:800;color:var(--gold-600);"><?= $p['discount_type']==='percent'?$p['discount_amount'].'% OFF':'$'.$p['discount_amount'].' OFF' ?></div><?php endif; ?>
        <div style="font-size:0.75rem;color:var(--ink-soft);margin-top:8px;"><?= $p['start_date']?date('d M Y',strtotime($p['start_date'])):'—' ?> → <?= $p['end_date']?date('d M Y',strtotime($p['end_date'])):'—' ?></div>
    </div>
</div>
<?php endforeach; endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
