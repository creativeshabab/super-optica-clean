<?php
require_once 'header.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['theme'])) {
    // Handle Image Upload
    if (isset($_FILES['cta_bg_img']) && $_FILES['cta_bg_img']['error'] == 0) {
        $uploadDir = '../assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $customName = 'cta_bg_' . time();
        $fileName = optimizeUpload($_FILES['cta_bg_img'], $uploadDir, $customName);
        
        if ($fileName) {
            $_POST['theme']['theme_cta_bg_img'] = $fileName;
        }
    }

    foreach ($_POST['theme'] as $key => $value) {
        // Auto-serialize arrays (like menus) to JSON
        if (is_array($value)) {
            $value = json_encode($value);
        }
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }
    setFlash('success', 'Design system updated successfully!');
    echo "<script>window.location.href='theme.php';</script>";
    exit;
}

// Helper to get setting with default
if (!function_exists('getThemeSetting')) {
    function getThemeSetting($key, $default) {
        return getSetting($key, $default);
    }
}

// --- Fetch Settings ---

// Colors
$primary_color = getThemeSetting('theme_primary_color', '#e31e24');
$secondary_color = getThemeSetting('theme_secondary_color', '#64748b');
$accent_color = getThemeSetting('theme_accent_color', '#0f172a');
$background_color = getThemeSetting('theme_background_color', '#f8fafc');
$surface_color = getThemeSetting('theme_surface_color', '#ffffff');
$text_main = getThemeSetting('theme_text_main', '#1e293b');
$text_light = getThemeSetting('theme_text_light', '#64748b');

// Typography
$font_family = getThemeSetting('theme_font_family', "'Montserrat', sans-serif");
$base_font_size = getThemeSetting('theme_base_font_size', '16px');
$heading_font_weight = getThemeSetting('theme_heading_weight', '700');

// Buttons
$btn_radius = getThemeSetting('theme_btn_radius', '12px');
$btn_padding = getThemeSetting('theme_btn_padding', '0.8rem 1.5rem');
$btn_font_weight = getThemeSetting('theme_btn_font_weight', '700');
$btn_shadow = getThemeSetting('theme_btn_shadow', '0 4px 6px rgba(0,0,0,0.1)');

// Layout
$container_width = getThemeSetting('theme_container_width', '1200px');
$grid_gap = getThemeSetting('theme_grid_gap', '2rem');
$section_spacing = getThemeSetting('theme_section_spacing', '4rem');
$products_per_row = getThemeSetting('theme_products_per_row', '4');

// Components
$input_radius = getThemeSetting('theme_input_radius', '8px');
$input_bg = getThemeSetting('theme_input_bg', '#ffffff');
$card_radius = getThemeSetting('theme_card_radius', '16px');
$card_shadow = getThemeSetting('theme_card_shadow', '0 4px 20px rgba(0, 0, 0, 0.05)');
$badge_radius = getThemeSetting('theme_badge_radius', '4px');

// Animations
$anim_duration = getThemeSetting('theme_anim_duration', '0.4s');
$anim_easing = getThemeSetting('theme_anim_easing', 'ease-in-out');

// CTA Section
$cta_bg = getThemeSetting('theme_cta_bg', '#e31e24');
$cta_text = getThemeSetting('theme_cta_text', '#ffffff');
$cta_padding = getThemeSetting('theme_cta_padding', '4rem 1rem');
$cta_btn_bg = getThemeSetting('theme_cta_btn_bg', '#ffffff');
$cta_btn_transparent = getThemeSetting('theme_cta_btn_transparent', '0');
$cta_btn_text = getThemeSetting('theme_cta_btn_text', '#e31e24');
$cta_border_color = getThemeSetting('theme_cta_border_color', 'transparent');
$cta_border_width = getThemeSetting('theme_cta_border_width', '0px');
$cta_radius = getThemeSetting('theme_cta_radius', '2.5rem');
$cta_bg_img = getThemeSetting('theme_cta_bg_img', '');
$cta_overlay = getThemeSetting('theme_cta_overlay', '0.0');

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="admin-title" style="margin-bottom: 0;">Design System Manager</h1>
        <p style="color: #64748b; margin-top: 0.5rem;">Control the global visual language of your website.</p>
    </div>
    <a href="../index.php" target="_blank" class="btn btn-outline"><i class="fa-solid fa-eye"></i> View Live Site</a>
</div>

