<?php
// Helper to safely get theme settings with defaults
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
$base_font_size_tablet = getThemeSetting('theme_base_font_size_tablet', '15px');
$base_font_size_mobile = getThemeSetting('theme_base_font_size_mobile', '14px');
$heading_font_weight = getThemeSetting('theme_heading_weight', '700');

// --- SIMPLIFIED & RESPONSIVE TYPOGRAPHY MAPPING ---

// 1. Helper for scaling (still used for H3/H5 hierarchy)
if (!function_exists('scale_font')) {
    function scale_font($size, $factor) {
        if (strpos($size, 'rem') !== false) {
            return (floatval($size) * $factor) . 'rem';
        }
        if (strpos($size, 'px') !== false) {
            return round(floatval($size) * $factor) . 'px';
        }
        return $size;
    }
}

// 2. Fetch & Map Settings (Desktop / Tablet / Mobile)

// A. Slider / Hero (H1)
$h1_size_d = getThemeSetting('theme_font_display_d', '3.5rem');
$h1_size_t = getThemeSetting('theme_font_display_t', '2.5rem');
$h1_size_m = getThemeSetting('theme_font_display_m', '2rem');

// B. Primary Heading (H2 is base, H3 is slightly smaller)
$h2_size_d = getThemeSetting('theme_font_heading_d', '2rem');
$h2_size_t = getThemeSetting('theme_font_heading_t', '1.75rem');
$h2_size_m = getThemeSetting('theme_font_heading_m', '1.5rem');

$h3_size_d = scale_font($h2_size_d, 0.9);
$h3_size_t = scale_font($h2_size_t, 0.9);
$h3_size_m = scale_font($h2_size_m, 0.9);

// C. Sub Heading (H4 is base, H5/H6 smaller)
$h4_size = getThemeSetting('theme_font_subheading_d', '1.25rem'); // Using Desktop as base for H4 variable
// Note: H4-H6 currently use a single variable in CSS "--h4-size" which isn't responsive in the root block same as H1-H3.
// To make them responsive, we need to specific media query overrides or just use the desktop value as base.
// For now, mapping the "Desktop" input to the variable.
// If full responsiveness is needed for H4, we'd need to update the @media blocks below.

// D. Body Text
$base_font_size = getThemeSetting('theme_font_body_d', '1rem');
$base_font_size_tablet = getThemeSetting('theme_font_body_t', '0.95rem');
$base_font_size_mobile = getThemeSetting('theme_font_body_m', '0.9rem');

// Extras
$p_line_height = '1.6';
$p_margin_bottom = '1rem';
$breadcrumb_size = scale_font($base_font_size, 0.85);
$font_family = getThemeSetting('theme_font_family', "'Montserrat', sans-serif"); // Restore font family fetching

// E. Buttons
$btn_size_preset = getThemeSetting('theme_btn_size_preset', 'medium');
switch($btn_size_preset) {
    case 'small':
        $btn_font_size = '0.75rem';
        $btn_padding = '0.5rem 1rem';
        break;
    case 'large':
        $btn_font_size = '1rem';
        $btn_padding = '1rem 2rem';
        break;
    default: // medium
        $btn_font_size = '0.875rem';
        $btn_padding = '0.8rem 1.5rem';
}

// Button Defaults
$btn_radius = '12px';
$btn_font_weight = '700';
$btn_shadow = '0 4px 6px rgba(0,0,0,0.1)';
$btn_hover_transform = 'translateY(-2px)';
$btn_hover_shadow = '0 6px 12px rgba(0,0,0,0.15)';

// Grid Columns (Desktop/Tablet/Mobile)
$prod_cols_d = getThemeSetting('theme_products_per_row', '4');
$prod_cols_t = getThemeSetting('theme_products_per_row_tablet', '2');
$prod_cols_m = getThemeSetting('theme_products_per_row_mobile', '2');

$serv_cols_d = getThemeSetting('theme_services_per_row', '3');
$serv_cols_t = getThemeSetting('theme_services_per_row_tablet', '2');
$serv_cols_m = getThemeSetting('theme_services_per_row_mobile', '1');

