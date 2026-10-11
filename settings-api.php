<?php
$pageTitle = 'API Integrations';
include 'includes/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $success = true;
}
?>

<div class="page-header">
    <div class="page-header-left">
        <h2>API Integrations</h2>
        <p>Connect third-party services and manage API keys</p>
    </div>
</div>

<div class="settings-tabs">
    <a href="settings-general.php"       class="stab"><i data-lucide="settings"></i> General</a>
    <a href="settings-users.php"         class="stab"><i data-lucide="users"></i> Users & Roles</a>
    <a href="settings-api.php"           class="stab active"><i data-lucide="code"></i> API Integrations</a>
    <a href="settings-notifications.php" class="stab"><i data-lucide="bell"></i> Notifications</a>
    <a href="settings-security.php"      class="stab"><i data-lucide="shield"></i> Security</a>
</div>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:20px;">✅ API settings saved.</div>
<?php endif; ?>

<style>
.settings-tabs { display:flex; gap:4px; margin-bottom:24px; border-bottom:2px solid var(--border); padding-bottom:0; flex-wrap:wrap; }
.stab { display:flex; align-items:center; gap:7px; padding:10px 16px; font-size:0.85rem; font-weight:600; color:var(--ink-soft); text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.15s; }
.stab svg { width:14px; height:14px; }
.stab:hover { color:var(--navy-800); }
.stab.active { color:var(--navy-800); border-bottom-color:var(--navy-800); }
.api-card {
    display:flex; align-items:flex-start; justify-content:space-between; gap:16px;
    padding:20px 24px; border-bottom:1px solid var(--border);
}
.api-card:last-child { border-bottom:none; }
.api-logo { width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; flex-shrink:0; }
.api-meta { flex:1; }
.api-meta h4 { font-size:0.95rem; font-weight:700; margin:0 0 3px; }
.api-meta p  { font-size:0.8rem; color:var(--ink-soft); margin:0 0 10px; }
.api-key-wrap { display:flex; gap:8px; align-items:center; }
.api-key-input { font-family:monospace; font-size:0.78rem; background:#f7f6f3; border:1px solid var(--border); border-radius:6px; padding:6px 12px; flex:1; color:var(--ink); }
.api-connected { display:inline-flex; align-items:center; gap:5px; font-size:0.75rem; font-weight:700; color:var(--success); }
.api-disconnected { display:inline-flex; align-items:center; gap:5px; font-size:0.75rem; font-weight:700; color:var(--ink-soft); }
</style>

<form method="POST" action="settings-api.php">

<div class="panel" style="margin-bottom:20px;max-width:800px;">
    <div class="panel-head"><h2>Payment Gateways</h2></div>
    <div>
        <!-- Stripe -->
        <div class="api-card">
            <div class="api-logo" style="background:#635bff22;">💳</div>
            <div class="api-meta">
                <h4>Stripe</h4>
                <p>Process credit and debit card payments online.</p>
                <div class="api-key-wrap">
                    <input type="text" name="stripe_key" class="api-key-input" placeholder="sk_live_xxxxxxxxxxxxxxxx" value="">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleApiKey(this)">Show</button>
                </div>
            </div>
            <div>
                <span class="api-disconnected"><span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span> Not Connected</span>
            </div>
        </div>
        <!-- PayPal -->
        <div class="api-card">
            <div class="api-logo" style="background:#003087;color:#fff;font-size:1rem;font-weight:800;">PP</div>
            <div class="api-meta">
                <h4>PayPal</h4>
                <p>Accept PayPal and international payments.</p>
                <div class="api-key-wrap">
                    <input type="text" name="paypal_client_id" class="api-key-input" placeholder="Client ID" value="">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleApiKey(this)">Show</button>
                </div>
            </div>
            <div>
                <span class="api-disconnected"><span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span> Not Connected</span>
            </div>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:20px;max-width:800px;">
    <div class="panel-head"><h2>Travel & GDS APIs</h2></div>
    <div>
        <!-- Amadeus -->
        <div class="api-card">
            <div class="api-logo" style="background:#00000015;">✈️</div>
            <div class="api-meta">
                <h4>Amadeus GDS</h4>
                <p>Live flight search, booking, and PNR management.</p>
                <div class="api-key-wrap">
                    <input type="text" name="amadeus_key" class="api-key-input" placeholder="Amadeus API Key" value="">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleApiKey(this)">Show</button>
                </div>
            </div>
            <div>
                <span class="api-disconnected"><span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span> Not Connected</span>
            </div>
        </div>
        <!-- Google Maps -->
        <div class="api-card">
            <div class="api-logo" style="background:#e8f5e9;">🗺️</div>
            <div class="api-meta">
                <h4>Google Maps</h4>
                <p>Show destinations and embassy locations on maps.</p>
                <div class="api-key-wrap">
                    <input type="text" name="maps_key" class="api-key-input" placeholder="Google Maps API Key" value="">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleApiKey(this)">Show</button>
                </div>
            </div>
            <div>
                <span class="api-disconnected"><span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span> Not Connected</span>
            </div>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:20px;max-width:800px;">
    <div class="panel-head"><h2>Communication</h2></div>
    <div>
        <!-- SMTP -->
        <div class="api-card">
            <div class="api-logo" style="background:#fef3c7;">📧</div>
            <div class="api-meta">
                <h4>SMTP Email</h4>
                <p>Send booking confirmations, invoices, and notifications via email.</p>
                <div class="form-grid-2" style="margin-top:8px;">
                    <input type="text" name="smtp_host" class="api-key-input" placeholder="SMTP Host (e.g. smtp.gmail.com)" value="">
                    <input type="text" name="smtp_port" class="api-key-input" placeholder="Port (587)" value="">
                    <input type="text" name="smtp_user" class="api-key-input" placeholder="SMTP Username" value="">
                    <input type="password" name="smtp_pass" class="api-key-input" placeholder="SMTP Password" value="">
                </div>
            </div>
        </div>
        <!-- WhatsApp / Twilio -->
        <div class="api-card">
            <div class="api-logo" style="background:#dcfce7;">📱</div>
            <div class="api-meta">
                <h4>Twilio / WhatsApp</h4>
                <p>Send SMS and WhatsApp messages to customers.</p>
                <div class="api-key-wrap">
                    <input type="text" name="twilio_sid" class="api-key-input" placeholder="Twilio Account SID" value="">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleApiKey(this)">Show</button>
                </div>
            </div>
            <div>
                <span class="api-disconnected"><span style="width:7px;height:7px;border-radius:50%;background:currentColor;display:inline-block;"></span> Not Connected</span>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;max-width:800px;justify-content:flex-end;margin-bottom:32px;">
    <button type="submit" class="btn btn-primary"><i data-lucide="save"></i> Save API Settings</button>
</div>

</form>

<script>
function toggleApiKey(btn) {
    const input = btn.previousElementSibling;
    if (!input) return;
    if (input.type === 'password' || input.type === 'text') {
        input.type = input.type === 'text' ? 'password' : 'text';
        btn.textContent = input.type === 'text' ? 'Hide' : 'Show';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