<style>
    .design-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        border-bottom: 1px solid var(--admin-border);
        padding-bottom: 1rem;
        flex-wrap: wrap;
    }
    .tab-btn {
        padding: 0.75rem 1.5rem;
        border: none;
        background: none;
        color: var(--admin-text-light);
        font-weight: 600;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .tab-btn:hover { background: var(--admin-bg); color: var(--admin-primary); }
    .tab-btn.active { background: var(--admin-primary); color: white; }
    .tab-content { display: none; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    
    .color-preview-box {
        width: 100%; height: 40px; border-radius: 6px; border: 1px solid var(--admin-border); margin-top: 0.5rem;
    }
    .control-group {
        background: var(--admin-bg); padding: 1.5rem; border-radius: 12px; border: 1px solid var(--admin-border); height: 100%;
    }
    .control-group h5 { margin-bottom: 1rem; color: var(--admin-text); font-size: 1rem; border-bottom: 1px solid var(--admin-border); padding-bottom: 0.5rem; }
    label { color: var(--admin-text); }
    small { color: var(--admin-text-light); }
    .bg-white.rounded { background: var(--admin-card) !important; border-color: var(--admin-border) !important; color: var(--admin-text) !important; }
    .text-muted { color: var(--admin-text-light) !important; }
</style>

<div class="card" style="max-width: 1200px;">
    <div class="design-tabs">
        <button class="tab-btn active" onclick="showTab('colors')"><i class="fa-solid fa-palette"></i> Colors</button>
        <button class="tab-btn" onclick="showTab('typography')"><i class="fa-solid fa-font"></i> Typography</button>
        <button class="tab-btn" onclick="showTab('buttons')"><i class="fa-solid fa-hand-pointer"></i> Buttons</button>
        <button class="tab-btn" onclick="showTab('layout')"><i class="fa-solid fa-layer-group"></i> Layout</button>
        <button class="tab-btn" onclick="showTab('layout-settings')"><i class="fa-solid fa-ruler-combined"></i> Layout Settings</button>
        <button class="tab-btn" onclick="showTab('footer')"><i class="fa-solid fa-layer-group"></i> Footer</button>
        <button class="tab-btn" onclick="showTab('components')"><i class="fa-solid fa-cubes"></i> Components</button>
        <button class="tab-btn" onclick="showTab('cta')"><i class="fa-solid fa-bullhorn"></i> CTA Section</button>
        <button class="tab-btn" onclick="showTab('animations')"><i class="fa-solid fa-film"></i> Animations</button>
        <button class="tab-btn" onclick="showTab('advanced')"><i class="fa-solid fa-code"></i> Advanced</button>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <!-- COLORS TAB -->
        <div id="colors" class="tab-content active">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Color System</h4>
            <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <!-- Brand Colors -->
                <div class="control-group">
                    <h5>Brand Identity</h5>
                    <div class="form-group">
                        <label>Primary Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_primary_color]" value="<?= $primary_color ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $primary_color ?>" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Accent Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_accent_color]" value="<?= $accent_color ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $accent_color ?>" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Secondary Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_secondary_color]" value="<?= $secondary_color ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $secondary_color ?>" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <!-- Surfaces -->
                <div class="control-group">
                    <h5>Surfaces & Backgrounds</h5>
                    <div class="form-group">
                        <label>Page Background</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_background_color]" value="<?= $background_color ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $background_color ?>" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Card/Surface Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_surface_color]" value="<?= $surface_color ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $surface_color ?>" class="form-control" readonly>
                        </div>
                    </div>
                </div>

                <!-- Text -->
                <div class="control-group">
                    <h5>Typography Colors</h5>
                    <div class="form-group">
                        <label>Main Text</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_text_main]" value="<?= $text_main ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $text_main ?>" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Muted/Light Text</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_text_light]" value="<?= $text_light ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $text_light ?>" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TYPOGRAPHY TAB -->
        <div id="typography" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Typography Settings</h4>
            <div class="admin-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="control-group">
                    <h5>Global Font</h5>
                    <div class="form-group">
                        <label>Font Family (Google Fonts)</label>
                        <select name="theme[theme_font_family]">
                            <option value="'Montserrat', sans-serif" <?= $font_family == "'Montserrat', sans-serif" ? 'selected' : '' ?>>Montserrat (Default)</option>
                            <option value="'Inter', sans-serif" <?= $font_family == "'Inter', sans-serif" ? 'selected' : '' ?>>Inter</option>
                            <option value="'Roboto', sans-serif" <?= $font_family == "'Roboto', sans-serif" ? 'selected' : '' ?>>Roboto</option>
                            <option value="'Poppins', sans-serif" <?= $font_family == "'Poppins', sans-serif" ? 'selected' : '' ?>>Poppins</option>
                            <option value="'Outfit', sans-serif" <?= $font_family == "'Outfit', sans-serif" ? 'selected' : '' ?>>Outfit</option>
                            <option value="'Lato', sans-serif" <?= $font_family == "'Lato', sans-serif" ? 'selected' : '' ?>>Lato</option>
                        </select>
                        <small>Selected font will be automatically loaded.</small>
                    </div>
                    <div class="form-group">
                        <label>Base Font Size (Desktop)</label>
                        <select name="theme[theme_base_font_size]">
                            <option value="14px" <?= $base_font_size == '14px' ? 'selected' : '' ?>>14px</option>
                            <option value="15px" <?= $base_font_size == '15px' ? 'selected' : '' ?>>15px</option>
                            <option value="16px" <?= $base_font_size == '16px' ? 'selected' : '' ?>>16px</option>
                            <option value="18px" <?= $base_font_size == '18px' ? 'selected' : '' ?>>18px</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Base Font Size (Tablet)</label>
                        <select name="theme[theme_base_font_size_tablet]">
                            <option value="14px" <?= getSetting('theme_base_font_size_tablet', '15px') == '14px' ? 'selected' : '' ?>>14px</option>
                            <option value="15px" <?= getSetting('theme_base_font_size_tablet', '15px') == '15px' ? 'selected' : '' ?>>15px</option>
                            <option value="16px" <?= getSetting('theme_base_font_size_tablet', '15px') == '16px' ? 'selected' : '' ?>>16px</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Base Font Size (Mobile)</label>
                        <select name="theme[theme_base_font_size_mobile]">
                            <option value="13px" <?= getSetting('theme_base_font_size_mobile', '14px') == '13px' ? 'selected' : '' ?>>13px</option>
                            <option value="14px" <?= getSetting('theme_base_font_size_mobile', '14px') == '14px' ? 'selected' : '' ?>>14px</option>
                            <option value="15px" <?= getSetting('theme_base_font_size_mobile', '14px') == '15px' ? 'selected' : '' ?>>15px</option>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <h5>Headings & Weights</h5>
                    <div class="form-group">
                        <label>Heading Font Weight</label>
                        <select name="theme[theme_heading_weight]">
                            <option value="600" <?= $heading_font_weight == '600' ? 'selected' : '' ?>>Semi Bold (600)</option>
                            <option value="700" <?= $heading_font_weight == '700' ? 'selected' : '' ?>>Bold (700)</option>
                            <option value="800" <?= $heading_font_weight == '800' ? 'selected' : '' ?>>Extra Bold (800)</option>
                        </select>
                    </div>
                    <div class="p-3 bg-white rounded mt-3" style="border: 1px solid #e2e8f0;">
                        <h2 style="font-weight: <?= $heading_font_weight ?>; margin: 0 0 0.5rem 0;">Heading Preview</h2>
                        <p style="margin: 0; color: #64748b;">This is how your body text will look.</p>
                    </div>
                </div>
                
                <div class="control-group" style="grid-column: span 2;">
                    <h5>Responsive Typography (Global)</h5>
                    <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.5rem;">Control font sizes across all devices. These settings apply to both the Website and Admin Dashboard.</p>
                    
                    <div class="row">
                        <!-- 1. Slider Heading (Big Display) -->
                        <div class="col-md-12 mb-4">
                            <h6 style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; border-bottom: 1px solid #eee; padding-bottom: 5px;">1. Slider / Hero Heading (Big)</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group"><label>Desktop</label><input type="text" name="theme[theme_font_display_d]" value="<?= getThemeSetting('theme_font_display_d', '3.5rem') ?>" class="form-control" placeholder="3.5rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Tablet</label><input type="text" name="theme[theme_font_display_t]" value="<?= getThemeSetting('theme_font_display_t', '2.5rem') ?>" class="form-control" placeholder="2.5rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Mobile</label><input type="text" name="theme[theme_font_display_m]" value="<?= getThemeSetting('theme_font_display_m', '2rem') ?>" class="form-control" placeholder="2rem"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Primary Heading -->
                        <div class="col-md-12 mb-4">
                            <h6 style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; border-bottom: 1px solid #eee; padding-bottom: 5px;">2. Primary Heading (H1, H2, H3)</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group"><label>Desktop</label><input type="text" name="theme[theme_font_heading_d]" value="<?= getThemeSetting('theme_font_heading_d', '2rem') ?>" class="form-control" placeholder="2rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Tablet</label><input type="text" name="theme[theme_font_heading_t]" value="<?= getThemeSetting('theme_font_heading_t', '1.75rem') ?>" class="form-control" placeholder="1.75rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Mobile</label><input type="text" name="theme[theme_font_heading_m]" value="<?= getThemeSetting('theme_font_heading_m', '1.5rem') ?>" class="form-control" placeholder="1.5rem"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Sub Heading -->
                        <div class="col-md-12 mb-4">
                            <h6 style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; border-bottom: 1px solid #eee; padding-bottom: 5px;">3. Sub Heading (H4, H5, H6)</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group"><label>Desktop</label><input type="text" name="theme[theme_font_subheading_d]" value="<?= getThemeSetting('theme_font_subheading_d', '1.25rem') ?>" class="form-control" placeholder="1.25rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Tablet</label><input type="text" name="theme[theme_font_subheading_t]" value="<?= getThemeSetting('theme_font_subheading_t', '1.1rem') ?>" class="form-control" placeholder="1.1rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Mobile</label><input type="text" name="theme[theme_font_subheading_m]" value="<?= getThemeSetting('theme_font_subheading_m', '1rem') ?>" class="form-control" placeholder="1rem"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Body Text -->
                        <div class="col-md-12 mb-4">
                            <h6 style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem; border-bottom: 1px solid #eee; padding-bottom: 5px;">4. Body Text (Paragraphs)</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group"><label>Desktop</label><input type="text" name="theme[theme_font_body_d]" value="<?= getThemeSetting('theme_font_body_d', '1rem') ?>" class="form-control" placeholder="1rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Tablet</label><input type="text" name="theme[theme_font_body_t]" value="<?= getThemeSetting('theme_font_body_t', '0.95rem') ?>" class="form-control" placeholder="0.95rem"></div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group"><label>Mobile</label><input type="text" name="theme[theme_font_body_m]" value="<?= getThemeSetting('theme_font_body_m', '0.9rem') ?>" class="form-control" placeholder="0.9rem"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. Button Size -->
                        <div class="col-md-12 mb-4">
                            <h6 style="font-size: 0.75rem; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 0.5rem;">5. Button Size</h6>
                            <div class="form-group">
                                <label>Select Size</label>
                                <select name="theme[theme_btn_size_preset]" class="form-control" style="max-width: 300px;">
                                    <option value="small" <?= getThemeSetting('theme_btn_size_preset', 'medium') == 'small' ? 'selected' : '' ?>>Small (Compact)</option>
                                    <option value="medium" <?= getThemeSetting('theme_btn_size_preset', 'medium') == 'medium' ? 'selected' : '' ?>>Medium (Standard)</option>
                                    <option value="large" <?= getThemeSetting('theme_btn_size_preset', 'medium') == 'large' ? 'selected' : '' ?>>Large (Impact)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BUTTONS TAB -->
        <div id="buttons" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Button Styles</h4>
            <div class="admin-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
                <div class="control-group">
                    <h5>Shape & Size</h5>
                    <div class="form-group">
                        <label>Border Radius</label>
                        <select name="theme[theme_btn_radius]">
                            <option value="4px" <?= $btn_radius == '4px' ? 'selected' : '' ?>>Square (4px)</option>
                            <option value="8px" <?= $btn_radius == '8px' ? 'selected' : '' ?>>Rounded (8px)</option>
                            <option value="12px" <?= $btn_radius == '12px' ? 'selected' : '' ?>>Soft (12px)</option>
                            <option value="50px" <?= $btn_radius == '50px' ? 'selected' : '' ?>>Pill (50px)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Padding (Size)</label>
                        <select name="theme[theme_btn_padding]">
                            <option value="0.5rem 1rem" <?= $btn_padding == '0.5rem 1rem' ? 'selected' : '' ?>>Compact</option>
                            <option value="0.75rem 1.5rem" <?= $btn_padding == '0.75rem 1.5rem' ? 'selected' : '' ?>>Standard</option>
                            <option value="1rem 2rem" <?= $btn_padding == '1rem 2rem' ? 'selected' : '' ?>>Large</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Font Size</label>
                        <select name="theme[theme_btn_font_size]">
                            <option value="0.875rem" <?= getThemeSetting('theme_btn_font_size', '0.875rem') == '0.875rem' ? 'selected' : '' ?>>Small (14px)</option>
                            <option value="1rem" <?= getThemeSetting('theme_btn_font_size', '0.875rem') == '1rem' ? 'selected' : '' ?>>Medium (16px)</option>
                            <option value="1.125rem" <?= getThemeSetting('theme_btn_font_size', '0.875rem') == '1.125rem' ? 'selected' : '' ?>>Large (18px)</option>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <h5>Style Attributes</h5>
                    <div class="form-group">
                        <label>Font Weight</label>
                        <select name="theme[theme_btn_font_weight]">
                            <option value="500" <?= $btn_font_weight == '500' ? 'selected' : '' ?>>Medium</option>
                            <option value="600" <?= $btn_font_weight == '600' ? 'selected' : '' ?>>Semi Bold</option>
                            <option value="700" <?= $btn_font_weight == '700' ? 'selected' : '' ?>>Bold</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Shadow (Default)</label>
                        <select name="theme[theme_btn_shadow]">
                            <option value="none" <?= $btn_shadow == 'none' ? 'selected' : '' ?>>Flat (None)</option>
                            <option value="0 2px 4px rgba(0,0,0,0.1)" <?= $btn_shadow == '0 2px 4px rgba(0,0,0,0.1)' ? 'selected' : '' ?>>Subtle</option>
                            <option value="0 4px 6px rgba(0,0,0,0.1)" <?= $btn_shadow == '0 4px 6px rgba(0,0,0,0.1)' ? 'selected' : '' ?>>Medium</option>
                            <option value="0 10px 15px rgba(0,0,0,0.1)" <?= $btn_shadow == '0 10px 15px rgba(0,0,0,0.1)' ? 'selected' : '' ?>>Floating</option>
                        </select>
                    </div>
                </div>

                <div class="control-group">
                    <h5>Hover Effects</h5>
                    <div class="form-group">
                        <label>Hover Lift (Transform)</label>
                        <select name="theme[theme_btn_hover_transform]">
                            <option value="none" <?= getThemeSetting('theme_btn_hover_transform', 'translateY(-2px)') == 'none' ? 'selected' : '' ?>>None</option>
                            <option value="translateY(-2px)" <?= getThemeSetting('theme_btn_hover_transform', 'translateY(-2px)') == 'translateY(-2px)' ? 'selected' : '' ?>>Slight Lift (-2px)</option>
                            <option value="translateY(-4px)" <?= getThemeSetting('theme_btn_hover_transform', 'translateY(-2px)') == 'translateY(-4px)' ? 'selected' : '' ?>>High Lift (-4px)</option>
                            <option value="scale(1.05)" <?= getThemeSetting('theme_btn_hover_transform', 'translateY(-2px)') == 'scale(1.05)' ? 'selected' : '' ?>>Scale Up (1.05x)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hover Shadow</label>
                        <select name="theme[theme_btn_hover_shadow]">
                            <option value="none" <?= getThemeSetting('theme_btn_hover_shadow', '0 6px 12px rgba(0,0,0,0.15)') == 'none' ? 'selected' : '' ?>>No Change</option>
                            <option value="0 4px 8px rgba(0,0,0,0.15)" <?= getThemeSetting('theme_btn_hover_shadow', '0 6px 12px rgba(0,0,0,0.15)') == '0 4px 8px rgba(0,0,0,0.15)' ? 'selected' : '' ?>>Subtle Increase</option>
                            <option value="0 6px 12px rgba(0,0,0,0.15)" <?= getThemeSetting('theme_btn_hover_shadow', '0 6px 12px rgba(0,0,0,0.15)') == '0 6px 12px rgba(0,0,0,0.15)' ? 'selected' : '' ?>>Distinct Pop</option>
                            <option value="0 15px 30px rgba(0,0,0,0.2)" <?= getThemeSetting('theme_btn_hover_shadow', '0 6px 12px rgba(0,0,0,0.15)') == '0 15px 30px rgba(0,0,0,0.2)' ? 'selected' : '' ?>>Deep Shadow</option>
                        </select>
                    </div>
                </div>

                <div class="control-group" style="display: flex; align-items: center; justify-content: center; flex-direction: column;">
                    <h5>Preview</h5>
                    <button type="button" class="btn btn-primary" style="margin-bottom: 1rem; border-radius: <?= $btn_radius ?>; padding: <?= $btn_padding ?>; font-weight: <?= $btn_font_weight ?>; box-shadow: <?= $btn_shadow ?>;">Primary Button</button>
                    <button type="button" class="btn btn-outline" style="border-radius: <?= $btn_radius ?>; padding: <?= $btn_padding ?>; font-weight: <?= $btn_font_weight ?>;">Outline Button</button>
                </div>
            </div>
        </div>

        <!-- LAYOUT TAB -->
        <div id="layout" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Layout & Spacing</h4>
            <div class="admin-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="control-group">
                    <h5>Containers</h5>
                    <div class="form-group">
                        <label>Max Container Width</label>
                        <select name="theme[theme_container_width]">
                            <option value="1100px" <?= $container_width == '1100px' ? 'selected' : '' ?>>Compact (1100px)</option>
                            <option value="1200px" <?= $container_width == '1200px' ? 'selected' : '' ?>>Standard (1200px)</option>
                            <option value="1400px" <?= $container_width == '1400px' ? 'selected' : '' ?>>Wide (1400px)</option>
                            <option value="100%" <?= $container_width == '100%' ? 'selected' : '' ?>>Fluid (100%)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Grid Gap</label>
                        <select name="theme[theme_grid_gap]">
                            <option value="1rem" <?= $grid_gap == '1rem' ? 'selected' : '' ?>>Tight (1rem)</option>
                            <option value="1.5rem" <?= $grid_gap == '1.5rem' ? 'selected' : '' ?>>Standard (1.5rem)</option>
                            <option value="2rem" <?= $grid_gap == '2rem' ? 'selected' : '' ?>>Spacious (2rem)</option>
                        </select>
                    </div>
                </div>
                
                <div class="control-group">
                    <h5>Vertical Rhythm (Section Spacing)</h5>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Desktop</label>
                            <input type="text" name="theme[theme_section_spacing]" value="<?= $section_spacing ?>" placeholder="e.g. 4rem" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tablet</label>
                            <input type="text" name="theme[theme_section_spacing_tablet]" value="<?= getSetting('theme_section_spacing_tablet', '3rem') ?>" placeholder="e.g. 3rem" class="form-control">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Mobile</label>
                            <input type="text" name="theme[theme_section_spacing_mobile]" value="<?= getSetting('theme_section_spacing_mobile', '2rem') ?>" placeholder="e.g. 2rem" class="form-control">
                        </div>
                    </div>

                    <h5 class="mt-4 pt-3 border-top border-gray-200">Grid Layout (Columns per Row)</h5>
                    
                    <div class="form-group mb-4">
                        <label class="font-bold d-block mb-2">Products</label>
                        <div class="d-flex gap-2">
                            <div class="flex-grow-1">
                                <small>Desktop</small>
                                <select name="theme[theme_products_per_row]" class="form-control">
                                    <option value="3" <?= $products_per_row == '3' ? 'selected' : '' ?>>3</option>
                                    <option value="4" <?= $products_per_row == '4' ? 'selected' : '' ?>>4</option>
                                    <option value="5" <?= $products_per_row == '5' ? 'selected' : '' ?>>5</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Tablet</small>
                                <select name="theme[theme_products_per_row_tablet]" class="form-control">
                                    <option value="2" <?= getSetting('theme_products_per_row_tablet', '2') == '2' ? 'selected' : '' ?>>2</option>
                                    <option value="3" <?= getSetting('theme_products_per_row_tablet', '2') == '3' ? 'selected' : '' ?>>3</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Mobile</small>
                                <select name="theme[theme_products_per_row_mobile]" class="form-control">
                                    <option value="1" <?= getSetting('theme_products_per_row_mobile', '2') == '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= getSetting('theme_products_per_row_mobile', '2') == '2' ? 'selected' : '' ?>>2</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-bold d-block mb-2">Services</label>
                        <div class="d-flex gap-2">
                             <div class="flex-grow-1">
                                <small>Desktop</small>
                                <select name="theme[theme_services_per_row]" class="form-control">
                                    <option value="3" <?= getSetting('theme_services_per_row', '3') == '3' ? 'selected' : '' ?>>3</option>
                                    <option value="4" <?= getSetting('theme_services_per_row', '3') == '4' ? 'selected' : '' ?>>4</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Tablet</small>
                                <select name="theme[theme_services_per_row_tablet]" class="form-control">
                                    <option value="2" <?= getSetting('theme_services_per_row_tablet', '2') == '2' ? 'selected' : '' ?>>2</option>
                                    <option value="3" <?= getSetting('theme_services_per_row_tablet', '2') == '3' ? 'selected' : '' ?>>3</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Mobile</small>
                                <select name="theme[theme_services_per_row_mobile]" class="form-control">
                                    <option value="1" <?= getSetting('theme_services_per_row_mobile', '1') == '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= getSetting('theme_services_per_row_mobile', '1') == '2' ? 'selected' : '' ?>>2</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-bold d-block mb-2">Customer Reviews</label>
                        <div class="d-flex gap-2">
                             <div class="flex-grow-1">
                                <small>Desktop</small>
                                <select name="theme[theme_reviews_per_row]" class="form-control">
                                    <option value="2" <?= getSetting('theme_reviews_per_row', '3') == '2' ? 'selected' : '' ?>>2</option>
                                    <option value="3" <?= getSetting('theme_reviews_per_row', '3') == '3' ? 'selected' : '' ?>>3</option>
                                    <option value="4" <?= getSetting('theme_reviews_per_row', '3') == '4' ? 'selected' : '' ?>>4</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Tablet</small>
                                <select name="theme[theme_reviews_per_row_tablet]" class="form-control">
                                    <option value="1" <?= getSetting('theme_reviews_per_row_tablet', '2') == '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= getSetting('theme_reviews_per_row_tablet', '2') == '2' ? 'selected' : '' ?>>2</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Mobile</small>
                                <select name="theme[theme_reviews_per_row_mobile]" class="form-control">
                                    <option value="1" <?= getSetting('theme_reviews_per_row_mobile', '1') == '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= getSetting('theme_reviews_per_row_mobile', '1') == '2' ? 'selected' : '' ?>>2</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="font-bold d-block mb-2">Latest News (Blog)</label>
                        <div class="d-flex gap-2">
                             <div class="flex-grow-1">
                                <small>Desktop</small>
                                <select name="theme[theme_blog_per_row]" class="form-control">
                                    <option value="2" <?= getSetting('theme_blog_per_row', '3') == '2' ? 'selected' : '' ?>>2</option>
                                    <option value="3" <?= getSetting('theme_blog_per_row', '3') == '3' ? 'selected' : '' ?>>3</option>
                                    <option value="4" <?= getSetting('theme_blog_per_row', '3') == '4' ? 'selected' : '' ?>>4</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Tablet</small>
                                <select name="theme[theme_blog_per_row_tablet]" class="form-control">
                                    <option value="1" <?= getSetting('theme_blog_per_row_tablet', '2') == '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= getSetting('theme_blog_per_row_tablet', '2') == '2' ? 'selected' : '' ?>>2</option>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <small>Mobile</small>
                                <select name="theme[theme_blog_per_row_mobile]" class="form-control">
                                    <option value="1" <?= getSetting('theme_blog_per_row_mobile', '1') == '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= getSetting('theme_blog_per_row_mobile', '1') == '2' ? 'selected' : '' ?>>2</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- LAYOUT SETTINGS TAB -->
        <div id="layout-settings" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Global Layout Settings</h4>
            <p style="color: #64748b; margin-bottom: 2rem;">Control container widths and padding across the entire website. These settings apply to all sections from header to footer.</p>
            
            <div class="row">
                <div class="col-md-12">
                    <div class="control-group">
                        <h5><i class="fa-solid fa-ruler-horizontal"></i> Container Max Width</h5>
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">Set the maximum width of content containers on different devices.</p>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Desktop Max Width</label>
                                    <input type="text" name="theme[theme_container_max_width_desktop]" value="<?= getThemeSetting('theme_container_max_width_desktop', '1400px') ?>" placeholder="e.g. 1400px" class="form-control">
                                    <small class="text-muted">Default: 1400px</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tablet Max Width</label>
                                    <input type="text" name="theme[theme_container_max_width_tablet]" value="<?= getThemeSetting('theme_container_max_width_tablet', '1024px') ?>" placeholder="e.g. 1024px" class="form-control">
                                    <small class="text-muted">Default: 1024px</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mobile Max Width</label>
                                    <input type="text" name="theme[theme_container_max_width_mobile]" value="<?= getThemeSetting('theme_container_max_width_mobile', '100%') ?>" placeholder="e.g. 100%" class="form-control">
                                    <small class="text-muted">Default: 100%</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="control-group">
                        <h5><i class="fa-solid fa-arrows-left-right"></i> Container Horizontal Padding</h5>
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1rem;">Set the left and right padding inside containers on different devices.</p>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Desktop Padding</label>
                                    <input type="text" name="theme[theme_container_padding_desktop]" value="<?= getThemeSetting('theme_container_padding_desktop', '2rem') ?>" placeholder="e.g. 2rem" class="form-control">
                                    <small class="text-muted">Default: 2rem</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Tablet Padding</label>
                                    <input type="text" name="theme[theme_container_padding_tablet]" value="<?= getThemeSetting('theme_container_padding_tablet', '1.5rem') ?>" placeholder="e.g. 1.5rem" class="form-control">
                                    <small class="text-muted">Default: 1.5rem</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Mobile Padding</label>
                                    <input type="text" name="theme[theme_container_padding_mobile]" value="<?= getThemeSetting('theme_container_padding_mobile', '1rem') ?>" placeholder="e.g. 1rem" class="form-control">
                                    <small class="text-muted">Default: 1rem</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <i class="fa-solid fa-info-circle"></i> <strong>Note:</strong> These settings will apply to all sections using the <code>.container</code> class. Changes will be reflected immediately after saving.
            </div>
        </div>

        <!-- FOOTER TAB -->
        <div id="footer" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Footer Configuration</h4>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Footer Background</label>
                        <div class="color-picker-wrapper">
                            <input type="color" name="theme[theme_footer_bg]" value="<?= getThemeSetting('theme_footer_bg', '#1e293b') ?>" class="form-control" style="height: 50px;">
                            <input type="text" value="<?= getThemeSetting('theme_footer_bg', '#1e293b') ?>" class="form-control color-code" readonly>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Footer Text Color</label>
                        <div class="color-picker-wrapper">
                            <input type="color" name="theme[theme_footer_text]" value="<?= getThemeSetting('theme_footer_text', '#f8fafc') ?>" class="form-control" style="height: 50px;">
                            <input type="text" value="<?= getThemeSetting('theme_footer_text', '#f8fafc') ?>" class="form-control color-code" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label>Footer Description (Below Logo)</label>
                <textarea name="theme[theme_footer_desc]" class="form-control" rows="3"><?= getThemeSetting('theme_footer_desc', 'Experience crystal clear vision with our advanced eye testing and premium eyewear collections.') ?></textarea>
            </div>

            <div class="form-group mb-4">
                <label>Copyright Text</label>
                <input type="text" name="theme[theme_copyright_text]" class="form-control" value="<?= getThemeSetting('theme_copyright_text', '&copy; ' . date('Y') . ' Super Optical. All Rights Reserved.') ?>">
            </div>

            <!-- Social Media Links -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5 class="mb-3">Social Media Links</h5>
                    <p style="color: #64748b; font-size: 0.9rem;">These links will appear in the top bar of your website.</p>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fa-brands fa-facebook"></i> Facebook URL</label>
                        <input type="url" name="theme[theme_social_facebook]" class="form-control" value="<?= getThemeSetting('theme_social_facebook', '') ?>" placeholder="https://facebook.com/yourpage">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fa-brands fa-instagram"></i> Instagram URL</label>
                        <input type="url" name="theme[theme_social_instagram]" class="form-control" value="<?= getThemeSetting('theme_social_instagram', '') ?>" placeholder="https://instagram.com/yourprofile">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fa-brands fa-twitter"></i> Twitter URL</label>
                        <input type="url" name="theme[theme_social_twitter]" class="form-control" value="<?= getThemeSetting('theme_social_twitter', '') ?>" placeholder="https://twitter.com/yourprofile">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label><i class="fa-brands fa-youtube"></i> YouTube URL</label>
                        <input type="url" name="theme[theme_social_youtube]" class="form-control" value="<?= getThemeSetting('theme_social_youtube', '') ?>" placeholder="https://youtube.com/yourchannel">
                    </div>
                </div>
            </div>

            <!-- Link Managers -->
            <div class="row">
                <!-- Quick Links -->
                <div class="col-md-6">
                    <div class="card p-3 bg-light border-0">
                        <h5 class="mb-3">Quick Links Menu</h5>
                        <div id="quick-links-container">
                            <?php 
                            $quick_links = json_decode(getThemeSetting('theme_footer_quick_links', '[]'), true);
                            if (empty($quick_links)) {
                                $quick_links = [
                                    ['label' => 'Home', 'url' => 'index.php'],
                                    ['label' => 'Shop', 'url' => 'shop.php'],
                                    ['label' => 'About Us', 'url' => 'about.php'],
                                    ['label' => 'Contact', 'url' => 'contact.php']
                                ];
                            }
                            foreach ($quick_links as $i => $link): 
                            ?>
                            <div class="d-flex gap-2 mb-2 link-row">
                                <input type="text" name="theme[theme_footer_quick_links][<?= $i ?>][label]" class="form-control" placeholder="Label" value="<?= htmlspecialchars($link['label']) ?>">
                                <input type="text" name="theme[theme_footer_quick_links][<?= $i ?>][url]" class="form-control" placeholder="URL" value="<?= htmlspecialchars($link['url']) ?>">
                            </div>
                            <?php endforeach; ?>
                            <!-- Extra Empty Slots -->
                             <?php for($j=count($quick_links); $j<6; $j++): ?>
                            <div class="d-flex gap-2 mb-2 link-row">
                                <input type="text" name="theme[theme_footer_quick_links][<?= $j ?>][label]" class="form-control" placeholder="Label">
                                <input type="text" name="theme[theme_footer_quick_links][<?= $j ?>][url]" class="form-control" placeholder="URL">
                            </div>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted">Leave empty to hide.</small>
                    </div>
                </div>

                <!-- Policy/Service Links -->
                <div class="col-md-6">
                   <div class="card p-3 bg-light border-0">
                        <h5 class="mb-3">Customer Service / Policies</h5>
                        <div id="service-links-container">
                            <?php 
                            $service_links = json_decode(getThemeSetting('theme_footer_service_links', '[]'), true);
                            if (empty($service_links)) {
                                $service_links = [
                                    ['label' => 'Privacy Policy', 'url' => 'privacy.php'],
                                    ['label' => 'Terms of Service', 'url' => 'terms.php'],
                                    ['label' => 'Refund Policy', 'url' => 'refund.php'],
                                    ['label' => 'Shipping Policy', 'url' => 'shipping.php']
                                ];
                            }
                            foreach ($service_links as $i => $link): 
                            ?>
                            <div class="d-flex gap-2 mb-2 link-row">
                                <input type="text" name="theme[theme_footer_service_links][<?= $i ?>][label]" class="form-control" placeholder="Label" value="<?= htmlspecialchars($link['label']) ?>">
                                <input type="text" name="theme[theme_footer_service_links][<?= $i ?>][url]" class="form-control" placeholder="URL" value="<?= htmlspecialchars($link['url']) ?>">
                            </div>
                            <?php endforeach; ?>
                             <!-- Extra Empty Slots -->
                             <?php for($j=count($service_links); $j<6; $j++): ?>
                            <div class="d-flex gap-2 mb-2 link-row">
                                <input type="text" name="theme[theme_footer_service_links][<?= $j ?>][label]" class="form-control" placeholder="Label">
                                <input type="text" name="theme[theme_footer_service_links][<?= $j ?>][url]" class="form-control" placeholder="URL">
                            </div>
                            <?php endfor; ?>
                        </div>
                        <small class="text-muted">Leave empty to hide.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- COMPONENTS TAB -->
        <div id="components" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Component Styling & Visibility</h4>
            <div class="admin-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                
                <!-- Visibility Controls -->
                <div class="control-group">
                    <h5>Homepage Sections</h5>
                    <div class="form-group">
                        <label>Show Hero Slider</label>
                        <select name="theme[theme_show_hero]">
                            <option value="1" <?= getSetting('theme_show_hero', '1') == '1' ? 'selected' : '' ?>>Show</option>
                            <option value="0" <?= getSetting('theme_show_hero', '1') == '0' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Show Eye Test Promo</label>
                        <select name="theme[theme_show_eyetest]">
                            <option value="1" <?= getSetting('theme_show_eyetest', '1') == '1' ? 'selected' : '' ?>>Show</option>
                            <option value="0" <?= getSetting('theme_show_eyetest', '1') == '0' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Show Featured Products</label>
                        <select name="theme[theme_show_featured]">
                            <option value="1" <?= getSetting('theme_show_featured', '1') == '1' ? 'selected' : '' ?>>Show</option>
                            <option value="0" <?= getSetting('theme_show_featured', '1') == '0' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Show Services Section</label>
                        <select name="theme[theme_show_services]">
                            <option value="1" <?= getSetting('theme_show_services', '1') == '1' ? 'selected' : '' ?>>Show</option>
                            <option value="0" <?= getSetting('theme_show_services', '1') == '0' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Show Trust Badges</label>
                        <select name="theme[theme_show_trust]">
                            <option value="1" <?= getSetting('theme_show_trust', '1') == '1' ? 'selected' : '' ?>>Show</option>
                            <option value="0" <?= getSetting('theme_show_trust', '1') == '0' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Show Customer Reviews</label>
                        <select name="theme[theme_show_reviews]">
                            <option value="1" <?= getSetting('theme_show_reviews', '1') == '1' ? 'selected' : '' ?>>Show</option>
                            <option value="0" <?= getSetting('theme_show_reviews', '1') == '0' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Show Latest News (Blog)</label>
                        <select name="theme[theme_show_blog]">
                            <option value="1" <?= getSetting('theme_show_blog', '1') == '1' ? 'selected' : '' ?>>Show</option>
                            <option value="0" <?= getSetting('theme_show_blog', '1') == '0' ? 'selected' : '' ?>>Hide</option>
                        </select>
                    </div>


                </div>
                <!-- Cards -->
                <div class="control-group">
                    <h5>Cards & Panels</h5>
                    <div class="form-group">
                        <label>Card Radius</label>
                        <input type="text" name="theme[theme_card_radius]" value="<?= htmlspecialchars($card_radius) ?>" placeholder="e.g. 16px">
                    </div>
                    <div class="form-group">
                        <label>Card Shadow</label>
                        <select name="theme[theme_card_shadow]">
                            <option value="none" <?= $card_shadow == 'none' ? 'selected' : '' ?>>None</option>
                            <option value="0 2px 5px rgba(0,0,0,0.05)" <?= $card_shadow == '0 2px 5px rgba(0,0,0,0.05)' ? 'selected' : '' ?>>Light</option>
                            <option value="0 4px 20px rgba(0, 0, 0, 0.05)" <?= $card_shadow == '0 4px 20px rgba(0, 0, 0, 0.05)' ? 'selected' : '' ?>>Medium</option>
                            <option value="0 10px 30px rgba(0,0,0,0.1)" <?= $card_shadow == '0 10px 30px rgba(0,0,0,0.1)' ? 'selected' : '' ?>>Heavy</option>
                        </select>
                    </div>
                </div>

                <!-- Labels -->
                <div class="control-group">
                    <h5>Form Labels</h5>
                    <div class="form-group">
                        <label>Font Size</label>
                        <select name="theme[theme_label_size]">
                            <option value="0.875rem" <?= getThemeSetting('theme_label_size', '0.9rem') == '0.875rem' ? 'selected' : '' ?>>Small (14px)</option>
                            <option value="0.9rem" <?= getThemeSetting('theme_label_size', '0.9rem') == '0.9rem' ? 'selected' : '' ?>>Standard (14.4px)</option>
                            <option value="1rem" <?= getThemeSetting('theme_label_size', '0.9rem') == '1rem' ? 'selected' : '' ?>>Large (16px)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Font Weight</label>
                        <select name="theme[theme_label_weight]">
                            <option value="500" <?= getThemeSetting('theme_label_weight', '700') == '500' ? 'selected' : '' ?>>Medium</option>
                            <option value="600" <?= getThemeSetting('theme_label_weight', '700') == '600' ? 'selected' : '' ?>>Semi Bold</option>
                            <option value="700" <?= getThemeSetting('theme_label_weight', '700') == '700' ? 'selected' : '' ?>>Bold</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bottom Spacing</label>
                         <select name="theme[theme_label_mb]">
                            <option value="0.25rem" <?= getThemeSetting('theme_label_mb', '0.5rem') == '0.25rem' ? 'selected' : '' ?>>Tight (0.25rem)</option>
                            <option value="0.5rem" <?= getThemeSetting('theme_label_mb', '0.5rem') == '0.5rem' ? 'selected' : '' ?>>Standard (0.5rem)</option>
                            <option value="0.75rem" <?= getThemeSetting('theme_label_mb', '0.5rem') == '0.75rem' ? 'selected' : '' ?>>Wide (0.75rem)</option>
                        </select>
                    </div>
                </div>

                <!-- Inputs -->
                <div class="control-group">
                    <h5>Form Inputs</h5>
                    <div class="form-group">
                        <label>Input Radius</label>
                        <select name="theme[theme_input_radius]">
                            <option value="4px" <?= $input_radius == '4px' ? 'selected' : '' ?>>Square</option>
                            <option value="8px" <?= $input_radius == '8px' ? 'selected' : '' ?>>Rounded</option>
                            <option value="12px" <?= $input_radius == '12px' ? 'selected' : '' ?>>Soft</option>
                            <option value="25px" <?= $input_radius == '25px' ? 'selected' : '' ?>>Pill</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Background Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_input_bg]" value="<?= $input_bg ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $input_bg ?>" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Border Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_input_border]" value="<?= getThemeSetting('theme_input_border', '#e2e8f0') ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= getThemeSetting('theme_input_border', '#e2e8f0') ?>" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Focus Border Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                             <input type="color" name="theme[theme_input_focus_border]" value="<?= getThemeSetting('theme_input_focus_border', $primary_color) ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                             <input type="text" value="<?= getThemeSetting('theme_input_focus_border', $primary_color) ?>" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 form-group">
                            <label>Vertical Padding</label>
                            <select name="theme[theme_input_padding_y]">
                                <option value="0.5rem" <?= getThemeSetting('theme_input_padding_y', '0.8rem') == '0.5rem' ? 'selected' : '' ?>>Compact (0.5rem)</option>
                                <option value="0.8rem" <?= getThemeSetting('theme_input_padding_y', '0.8rem') == '0.8rem' ? 'selected' : '' ?>>Standard (0.8rem)</option>
                                <option value="1rem" <?= getThemeSetting('theme_input_padding_y', '0.8rem') == '1rem' ? 'selected' : '' ?>>Spacious (1rem)</option>
                            </select>
                        </div>
                        <div class="col-6 form-group">
                             <label>Horizontal Padding</label>
                             <select name="theme[theme_input_padding_x]">
                                <option value="0.75rem" <?= getThemeSetting('theme_input_padding_x', '1rem') == '0.75rem' ? 'selected' : '' ?>>Compact</option>
                                <option value="1rem" <?= getThemeSetting('theme_input_padding_x', '1rem') == '1rem' ? 'selected' : '' ?>>Standard</option>
                                <option value="1.5rem" <?= getThemeSetting('theme_input_padding_x', '1rem') == '1.5rem' ? 'selected' : '' ?>>Wide</option>
                             </select>
                        </div>
                    </div>
                    
                    <div class="form-group" style="border-top: 1px dashed #cbd5e1; margin-top: 1rem; padding-top: 1rem;">
                         <label class="d-block mb-2 font-bold" style="color: var(--accent);">Icon Input Settings</label>
                         
                         <div class="row">
                             <div class="col-6">
                                <label>Icon Position</label>
                                <select name="theme[theme_input_icon_position]">
                                    <option value="left" <?= getThemeSetting('theme_input_icon_position', 'left') == 'left' ? 'selected' : '' ?>>Left Side</option>
                                    <option value="right" <?= getThemeSetting('theme_input_icon_position', 'left') == 'right' ? 'selected' : '' ?>>Right Side</option>
                                </select>
                             </div>
                             <div class="col-6">
                                <label>Icon Color</label>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="color" name="theme[theme_input_icon_color]" value="<?= getThemeSetting('theme_input_icon_color', '#94a3b8') ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                                    <input type="text" value="<?= getThemeSetting('theme_input_icon_color', '#94a3b8') ?>" class="form-control" readonly>
                                </div>
                             </div>
                         </div>
                         
                         <div class="row mt-3">
                             <div class="col-6">
                                 <label>Icon Padding (Spacing)</label>
                                <select name="theme[theme_input_icon_padding]">
                                    <option value="2rem" <?= getThemeSetting('theme_input_icon_padding', '2.5rem') == '2rem' ? 'selected' : '' ?>>Compact (2rem)</option>
                                    <option value="2.5rem" <?= getThemeSetting('theme_input_icon_padding', '2.5rem') == '2.5rem' ? 'selected' : '' ?>>Standard (2.5rem)</option>
                                    <option value="3rem" <?= getThemeSetting('theme_input_icon_padding', '2.5rem') == '3rem' ? 'selected' : '' ?>>Wide (3rem)</option>
                                </select>
                             </div>
                             <div class="col-6">
                                <label>Icon Size</label>
                                <select name="theme[theme_input_icon_size]">
                                    <option value="0.9rem" <?= getThemeSetting('theme_input_icon_size', '1rem') == '0.9rem' ? 'selected' : '' ?>>Small</option>
                                    <option value="1rem" <?= getThemeSetting('theme_input_icon_size', '1rem') == '1rem' ? 'selected' : '' ?>>Standard</option>
                                    <option value="1.2rem" <?= getThemeSetting('theme_input_icon_size', '1rem') == '1.2rem' ? 'selected' : '' ?>>Large</option>
                                </select>
                             </div>
                         </div>
                    </div>
                </div>

                <!-- Badges -->
                <div class="control-group">
                    <h5>Badges & Labels</h5>
                    <div class="form-group">
                        <label>Badge Radius</label>
                        <select name="theme[theme_badge_radius]">
                            <option value="4px" <?= $badge_radius == '4px' ? 'selected' : '' ?>>Square</option>
                            <option value="12px" <?= $badge_radius == '12px' ? 'selected' : '' ?>>Rounded</option>
                        </select>
                    </div>
                </div>

                <!-- Status Colors -->
                <div class="control-group">
                    <h5>Status & Alerts</h5>
                    <div class="form-group">
                        <label>Success Light (BG)</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_success_light]" value="<?= getSetting('theme_success_light', '#dcfce7') ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= getSetting('theme_success_light', '#dcfce7') ?>" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA SECTION TAB -->
        <div id="cta" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">CTA Section Manager</h4>
            <div class="alert alert-info" style="margin-bottom: 2rem; background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; padding: 1rem; border-radius: 8px;">
                <i class="fa-solid fa-info-circle"></i> This controls the "Ready to Experience" section on your homepage.
            </div>
            
            <div class="admin-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="control-group">
                    <h5>Appearance</h5>
                    <div class="form-group">
                        <label>Background Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_cta_bg]" value="<?= $cta_bg ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $cta_bg ?>" class="form-control" readonly>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Text Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_cta_text]" value="<?= $cta_text ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $cta_text ?>" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Background Image (Optional)</label>
                        <input type="file" name="cta_bg_img" accept="image/*" class="form-control" style="padding: 0.5rem;">
                        <?php if($cta_bg_img): ?>
                            <div style="margin-top: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                                <img src="../assets/uploads/<?= $cta_bg_img ?>" style="height: 40px; border-radius: 4px; object-fit: cover;">
                                <small class="text-muted">Current Image</small>
                                <input type="hidden" name="theme[theme_cta_bg_img]" value="<?= $cta_bg_img ?>">
                                <div style="margin-left: auto;">
                                    <label style="font-size: 0.8rem; display: flex; align-items: center; gap: 0.25rem;">
                                        <input type="checkbox" name="theme[theme_cta_bg_img]" value="" style="width: auto;"> Remove
                                    </label>
                                </div>
                            </div>
                            
                            <div style="margin-top: 1rem;">
                                <label style="font-size: 0.9rem; color: #64748b;">Overlay Opacity</label>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="range" min="0" max="100" value="<?= intval($cta_overlay * 100) ?>" oninput="this.nextElementSibling.value = this.value + '%'; document.getElementById('cta_overlay_input').value = (this.value / 100)" style="flex: 1;">
                                    <input type="text" value="<?= intval($cta_overlay * 100) ?>%" style="width: 50px; text-align: center; border:none; background:none; font-weight:bold;" readonly>
                                    <input type="hidden" id="cta_overlay_input" name="theme[theme_cta_overlay]" value="<?= $cta_overlay ?>">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Section Radius</label>
                         <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="range" min="0" max="100" value="<?= intval($cta_radius) ?>" oninput="this.nextElementSibling.value = this.value + 'px'; document.getElementById('cta_radius_input').value = this.value + 'px'" style="flex: 1;">
                            <input type="text" id="cta_radius_input" name="theme[theme_cta_radius]" value="<?= $cta_radius ?>" style="width: 80px; text-align: center;" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Section Padding</label>
                        <select name="theme[theme_cta_padding]">
                            <option value="2rem 1rem" <?= $cta_padding == '2rem 1rem' ? 'selected' : '' ?>>Compact (2rem)</option>
                            <option value="4rem 1rem" <?= $cta_padding == '4rem 1rem' ? 'selected' : '' ?>>Standard (4rem)</option>
                            <option value="6rem 1rem" <?= $cta_padding == '6rem 1rem' ? 'selected' : '' ?>>Spacious (6rem)</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #cbd5e1;">
                         <label>Border Style</label>
                         <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <small style="display:block; margin-bottom:0.25rem; color:#64748b;">Width</small>
                                <select name="theme[theme_cta_border_width]">
                                    <option value="0px" <?= $cta_border_width == '0px' ? 'selected' : '' ?>>None (0px)</option>
                                    <option value="1px" <?= $cta_border_width == '1px' ? 'selected' : '' ?>>Thin (1px)</option>
                                    <option value="2px" <?= $cta_border_width == '2px' ? 'selected' : '' ?>>Medium (2px)</option>
                                    <option value="4px" <?= $cta_border_width == '4px' ? 'selected' : '' ?>>Thick (4px)</option>
                                    <option value="8px" <?= $cta_border_width == '8px' ? 'selected' : '' ?>>Heavy (8px)</option>
                                </select>
                            </div>
                            <div>
                                <small style="display:block; margin-bottom:0.25rem; color:#64748b;">Color</small>
                                <div style="display: flex; gap: 0.5rem;">
                                    <input type="color" name="theme[theme_cta_border_color]" value="<?= $cta_border_color ?>" style="height: 40px; border: none; background: none; cursor: pointer; width: 100%;">
                                </div>
                            </div>
                         </div>
                    </div>
                </div>

                <div class="control-group">
                    <h5>CTA Buttons</h5>
                    <div class="form-group">
                        <label>Button Background</label>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color" name="theme[theme_cta_btn_bg]" value="<?= $cta_btn_bg ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $cta_btn_bg ?>" class="form-control" readonly>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <label style="font-size: 0.9rem; display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="hidden" name="theme[theme_cta_btn_transparent]" value="0">
                                <input type="checkbox" name="theme[theme_cta_btn_transparent]" value="1" <?= $cta_btn_transparent ? 'checked' : '' ?>> 
                                Transparent Background (Outline Style)
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Button Text Color</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <input type="color" name="theme[theme_cta_btn_text]" value="<?= $cta_btn_text ?>" style="height: 40px; border: none; background: none; cursor: pointer;">
                            <input type="text" value="<?= $cta_btn_text ?>" class="form-control" readonly>
                        </div>
                    </div>
                    
                    <div class="p-3 mt-3 rounded" style="background: <?= $cta_bg ?>; color: <?= $cta_text ?>; text-align: center;">
                        <span style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Preview</span>
                        <button type="button" style="background: <?= $cta_btn_bg ?>; color: <?= $cta_btn_text ?>; padding: 0.5rem 1rem; border: none; border-radius: 8px; font-weight: bold;">Click Me</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ANIMATIONS TAB -->
        <div id="animations" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Animation Settings</h4>
            <div class="admin-grid" style="grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div class="control-group">
                    <h5>Timing</h5>
                    <div class="form-group">
                        <label>Global Animation Duration</label>
                        <select name="theme[theme_anim_duration]">
                            <option value="0.2s" <?= $anim_duration == '0.2s' ? 'selected' : '' ?>>Fast (0.2s)</option>
                            <option value="0.4s" <?= $anim_duration == '0.4s' ? 'selected' : '' ?>>Standard (0.4s)</option>
                            <option value="0.8s" <?= $anim_duration == '0.8s' ? 'selected' : '' ?>>Slow (0.8s)</option>
                        </select>
                    </div>
                </div>
                <div class="control-group">
                    <h5>Feel</h5>
                    <div class="form-group">
                        <label>Easing Function</label>
                        <select name="theme[theme_anim_easing]">
                            <option value="linear" <?= $anim_easing == 'linear' ? 'selected' : '' ?>>Linear (Constant)</option>
                            <option value="ease" <?= $anim_easing == 'ease' ? 'selected' : '' ?>>Ease (Default)</option>
                            <option value="ease-in-out" <?= $anim_easing == 'ease-in-out' ? 'selected' : '' ?>>Smooth (Ease In Out)</option>
                            <option value="cubic-bezier(0.68, -0.55, 0.27, 1.55)" <?= $anim_easing == 'cubic-bezier(0.68, -0.55, 0.27, 1.55)' ? 'selected' : '' ?>>Bouncy</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ADVANCED TAB -->
        <div id="advanced" class="tab-content">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-text);">Advanced Overrides</h4>
            <div class="alert alert-warning" style="margin-bottom: 2rem; background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 1rem; border-radius: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>CAUTION:</strong> Any CSS or JS added here will bypass the Design System settings. Use for specific overrides only.
            </div>

            <div class="form-group">
                <label class="font-bold">Custom CSS Overrides</label>
                <textarea name="theme[theme_custom_css]" rows="10" class="form-control code-editor" style="font-family: monospace; background: #1e293b; color: #e2e8f0; border-radius: 8px;"><?= getSetting('theme_custom_css', '') ?></textarea>
                <small class="text-muted">Applied globally after all other styles.</small>
            </div>

            <div class="form-group mt-4">
                <label class="font-bold">Custom Footer Scripts (JS)</label>
                <textarea name="theme[theme_custom_js]" rows="6" class="form-control code-editor" style="font-family: monospace; background: #1e293b; color: #e2e8f0; border-radius: 8px;"><?= getSetting('theme_custom_js', '') ?></textarea>
                <small class="text-muted">Injected before the closing &lt;/body&gt; tag.</small>
            </div>
        </div>

        <div style="margin-top: 3rem; border-top: 2px solid #f1f5f9; padding-top: 2rem; position: sticky; bottom: 0; background: white; z-index: 10;">
            <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 1rem; border-radius: 12px; width: 100%;">
                <i class="fa-solid fa-save"></i> Save System Changes
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
</script>

<?php require_once 'footer.php'; ?>
