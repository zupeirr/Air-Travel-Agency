<?php
$pageTitle   = 'Partners — Universities';
$pageHeading = 'University Partners';
$pageDesc    = 'Education institutions and university partner accounts';
$partnerType = 'university';
require_once __DIR__ . '/config/db.php';
include 'includes/partners-actions.php';
include 'includes/header.php';
include 'includes/partners-crud.php';
include 'includes/footer.php';
