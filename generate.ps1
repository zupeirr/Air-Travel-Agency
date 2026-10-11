$pages = @(
"bookings-all", "bookings-flights", "bookings-hotels", "bookings-cars", "bookings-tours",
"travel-packages", "travel-itineraries", "travel-destinations",
"visa-all", "visa-travel", "visa-education", "visa-business", "visa-family", "visa-requirements", "visa-checklist", "visa-appointments", "visa-tracking", "visa-passports",
"customers-directory", "customers-inquiries", "customers-appointments", "customers-reviews",
"documents-all", "documents-pending", "documents-verified",
"partners-b2b", "partners-airlines", "partners-hotels", "partners-universities", "partners-operators",
"finance-invoices", "finance-payments", "finance-refunds", "finance-commissions",
"marketing-promotions", "marketing-discounts", "marketing-campaigns",
"reports-booking", "reports-visa", "reports-customer", "reports-revenue",
"settings-general", "settings-users", "settings-api", "settings-notifications", "settings-security"
)

foreach ($p in $pages) {
    $title = (Get-Culture).TextInfo.ToTitleCase($p.Replace("-", " "))
    $content = @"
<?php
`$pageTitle = '$title';
include 'includes/header.php';
?>

<div class="panel">
    <div class="panel-head">
        <h2>$title</h2>
    </div>
    <div class="panel-body">
        <p>This page is under construction.</p>
    </div>
</div>

<?php
include 'includes/footer.php';
?>
"@
    Set-Content -Path "$p.php" -Value $content -Encoding UTF8
}
