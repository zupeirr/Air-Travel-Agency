<?php
$pageTitle = 'Dashboard';
$agencyName = 'Air Travel Online Agency';
include 'includes/header.php';
?>

<!-- ══════════════════════════════════════
     WELCOME BANNER
══════════════════════════════════════ -->
<div class="dash-welcome">
    <div class="dash-welcome-text">
        <h2>Welcome back, Admin 👋</h2>
        <p>Here's what's happening with <strong style="color:var(--gold-500);">Air Travel Online Agency</strong> today — <?php echo date('l, F j, Y'); ?></p>
    </div>
    <div class="dash-welcome-actions">
        <a href="visa-all.php" class="btn btn-gold" id="btn-new-application">
            <i data-lucide="file-plus"></i> New Application
        </a>
        <a href="bookings-all.php" class="btn btn-white-outline" id="btn-new-booking">
            <i data-lucide="calendar-plus"></i> New Booking
        </a>
    </div>
</div>

<!-- ══════════════════════════════════════
     KPI CARDS
══════════════════════════════════════ -->
<div class="dash-stats">
    <div class="dash-card" id="kpi-customers">
        <div class="ic-wrap navy"><i data-lucide="users"></i></div>
        <div class="kpi-body">
            <h3>1,248</h3>
            <span class="kpi-label">Total Customers</span>
            <div class="kpi-trend up">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                +12.5% this month
            </div>
        </div>
    </div>
    <div class="dash-card" id="kpi-visa">
        <div class="ic-wrap gold"><i data-lucide="file-text"></i></div>
        <div class="kpi-body">
            <h3>86</h3>
            <span class="kpi-label">Active Visa Applications</span>
            <div class="kpi-trend up">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                +8.2% this month
            </div>
        </div>
    </div>
    <div class="dash-card" id="kpi-bookings">
        <div class="ic-wrap" style="background:#2563eb;"><i data-lucide="calendar"></i></div>
        <div class="kpi-body">
            <h3>324</h3>
            <span class="kpi-label">Total Bookings</span>
            <div class="kpi-trend up">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                +15.4% this month
            </div>
        </div>
    </div>
    <div class="dash-card" id="kpi-revenue">
        <div class="ic-wrap green"><i data-lucide="dollar-sign"></i></div>
        <div class="kpi-body">
            <h3>$24,850</h3>
            <span class="kpi-label">Total Revenue</span>
            <div class="kpi-trend up">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                +10.2% this month
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     VISA APPLICATIONS FUNNEL
══════════════════════════════════════ -->
<div class="panel" id="panel-visa-funnel">
    <div class="panel-head">
        <h2><i data-lucide="file-text" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px;"></i> Visa Applications</h2>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="panel-head-stat">86 active · 234 total</span>
            <a href="visa-all.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
    </div>
    <div class="panel-body" style="padding-top:20px;padding-bottom:20px;">
        <div class="visa-funnel">
            <a href="visa-all.php?status=pending" class="visa-funnel-card vf-pending">
                <div class="vf-dot"></div>
                <div class="vf-count">24</div>
                <div class="vf-label">Pending</div>
            </a>
            <a href="visa-all.php?status=review" class="visa-funnel-card vf-review">
                <div class="vf-dot"></div>
                <div class="vf-count">18</div>
                <div class="vf-label">Under Review</div>
            </a>
            <a href="visa-all.php?status=submitted" class="visa-funnel-card vf-submitted">
                <div class="vf-dot"></div>
                <div class="vf-count">12</div>
                <div class="vf-label">Submitted</div>
            </a>
            <a href="visa-all.php?status=approved" class="visa-funnel-card vf-approved">
                <div class="vf-dot"></div>
                <div class="vf-count">156</div>
                <div class="vf-label">Approved</div>
            </a>
            <a href="visa-all.php?status=rejected" class="visa-funnel-card vf-rejected">
                <div class="vf-dot"></div>
                <div class="vf-count">24</div>
                <div class="vf-label">Rejected</div>
            </a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     EDUCATION APPLICATIONS FUNNEL
