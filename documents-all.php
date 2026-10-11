<?php
$pageTitle = 'Documents — All';
include 'includes/header.php';
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');
$where = ['1=1']; $params = [];
if ($status) { $where[] = 'd.status = ?'; $params[] = $status; }
if ($search) { $where[] = '(c.first_name LIKE ? OR c.last_name LIKE ? OR d.document_name LIKE ?)'; $s="%$search%"; $params=array_merge($params,[$s,$s,$s]); }
$whereSQL = implode(' AND ', $where);
$docs = db_select("SELECT d.*, c.first_name, c.last_name, c.customer_code FROM customer_documents d JOIN customers c ON c.id = d.customer_id WHERE $whereSQL ORDER BY d.uploaded_at DESC", $params);
function dBadge($s) {
    $m=['pending'=>'badge-pending','verified'=>'badge-approved','rejected'=>'badge-rejected','expired'=>'badge-inactive'];
    return '<span class="badge '.($m[$s]??'badge-draft').'">'.ucfirst($s).'</span>';
}
?>
<div class="page-header">
    <div class="page-header-left"><h2>All Documents</h2><p>Manage customer uploaded documents and verification status</p></div>
</div>
<form method="GET" action="documents-all.php">
<div class="sfbar">
    <div class="sfbar-search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, document type…"></div>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['pending','verified','rejected','expired'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($search||$status): ?><a href="documents-all.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    <span class="sfbar-count"><?= count($docs) ?> document<?= count($docs)!==1?'s':'' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
</div>
</form>
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($docs)): ?><div class="empty-state" style="padding:48px;">No documents found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Document Type</th><th>Document Name</th><th>Uploaded</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($docs as $d): ?>
            <tr>
                <td><div style="font-weight:600;"><a href="customer-profile.php?id=<?= $d['customer_id'] ?>&tab=documents"><?= htmlspecialchars($d['first_name'].' '.$d['last_name']) ?></a></div><div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($d['customer_code']) ?></div></td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($d['document_type'] ?? '—') ?></td>
                <td style="font-size:0.85rem;font-weight:500;"><?= htmlspecialchars($d['document_name'] ?? '—') ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y', strtotime($d['uploaded_at'])) ?></td>
                <td><?= dBadge($d['status']) ?></td>
                <td>
                    <div class="tbl-actions">
                        <?php if ($d['file_path']): ?><a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" class="tbl-btn" title="Download"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg></a><?php endif; ?>
                        <?php if ($d['status']==='pending'): ?>
                        <a href="#" class="tbl-btn success" title="Verify"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg></a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
