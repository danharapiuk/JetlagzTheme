<?php

/**
 * Funkcje WooCommerce - tylko niezbędne dostosowania
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dostosowanie liczby produktów na stronę (z konfiguracji motywu)
 */
function universal_theme_products_per_page()
{
    $products_per_page = get_theme_option('woocommerce.products_per_page');
    return $products_per_page ? $products_per_page : 12;
}
add_filter('loop_shop_per_page', 'universal_theme_products_per_page', 20);

/**
 * Dostosowanie liczby produktów w rzędzie (z konfiguracji motywu)
 */
function universal_theme_products_per_row()
{
    $products_per_row = get_theme_option('woocommerce.products_per_row');
    return $products_per_row ? $products_per_row : 3; // domyślnie jak Storefront
}
add_filter('storefront_loop_columns', 'universal_theme_products_per_row');
