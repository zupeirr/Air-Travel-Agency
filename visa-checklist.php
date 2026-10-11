<?php
$pageTitle = 'Document Checklist';
include 'includes/header.php';
$visaTypes = db_select("SELECT vt.*, COUNT(vr.id) AS req_count FROM visa_types vt LEFT JOIN visa_requirements vr ON vr.visa_type_id = vt.id GROUP BY vt.id ORDER BY vt.country, vt.name");
$selected = (int)($_GET['vt'] ?? 0);
$requirements = [];
if ($selected) {
    $requirements = db_select("SELECT * FROM visa_requirements WHERE visa_type_id = ? ORDER BY is_mandatory DESC, document_name", [$selected]);
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Document Checklist</h2>
        <p>Required documents per visa type</p>
    </div>
</div>
<div class="dash-grid-3">
    <div style="grid-column:span 1;">
        <div class="panel" style="position:sticky;top:80px;">
            <div class="panel-head"><h2>Select Visa Type</h2></div>
            <div style="max-height:500px;overflow-y:auto;">
                <?php foreach ($visaTypes as $vt): ?>
                <a href="visa-checklist.php?vt=<?= $vt['id'] ?>" style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--ink);<?= $selected==$vt['id']?'background:var(--navy-800);color:#fff;':'' ?>">
                    <div>
                        <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($vt['name']) ?></div>
                        <div style="font-size:0.75rem;opacity:0.7;"><?= htmlspecialchars($vt['country']) ?></div>
                    </div>
                    <span style="font-size:0.75rem;font-weight:700;background:rgba(234,179,8,0.15);color:var(--gold-600);border-radius:99px;padding:2px 8px;"><?= $vt['req_count'] ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div style="grid-column:span 2;">
        <?php if (!$selected): ?>
        <div class="panel"><div class="empty-state" style="padding:64px;">← Select a visa type to view its document checklist.</div></div>
        <?php elseif (empty($requirements)): ?>
        <div class="panel"><div class="empty-state" style="padding:64px;">No document requirements configured for this visa type.</div></div>
        <?php else: ?>
        <div class="panel">
            <div class="panel-head"><h2>Required Documents (<?= count($requirements) ?>)</h2></div>
            <div class="panel-body" style="padding:0;">
                <table class="data-table">
                    <thead><tr><th>Document</th><th>Type</th><th>Notes</th><th>Required</th></tr></thead>
                    <tbody>
                    <?php foreach ($requirements as $r): ?>
                    <tr>
                        <td style="font-weight:600;"><?= htmlspecialchars($r['document_name']) ?></td>
                        <td style="font-size:0.83rem;color:var(--ink-soft);"><?= ucfirst($r['document_type'] ?? '—') ?></td>
                        <td style="font-size:0.82rem;color:var(--ink-soft);"><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
                        <td><?php if ($r['is_mandatory']): ?><span style="color:var(--danger);font-weight:700;">Mandatory</span><?php else: ?><span style="color:var(--ink-soft);">Optional</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