$rev_cols_d = getThemeSetting('theme_reviews_per_row', '3');
$rev_cols_t = getThemeSetting('theme_reviews_per_row_tablet', '2');
$rev_cols_m = getThemeSetting('theme_reviews_per_row_mobile', '1');

$blog_cols_d = getThemeSetting('theme_blog_per_row', '3');
$blog_cols_t = getThemeSetting('theme_blog_per_row_tablet', '2');
$blog_cols_m = getThemeSetting('theme_blog_per_row_mobile', '1');

// Layout
$container_width = getThemeSetting('theme_container_width', '1200px');
$grid_gap = getThemeSetting('theme_grid_gap', '2rem');
$section_spacing = getThemeSetting('theme_section_spacing', '4rem');
$section_spacing_tablet = getThemeSetting('theme_section_spacing_tablet', '3rem');
$section_spacing_mobile = getThemeSetting('theme_section_spacing_mobile', '2rem');

// Grid Columns (Desktop/Tablet/Mobile)
$prod_cols_d = getThemeSetting('theme_products_per_row', '4');
$prod_cols_t = getThemeSetting('theme_products_per_row_tablet', '2');
$prod_cols_m = getThemeSetting('theme_products_per_row_mobile', '2');

$serv_cols_d = getThemeSetting('theme_services_per_row', '3');
$serv_cols_t = getThemeSetting('theme_services_per_row_tablet', '2');
$serv_cols_m = getThemeSetting('theme_services_per_row_mobile', '1');

$rev_cols_d = getThemeSetting('theme_reviews_per_row', '3');
$rev_cols_t = getThemeSetting('theme_reviews_per_row_tablet', '2');
$rev_cols_m = getThemeSetting('theme_reviews_per_row_mobile', '1');

$blog_cols_d = getThemeSetting('theme_blog_per_row', '3');
$blog_cols_t = getThemeSetting('theme_blog_per_row_tablet', '2');
$blog_cols_m = getThemeSetting('theme_blog_per_row_mobile', '1');

// Container Layout Settings
$container_max_width_desktop = getThemeSetting('theme_container_max_width_desktop', '1400px');
$container_max_width_tablet = getThemeSetting('theme_container_max_width_tablet', '1024px');
$container_max_width_mobile = getThemeSetting('theme_container_max_width_mobile', '100%');
$container_padding_desktop = getThemeSetting('theme_container_padding_desktop', '2rem');
$container_padding_tablet = getThemeSetting('theme_container_padding_tablet', '1.5rem');
$container_padding_mobile = getThemeSetting('theme_container_padding_mobile', '1rem');



// Components
$input_radius = getThemeSetting('theme_input_radius', '8px');
$input_bg = getThemeSetting('theme_input_bg', '#ffffff');
$input_border = getThemeSetting('theme_input_border', '#e2e8f0');
$input_focus_border = getThemeSetting('theme_input_focus_border', $primary_color);
$input_padding_y = getThemeSetting('theme_input_padding_y', '0.8rem');
$input_padding_x = getThemeSetting('theme_input_padding_x', '1rem');
$input_icon_padding = getThemeSetting('theme_input_icon_padding', '2.5rem');
$input_icon_position = getThemeSetting('theme_input_icon_position', 'left');
$input_icon_color = getThemeSetting('theme_input_icon_color', '#94a3b8');
$input_icon_size = getThemeSetting('theme_input_icon_size', '1rem');

$label_size = getThemeSetting('theme_label_size', '0.9rem');
$label_weight = getThemeSetting('theme_label_weight', '700');
$label_mb = getThemeSetting('theme_label_mb', '0.5rem');
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
$cta_btn_text = getThemeSetting('theme_cta_btn_text', '#e31e24');
$cta_btn_transparent = getThemeSetting('theme_cta_btn_transparent', '0');
$cta_border_color = getThemeSetting('theme_cta_border_color', 'transparent');
$cta_border_width = getThemeSetting('theme_cta_border_width', '0px');
$cta_radius = getThemeSetting('theme_cta_radius', '2.5rem');
$cta_bg_img = getThemeSetting('theme_cta_bg_img', '');
$cta_overlay = getThemeSetting('theme_cta_overlay', '0.0');

