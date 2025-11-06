<?php

/**
 * Ogólne funkcje motywu
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Funkcja do łatwego pobierania kolorów motywu
 */
function get_theme_color($color_name, $default = '#000000')
{
    $colors = get_theme_option('colors');

    return isset($colors[$color_name]) ? $colors[$color_name] : $default;
}

/**
 * Dodanie meta tagów dla SEO
 */
function universal_theme_meta_tags()
{
    echo '<meta name="theme-color" content="' . get_theme_color('primary') . '">' . "\n";
    echo '<meta name="msapplication-navbutton-color" content="' . get_theme_color('primary') . '">' . "\n";
    echo '<meta name="apple-mobile-web-app-status-bar-style" content="' . get_theme_color('primary') . '">' . "\n";
}
add_action('wp_head', 'universal_theme_meta_tags');

/**
 * Dodanie preloader'a (opcjonalnie)
 */
function universal_theme_preloader()
{
    if (get_theme_option('features.preloader', false)) {
?>
        <div id="universal-preloader" class="universal-preloader">
            <div class="universal-spinner">
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
            </div>
        </div>
<?php
    }
}
add_action('wp_body_open', 'universal_theme_preloader');

/**
 * Dodanie dodatkowych klas CSS do body
 */
function universal_theme_body_classes($classes)
{
    // Dodaj klasę z nazwą motywu
    $classes[] = 'universal-theme';

    // Dodaj klasę mobilną
    if (wp_is_mobile()) {
        $classes[] = 'is-mobile';
    }

    // Dodaj klasę z aktualną stroną
    if (is_front_page()) {
        $classes[] = 'is-homepage';
    }

    return $classes;
}
add_filter('body_class', 'universal_theme_body_classes');

/**
 * Dodanie wsparcia dla różnych formatów postów
 */
