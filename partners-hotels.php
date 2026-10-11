<?php
$pageTitle   = 'Partners — Hotels';
$pageHeading = 'Hotel Partners';
$pageDesc    = 'Contracted hotel chains and accommodation providers';
$partnerType = 'hotel';
require_once __DIR__ . '/config/db.php';
include 'includes/partners-actions.php';
include 'includes/header.php';
include 'includes/partners-crud.php';
include 'includes/footer.php';
