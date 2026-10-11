<?php
$pageTitle   = 'Partners — Tour Operators';
$pageHeading = 'Tour Operator Partners';
$pageDesc    = 'Ground handlers and licensed tour operator partners';
$partnerType = 'tour_operator';
require_once __DIR__ . '/config/db.php';
include 'includes/partners-actions.php';
include 'includes/header.php';
include 'includes/partners-crud.php';
include 'includes/footer.php';
