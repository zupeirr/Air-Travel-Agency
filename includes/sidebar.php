<?php
// Helper: return 'active' class if current page matches
function isActive($page) {
    global $currentPage;
    return $currentPage === $page ? ' class="active"' : '';
}
// Helper: return 'open active-group' if any child page is current
function isGroupActive($pages) {
    global $currentPage;
    return in_array($currentPage, $pages) ? ' class="nav-group open active-group"' : ' class="nav-group"';
}
?>
<aside class="admin-sidebar" id="adminSidebar">
    <a href="index.php" class="brand brand-airtravel">
        <img src="assets/images/Air travel.png" alt="Air Travel Online Agency" class="brand-logo" style="border-radius: 8px; mix-blend-mode: screen; filter: grayscale(1) invert(1) contrast(2);">
        <div class="brand-text">
            <span class="brand-name">Air Travel</span>
            <span class="brand-sub">Online Agency</span>
        </div>
    </a>
    <nav class="admin-nav">

        <!-- MAIN -->

        <a href="index.php"<?php echo isActive('index.php'); ?> data-tip="Dashboard">
            <span class="ic"><i data-lucide="layout-dashboard"></i></span> <span>Dashboard</span>
        </a>

        <!-- BOOKINGS -->

        <div<?php echo isGroupActive(['bookings-all.php','bookings-flights.php','bookings-hotels.php','bookings-cars.php','bookings-tours.php']); ?>>
            <button class="nav-group-toggle" data-tip="Bookings">
                <span class="ic"><i data-lucide="calendar"></i></span>
                <span>Bookings</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="bookings-all.php"<?php echo isActive('bookings-all.php'); ?>>All Bookings</a>
                <a href="bookings-flights.php"<?php echo isActive('bookings-flights.php'); ?>>Flights</a>
                <a href="bookings-hotels.php"<?php echo isActive('bookings-hotels.php'); ?>>Hotels & Stays</a>
                <a href="bookings-cars.php"<?php echo isActive('bookings-cars.php'); ?>>Car Rentals</a>
                <a href="bookings-tours.php"<?php echo isActive('bookings-tours.php'); ?>>Tours & Activities</a>
            </div>
        </div>

        <!-- TRAVEL -->

        <div<?php echo isGroupActive(['travel-packages.php','travel-itineraries.php','travel-destinations.php']); ?>>
            <button class="nav-group-toggle" data-tip="Travel">
                <span class="ic"><i data-lucide="map"></i></span>
                <span>Travel</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="travel-packages.php"<?php echo isActive('travel-packages.php'); ?>>Travel Packages</a>
                <a href="travel-itineraries.php"<?php echo isActive('travel-itineraries.php'); ?>>Custom Itineraries</a>
                <a href="travel-destinations.php"<?php echo isActive('travel-destinations.php'); ?>>Destinations</a>
            </div>
        </div>

        <!-- VISA SERVICES -->

        <div<?php echo isGroupActive(['visa-all.php','visa-travel.php','visa-education.php','visa-business.php','visa-family.php','visa-requirements.php','visa-checklist.php','visa-appointments.php','visa-tracking.php','visa-passports.php']); ?>>
            <button class="nav-group-toggle" data-tip="Visa Services">
                <span class="ic"><i data-lucide="file-text"></i></span>
                <span>Visa Services</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="visa-all.php"<?php echo isActive('visa-all.php'); ?>>All Applications</a>
                <a href="visa-travel.php"<?php echo isActive('visa-travel.php'); ?>>Travel Visa</a>
                <a href="visa-education.php"<?php echo isActive('visa-education.php'); ?>>Education Visa</a>
                <a href="visa-business.php"<?php echo isActive('visa-business.php'); ?>>Business Visa</a>
                <a href="visa-family.php"<?php echo isActive('visa-family.php'); ?>>Family / Visit Visa</a>
                <a href="visa-requirements.php"<?php echo isActive('visa-requirements.php'); ?>>Visa Requirements</a>
                <a href="visa-checklist.php"<?php echo isActive('visa-checklist.php'); ?>>Document Checklist</a>
                <a href="visa-appointments.php"<?php echo isActive('visa-appointments.php'); ?>>Visa Appointments</a>
                <a href="visa-tracking.php"<?php echo isActive('visa-tracking.php'); ?>>Application Tracking</a>
                <a href="visa-passports.php"<?php echo isActive('visa-passports.php'); ?>>Passport Expirations</a>
            </div>
        </div>

        <!-- CUSTOMERS -->

        <div<?php echo isGroupActive(['customers-directory.php','customers-inquiries.php','customers-appointments.php','customers-reviews.php']); ?>>
            <button class="nav-group-toggle" data-tip="Customers">
                <span class="ic"><i data-lucide="users"></i></span>
                <span>Customers</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="customers-directory.php"<?php echo isActive('customers-directory.php'); ?>>Customer Directory</a>
                <a href="customers-inquiries.php"<?php echo isActive('customers-inquiries.php'); ?>>Traveler Inquiries</a>
                <a href="customers-appointments.php"<?php echo isActive('customers-appointments.php'); ?>>Appointments</a>
                <a href="customers-reviews.php"<?php echo isActive('customers-reviews.php'); ?>>Reviews & Feedback</a>
            </div>
        </div>

        <!-- DOCUMENTS -->

        <div<?php echo isGroupActive(['documents-all.php','documents-pending.php','documents-verified.php']); ?>>
            <button class="nav-group-toggle" data-tip="Documents">
                <span class="ic"><i data-lucide="folder"></i></span>
                <span>Documents</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="documents-all.php"<?php echo isActive('documents-all.php'); ?>>All Documents</a>
                <a href="documents-pending.php"<?php echo isActive('documents-pending.php'); ?>>Pending Documents</a>
                <a href="documents-verified.php"<?php echo isActive('documents-verified.php'); ?>>Verified Documents</a>
            </div>
        </div>

        <!-- PARTNERS -->

        <div<?php echo isGroupActive(['partners-b2b.php','partners-airlines.php','partners-hotels.php','partners-universities.php','partners-operators.php']); ?>>
            <button class="nav-group-toggle" data-tip="Partners">
                <span class="ic"><i data-lucide="briefcase"></i></span>
                <span>Partners</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="partners-b2b.php"<?php echo isActive('partners-b2b.php'); ?>>B2B Agents</a>
                <a href="partners-airlines.php"<?php echo isActive('partners-airlines.php'); ?>>Airlines</a>
                <a href="partners-hotels.php"<?php echo isActive('partners-hotels.php'); ?>>Hotels</a>
                <a href="partners-universities.php"<?php echo isActive('partners-universities.php'); ?>>Universities</a>
                <a href="partners-operators.php"<?php echo isActive('partners-operators.php'); ?>>Tour Operators</a>
            </div>
        </div>

        <!-- FINANCE -->

        <div<?php echo isGroupActive(['finance-invoices.php','finance-payments.php','finance-refunds.php','finance-commissions.php']); ?>>
            <button class="nav-group-toggle" data-tip="Finance">
                <span class="ic"><i data-lucide="dollar-sign"></i></span>
                <span>Finance</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="finance-invoices.php"<?php echo isActive('finance-invoices.php'); ?>>Invoices</a>
                <a href="finance-payments.php"<?php echo isActive('finance-payments.php'); ?>>Payments</a>
                <a href="finance-refunds.php"<?php echo isActive('finance-refunds.php'); ?>>Refunds</a>
                <a href="finance-commissions.php"<?php echo isActive('finance-commissions.php'); ?>>Agent Commissions</a>
            </div>
        </div>

        <!-- MARKETING -->

        <div<?php echo isGroupActive(['marketing-promotions.php','marketing-discounts.php','marketing-campaigns.php']); ?>>
            <button class="nav-group-toggle" data-tip="Marketing">
                <span class="ic"><i data-lucide="megaphone"></i></span>
                <span>Marketing</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="marketing-promotions.php"<?php echo isActive('marketing-promotions.php'); ?>>Promotions</a>
                <a href="marketing-discounts.php"<?php echo isActive('marketing-discounts.php'); ?>>Discount Codes</a>
                <a href="marketing-campaigns.php"<?php echo isActive('marketing-campaigns.php'); ?>>Campaigns</a>
            </div>
        </div>

        <!-- REPORTS -->

        <div<?php echo isGroupActive(['reports-booking.php','reports-visa.php','reports-customer.php','reports-revenue.php']); ?>>
            <button class="nav-group-toggle" data-tip="Reports">
                <span class="ic"><i data-lucide="bar-chart"></i></span>
                <span>Reports</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="reports-booking.php"<?php echo isActive('reports-booking.php'); ?>>Booking Reports</a>
                <a href="reports-visa.php"<?php echo isActive('reports-visa.php'); ?>>Visa Reports</a>
                <a href="reports-customer.php"<?php echo isActive('reports-customer.php'); ?>>Customer Reports</a>
                <a href="reports-revenue.php"<?php echo isActive('reports-revenue.php'); ?>>Revenue Reports</a>
            </div>
        </div>

        <!-- SETTINGS -->

        <div<?php echo isGroupActive(['settings-general.php','settings-users.php','settings-api.php','settings-notifications.php','settings-security.php']); ?>>
            <button class="nav-group-toggle" data-tip="Settings">
                <span class="ic"><i data-lucide="settings"></i></span>
                <span>Settings</span>
                <span class="chevron"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
            </button>
            <div class="nav-group-children">
                <a href="settings-general.php"<?php echo isActive('settings-general.php'); ?>>General</a>
                <a href="settings-users.php"<?php echo isActive('settings-users.php'); ?>>Users & Roles</a>
                <a href="settings-api.php"<?php echo isActive('settings-api.php'); ?>>API Integrations</a>
                <a href="settings-notifications.php"<?php echo isActive('settings-notifications.php'); ?>>Notifications</a>
                <a href="settings-security.php"<?php echo isActive('settings-security.php'); ?>>Security</a>
            </div>
        </div>

    </nav>
    <div class="admin-sidebar-footer">
        <a href="logout.php" class="btn btn-outline btn-block" style="border-color:rgba(255,255,255,0.3); color:#fff; display:flex; align-items:center; gap:8px; justify-content:center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg> <span class="btn-label">Log Out</span>
        </a>
    </div>
</aside>
