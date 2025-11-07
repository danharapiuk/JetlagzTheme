<?php

/**
 * Funkcje WooCommerce - tylko dostosowania specyficzne dla naszego motywu
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dostosowanie liczby produktów na stronę (nadpisanie Storefront)
 */
function universal_theme_products_per_page()
{
    $products_per_page = get_theme_option('woocommerce.products_per_page');
    return $products_per_page ? $products_per_page : 12; // fallback jeśli nie ustawione
}
add_filter('loop_shop_per_page', 'universal_theme_products_per_page', 20);

/**
 * Dostosowanie liczby produktów w rzędzie (nadpisanie Storefront)
 */
function universal_theme_products_per_row()
{
    $products_per_row = get_theme_option('woocommerce.products_per_row');
    return $products_per_row ? $products_per_row : 3; // fallback jeśli nie ustawione
}
add_filter('storefront_loop_columns', 'universal_theme_products_per_row');

/**
 * Optymalizacja ładowania skryptów WooCommerce
 * Ładuj skrypty WooCommerce tylko na stronach sklepu
 */
function universal_theme_optimize_woocommerce_scripts()
{
    if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
        wp_dequeue_script('wc-cart-fragments');
        wp_dequeue_script('wc-add-to-cart');
    }
}
add_action('wp_enqueue_scripts', 'universal_theme_optimize_woocommerce_scripts', 99);

/**
 * Dostosowanie tekstu "Dodaj do koszyka" (jeśli potrzebne)
 */
function universal_theme_add_to_cart_text($text, $product)
{
    if ($product->get_type() === 'simple') {
        return __('Dodaj do koszyka', 'universal-theme');
    }
    return $text;
}
add_filter('woocommerce_product_add_to_cart_text', 'universal_theme_add_to_cart_text', 10, 2);
