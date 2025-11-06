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

    // Dodaj wsparcie dla niestandardowego tła
    add_theme_support('custom-background');

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
 * Usunięcie wsparcia dla custom header (blokada edycji w panelu WordPress)
 */
function universal_theme_remove_header_support()
{
    remove_theme_support('custom-header');
}
add_action('after_setup_theme', 'universal_theme_remove_header_support', 11); // AKTYWNE - blokuje edycję

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
function universal_theme_header_background_css() {
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
 * Funkcja CSS dla custom header została usunięta
 * - custom header jest zablokowany w panelu WordPress
 * - obrazy tła header należy ustawiać przez CSS lub theme-config.php
 */