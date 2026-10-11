<?php
$pageTitle   = 'Partners — Airlines';
$pageHeading = 'Airline Partners';
$pageDesc    = 'Contracted airlines and GDS accounts';
$partnerType = 'airline';
require_once __DIR__ . '/config/db.php';
include 'includes/partners-actions.php';
include 'includes/header.php';
include 'includes/partners-crud.php';
include 'includes/footer.php';