══════════════════════════════════════ -->
<div class="panel" id="panel-edu-funnel">
    <div class="panel-head">
        <h2><i data-lucide="graduation-cap" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px;"></i> Education Applications</h2>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="panel-head-stat">42 active · 118 total</span>
            <a href="visa-education.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
    </div>
    <div class="panel-body" style="padding-top:20px;padding-bottom:20px;">
        <div class="edu-funnel">
            <a href="visa-education.php?status=new" class="visa-funnel-card" style="border-top:3px solid #7c3aed;">
                <div class="vf-dot" style="background:#7c3aed;"></div>
                <div class="vf-count" style="color:#7c3aed;">11</div>
                <div class="vf-label">New</div>
            </a>
            <a href="visa-education.php?status=docs" class="visa-funnel-card" style="border-top:3px solid #d97706;">
                <div class="vf-dot" style="background:#d97706;"></div>
                <div class="vf-count" style="color:#d97706;">9</div>
                <div class="vf-label">Documents Pending</div>
            </a>
            <a href="visa-education.php?status=applied" class="visa-funnel-card" style="border-top:3px solid #2563eb;">
                <div class="vf-dot" style="background:#2563eb;"></div>
                <div class="vf-count" style="color:#2563eb;">14</div>
                <div class="vf-label">Applied</div>
            </a>
            <a href="visa-education.php?status=accepted" class="visa-funnel-card" style="border-top:3px solid #047857;">
                <div class="vf-dot" style="background:#047857;"></div>
                <div class="vf-count" style="color:#047857;">72</div>
                <div class="vf-label">Accepted</div>
            </a>
            <a href="visa-education.php?status=rejected" class="visa-funnel-card" style="border-top:3px solid #991b1b;">
                <div class="vf-dot" style="background:#991b1b;"></div>
                <div class="vf-count" style="color:#991b1b;">12</div>
                <div class="vf-label">Rejected</div>
            </a>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════
     RECENT BOOKINGS + UPCOMING APPOINTMENTS
══════════════════════════════════════ -->
<div class="dash-grid-3">

    <!-- Recent Bookings -->
    <div class="panel" id="panel-recent-bookings" style="margin-bottom:0;">
        <div class="panel-head">
            <h2><i data-lucide="calendar" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px;"></i> Recent Bookings</h2>
            <a href="bookings-all.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="panel-body table-wrap" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>Destination</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#2563eb;">JD</div>
                                <div><div class="avatar-name">John Doe</div><div class="avatar-sub">#B-1001</div></div>
                            </div>
                        </td>
                        <td>Flight + Hotel</td>
                        <td>Bali, Indonesia</td>
                        <td>Oct 15, 2026</td>
                        <td style="font-weight:700;">$2,450</td>
                        <td><span class="status-pill confirmed">Confirmed</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#7c3aed;">SS</div>
                                <div><div class="avatar-name">Sarah Smith</div><div class="avatar-sub">#B-1002</div></div>
                            </div>
                        </td>
                        <td>Tour Package</td>
                        <td>Paris & Rome</td>
                        <td>Nov 2, 2026</td>
                        <td style="font-weight:700;">$4,200</td>
                        <td><span class="status-pill pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#047857;">MB</div>
                                <div><div class="avatar-name">Michael Brown</div><div class="avatar-sub">#B-1003</div></div>
                            </div>
                        </td>
                        <td>Custom Itinerary</td>
                        <td>Kyoto, Japan</td>
                        <td>Oct 28, 2026</td>
                        <td style="font-weight:700;">$3,150</td>
                        <td><span class="status-pill confirmed">Confirmed</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#d97706;">AL</div>
                                <div><div class="avatar-name">Aisha Lawal</div><div class="avatar-sub">#B-1004</div></div>
                            </div>
                        </td>
                        <td>Hotel Only</td>
                        <td>Dubai, UAE</td>
                        <td>Oct 20, 2026</td>
                        <td style="font-weight:700;">$980</td>
                        <td><span class="status-pill review">Review</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#b0413e;">RK</div>
                                <div><div class="avatar-name">Ravi Kumar</div><div class="avatar-sub">#B-1005</div></div>
                            </div>
                        </td>
                        <td>Car Rental</td>
                        <td>London, UK</td>
                        <td>Oct 10, 2026</td>
                        <td style="font-weight:700;">$420</td>
                        <td><span class="status-pill cancelled">Cancelled</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="panel" id="panel-appointments" style="margin-bottom:0;">
        <div class="panel-head">
            <h2><i data-lucide="clock" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px;"></i> Upcoming Appointments</h2>
            <a href="customers-appointments.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="panel-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#2563eb;">AM</div>
                                <div class="avatar-name">Ahmed Malik</div>
                            </div>
                        </td>
                        <td><span class="appt-type-pill visa">Visa</span></td>
                        <td><strong>Oct 5</strong><br><span style="font-size:0.78rem;color:var(--ink-soft);">9:00 AM</span></td>
                        <td><span class="status-pill confirmed">Confirmed</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#7c3aed;">FO</div>
                                <div class="avatar-name">Fatima Omar</div>
                            </div>
                        </td>
                        <td><span class="appt-type-pill edu">Education</span></td>
                        <td><strong>Oct 5</strong><br><span style="font-size:0.78rem;color:var(--ink-soft);">11:30 AM</span></td>
                        <td><span class="status-pill pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#047857;">CN</div>
                                <div class="avatar-name">Carlos Neto</div>
                            </div>
                        </td>
                        <td><span class="appt-type-pill travel">Travel</span></td>
                        <td><strong>Oct 6</strong><br><span style="font-size:0.78rem;color:var(--ink-soft);">2:00 PM</span></td>
                        <td><span class="status-pill confirmed">Confirmed</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#d97706;">LW</div>
                                <div class="avatar-name">Lin Wei</div>
                            </div>
                        </td>
                        <td><span class="appt-type-pill consult">Consult</span></td>
                        <td><strong>Oct 7</strong><br><span style="font-size:0.78rem;color:var(--ink-soft);">10:00 AM</span></td>
                        <td><span class="status-pill pending">Pending</span></td>
                    </tr>
                    <tr>
                        <td>
                            <div class="avatar-wrap">
                                <div class="customer-avatar" style="background:#b0413e;">NA</div>
                                <div class="avatar-name">Nour Al-Hassan</div>
                            </div>
                        </td>
                        <td><span class="appt-type-pill visa">Visa</span></td>
                        <td><strong>Oct 8</strong><br><span style="font-size:0.78rem;color:var(--ink-soft);">3:30 PM</span></td>
                        <td><span class="status-pill confirmed">Confirmed</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Spacer -->