// Footer
$footer_bg = getThemeSetting('theme_footer_bg', '#1e293b');
$footer_text = getThemeSetting('theme_footer_text', '#f8fafc');
?>

<style>
/* --- GENERATED THEME STYLES --- */
:root {
    --primary: <?= $primary_color ?>;
    --primary-rgb: <?= hex2rgb($primary_color) ?>;
    --secondary: <?= $secondary_color ?>;
    --accent: <?= $accent_color ?>;
    --background: <?= $background_color ?>;
    --surface: <?= $surface_color ?>;
    --text-main: <?= $text_main ?>;
    --text-light: <?= $text_light ?>;
    
    --font-family: <?= $font_family ?>;
    --base-font-size: <?= $base_font_size_mobile ?>; /* Default Mobile */
    --heading-weight: <?= $heading_font_weight ?>;
    
    /* Responsive Headings (Managed) */
    --h1-size-m: <?= $h1_size_m ?>;
    --h1-size-t: <?= $h1_size_t ?>;
    --h1-size-d: <?= $h1_size_d ?>;
    --h1-size: var(--h1-size-m);

    --h2-size-m: <?= $h2_size_m ?>;
    --h2-size-t: <?= $h2_size_t ?>;
    --h2-size-d: <?= $h2_size_d ?>;
    --h2-size: var(--h2-size-m);

    --h3-size-m: <?= $h3_size_m ?>;
    --h3-size-t: <?= $h3_size_t ?>;
    --h3-size-d: <?= $h3_size_d ?>;
    --h3-size-d: <?= $h3_size_d ?>;
    --h3-size: var(--h3-size-m);

    /* Subheaders Responsive */
    --h4-size-m: <?= $h4_size ?>; /* Mobile default from mapping */
    --h4-size-t: <?= scale_font($h4_size, 1.1) ?>; /* Auto-scale T from M, or use distinct var if mapped */
    --h4-size-d: <?= scale_font($h4_size, 1.25) ?>; /* Auto-scale D from M */
    
    /* Using specific variables if we mapped them above (which we did partially)
       Actually, let's look at my mapping in Step 1434.
       I mapped $h4_size to theme_font_subheading_d (Desktop).
       So $h4_size IS Desktop.
       I need to fix the mapping variable names or usage here.
    */
    --h4-size-d: <?= $h4_size ?>;
    --h4-size-t: <?= getThemeSetting('theme_font_subheading_t', '1.1rem') ?>;
    --h4-size-m: <?= getThemeSetting('theme_font_subheading_m', '1rem') ?>;
    --h4-size: var(--h4-size-m);

    --h5-size-d: <?= scale_font($h4_size, 0.9) ?>;
    --h5-size-t: <?= scale_font(getThemeSetting('theme_font_subheading_t', '1.1rem'), 0.9) ?>;
    --h5-size-m: <?= scale_font(getThemeSetting('theme_font_subheading_m', '1rem'), 0.9) ?>;
    --h5-size: var(--h5-size-m);

    --h6-size-d: <?= scale_font($h4_size, 0.8) ?>;
    --h6-size-t: <?= scale_font(getThemeSetting('theme_font_subheading_t', '1.1rem'), 0.8) ?>;
    --h6-size-m: <?= scale_font(getThemeSetting('theme_font_subheading_m', '1rem'), 0.8) ?>;
    --h6-size: var(--h6-size-m);

    /* Components */
    --breadcrumb-size: <?= $breadcrumb_size ?>;
    --btn-font-size: <?= $btn_font_size ?>;

    /* Paragraphs */
    --p-line-height: <?= $p_line_height ?>;
    --p-margin: <?= $p_margin_bottom ?>;

    --container-width: <?= $container_width ?>;
    --grid-gap: <?= $grid_gap ?>;
    
    /* Global Container Layout */
    --container-max-width: <?= $container_max_width_mobile ?>;
    --container-padding: <?= $container_padding_mobile ?>;
    
    /* Section Spacing Variables */
    --section-spacing: <?= $section_spacing_mobile ?>; /* Default Mobile */
    
    --btn-radius: <?= $btn_radius ?>;
    --btn-padding: <?= $btn_padding ?>;
    --btn-font-weight: <?= $btn_font_weight ?>;
    --btn-shadow: <?= $btn_shadow ?>;
    --btn-hover-transform: <?= $btn_hover_transform ?>;
    --btn-hover-shadow: <?= $btn_hover_shadow ?>;
    
    --input-radius: <?= $input_radius ?>;
    --input-bg: <?= $input_bg ?>;
    --input-border: <?= $input_border ?>;
    --input-focus-border: <?= $input_focus_border ?>;
    --input-padding-y: <?= $input_padding_y ?>;
    --input-padding-x: <?= $input_padding_x ?>;
    --input-icon-padding: <?= $input_icon_padding ?>;
    --input-icon-color: <?= $input_icon_color ?>;
    --input-icon-size: <?= $input_icon_size ?>;
    --input-icon-left: <?= $input_icon_position == 'left' ? '0' : 'auto' ?>;
    --input-icon-right: <?= $input_icon_position == 'right' ? '0' : 'auto' ?>;
    --input-pl: <?= $input_icon_position == 'left' ? $input_icon_padding : $input_padding_x ?>;
    --input-pr: <?= $input_icon_position == 'right' ? $input_icon_padding : $input_padding_x ?>;
    
    --label-size: <?= $label_size ?>;
    --label-weight: <?= $label_weight ?>;
    --label-mb: <?= $label_mb ?>;
    
    --card-radius: <?= $card_radius ?>;
    --card-shadow: <?= $card_shadow ?>;
    
    --badge-radius: <?= $badge_radius ?>;
    
    --anim-duration: <?= $anim_duration ?>;
    --anim-easing: <?= $anim_easing ?>;
    
    /* CTA Variables */
    --cta-bg: <?= $cta_bg ?>;
    --cta-text: <?= $cta_text ?>;
    --cta-padding: <?= $cta_padding ?>;
    --cta-btn-bg: <?= $cta_btn_bg ?>;
    --cta-btn-text: <?= $cta_btn_text ?>;
    --cta-border-color: <?= $cta_border_color ?>;
    --cta-border-width: <?= $cta_border_width ?>;
    --cta-radius: <?= $cta_radius ?>;
    <?php if ($cta_bg_img): ?>
    --cta-bg-img: url('../assets/uploads/<?= $cta_bg_img ?>');
    <?php endif; ?>
    --cta-overlay: <?= $cta_overlay ?>;

    /* Footer Variables */
    --footer-bg: <?= $footer_bg ?>;
    --footer-text: <?= $footer_text ?>;

    /* Legacy Aliases for Compatibility */
    --color-primary: var(--primary);
    --color-secondary: var(--secondary);
    --color-accent: var(--accent);
    --bg-color: var(--background);
    --surface-color: var(--surface);
}

