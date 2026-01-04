<?php

/**
 * Template Parts Helper Functions
 * 
 * Funkcje pomocnicze do wstrzykiwania i zarządzania template parts
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wstrzykuje template part w dowolnym miejscu
 * 
 * @param string $slug Nazwa pliku template part (bez .php)
 * @param array $args Opcjonalne argumenty do przekazania do template
 * @param bool $echo Czy wyświetlić od razu (true) czy zwrócić jako string (false)
 * @return string|void
 */
function jetlagz_inject_template_part($slug, $args = array(), $echo = true)
{
    $template_path = get_stylesheet_directory() . '/template-parts/' . $slug . '.php';

    if (!file_exists($template_path)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Template part not found: {$slug} at {$template_path}");
        }
        if (current_user_can('manage_options')) {
            echo '<div style="padding:10px;background:#ffebee;color:red;margin:10px;">Template part not found: ' . esc_html($slug) . '</div>';
        }
        return $echo ? '' : '';
    }

    if ($echo) {
        // Przekaż $args jako zmienną dostępną w template
        set_query_var('args', $args);
        get_template_part('template-parts/' . $slug);
    } else {
        ob_start();
        set_query_var('args', $args);
        get_template_part('template-parts/' . $slug);
        return ob_get_clean();
    }
}

/**
 * Wstrzykuje template part przez hook
 * Użycie: jetlagz_hook_template_part('woocommerce_before_main_content', 'banner-hero');
 * 
 * @param string $hook_name Nazwa hooka WordPress/WooCommerce
 * @param string $slug Nazwa pliku template part
 * @param int $priority Priorytet wykonania (domyślnie 10)
 * @param array $args Opcjonalne argumenty
 */
function jetlagz_hook_template_part($hook_name, $slug, $priority = 10, $args = array())
{
    add_action($hook_name, function () use ($slug, $args) {
        jetlagz_inject_template_part($slug, $args);
    }, $priority);
}

/**
 * Wstrzykuje wiele template parts przez ten sam hook
 * 
 * @param string $hook_name Nazwa hooka
 * @param array $templates Tablica z konfiguracją: [['slug' => 'banner-hero', 'priority' => 10], ...]
 */
function jetlagz_hook_multiple_templates($hook_name, $templates)
{
    foreach ($templates as $template) {
        $slug = $template['slug'] ?? '';
        $priority = $template['priority'] ?? 10;
        $args = $template['args'] ?? array();

        if ($slug) {
            jetlagz_hook_template_part($hook_name, $slug, $priority, $args);
        }
    }
}

/**
 * Zwraca template part jako string (do użycia w shortcode lub gdzie indziej)
 * 
 * @param string $slug Nazwa template part
 * @param array $args Argumenty
 * @return string
 */
function jetlagz_get_template_part($slug, $args = array())
{
    return jetlagz_inject_template_part($slug, $args, false);
}

/**
 * Shortcode do wstawiania template parts w treści
 * Użycie: [template_part slug="banner-hero"]
 * 
 * @param array $atts Atrybuty shortcode
 * @return string
 */
function jetlagz_template_part_shortcode($atts)
{
    $atts = shortcode_atts(array(
        'slug' => '',
        'promo_text' => '',
        'promo_link' => '',
        'promo_bg' => '',
        'promo_color' => '',
    ), $atts);

    if (empty($atts['slug'])) {
        return '';
    }

    $args = array();
    foreach ($atts as $key => $value) {
        if ($key !== 'slug' && !empty($value)) {
            $args[$key] = $value;
        }
    }

    return jetlagz_get_template_part($atts['slug'], $args);
}
add_shortcode('template_part', 'jetlagz_template_part_shortcode');

/**
 * Conditional display helper - wyświetla template tylko jeśli warunek jest spełniony
 * 
 * @param string $slug Template part slug
 * @param callable|bool $condition Warunek (funkcja lub boolean)
 * @param array $args Argumenty
 */
function jetlagz_conditional_template($slug, $condition, $args = array())
{
    $should_display = is_callable($condition) ? call_user_func($condition) : (bool) $condition;

    if ($should_display) {
        jetlagz_inject_template_part($slug, $args);
    }
}

/**
 * Przykładowa funkcja do wyświetlania template parts na konkretnych stronach
 */
function jetlagz_page_specific_templates()
{
    // Banner hero tylko na stronie głównej
    if (is_front_page()) {
        jetlagz_inject_template_part('banner-hero');
    }

    // CTA na stronach produktów
    if (is_product()) {
        jetlagz_inject_template_part('cta-simple');
    }

    // Features na stronach kategorii
    if (is_product_category()) {
        jetlagz_inject_template_part('content-features');
    }
}

/**
 * Helper do sprawdzania czy template part istnieje
 * 
 * @param string $slug Nazwa template part
 * @return bool
 */
function jetlagz_template_part_exists($slug)
{
    $template_path = get_stylesheet_directory() . '/template-parts/' . $slug . '.php';
    return file_exists($template_path);
}

/**
 * Lista wszystkich dostępnych template parts
 * 
 * @return array Lista slugów dostępnych template parts
 */
function jetlagz_get_available_template_parts()
{
    $template_dir = get_stylesheet_directory() . '/template-parts/';
    $templates = array();

    if (is_dir($template_dir)) {
        $files = glob($template_dir . '*.php');
        foreach ($files as $file) {
            $templates[] = basename($file, '.php');
        }
    }

    return $templates;
}

/**
 * Debug helper - wyświetla informacje o dostępnych template parts
 * Tylko dla administratorów w trybie WP_DEBUG
 */
function jetlagz_debug_template_parts()
{
    if (!current_user_can('manage_options') || !defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }

    $templates = jetlagz_get_available_template_parts();

    echo '<!-- Available Template Parts: ' . implode(', ', $templates) . ' -->';
}
add_action('wp_footer', 'jetlagz_debug_template_parts');
