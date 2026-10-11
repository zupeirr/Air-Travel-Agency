<?php
require_once __DIR__ . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // Optional: check if customer exists first
    $exists = db_count("SELECT COUNT(*) FROM customers WHERE id = ?", [$id]);
    if ($exists) {
        // We delete the customer. Foreign keys (if properly configured with ON DELETE CASCADE) 
        // will take care of related records, or XAMPP might throw an error if restrict is used.
        // Assuming simple delete for the template.
        db_execute("DELETE FROM customers WHERE id = ?", [$id]);
    }
}

header("Location: customers-directory.php?deleted=1");
exit;
