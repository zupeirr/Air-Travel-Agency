<?php
require_once __DIR__ . '/config/db.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');

    if ($action === 'create_inquiry') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $subject  = trim($_POST['subject'] ?? '');
        $message  = trim($_POST['message'] ?? '');
        $custId   = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;

        if (!empty($name) && !empty($subject) && !empty($message)) {
            $ref = generate_code('inquiries', 'inquiry_ref', 'INQ');
            db_insert(
                "INSERT INTO inquiries (inquiry_ref, customer_id, name, email, phone, category, subject, message, status, source) VALUES (?,?,?,?,?,?,?,?,'new','email')",
                [$ref, $custId, $name, $email, $phone, $category, $subject, $message]
            );
            header('Location: customers-inquiries.php?success=created');
            exit;
        }
    }

    if ($action === 'update_status') {
        $inqId  = (int)($_POST['inquiry_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $validStatuses = ['new','open','in_progress','replied','resolved','closed'];

        if ($inqId > 0 && in_array($status, $validStatuses)) {
            db_execute("UPDATE inquiries SET status = ?, updated_at = NOW() WHERE id = ?", [$status, $inqId]);
            header('Location: customers-inquiries.php?success=updated');
            exit;
        }
    }
}

$pageTitle = 'Traveler Inquiries';
include 'includes/header.php';
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');
$where = ['1=1']; $params = [];
if ($status) { $where[] = 'i.status = ?'; $params[] = $status; }
if ($search) {
    $where[] = '(i.name LIKE ? OR i.email LIKE ? OR i.subject LIKE ? OR i.inquiry_ref LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$inquiries = db_select("
    SELECT i.*, c.first_name, c.last_name, c.customer_code 
    FROM inquiries i 
    LEFT JOIN customers c ON c.id = i.customer_id 
    WHERE ".implode(' AND ',$where)." 
    ORDER BY i.created_at DESC", $params);

function iqBadge($s) {
    $m=['new'=>'badge-pending','open'=>'badge-draft','in_progress'=>'badge-review','replied'=>'badge-review','resolved'=>'badge-approved','closed'=>'badge-inactive'];
    return '<span class="badge '.($m[$s]??'badge-draft').'">'.ucwords(str_replace('_',' ',$s)).'</span>';
}
?>
<div class="page-header">
    <div class="page-header-left">
        <h2>Traveler Inquiries</h2>
        <p>Manage incoming customer requests, messages, and resolutions</p>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="document.getElementById('create-inq-modal').classList.add('show')">
            <i data-lucide="plus"></i> Log Inquiry
        </button>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success" style="margin-bottom:20px;">
    <?= $_GET['success'] === 'created' ? 'Inquiry logged successfully.' : 'Inquiry status updated successfully.' ?>
</div>
<?php endif; ?>

<form method="GET" action="customers-inquiries.php">
<div class="sfbar">
    <div class="sfbar-search">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, email, subject, ref…">
    </div>
    <select name="status" class="sfbar-select" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <?php foreach (['new','open','in_progress','replied','resolved','closed'] as $st): ?>
        <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= ucwords(str_replace('_',' ',$st)) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($status || $search): ?><a href="customers-inquiries.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
    <span class="sfbar-count"><?= count($inquiries) ?> inquir<?= count($inquiries)!==1?'ies':'y' ?></span>
    <button type="submit" class="btn btn-ghost btn-sm">Filter</button>
</div>
</form>

<div class="panel">
    <div class="panel-body table-wrap" style="padding:0;">
        <?php if (empty($inquiries)): ?><div class="empty-state" style="padding:48px;">No inquiries found.</div>
        <?php else: ?>
        <table class="data-table">
            <thead><tr><th>Ref / From</th><th>Subject</th><th>Category</th><th>Received</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($inquiries as $i): 
                $senderName = $i['first_name'] ? ($i['first_name'].' '.$i['last_name']) : ($i['name'] ?? 'Unknown');
            ?>
            <tr>
                <td>
                    <div style="font-weight:600;"><?= htmlspecialchars($senderName) ?></div>
                    <code style="font-size:0.75rem;"><?= htmlspecialchars($i['inquiry_ref'] ?? 'INQ-'.$i['id']) ?></code>
                    <div style="font-size:0.75rem;color:var(--ink-soft);"><?= htmlspecialchars($i['email'] ?? '—') ?></div>
                </td>
                <td style="max-width:260px;">
                    <div style="font-weight:600;color:var(--navy-900);"><?= htmlspecialchars($i['subject'] ?? '—') ?></div>
                    <div style="font-size:0.8rem;color:var(--ink-soft);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px;">
                        <?= htmlspecialchars($i['message'] ?? '') ?>
                    </div>
                </td>
                <td style="font-size:0.83rem;"><?= ucfirst($i['category'] ?? $i['inquiry_type'] ?? 'general') ?></td>
                <td style="font-size:0.8rem;color:var(--ink-soft);"><?= date('d M Y, g:i A', strtotime($i['created_at'])) ?></td>
                <td><?= iqBadge($i['status'] ?? 'new') ?></td>
                <td>
                    <div class="tbl-actions">
                        <button type="button" class="tbl-btn" title="View Message" onclick="viewInquiry(<?= htmlspecialchars(json_encode($i), ENT_QUOTES, 'UTF-8') ?>)">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- View & Update Inquiry Modal -->
<div class="modal-overlay" id="view-inq-modal">
    <div class="modal-content" style="max-width:550px;">
        <div class="modal-header">
            <div>
                <h3 id="modal-inq-subject">Inquiry Details</h3>
                <code id="modal-inq-ref" style="font-size:0.8rem;color:var(--ink-soft);"></code>
            </div>
            <button class="modal-close" onclick="document.getElementById('view-inq-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <div style="background:#f8f9fa;border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                    <div style="font-weight:700;" id="modal-inq-from"></div>
                    <div style="font-size:0.8rem;color:var(--ink-soft);" id="modal-inq-date"></div>
                </div>
                <div style="font-size:0.82rem;color:var(--ink-soft);" id="modal-inq-contact"></div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="font-size:0.8rem;font-weight:700;color:var(--ink-soft);text-transform:uppercase;">Message</label>
                <div id="modal-inq-message" style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:12px;margin-top:6px;font-size:0.9rem;line-height:1.5;max-height:180px;overflow-y:auto;white-space:pre-wrap;"></div>
            </div>

            <form method="POST" action="customers-inquiries.php">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="inquiry_id" id="modal-inq-id">
                <div class="field-block">
                    <label>Update Status</label>
                    <div style="display:flex;gap:10px;">
                        <select name="status" id="modal-inq-status" class="form-input" style="flex:1;">
                            <option value="new">New</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="replied">Replied</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                        <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Log New Inquiry Modal -->
<div class="modal-overlay" id="create-inq-modal">
    <div class="modal-content" style="max-width:520px;">
        <div class="modal-header">
            <h3>Log Customer Inquiry</h3>
            <button class="modal-close" onclick="document.getElementById('create-inq-modal').classList.remove('show')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="customers-inquiries.php">
                <input type="hidden" name="action" value="create_inquiry">
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Customer Name <span class="req">*</span></label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g. John Doe">
                    </div>
                    <div class="field-block">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-input" placeholder="e.g. john@example.com">
                    </div>
                </div>
                <div class="form-grid-2">
                    <div class="field-block">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-input" placeholder="+1 ...">
                    </div>
                    <div class="field-block">
                        <label>Category</label>
                        <select name="category" class="form-input">
                            <option value="general">General</option>
                            <option value="booking">Booking</option>
                            <option value="visa">Visa Services</option>
                            <option value="education">Education</option>
                            <option value="travel">Travel</option>
                            <option value="complaint">Complaint</option>
                        </select>
                    </div>
                </div>
                <div class="field-block">
                    <label>Subject <span class="req">*</span></label>
                    <input type="text" name="subject" class="form-input" required placeholder="e.g. Inquiry regarding Schengen Visa processing">
                </div>
                <div class="field-block">
                    <label>Message / Details <span class="req">*</span></label>
                    <textarea name="message" class="form-input" rows="4" required placeholder="Type the inquiry content or notes from caller..."></textarea>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                    <button type="button" onclick="document.getElementById('create-inq-modal').classList.remove('show')" class="btn btn-ghost">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i data-lucide="check"></i> Save Inquiry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewInquiry(i) {
    document.getElementById('modal-inq-id').value = i.id;
    document.getElementById('modal-inq-ref').textContent = i.inquiry_ref || ('INQ-' + i.id);
    document.getElementById('modal-inq-subject').textContent = i.subject || 'Inquiry Details';
    const name = (i.first_name ? (i.first_name + ' ' + i.last_name) : (i.name || 'Unknown'));
    document.getElementById('modal-inq-from').textContent = name;
    document.getElementById('modal-inq-contact').textContent = (i.email || 'No email') + ' • ' + (i.phone || 'No phone');
    document.getElementById('modal-inq-date').textContent = i.created_at || '';
    document.getElementById('modal-inq-message').textContent = i.message || 'No message provided.';
    document.getElementById('modal-inq-status').value = i.status || 'new';
    document.getElementById('view-inq-modal').classList.add('show');
}
</script>

<?php include 'includes/footer.php'; ?>
