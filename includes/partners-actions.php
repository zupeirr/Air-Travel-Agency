<?php
/**
 * partners-actions.php — POST-only handler (no output)
 * Must be included BEFORE header.php
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name         = trim($_POST['name'] ?? '');
        $country      = trim($_POST['country'] ?? '');
        $city         = trim($_POST['city'] ?? '');
        $contactName  = trim($_POST['contact_name'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');
        $contactPhone = trim($_POST['contact_phone'] ?? '');
        $website      = trim($_POST['website'] ?? '');
        $commission   = (float)($_POST['commission_rate'] ?? 0);
        $contractStart= trim($_POST['contract_start'] ?? '');
        $contractEnd  = trim($_POST['contract_end'] ?? '');
        $status       = trim($_POST['status'] ?? 'pending');
        $notes        = trim($_POST['notes'] ?? '');

        if ($action === 'add') {
            db_insert("INSERT INTO partners
                (name, type, country, city, contact_name, contact_email, contact_phone, website,
                 commission_rate, contract_start, contract_end, status, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)",
                [$name, $partnerType, $country, $city, $contactName, $contactEmail, $contactPhone,
                 $website ?: null, $commission, $contractStart ?: null, $contractEnd ?: null, $status, $notes]);
        } else {
            $pid = (int)($_POST['id'] ?? 0);
            db_execute("UPDATE partners SET
                name=?, country=?, city=?, contact_name=?, contact_email=?, contact_phone=?,
                website=?, commission_rate=?, contract_start=?, contract_end=?, status=?, notes=?
                WHERE id=? AND type=?",
                [$name, $country, $city, $contactName, $contactEmail, $contactPhone,
                 $website ?: null, $commission, $contractStart ?: null, $contractEnd ?: null,
                 $status, $notes, $pid, $partnerType]);
        }
        header("Location: {$_SERVER['PHP_SELF']}?saved=1");
        exit;
    }

    if ($action === 'delete') {
        $pid = (int)($_POST['id'] ?? 0);
        db_execute("DELETE FROM partners WHERE id=? AND type=?", [$pid, $partnerType]);
        header("Location: {$_SERVER['PHP_SELF']}?deleted=1");
        exit;
    }
}