/* Tablet Spacing */
@media (min-width: 768px) {
    :root {
        --section-spacing: <?= $section_spacing_tablet ?>;
        --base-font-size: <?= $base_font_size_tablet ?>;
        --container-max-width: <?= $container_max_width_tablet ?>;
        --container-padding: <?= $container_padding_tablet ?>;
        
        /* Typography Update */
        --h1-size: var(--h1-size-t);
        --h2-size: var(--h2-size-t);
        --h3-size: var(--h3-size-t);
        --h4-size: var(--h4-size-t);
        --h5-size: var(--h5-size-t);
        --h6-size: var(--h6-size-t);
    }
}

/* Desktop Spacing */
@media (min-width: 1024px) {
    :root {
        --section-spacing: <?= $section_spacing ?>;
        --base-font-size: <?= $base_font_size ?>;
        --container-max-width: <?= $container_max_width_desktop ?>;
        --container-padding: <?= $container_padding_desktop ?>;

        /* Typography Update */
        --h1-size: var(--h1-size-d);
        --h2-size: var(--h2-size-d);
        --h3-size: var(--h3-size-d);
        --h4-size: var(--h4-size-d);
        --h5-size: var(--h5-size-d);
        --h6-size: var(--h6-size-d);
    }
}

/* Typography Base */
body {
    font-family: var(--font-family);
    font-size: var(--base-font-size);
    color: var(--text-main);
    background-color: var(--background);
    line-height: 1.6;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: var(--heading-weight);
    color: var(--accent);
    line-height: 1.2;
}

