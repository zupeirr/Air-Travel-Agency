<?php
$pageTitle = 'Visa Appointments';
include 'includes/header.php';
$status = trim($_GET['status'] ?? '');
$where = ['1=1']; $params = [];
if ($status) { $where[] = 'a.status = ?'; $params[] = $status; }
$whereSQL = implode(' AND ', $where);
$appts = db_select("
    SELECT a.*, va.application_code, c.first_name, c.last_name, c.customer_code
    FROM visa_appointments a
    JOIN visa_applications va ON va.id = a.visa_application_id
    JOIN customers c ON c.id = va.customer_id
    WHERE $whereSQL
    ORDER BY a.appointment_date DESC", $params);
function aBadge($s) {
    $m=['scheduled'=>'badge-review','completed'=>'badge-approved','cancelled'=>'badge-cancelled','rescheduled'=>'badge-pending'];
    return '<span class="badge '.($m[$s]??'badge-draft').'">'.ucfirst($s).'</span>';
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Visa Appointments</h2>
        <p>Embassy and consulate appointment tracker</p>
    </div>
</div>
<form method="GET" action="visa-appointments.php">
<div class="sfbar">
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['scheduled','completed','cancelled','rescheduled'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($status): ?><a href="visa-appointments.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    <span class="sfbar-count"><?= count($appts) ?> appointment<?= count($appts)!==1?'s':'' ?></span>
</div>
</form>
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($appts)): ?>
        <div class="empty-state" style="padding:48px;">No appointments found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Application</th><th>Embassy</th><th>Date & Time</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
            <tr>
                <td><div style="font-weight:600;"><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></div><div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($a['customer_code']) ?></div></td>
                <td><code style="font-size:0.78rem;"><?= htmlspecialchars($a['application_code']) ?></code></td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($a['embassy_location'] ?? '—') ?></td>
                <td style="font-size:0.85rem;font-weight:500;"><?= $a['appointment_date'] ? date('d M Y, g:i A', strtotime($a['appointment_date'])) : '—' ?></td>
                <td><?= aBadge($a['status']) ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);max-width:180px;"><?= htmlspecialchars($a['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