<div style="height:24px;"></div>

<!-- ══════════════════════════════════════
     REVENUE OVERVIEW + RECENT ACTIVITY
══════════════════════════════════════ -->
<div class="dash-grid-2">

    <!-- Revenue Chart -->
    <div class="panel" id="panel-revenue" style="margin-bottom:0;">
        <div class="panel-head">
            <h2><i data-lucide="bar-chart-2" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px;"></i> Revenue Overview</h2>
            <a href="reports-revenue.php" class="btn btn-ghost btn-sm">Full Report</a>
        </div>
        <div class="rev-chart">
            <div style="display:flex;gap:24px;margin-bottom:20px;flex-wrap:wrap;">
                <div>
                    <div style="font-size:0.78rem;color:var(--ink-soft);font-weight:500;margin-bottom:2px;">Bookings Revenue</div>
                    <div style="font-size:1.3rem;font-weight:800;color:var(--navy-900);font-family:'Poppins',sans-serif;">$14,200</div>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:var(--ink-soft);font-weight:500;margin-bottom:2px;">Visa Services</div>
                    <div style="font-size:1.3rem;font-weight:800;color:var(--gold-600);font-family:'Poppins',sans-serif;">$7,350</div>
                </div>
                <div>
                    <div style="font-size:0.78rem;color:var(--ink-soft);font-weight:500;margin-bottom:2px;">Education Services</div>
                    <div style="font-size:1.3rem;font-weight:800;color:#7c3aed;font-family:'Poppins',sans-serif;">$3,300</div>
                </div>
            </div>
            <div class="rev-chart-bars" id="revChart">
                <!-- JS will render bars -->
            </div>
            <div style="display:flex;justify-content:space-between;padding:0 0 4px;font-size:0.7rem;color:var(--ink-soft);">
                <span>Apr</span><span>May</span><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span>
            </div>
        </div>
        <div class="rev-legend">
            <div class="rev-legend-item"><div class="rev-legend-dot" style="background:var(--navy-800);"></div> Bookings</div>
            <div class="rev-legend-item"><div class="rev-legend-dot" style="background:var(--gold-500);"></div> Visa Services</div>
            <div class="rev-legend-item"><div class="rev-legend-dot" style="background:#7c3aed;"></div> Education</div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="panel" id="panel-activity" style="margin-bottom:0;">
        <div class="panel-head">
            <h2><i data-lucide="activity" style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px;"></i> Recent Activity</h2>
            <span class="panel-head-stat">Today</span>
        </div>
        <div class="panel-body">
            <ul class="activity-feed">
                <li>
                    <div class="activity-icon green"><i data-lucide="check-circle" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        <strong>Ahmed's</strong> student visa was approved
                        <div class="activity-time">2 minutes ago</div>
                    </div>
                </li>
                <li>
                    <div class="activity-icon blue"><i data-lucide="plane" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        New flight booking received from <strong>Sarah Smith</strong>
                        <div class="activity-time">18 minutes ago</div>
                    </div>
                </li>
                <li>
                    <div class="activity-icon gold"><i data-lucide="upload" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        <strong>Amina</strong> uploaded required passport documents
                        <div class="activity-time">45 minutes ago</div>
                    </div>
                </li>
                <li>
                    <div class="activity-icon purple"><i data-lucide="graduation-cap" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        New education application created for <strong>Ravi Kumar</strong>
                        <div class="activity-time">1 hour ago</div>
                    </div>
                </li>
                <li>
                    <div class="activity-icon red"><i data-lucide="x-circle" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        Business visa for <strong>Lin Wei</strong> was rejected
                        <div class="activity-time">2 hours ago</div>
                    </div>
                </li>
                <li>
                    <div class="activity-icon green"><i data-lucide="dollar-sign" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        Payment of <strong>$2,450</strong> received from John Doe
                        <div class="activity-time">3 hours ago</div>
                    </div>
                </li>
                <li>
                    <div class="activity-icon blue"><i data-lucide="calendar-check" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        Appointment confirmed with <strong>Carlos Neto</strong>
                        <div class="activity-time">5 hours ago</div>
                    </div>
                </li>
                <li>
                    <div class="activity-icon gold"><i data-lucide="map-pin" style="width:14px;height:14px;"></i></div>
                    <div class="activity-text">
                        New destination <strong>Maldives Package</strong> added
                        <div class="activity-time">Yesterday, 4:30 PM</div>
                    </div>
                </li>
            </ul>
        </div>
    </div>