h1 { font-size: var(--h1-size); }
h2 { font-size: var(--h2-size); }
h3 { font-size: var(--h3-size); }
h4 { font-size: var(--h4-size); }
h5 { font-size: var(--h5-size); }
h6 { font-size: var(--h6-size); }

p {
    line-height: var(--p-line-height);
    margin-bottom: var(--p-margin);
}

/* Global Container - Responsive Width & Padding */
.container {
    max-width: var(--container-max-width);
    margin-left: auto;
    margin-right: auto;
    padding-left: var(--container-padding);
    padding-right: var(--container-padding);
    width: 100%;
}

/* Buttons */
.btn {
    border-radius: var(--btn-radius);
    padding: var(--btn-padding);
    font-weight: var(--btn-font-weight);
    transition: all var(--anim-duration) var(--anim-easing);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    text-align: center;
}

.btn:hover {
    transform: var(--btn-hover-transform);
    box-shadow: var(--btn-hover-shadow);
}

.btn-primary {
    background-color: var(--primary);
    color: #ffffff;
    box-shadow: var(--btn-shadow);
    border: 2px solid var(--primary);
}

.btn-primary:hover {
    background-color: var(--accent);
    border-color: var(--accent);
    /* Transform handled by generic .btn:hover */
}

.btn-outline {
    background-color: transparent;
    color: var(--primary);
    border: 2px solid var(--primary);
}

.btn-outline:hover {
    background-color: var(--primary);
    color: #ffffff;
}

/* Inputs */
input.form-control, select.form-control, textarea.form-control, 
.form-input, input[type="text"], input[type="email"], input[type="password"], input[type="tel"], select, textarea {
    border-radius: var(--input-radius);
    background-color: var(--input-bg);
    border: 1px solid var(--input-border);
    padding: var(--input-padding-y) var(--input-padding-x);
    transition: all 0.2s;
    width: 100%; /* Ensure full width by default */
}

input.form-control:focus, select.form-control:focus, textarea.form-control:focus,
.form-input:focus, input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, input[type="tel"]:focus, select:focus, textarea:focus {
    border-color: var(--input-focus-border);
    outline: none;
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1); 
}

/* Label Styling */
label, .form-label {
    font-size: var(--label-size);
    font-weight: var(--label-weight);
    margin-bottom: var(--label-mb);
    display: inline-block;
    color: var(--text-main);
}

/* Icon Input Override & Layout */
.relative {
    position: relative;
}

/* The Icon Container */
.relative .absolute {
    left: var(--input-icon-left) !important;
    right: var(--input-icon-right) !important;
    padding-left: <?= $input_icon_position == 'left' ? '0.75rem' : '0' ?>;
    padding-right: <?= $input_icon_position == 'right' ? '0.75rem' : '0' ?>;
    color: var(--input-icon-color) !important;
    font-size: var(--input-icon-size);
    display: flex;
    align-items: center;
    height: 100%;
    pointer-events: none;
    transition: color 0.2s;
}

/* The Input Field */
.form-input.pl-10, input.pl-10 {
    padding-left: var(--input-pl) !important;
    padding-right: var(--input-pr) !important;
}

/* Focus State for Icon */
.relative:focus-within .absolute {
    color: var(--primary) !important;
}

/* Cards (Standard) */
.card-custom {
    background-color: var(--surface);
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    border: 1px solid rgba(0,0,0,0.03);
    transition: transform 0.3s ease;
}

.card-custom:hover {
    transform: translateY(-5px);
}

/* Badges */
.badge {
    border-radius: var(--badge-radius);
    padding: 0.35em 0.65em;
    font-size: 0.75em;
    font-weight: 700;
}

/* Section Spacing Utility */
.section-padding {
    padding-top: var(--section-spacing);
    padding-bottom: var(--section-spacing);
}

/* --- DYNAMIC GRIDS V2 (Clean Implementation) --- */

