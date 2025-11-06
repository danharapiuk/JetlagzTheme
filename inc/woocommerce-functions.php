<?php

/**
 * Funkcje WooCommerce - dostosowania sklepu
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Wsparcie dla WooCommerce
 */
function universal_theme_woocommerce_setup()
{
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'universal_theme_woocommerce_setup');

/**
 * Dostosowanie liczby produktów na stronę
 */
function universal_theme_products_per_page()
{
    return get_theme_option('woocommerce.products_per_page');
}
add_filter('loop_shop_per_page', 'universal_theme_products_per_page', 20);

/**
 * Dostosowanie liczby produktów w rzędzie
 */
function universal_theme_products_per_row()
{
    return get_theme_option('woocommerce.products_per_row');
}
add_filter('storefront_loop_columns', 'universal_theme_products_per_row');

/**
 * Usunięcie breadcrumbów ze strony produktu (opcjonalnie)
 */
function universal_theme_remove_breadcrumbs()
{
    remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20, 0);
}
// add_action('init', 'universal_theme_remove_breadcrumbs'); // Odkomentuj jeśli chcesz usunąć breadcrumby

/**
 * Dostosowanie wyglądu przycisków "Dodaj do koszyka"
 */
function universal_theme_add_to_cart_text($text, $product)
{
    if ($product->get_type() === 'simple') {
        return __('Dodaj do koszyka', 'universal-theme');
    }
    return $text;
}
add_filter('woocommerce_product_add_to_cart_text', 'universal_theme_add_to_cart_text', 10, 2);

/**
 * Dodanie klasy CSS do body dla różnych stron WooCommerce
 */
function universal_theme_woocommerce_body_class($classes)
{
    if (is_woocommerce()) {
        $classes[] = 'universal-woocommerce';
    }

    if (is_shop()) {
        $classes[] = 'universal-shop-page';
    }

    if (is_product()) {
        $classes[] = 'universal-product-page';
    }

    if (is_cart()) {
        $classes[] = 'universal-cart-page';
    }

    if (is_checkout()) {
        $classes[] = 'universal-checkout-page';
    }

    return $classes;
}
add_filter('body_class', 'universal_theme_woocommerce_body_class');

/**
 * Dostosowanie placeholder'a wyszukiwania produktów
 */
function universal_theme_search_placeholder()
{
    return __('Wyszukaj produkty...', 'universal-theme');
}
add_filter('get_product_search_form', function ($form) {
    return str_replace('placeholder="Search products&hellip;"', 'placeholder="' . universal_theme_search_placeholder() . '"', $form);
});

/**
 * Usunięcie dodatkowych informacji z listy produktów
 */
function universal_theme_remove_product_meta()
{
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
}
// add_action('init', 'universal_theme_remove_product_meta'); // Odkomentuj jeśli chcesz usunąć meta informacje

/**
 * Dodanie quick view (szkielet funkcji)
 */
function universal_theme_quick_view_button()
{
    global $product;

    if (get_theme_option('features.quick_view')) {
        echo '<a href="#" class="universal-quick-view" data-product-id="' . $product->get_id() . '">';
        echo '<span>' . __('Szybki podgląd', 'universal-theme') . '</span>';
        echo '</a>';
    }
}
add_action('woocommerce_after_shop_loop_item', 'universal_theme_quick_view_button', 15);

/**
 * Dostosowanie komunikatów WooCommerce
 */
function universal_theme_woocommerce_messages()
{
    return array(
        'cart_empty' => __('Twój koszyk jest pusty.', 'universal-theme'),
        'continue_shopping' => __('Kontynuuj zakupy', 'universal-theme'),
        'proceed_to_checkout' => __('Przejdź do kasy', 'universal-theme'),
    );
}

/**
 * Optymalizacja ładowania skryptów WooCommerce
 */
function universal_theme_optimize_woocommerce_scripts()
{
    // Ładuj skrypty WooCommerce tylko na stronach sklepu
    if (!is_woocommerce() && !is_cart() && !is_checkout()) {
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('woocommerce');
        wp_dequeue_script('wc-add-to-cart');
    }
}
add_action('wp_enqueue_scripts', 'universal_theme_optimize_woocommerce_scripts', 99);