function universal_theme_setup()
{
    // Dodaj wsparcie dla logo
    add_theme_support('custom-logo', array(
        'height'      => 100,
        'width'       => 300,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // Custom background został wyłączony - brak edycji w panelu WordPress
    // add_theme_support('custom-background'); // WYŁĄCZONE

    // Custom header został wyłączony - brak edycji w panelu WordPress
    // add_theme_support('custom-header', array(...)); // WYŁĄCZONE

    // Dodaj wsparcie dla miniaturek postów
    add_theme_support('post-thumbnails');

    // Dodaj wsparcie dla title-tag
    add_theme_support('title-tag');

    // Dodaj wsparcie dla HTML5
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'universal_theme_setup');

/**
 * Usunięcie wsparcia dla opcji edycji w panelu WordPress
 */
function universal_theme_remove_customizer_support()
{
    // Blokada custom header
    remove_theme_support('custom-header');

    // Blokada custom background  
    remove_theme_support('custom-background');

    // Blokada custom colors (kolory motywu)
    remove_theme_support('custom-colors');

    // Blokada editor color palette
    remove_theme_support('editor-color-palette');

    // Blokada custom font sizes
    remove_theme_support('custom-font-sizes');

    // Blokada layout controls
    remove_theme_support('custom-spacing');
    remove_theme_support('custom-line-height');
    remove_theme_support('custom-units');

    // Blokada responsive embeds
    remove_theme_support('responsive-embeds');

    // Blokada align wide/full
    remove_theme_support('align-wide');

    // Blokada dark editor style
    remove_theme_support('dark-editor-style');

    // Blokada border controls
    remove_theme_support('border');

    // Blokada link color
    remove_theme_support('link-color');
}
// WYŁĄCZONE - pozwalamy na rejestrację, ale usuwamy później
// add_action('after_setup_theme', 'universal_theme_remove_customizer_support', 11);

/**
 * Usunięcie sekcji z customizer WordPress i Storefront
 */
function universal_theme_remove_customizer_sections($wp_customize)
{
    // Usuń sekcje WordPress
    $wp_customize->remove_section('background_image');
    $wp_customize->remove_section('colors');
    $wp_customize->remove_section('header_image');
    $wp_customize->remove_section('static_front_page');

    // Usuń sekcje Storefront
    $wp_customize->remove_section('storefront_typography');
    $wp_customize->remove_section('storefront_buttons');
    $wp_customize->remove_section('storefront_layout');
    $wp_customize->remove_section('storefront_more');
    $wp_customize->remove_section('storefront_header');
    $wp_customize->remove_section('storefront_footer');
    $wp_customize->remove_section('storefront_homepage');
    $wp_customize->remove_section('storefront_product_catalog');
    $wp_customize->remove_section('storefront_single_product');
    
    // Usuń panele WordPress
    $wp_customize->remove_panel('widgets');
    $wp_customize->remove_panel('nav_menus');
    
    // Usuń panele Storefront
    $wp_customize->remove_panel('storefront_styling');

    // Usuń kontrolki WordPress
    $wp_customize->remove_control('background_color');
    $wp_customize->remove_control('header_textcolor');
    $wp_customize->remove_control('display_header_text');
    
    // Usuń wszystkie ustawienia Storefront
    $wp_customize->remove_setting('storefront_heading_color');
    $wp_customize->remove_setting('storefront_text_color');
    $wp_customize->remove_setting('storefront_accent_color');
    $wp_customize->remove_setting('storefront_hero_heading_color');
    $wp_customize->remove_setting('storefront_hero_text_color');
    $wp_customize->remove_setting('storefront_header_background_color');
    $wp_customize->remove_setting('storefront_header_text_color');
    $wp_customize->remove_setting('storefront_header_link_color');
    $wp_customize->remove_setting('storefront_footer_background_color');
    $wp_customize->remove_setting('storefront_footer_link_color');
    $wp_customize->remove_setting('storefront_footer_heading_color');
    $wp_customize->remove_setting('storefront_footer_text_color');
    $wp_customize->remove_setting('storefront_button_background_color');
    $wp_customize->remove_setting('storefront_button_text_color');
    $wp_customize->remove_setting('storefront_button_alt_background_color');
    $wp_customize->remove_setting('storefront_button_alt_text_color');
}
add_action('customize_register', 'universal_theme_remove_customizer_sections', 30);

/**
 * Usunięcie opcji "Wygląd" z menu WordPress dla zwykłych użytkowników
 */
function universal_theme_remove_appearance_menu()
{
    // Usuń menu "Wygląd" dla wszystkich oprócz administratorów
    if (!current_user_can('administrator')) {
        remove_menu_page('themes.php');
        remove_menu_page('customize.php');
    }

    // Usuń submenu z "Wygląd" nawet dla administratorów
    remove_submenu_page('themes.php', 'customize.php');
    remove_submenu_page('themes.php', 'themes.php');
    remove_submenu_page('themes.php', 'theme-editor.php');
}
add_action('admin_menu', 'universal_theme_remove_appearance_menu');

/**
 * Blokada dostępu do customizer przez URL
 */
function universal_theme_block_customizer_access()
{
    global $pagenow;
    if ($pagenow == 'customize.php' && !current_user_can('administrator')) {
        wp_die(__('Nie masz uprawnień do tej strony.'));
    }
}
add_action('admin_init', 'universal_theme_block_customizer_access');

/**
 * Funkcja do generowania inline CSS
 */
function universal_theme_inline_styles()
{
    $primary_color = get_theme_color('primary');
    $secondary_color = get_theme_color('secondary');
    $accent_color = get_theme_color('accent');

    $css = "
        .universal-primary-bg { background-color: {$primary_color} !important; }
        .universal-secondary-bg { background-color: {$secondary_color} !important; }
        .universal-accent-bg { background-color: {$accent_color} !important; }
        .universal-primary-color { color: {$primary_color} !important; }
        .universal-secondary-color { color: {$secondary_color} !important; }
        .universal-accent-color { color: {$accent_color} !important; }
    ";

    return $css;
}

/**
 * Dodanie breadcrumbów (jeśli nie są dostępne przez plugin)
 */
function universal_theme_breadcrumbs()
{
    if (!is_home() && !is_front_page()) {
        echo '<div class="universal-breadcrumbs">';
        echo '<a href="' . home_url() . '">' . __('Strona główna', 'universal-theme') . '</a>';

        if (is_shop()) {
            echo ' / <span>' . __('Sklep', 'universal-theme') . '</span>';
        } elseif (is_product()) {
            echo ' / <a href="' . get_permalink(wc_get_page_id('shop')) . '">' . __('Sklep', 'universal-theme') . '</a>';
            echo ' / <span>' . get_the_title() . '</span>';
        } elseif (is_product_category()) {
            echo ' / <a href="' . get_permalink(wc_get_page_id('shop')) . '">' . __('Sklep', 'universal-theme') . '</a>';
            echo ' / <span>' . single_cat_title('', false) . '</span>';
        }

        echo '</div>';
    }
}

/**
 * Optymalizacja ładowania fontów Google
 */
function universal_theme_google_fonts()
{
    $primary_font = get_theme_option('typography.primary_font');

    if (strpos($primary_font, 'Google') !== false || in_array($primary_font, [
        'Inter, sans-serif',
        'Roboto, sans-serif',
        'Open Sans, sans-serif',
        'Lato, sans-serif',
        'Montserrat, sans-serif',
        'Poppins, sans-serif'
    ])) {
        $font_name = explode(',', $primary_font)[0];
        $font_url = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $font_name) . ':wght@300;400;500;600;700&display=swap';

        wp_enqueue_style('universal-google-fonts', $font_url, array(), null);

        // Preconnect dla szybszego ładowania
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    }
}
add_action('wp_enqueue_scripts', 'universal_theme_google_fonts');
add_action('wp_head', function () {
    if (function_exists('universal_theme_google_fonts')) {
        universal_theme_google_fonts();
    }
}, 1);

/**
 * Header background CSS z konfiguracji motywu
 */
function universal_theme_header_background_css()
{
    $header_config = get_theme_option('header');
    $background_image = $header_config['background_image'] ?? '';
    $background_color = $header_config['background_color'] ?? '';
    $overlay_opacity = $header_config['overlay_opacity'] ?? 0.3;

    if (!empty($background_image) || !empty($background_color)) {
        echo '<style type="text/css">';

        if (!empty($background_image)) {
            $image_url = get_stylesheet_directory_uri() . '/' . ltrim($background_image, '/');
            echo '.site-header { background-image: url(' . esc_url($image_url) . '); }';
            echo '.site-header.has-background-image::before { background: rgba(0,0,0,' . floatval($overlay_opacity) . '); }';
            echo '.site-header { } /* Dodaj klasę has-background-image przez JS lub PHP */';
        }

        if (!empty($background_color)) {
            echo '.site-header { background-color: ' . esc_attr($background_color) . '; }';
        }

        echo '</style>';
    }
}
add_action('wp_head', 'universal_theme_header_background_css');

/**
 * Footer background CSS z konfiguracji motywu
 */
function universal_theme_footer_background_css()
{
    $footer_config = get_theme_option('footer');
    $background_image = $footer_config['background_image'] ?? '';
    $background_color = $footer_config['background_color'] ?? '#2c3e50';
    $text_color = $footer_config['text_color'] ?? '#ffffff';
    $overlay_opacity = $footer_config['overlay_opacity'] ?? 0.2;

    echo '<style type="text/css">';

    // Footer background
    echo '.site-footer { background-color: ' . esc_attr($background_color) . '; color: ' . esc_attr($text_color) . '; }';

    if (!empty($background_image)) {
        $image_url = get_stylesheet_directory_uri() . '/' . ltrim($background_image, '/');
        echo '.site-footer { background-image: url(' . esc_url($image_url) . '); background-size: cover; background-position: center; }';
        echo '.site-footer::before { content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,' . floatval($overlay_opacity) . '); z-index: 1; }';
        echo '.site-footer > * { position: relative; z-index: 2; }';
    }

    echo '</style>';
}
add_action('wp_head', 'universal_theme_footer_background_css');

/**
 * Buttons CSS z konfiguracji motywu
 */
function universal_theme_buttons_css()
{
    $buttons_config = get_theme_option('buttons');
    $primary_bg = $buttons_config['primary_bg'] ?? '#e74c3c';
    $primary_text = $buttons_config['primary_text'] ?? '#ffffff';
    $primary_hover = $buttons_config['primary_hover'] ?? '#c0392b';
    $secondary_bg = $buttons_config['secondary_bg'] ?? '#3498db';
    $secondary_text = $buttons_config['secondary_text'] ?? '#ffffff';
    $secondary_hover = $buttons_config['secondary_hover'] ?? '#2980b9';
    $border_radius = $buttons_config['border_radius'] ?? '8px';
    $padding = $buttons_config['padding'] ?? '12px 24px';

    echo '<style type="text/css">';

    // Główne przyciski
    echo '.button, .woocommerce .button, input[type="submit"], .wp-block-button__link {';
    echo 'background-color: ' . esc_attr($primary_bg) . ';';
    echo 'color: ' . esc_attr($primary_text) . ';';
    echo 'border-radius: ' . esc_attr($border_radius) . ';';
    echo 'padding: ' . esc_attr($padding) . ';';
    echo 'border: none; text-decoration: none; display: inline-block; cursor: pointer;';
    echo '}';

    // Hover głównych przycisków
    echo '.button:hover, .woocommerce .button:hover, input[type="submit"]:hover, .wp-block-button__link:hover {';
    echo 'background-color: ' . esc_attr($primary_hover) . ';';
    echo '}';

    // Drugorzędne przyciski
    echo '.button.secondary, .woocommerce .button.alt {';
    echo 'background-color: ' . esc_attr($secondary_bg) . ';';
    echo 'color: ' . esc_attr($secondary_text) . ';';
    echo '}';

    // Hover drugorzędnych przycisków
    echo '.button.secondary:hover, .woocommerce .button.alt:hover {';
    echo 'background-color: ' . esc_attr($secondary_hover) . ';';
    echo '}';

    echo '</style>';
}
add_action('wp_head', 'universal_theme_buttons_css');

/**
 * Background strony CSS z konfiguracji motywu
 */
function universal_theme_body_background_css()
{
    $bg_config = get_theme_option('background');
    $body_bg = $bg_config['body_bg'] ?? '#ffffff';
    $body_image = $bg_config['body_image'] ?? '';
    $body_repeat = $bg_config['body_repeat'] ?? 'no-repeat';
    $body_position = $bg_config['body_position'] ?? 'center';
    $body_size = $bg_config['body_size'] ?? 'cover';

    echo '<style type="text/css">';

    echo 'body { background-color: ' . esc_attr($body_bg) . '; }';

    if (!empty($body_image)) {
        $image_url = get_stylesheet_directory_uri() . '/' . ltrim($body_image, '/');
        echo 'body {';
        echo 'background-image: url(' . esc_url($image_url) . ');';
        echo 'background-repeat: ' . esc_attr($body_repeat) . ';';
        echo 'background-position: ' . esc_attr($body_position) . ';';
        echo 'background-size: ' . esc_attr($body_size) . ';';
        echo '}';
    }

    echo '</style>';
}
add_action('wp_head', 'universal_theme_body_background_css');

/**
 * Layout CSS z konfiguracji motywu
 */
function universal_theme_layout_css()
{
    $layout_config = get_theme_option('layout');

    echo '<style type="text/css">';

    // Container główny
    $container_width = $layout_config['container_width'] ?? '1200px';
    echo '.container, .site-content, .woocommerce .col2-set { max-width: ' . esc_attr($container_width) . '; margin: 0 auto; }';

    // Content width
    $content_width = $layout_config['content_width'] ?? '800px';
    echo '.content-area { max-width: ' . esc_attr($content_width) . '; }';

    // Sidebar
    $sidebar_width = $layout_config['sidebar_width'] ?? '300px';
    $enable_sidebar = $layout_config['enable_sidebar'] ?? false;
    if (!$enable_sidebar) {
        echo '.widget-area, .sidebar { display: none !important; }';
        echo '.content-area { width: 100% !important; max-width: 100% !important; }';
    } else {
        echo '.widget-area { width: ' . esc_attr($sidebar_width) . '; }';
    }

    // Spacing
    $spacing_small = $layout_config['spacing_small'] ?? '0.5rem';
    $spacing_medium = $layout_config['spacing_medium'] ?? '1rem';
    $spacing_large = $layout_config['spacing_large'] ?? '2rem';
    $spacing_xlarge = $layout_config['spacing_xlarge'] ?? '3rem';

    echo ':root {';
    echo '--spacing-small: ' . esc_attr($spacing_small) . ';';
    echo '--spacing-medium: ' . esc_attr($spacing_medium) . ';';
    echo '--spacing-large: ' . esc_attr($spacing_large) . ';';
    echo '--spacing-xlarge: ' . esc_attr($spacing_xlarge) . ';';
    echo '}';

    // Grid gap
    $grid_gap = $layout_config['grid_gap'] ?? '1.5rem';
    echo '.woocommerce ul.products { gap: ' . esc_attr($grid_gap) . '; }';
    echo '.wp-block-columns { gap: ' . esc_attr($grid_gap) . '; }';

    // Content alignment
    $content_alignment = $layout_config['content_alignment'] ?? 'left';
    if ($content_alignment === 'center') {
        echo '.site-content, .entry-content { text-align: center; }';
    } elseif ($content_alignment === 'right') {
        echo '.site-content, .entry-content { text-align: right; }';
    }

    // Wide alignment disable
    $enable_wide = $layout_config['enable_wide_alignment'] ?? false;
    if (!$enable_wide) {
        echo '.alignwide, .alignfull { width: 100% !important; max-width: 100% !important; }';
    }

    echo '</style>';
}
add_action('wp_head', 'universal_theme_layout_css');

/**
 * Funkcja CSS dla custom header została usunięta
 * - custom header jest zablokowany w panelu WordPress
 * - obrazy tła header należy ustawiać przez CSS lub theme-config.php
 */
