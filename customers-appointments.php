<?php
$pageTitle = 'Customers — Appointments';
include 'includes/header.php';
$appts = db_select("
    SELECT a.*, c.first_name, c.last_name, c.customer_code, c.email, u.name AS agent_name
    FROM appointments a
    JOIN customers c ON c.id = a.customer_id
    LEFT JOIN users u ON u.id = a.agent_id
    ORDER BY a.appointment_date DESC");
function aptBadge($s) {
    $m=['scheduled'=>'badge-review','completed'=>'badge-approved','cancelled'=>'badge-cancelled','no_show'=>'badge-rejected'];
    return '<span class="badge '.($m[$s]??'badge-draft').'">'.ucwords(str_replace('_',' ',$s)).'</span>';
}
?>
<div class="page-header">
    <div class="page-header-left"><h2>Appointments</h2><p>All customer consultation and meeting appointments</p></div>
    <div class="page-header-actions"><button class="btn btn-primary" onclick="alert('Add appointment form coming soon.')"><i data-lucide="plus"></i> New Appointment</button></div>
</div>
<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($appts)): ?><div class="empty-state" style="padding:48px;">No appointments scheduled.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Customer</th><th>Purpose</th><th>Date & Time</th><th>Agent</th><th>Status</th><th>Notes</th></tr></thead>
            <tbody>
            <?php foreach ($appts as $a): ?>
            <tr>
                <td><div style="font-weight:600;"><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></div><div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($a['email'] ?? $a['customer_code']) ?></div></td>
                <td style="font-size:0.88rem;"><?= htmlspecialchars($a['purpose'] ?? '—') ?></td>
                <td style="font-size:0.88rem;font-weight:500;"><?= $a['appointment_date'] ? date('d M Y, g:i A', strtotime($a['appointment_date'])) : '—' ?></td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars($a['agent_name'] ?? '—') ?></td>
                <td><?= aptBadge($a['status'] ?? 'scheduled') ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= htmlspecialchars($a['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
