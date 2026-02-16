<?php
require_once 'header.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle Standard Settings
    if (isset($_POST['settings'])) {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$key, $value, $value]);
        }
    }

    // Handle Custom Services (JSON)
    if (isset($_POST['custom_services'])) {
        $services = array_values($_POST['custom_services']); // Re-index array
        $json_services = json_encode($services);
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute(['custom_scripts', $json_services, $json_services]);
    } else {
        // If empty (all deleted), save empty array
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute(['custom_scripts', '[]', '[]']);
    }

    // Handle Appointment Closed Days
    if (isset($_POST['appointment_closed_days'])) {
        $closed_days = json_encode($_POST['appointment_closed_days']);
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute(['appointment_closed_days', $closed_days, $closed_days]);
    } else {
        // If not set (all days open), save empty array BUT only if we are in a POST request that likely included the settings form (simple check)
        // Better: check if 'settings' param was present implies settings form submit
        if (isset($_POST['settings'])) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute(['appointment_closed_days', '[]', '[]']);
        }
    }

    // Handle Asset Uploads (Optimized)
    $assets = ['favicon', 'og_image', 'site_logo', 'site_footer_logo'];
    foreach ($assets as $asset) {
        if (isset($_FILES[$asset]) && $_FILES[$asset]['error'] === 0) {
            $customName = $asset . '_' . uniqid();
            $filename = optimizeUpload($_FILES[$asset], '../assets/uploads/', $customName);
            
            if ($filename) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$asset, $filename, $filename]);
            }
        }
    }

    setFlash('success', 'Global settings updated successfully!');
    echo "<script>window.location.href='settings.php';</script>";
    exit;
}

// Fetch all settings
$site_title = getSetting('site_title');
$meta_desc = getSetting('meta_description');
$meta_keys = getSetting('meta_keywords');
$analytics_id = getSetting('analytics_id');
$gtm_id = getSetting('google_tag_manager_id');
$facebook_pixel = getSetting('facebook_pixel');
$favicon = getSetting('favicon');
$og_image = getSetting('og_image');
$site_logo = getSetting('site_logo');
$site_footer_logo = getSetting('site_footer_logo');
$contact_email = getSetting('contact_email');
$contact_phone = getSetting('contact_phone');
$contact_address = getSetting('contact_address');
$google_maps = getSetting('google_maps');
$facebook_link = getSetting('facebook_link');
$instagram_link = getSetting('instagram_link');
$twitter_link = getSetting('twitter_link');
$whatsapp_number = getSetting('whatsapp_number');
$maintenance_mode = getSetting('maintenance_mode', 'off');
$header_code = getSetting('header_code');
$header_code = getSetting('header_code');
$footer_code = getSetting('footer_code');
$custom_scripts = json_decode(getSetting('custom_scripts', '[]'), true);
$appointment_start = getSetting('appointment_start', '10:00');
$appointment_end = getSetting('appointment_end', '20:00');
$appointment_duration = getSetting('appointment_duration', '30');
$appointment_max_slots = getSetting('appointment_max_slots', '2');
$appointment_closed_days = json_decode(getSetting('appointment_closed_days', '[]'), true);

// Trust Sections settings
$trust_title = getSetting('trust_title', 'Your Vision is Our Priority');
$trust_desc = getSetting('trust_desc', 'With over 12 years of experience ensuring day-to-day clarity for better living through our premium eyewear and exceptional service.');
$trust_points = getSetting('trust_points', "Advanced eye testing with equipment\nWide range of 100% genuine frames\nAffordable prices with premium quality\nExpress delivery for spectacles");
$trust_btn_text = getSetting('trust_btn_text', 'Book Eye Test');
$trust_btn_link = getSetting('trust_btn_link', 'book_appointment.php');
?>

<div class="d-flex justify-content-between align-items-center mb-4" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
    <h1 class="admin-title" style="margin-bottom: 0;">Website Control Center</h1>
</div>

<style>
    .settings-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        border-bottom: 1px solid var(--admin-border);
        padding-bottom: 1rem;
        flex-wrap: wrap;
    }
    .tab-btn {
        padding: 0.6rem 1.2rem;
        border: none;
        background: none;
        color: var(--admin-text-light);
        font-weight: 600;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }
    .tab-btn.active {
        background: var(--admin-primary);
        color: white;
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }




    label { color: var(--admin-text); }
    small { color: var(--admin-text-light); }
    .alert-info { background: var(--admin-bg); border-color: var(--admin-border); color: var(--admin-text); }
    .card.bg-light { background: var(--admin-bg) !important; border: 1px solid var(--admin-border) !important; }
    .service-item.card { background: var(--admin-bg) !important; border: 1px solid var(--admin-border) !important; }
    .form-group label { margin-bottom: 0.5rem; display: block; }
