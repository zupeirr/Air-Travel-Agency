<?php
$pageTitle = 'Reviews & Feedback';
include 'includes/header.php';
$reviews = db_select("SELECT r.*, c.first_name, c.last_name FROM reviews r LEFT JOIN customers c ON c.id = r.customer_id ORDER BY r.created_at DESC");
function stars($n) {
    $s = '';
    for ($i=1;$i<=5;$i++) $s .= '<span style="color:'.($i<=$n?'var(--gold-500)':'#e5e7eb').';">★</span>';
    return $s;
}
$avg = count($reviews) ? round(array_sum(array_column($reviews,'rating'))/count($reviews),1) : 0;
?>
<div class="page-header">
    <div class="page-header-left"><h2>Reviews & Feedback</h2><p>Customer satisfaction ratings and testimonials</p></div>
</div>
<div class="stats-row" style="grid-template-columns:repeat(3,1fr);">
    <div class="stat-mini border-gold"><div class="stat-mini-val"><?= $avg ?> / 5</div><div class="stat-mini-label">Avg. Rating</div></div>
    <div class="stat-mini border-navy"><div class="stat-mini-val"><?= count($reviews) ?></div><div class="stat-mini-label">Total Reviews</div></div>
    <div class="stat-mini border-green"><div class="stat-mini-val"><?= count(array_filter($reviews, fn($r) => $r['rating'] >= 4)) ?></div><div class="stat-mini-label">Positive (4–5 ★)</div></div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;margin-top:20px;">
    <?php if (empty($reviews)): ?>
    <div class="panel" style="grid-column:1/-1;"><div class="empty-state" style="padding:48px;">No reviews yet.</div></div>
    <?php else: ?>
    <?php foreach ($reviews as $r): ?>
    <div class="panel">
        <div class="panel-body">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                <div>
                    <div style="font-weight:700;"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($r['created_at'])) ?></div>
                </div>
                <div style="font-size:1.1rem;"><?= stars($r['rating']) ?></div>
            </div>
            <p style="font-size:0.88rem;color:var(--ink);line-height:1.6;"><?= htmlspecialchars($r['comment'] ?? '') ?></p>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
