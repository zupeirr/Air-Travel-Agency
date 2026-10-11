<?php
// Set up a redirect or just include the main bookings page with a pre-set filter.
// A cleaner way for the template is to just include bookings-all.php and spoof the $_GET['type']
$_GET['type'] = 'hotel';
$pageTitleOverride = 'Hotel & Stays Bookings';
include 'bookings-all.php';