</style>

<div class="card" style="max-width: 1000px;">
    <div class="settings-tabs">
        <button class="tab-btn active" onclick="showTab('seo')"><i class="fa-solid fa-search"></i> SEO & Meta</button>
        <button class="tab-btn" onclick="showTab('branding')"><i class="fa-solid fa-paint-brush"></i> Branding</button>
        <button class="tab-btn" onclick="showTab('social')"><i class="fa-solid fa-hashtag"></i> Social Media</button>
        <button class="tab-btn" onclick="showTab('appointments')"><i class="fa-solid fa-calendar-check"></i> Appointments</button>
        <button class="tab-btn" onclick="showTab('advanced')"><i class="fa-solid fa-gears"></i> Advanced</button>
        <button class="tab-btn" onclick="showTab('integration')"><i class="fa-solid fa-code"></i> Integrations</button>
        <button class="tab-btn" onclick="showTab('trust')"><i class="fa-solid fa-shield-halved"></i> Trust Sections</button>
        <button class="tab-btn" onclick="window.location.href='theme.php'" style="background: #eff6ff; color: var(--admin-primary); border: 1px solid #dbeafe;"><i class="fa-solid fa-palette"></i> Design & Layout <i class="fa-solid fa-arrow-right" style="font-size: 0.8em; margin-left: 5px;"></i></button>
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        <!-- SEO Tab -->
        <div id="seo" class="tab-content active">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Search Engine Optimization</h4>
            <div class="form-group">
                <label>Global Site Title</label>
                <input type="text" name="settings[site_title]" value="<?= htmlspecialchars($site_title) ?>">
            </div>
            
            <div class="form-group">
                <label>Global Meta Description</label>
                <textarea name="settings[meta_description]" rows="3"><?= htmlspecialchars($meta_desc) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Meta Keywords</label>
                <input type="text" name="settings[meta_keywords]" value="<?= htmlspecialchars($meta_keys) ?>">
            </div>

            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #f1f5f9;">
                <label style="margin-bottom: 1rem; display: block; color: var(--admin-text);">Social Share Image (OG Image)</label>
                <div class="drag-upload-container">
                    <div id="ogDragBox" class="drag-upload-box">
                        <div class="drag-upload-icon"><i class="fa-solid fa-share-nodes"></i></div>
                        <div class="drag-upload-text">Drop Social Image Here</div>
                        <button type="button" class="drag-upload-btn">Browse</button>
                        <input type="file" name="og_image" id="ogImageInput" style="display: none;">
                    </div>
                    <div id="ogPreview" class="drag-preview-container">
                        <img src="" class="drag-preview-image">
                    </div>
                </div>
                <?php if ($og_image): ?>
                    <img src="../assets/uploads/<?= $og_image ?>" style="width: 200px; margin-top: 1rem; border-radius: 8px;">
                <?php endif; ?>
            </div>
        </div>

        <!-- Branding Tab -->
        <div id="branding" class="tab-content">
             <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Site Assets & Identity</h4>
             
             <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
                 <div>
                    <label style="margin-bottom: 1rem; display: block;">Site Favicon</label>
                    <div class="drag-upload-container">
                        <div id="faviconDragBox" class="drag-upload-box" style="padding: 1.5rem;">
                            <div class="drag-upload-icon" style="width: 40px; height: 60px; font-size: 1.2rem;"><i class="fa-solid fa-star"></i></div>
                            <button type="button" class="drag-upload-btn" style="padding: 0.3rem 1rem; font-size: 0.8rem;">Select</button>
                            <input type="file" name="favicon" id="faviconInput" style="display: none;">
                        </div>
                        <div id="faviconPreview" class="drag-preview-container">
                            <img src="" class="drag-preview-image" style="width: 32px; height: 32px;">
                        </div>
                    </div>
                    <?php if ($favicon): ?>
                        <div style="margin-top: 1rem;"><img src="../assets/uploads/<?= $favicon ?>" style="width: 32px;"></div>
                    <?php endif; ?>
                 </div>

                 <div>
                    <label style="margin-bottom: 1rem; display: block;">Main Site Logo</label>
                    <div class="drag-upload-container">
                        <div id="logoDragBox" class="drag-upload-box" style="padding: 1.5rem;">
                            <div class="drag-upload-icon" style="width: 40px; height: 60px; font-size: 1.2rem;"><i class="fa-solid fa-image"></i></div>
                            <button type="button" class="drag-upload-btn" style="padding: 0.3rem 1rem; font-size: 0.8rem;">Select</button>
                            <input type="file" name="site_logo" id="logoInput" style="display: none;">
                        </div>
                        <div id="logoPreview" class="drag-preview-container">
                            <img src="" class="drag-preview-image">
                        </div>
                    </div>
                    <?php if ($site_logo): ?>
                        <div style="margin-top: 1rem;"><img src="../assets/uploads/<?= $site_logo ?>" style="height: 60px;"></div>
                    <?php endif; ?>
                 </div>

                 <div>
                    <label style="margin-bottom: 1rem; display: block;">Footer Logo (Optional)</label>
                    <div class="drag-upload-container">
                        <div id="footerLogoDragBox" class="drag-upload-box" style="padding: 1.5rem;">
                            <div class="drag-upload-icon" style="width: 40px; height: 60px; font-size: 1.2rem;"><i class="fa-solid fa-image"></i></div>
                            <button type="button" class="drag-upload-btn" style="padding: 0.3rem 1rem; font-size: 0.8rem;">Select</button>
                            <input type="file" name="site_footer_logo" id="footerLogoInput" style="display: none;">
                        </div>
                        <div id="footerLogoPreview" class="drag-preview-container">
                            <img src="" class="drag-preview-image">
                        </div>
                    </div>
                    <?php if ($site_footer_logo): ?>
                        <div style="margin-top: 1rem;"><img src="../assets/uploads/<?= $site_footer_logo ?>" style="height: 60px;"></div>
                    <?php endif; ?>
                 </div>
             </div>

             <div class="form-group" style="margin-top: 2rem;">
                <label>Business Address</label>
                <textarea name="settings[contact_address]" rows="2"><?= htmlspecialchars($contact_address) ?></textarea>
             </div>

             <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
                 <div class="form-group">
                    <label>Contact Phone</label>
                    <input type="text" name="settings[contact_phone]" value="<?= htmlspecialchars($contact_phone) ?>">
                 </div>
                 <div class="form-group">
                    <label>Support Email</label>
                    <input type="email" name="settings[contact_email]" value="<?= htmlspecialchars($contact_email) ?>">
                 </div>
             </div>

             <div class="form-group">
                <label>Google Maps Embed (Iframe Code)</label>
                <textarea name="settings[google_maps]" rows="3"><?= htmlspecialchars($google_maps) ?></textarea>
             </div>
        </div>

        <!-- Social Tab -->
        <div id="social" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Social Media Connectivity</h4>
            <div class="form-group">
                <label><i class="fa-brands fa-facebook"></i> Facebook Page URL</label>
                <input type="text" name="settings[facebook_link]" value="<?= htmlspecialchars($facebook_link) ?>">
            </div>
            <div class="form-group">
                <label><i class="fa-brands fa-instagram"></i> Instagram Profile URL</label>
                <input type="text" name="settings[instagram_link]" value="<?= htmlspecialchars($instagram_link) ?>">
            </div>
            <div class="form-group">
                <label><i class="fa-brands fa-twitter"></i> Twitter / X URL</label>
                <input type="text" name="settings[twitter_link]" value="<?= htmlspecialchars($twitter_link) ?>">
            </div>

        <!-- Appointments Tab -->
        <div id="appointments" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Eye Test Booking Configuration</h4>
            
            <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Start Time</label>
                    <input type="time" name="settings[appointment_start]" value="<?= htmlspecialchars($appointment_start) ?>">
                </div>
                <div class="form-group">
                    <label>End Time</label>
                    <input type="time" name="settings[appointment_end]" value="<?= htmlspecialchars($appointment_end) ?>">
                </div>
            </div>

            <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="form-group">
                    <label>Slot Duration (Minutes)</label>
                    <input type="number" name="settings[appointment_duration]" value="<?= htmlspecialchars($appointment_duration) ?>" min="10" step="5">
                </div>
                <div class="form-group">
                    <label>Max People Per Slot</label>
                    <input type="number" name="settings[appointment_max_slots]" value="<?= htmlspecialchars($appointment_max_slots) ?>" min="1">
                </div>
            </div>

            <div class="form-group">
                <label>Closed Days</label>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.5rem;">
                    <?php 
                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($days as $day): 
                    ?>
                        <label style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.6rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; cursor: pointer;">
                            <!-- Handled via hidden input to submit empty array if none checked, but basic loop here -->
                            <input type="checkbox" name="appointment_closed_days[]" value="<?= $day ?>" <?= in_array($day, $appointment_closed_days) ? 'checked' : '' ?>>
                            <?= $day ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
            <div class="form-group">
                <label><i class="fa-brands fa-whatsapp"></i> WhatsApp Number (with country code)</label>
                <input type="text" name="settings[whatsapp_number]" value="<?= htmlspecialchars($whatsapp_number) ?>" placeholder="e.g. 919876543210">
            </div>
        </div>

        <!-- Advanced Tab -->
        <div id="advanced" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Advanced Controls</h4>
            
            <div style="background: #fff7ed; padding: 1.5rem; border-radius: 12px; border: 1px solid #ffedd5; margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <h5 style="margin: 0; color: #9a3412;">Maintenance Mode</h5>
                    <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: #c2410c;">Directs visitors to an "Under Construction" page.</p>
                </div>
                <label class="toggle-switch">
                    <input type="hidden" name="settings[maintenance_mode]" value="off">
                    <input type="checkbox" name="settings[maintenance_mode]" value="on" <?= $maintenance_mode === 'on' ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div class="form-group">
                <label>Custom Header Code (Before &lt;/head&gt;)</label>
                <textarea name="settings[header_code]" rows="5" placeholder="&lt;script&gt;...&lt;/script&gt;" style="font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($header_code) ?></textarea>
                <small>Use for custom CSS or 3rd party verification script.</small>
            </div>

            <div class="form-group">
                <label>Custom Footer Code (Before &lt;/body&gt;)</label>
                <textarea name="settings[footer_code]" rows="5" placeholder="&lt;script&gt;...&lt;/script&gt;" style="font-family: monospace; font-size: 0.85rem;"><?= htmlspecialchars($footer_code) ?></textarea>
                <small>Use for chat widgets or tracking scripts.</small>
            </div>
        </div>

        <!-- Integration Tab -->
        <div id="integration" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Service Integrations</h4>
            
            <div class="alert alert-info" style="margin-bottom: 2rem;">
                <i class="fa-solid fa-lightbulb"></i> Manage third-party services like Analytics, Chat Widgets, Pixels, etc. 
            </div>

            <!-- Standard Integrations -->
            <div class="card mb-4" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                <h5 class="mb-3">Standard Integrations</h5>
                <div class="form-group">
                    <label>Google Analytics ID</label>
                    <input type="text" name="settings[analytics_id]" value="<?= htmlspecialchars($analytics_id) ?>" placeholder="e.g. G-XXXXXXXXXX">
                </div>

                <div class="form-group">
                    <label>Google Tag Manager ID</label>
                    <input type="text" name="settings[google_tag_manager_id]" value="<?= htmlspecialchars($gtm_id) ?>" placeholder="e.g. GTM-XXXXXX">
                </div>
                
                <div class="form-group">
                    <label>Facebook Pixel ID</label>
                    <input type="text" name="settings[facebook_pixel]" value="<?= htmlspecialchars($facebook_pixel) ?>">
                </div>
            </div>

            <!-- Custom Services Manager -->
            <h5 class="mb-3">Custom Services</h5>
            <div id="services-container">
                <?php if (!empty($custom_scripts)): ?>
                    <?php foreach ($custom_scripts as $index => $service): ?>
                        <div class="service-item card mb-3" style="border: 1px solid #e2e8f0; padding: 1.5rem; position: relative;">
                            <button type="button" class="btn btn-sm btn-danger remove-service" style="position: absolute; top: 1rem; right: 1rem;">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                            <div class="row" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                                <div style="flex: 1;">
                                    <label>Service Name</label>
                                    <input type="text" name="custom_services[<?= $index ?>][name]" value="<?= htmlspecialchars($service['name']) ?>" class="form-control" placeholder="e.g. Hotjar">
                                </div>
                                <div style="width: 200px;">
                                    <label>Placement</label>
                                    <select name="custom_services[<?= $index ?>][placement]" class="form-control">
                                        <option value="head" <?= $service['placement'] == 'head' ? 'selected' : '' ?>>Header (&lt;head&gt;)</option>
                                        <option value="body" <?= $service['placement'] == 'body' ? 'selected' : '' ?>>Body (Footer)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label>Script / Code</label>
                                <textarea name="custom_services[<?= $index ?>][code]" class="form-control" rows="4" style="font-family: monospace; font-size: 0.85rem;" placeholder="<script>...</script>"><?= htmlspecialchars($service['code']) ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" id="add-service-btn" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-plus"></i> Add New Service
            </button>
        </div>

        <!-- Trust Sections Tab -->
        <div id="trust" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Fixed Layout Trust Sections</h4>
            <div class="alert alert-info">
                <i class="fa-solid fa-lock"></i> The layout and design of these sections are fixed to maintain branding. You can only edit the text and links.
            </div>

            <!-- Section 1: Why Choose Us -->
            <div class="card mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0; margin-top: 2rem;">
                <h5 class="mb-4">Section 1: Why Choose Us (With Dynamic Rating)</h5>
                <div class="form-group">
                    <label>Section Title</label>
                    <input type="text" name="settings[trust_title]" value="<?= htmlspecialchars($trust_title) ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="settings[trust_desc]" rows="3"><?= htmlspecialchars($trust_desc) ?></textarea>
                </div>
                <div class="form-group">
                    <label>Trust Points (One per line)</label>
                    <textarea name="settings[trust_points]" rows="5" placeholder="Advanced eye testing..."><?= htmlspecialchars($trust_points) ?></textarea>
                </div>
                <div class="admin-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="form-group">
                        <label>Button Text</label>
                        <input type="text" name="settings[trust_btn_text]" value="<?= htmlspecialchars($trust_btn_text) ?>">
                    </div>
                    <div class="form-group">
                        <label>Button Link</label>
                        <input type="text" name="settings[trust_btn_link]" value="<?= htmlspecialchars($trust_btn_link) ?>">
                    </div>
                </div>
            </div>
        </div>

        <div style="margin-top: 3rem; border-top: 2px solid #f1f5f9; padding-top: 2rem;">
            <button type="submit" class="settings-save-btn btn btn-primary" style="padding: 1rem 3rem; font-size: 1rem; border-radius: 12px;">
                <i class="fa-solid fa-save"></i> Save Global Configuration
            </button>
        </div>
    </form>
