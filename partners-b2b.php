<?php
$pageTitle   = 'Partners — B2B Agents';
$pageHeading = 'B2B Agent Partners';
$pageDesc    = 'Sub-agents and white-label partner accounts';
$partnerType = 'b2b_agent';
require_once __DIR__ . '/config/db.php';
include 'includes/partners-actions.php';
include 'includes/header.php';
include 'includes/partners-crud.php';
include 'includes/footer.php';