/* 1. Product Grid */
.dynamic-product-grid-v2 {
    display: grid;
    gap: var(--grid-gap);
    /* Mobile Default */
    grid-template-columns: repeat(<?= $prod_cols_m ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-product-grid-v2 {
        grid-template-columns: repeat(<?= $prod_cols_t ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-product-grid-v2 {
        grid-template-columns: repeat(<?= $prod_cols_d ?>, minmax(0, 1fr));
    }
}

/* 2. Service Grid */
.dynamic-service-grid-v2 {
    display: grid;
    gap: var(--grid-gap);
    /* Mobile Default */
    grid-template-columns: repeat(<?= $serv_cols_m ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-service-grid-v2 {
        grid-template-columns: repeat(<?= $serv_cols_t ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-service-grid-v2 {
        grid-template-columns: repeat(<?= $serv_cols_d ?>, minmax(0, 1fr));
    }
}

/* 3. Review Grid */
.dynamic-review-grid-v2 {
    display: grid;
    gap: var(--grid-gap);
    /* Mobile Default */
    grid-template-columns: repeat(<?= $rev_cols_m ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-review-grid-v2 {
        grid-template-columns: repeat(<?= $rev_cols_t ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-review-grid-v2 {
        grid-template-columns: repeat(<?= $rev_cols_d ?>, minmax(0, 1fr));
    }
}

/* 4. Blog Grid */
.dynamic-blog-grid-final {
    display: grid;
    gap: var(--grid-gap);
    /* Mobile Default */
    grid-template-columns: repeat(<?= $blog_cols_m ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-blog-grid-final {
        grid-template-columns: repeat(<?= $blog_cols_t ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-blog-grid-final {
        grid-template-columns: repeat(<?= $blog_cols_d ?>, minmax(0, 1fr));
    }
}

/* --- CTA Section Styling --- */
.dynamic-cta-section {
    background-color: var(--cta-bg);
    color: var(--cta-text);
    padding: var(--cta-padding);
    border-radius: var(--cta-radius);
    border: var(--cta-border-width) solid var(--cta-border-color);
    position: relative;
    overflow: hidden;
    margin-bottom: var(--section-spacing);
}

<?php if ($cta_bg_img): ?>
.dynamic-cta-section::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: var(--cta-bg-img);
    background-size: cover;
    background-position: center;
    opacity: 0.15; /* Optional opacity for bg image */
    z-index: 0;
}
.dynamic-cta-section::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0,0,0, var(--cta-overlay));
    z-index: 1;
}
<?php endif; ?>

.dynamic-cta-content {
    position: relative;
    z-index: 2;
}

.dynamic-cta-btn {
    background-color: var(--cta-btn-bg);
    color: var(--cta-btn-text);
    border: 2px solid transparent; /* Or calculate contrasting border */
    padding: 1rem 2.5rem;
    font-weight: 800;
    text-transform: uppercase;
    border-radius: 50px;
    display: inline-block;
    margin-top: 1.5rem;
    transition: all 0.3s ease;
}

.dynamic-cta-btn:hover {
    background-color: rgba(255,255,255,0.9);
    transform: scale(1.05);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

<?php if ($cta_btn_transparent == '1'): ?>
.dynamic-cta-btn {
    background-color: transparent;
    border: 2px solid var(--cta-btn-bg); /* Use btn-bg as border color */
    color: var(--cta-btn-bg); /* Text color matches border */
}
.dynamic-cta-btn:hover {
    background-color: var(--cta-btn-bg);
    color: var(--cta-text); /* Invert on hover */
}
<?php endif; ?>

/* --- Premium Card Tweaks --- */
.product-card-premium, .blog-card-premium, .review-card-premium {
    height: 100%;
    transition: transform 0.4s ease;
}

.card-inner {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: var(--card-radius);
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.card-inner:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08); 
    border-color: var(--primary);
}

/* wishlist button */
.wishlist-btn {
    background: rgba(255,255,255,0.9);
    backdrop-filter: blur(4px);
    color: #cbd5e1;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}

.wishlist-btn:hover {
    transform: scale(1.1);
    color: #ef4444; 
}

@media (max-width: 768px) {
    .product-card-premium, .blog-card-premium, .review-card-premium { padding: 5px; }
    .card-body { padding: 1rem !important; }
    .final-price { font-size: 1.3rem; }
    .old-price { font-size: 0.9rem; }
    .discount-tag { font-size: 0.8rem; }
    .product-brand { font-size: 1rem; }
}

/* Enhanced Responsive Image Safety */
.product-card-premium img, .blog-card-premium img, .review-card-premium img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto;
}

.product-card-premium a.block.relative, .blog-card-premium a.block.relative {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 200px; /* Minimum height to prevent collapse */
}

@media (max-width: 480px) {
    .product-card-premium a.block.relative {
        min-height: 150px;
    }
}

/* --- DYNAMIC GRIDS V2 (Clean Implementation) --- */

/* 1. Product Grid */
.dynamic-product-grid-v2 {
    display: grid;
    gap: var(--grid-gap);
    /* Mobile Default */
    grid-template-columns: repeat(<?= $prod_cols_m ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-product-grid-v2 {
        grid-template-columns: repeat(<?= $prod_cols_t ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-product-grid-v2 {
        grid-template-columns: repeat(<?= $prod_cols_d ?>, minmax(0, 1fr));
    }
}

/* 2. Blog Grid */
.dynamic-blog-grid-v2 {
    display: grid;
    gap: var(--grid-gap);
    grid-template-columns: repeat(<?= $blog_cols_m ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-blog-grid-v2 {
        grid-template-columns: repeat(<?= $blog_cols_t ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-blog-grid-v2 {
        grid-template-columns: repeat(<?= $blog_cols_d ?>, minmax(0, 1fr));
    }
}

/* 3. Review Grid */
/* 3. Review Grid */
.dynamic-review-grid-v2 {
    display: grid;
    gap: var(--grid-gap);
    grid-template-columns: repeat(<?= $rev_cols_m ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-review-grid-v2 {
        grid-template-columns: repeat(<?= $rev_cols_t ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-review-grid-v2 {
        grid-template-columns: repeat(<?= $rev_cols_d ?>, minmax(0, 1fr));
    }
}

/* 4. Service Grid */
/* 4. Service Grid */
.dynamic-service-grid-v2 {
    display: grid;
    gap: var(--grid-gap);
    grid-template-columns: repeat(<?= $serv_cols_m ?? 1 ?>, minmax(0, 1fr));
}
@media (min-width: 768px) {
    .dynamic-service-grid-v2 {
        grid-template-columns: repeat(<?= $serv_cols_t ?? 2 ?>, minmax(0, 1fr));
    }
}
@media (min-width: 1024px) {
    .dynamic-service-grid-v2 {
        grid-template-columns: repeat(<?= $serv_cols_d ?? 4 ?>, minmax(0, 1fr));
    }
}



/* --- Mobile Filter Modal (Inline Force) --- */
.mobile-filter-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 99999 !important; /* Max Z-Index */
    visibility: hidden;
    opacity: 0;
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .mobile-filter-modal {
        display: block !important;
    }
}

.mobile-filter-modal.active {
    visibility: visible !important;
    opacity: 1 !important;
}

.mobile-filter-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.mobile-filter-content {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #fff;
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
    padding: 1.5rem;
    transform: translateY(100%);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 -10px 40px rgba(0,0,0,0.2);
    max-height: 85vh;
    display: flex;
    flex-direction: column;
}

.mobile-filter-modal.active .mobile-filter-content {
    transform: translateY(0) !important;
}

/* Header & Categories */
.mobile-filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.mobile-filter-header h3 {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--accent);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.mobile-filter-close {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    border: none;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.mobile-filter-body {
    overflow-y: auto;
    flex: 1;
}
.mobile-category-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}
.mobile-category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    text-align: center;
    gap: 0.75rem;
    color: var(--text-main);
    font-weight: 600;
}
.mobile-category-item i {
    font-size: 1.5rem;
    color: var(--primary);
}
.mobile-category-item.active {
    background: #fff;
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15);
    color: var(--primary);
}

/* Responsive Visibility Utilities (Inline Safety) */
@media (min-width: 768px) {
    .md\:hidden { display: none !important; }
    .md\:flex { display: flex !important; }
    .md\:block { display: block !important; }
    .md\:inline-block { display: inline-block !important; }
    .md\:flex-row { flex-direction: row !important; }
    .md\:flex-col { flex-direction: column !important; }
}
@media (max-width: 767px) {
    .hidden { display: none !important; }
}

</style>