</div>

<script>
    function showTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
        initDragAndDrop('ogDragBox', 'ogImageInput', 'ogPreview');
        initDragAndDrop('faviconDragBox', 'faviconInput', 'faviconPreview');
        // ... (other drag and drops)

        // Service Manager Logic
        const container = document.getElementById('services-container');
        const addBtn = document.getElementById('add-service-btn');

        addBtn.addEventListener('click', () => {
            const index = container.children.length;
            const item = document.createElement('div');
            item.className = 'service-item card mb-3';
            item.style.cssText = 'border: 1px solid #e2e8f0; padding: 1.5rem; position: relative; animation: fadeIn 0.3s ease;';
            item.innerHTML = `
                <button type="button" class="btn btn-sm btn-danger remove-service" style="position: absolute; top: 1rem; right: 1rem;">
                    <i class="fa-solid fa-trash"></i>
                </button>
                <div class="row" style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <div style="flex: 1;">
                        <label>Service Name</label>
                        <input type="text" name="custom_services[${index}][name]" class="form-control" placeholder="e.g. Hotjar">
                    </div>
                    <div style="width: 200px;">
                        <label>Placement</label>
                        <select name="custom_services[${index}][placement]" class="form-control">
                            <option value="head">Header (&lt;head&gt;)</option>
                            <option value="body">Body (Footer)</option>
                        </select>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label>Script / Code</label>
                    <textarea name="custom_services[${index}][code]" class="form-control" rows="4" style="font-family: monospace; font-size: 0.85rem;" placeholder="<script>...<\/script>"></textarea>
                </div>
            `;
            container.appendChild(item);
        });

        container.addEventListener('click', (e) => {
            if (e.target.closest('.remove-service')) {
                if(confirm('Remove this service?')) {
                    e.target.closest('.service-item').remove();
                }
            }
        });

        // Initialize missing Drag and Helps
        initDragAndDrop('logoDragBox', 'logoInput', 'logoPreview');
        initDragAndDrop('footerLogoDragBox', 'footerLogoInput', 'footerLogoPreview');
    });
</script>

<?php require_once 'footer.php'; ?>
