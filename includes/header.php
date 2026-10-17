<?php require_once __DIR__ . '/../config/db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — Air Travel Online Agency' : 'Air Travel Online Agency'; ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/admin.css">
<style>
/* ── Brand Logo ────────────────────────────────── */
.brand-airtravel {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 16px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    text-decoration: none;
    transition: background 0.15s;
}
.brand-airtravel:hover { background: rgba(255,255,255,0.04); }
.brand-logo {
    width: 44px;
    height: 44px;
    object-fit: contain;
    flex-shrink: 0;
    filter: drop-shadow(0 0 6px rgba(255,255,255,0.15));
}
.brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.2;
}
.brand-name {
    font-family: 'Poppins', sans-serif;
    font-weight: 800;
    font-size: 1.05rem;
    color: #fff;
    letter-spacing: -0.01em;
}
.brand-sub {
    font-size: 0.65rem;
    font-weight: 500;
    color: var(--gold-500);
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
</style>
</head>
<body class="admin-body">

<?php
// Determine active page for sidebar highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
include 'includes/sidebar.php';
?>

<div class="admin-main">
    <div class="admin-topbar">
        <div style="display:flex; align-items:center; gap:14px;">
            <button class="mobile-topbar-toggle" id="sidebarToggle" aria-label="Toggle menu"
                style="background:transparent; border:none; cursor:pointer; color:var(--gold-500); padding:0; display:flex; align-items:center; justify-content:center; transition: opacity 0.2s;"
                onmouseover="this.style.opacity=0.8" onmouseout="this.style.opacity=1">
                <i data-lucide="menu" style="width:24px;height:24px;"></i>
            </button>
            <h1><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard'; ?></h1>
        </div>
        <div class="who" style="display:flex;align-items:center;gap:10px;">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></strong></span>
            <?php if (!empty($_SESSION['user_role_name'])): ?>
            <span style="background:rgba(234,179,8,0.15);color:var(--gold-500);font-size:0.72rem;font-weight:700;padding:2px 8px;border-radius:12px;text-transform:uppercase;letter-spacing:0.04em;">
                <?php echo htmlspecialchars($_SESSION['user_role_name']); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-content">