</div>

<script>
// ── Revenue Chart (vanilla JS canvas-free bar chart) ──────────────────────────
document.addEventListener('DOMContentLoaded', function () {

    const data = {
        labels: ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct'],
        bookings: [8200, 9400, 11000, 10200, 12800, 13500, 14200],
        visa:     [3200, 4100, 4800,  5200,  6000,  6800,  7350],
        edu:      [1200, 1500, 1800,  2100,  2400,  2900,  3300],
    };

    const container = document.getElementById('revChart');
    if (!container) return;

    const allVals = [...data.bookings, ...data.visa, ...data.edu];
    const maxVal  = Math.max(...allVals);
    const maxH    = 120; // px

    container.innerHTML = '';

    data.labels.forEach(function (label, i) {
        const wrap = document.createElement('div');
        wrap.className = 'rev-bar-wrap';

        function makeBar(val, cls) {
            const bar = document.createElement('div');
            bar.className = 'rev-bar ' + cls;
            const h = Math.round((val / maxVal) * maxH);
            bar.style.height = '0px';
            bar.title = '$' + val.toLocaleString();
            setTimeout(function () { bar.style.height = h + 'px'; }, 80 + i * 60);
            return bar;
        }

        const grp = document.createElement('div');
        grp.style.cssText = 'display:flex;gap:3px;align-items:flex-end;width:100%;';
        grp.appendChild(makeBar(data.bookings[i], 'bookings'));
        grp.appendChild(makeBar(data.visa[i],     'visa'));
        grp.appendChild(makeBar(data.edu[i],      'edu'));

        wrap.appendChild(grp);

        container.appendChild(wrap);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
