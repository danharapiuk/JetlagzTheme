<?php

/**
 * Web Styles - Critical CSS
 * Ładuje się natychmiast w <head> przed innymi stylami
 * Zapobiega efektowi "skakania" i FOUC (Flash of Unstyled Content)
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Critical CSS - ładuje się natychmiast w <head> przed innymi stylami
 */
function universal_critical_css()
{
?>
    <style id="universal-critical-css">
        /* Critical styles - ładują się NATYCHMIAST */
        :root {
            /* Typografia */
            --font-primary: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-size-base: 14px;
            --font-size-small: 13px;
            --font-size-large: 16px;
            --font-size-xlarge: 18px;
            --font-size-xxlarge: 20px;

            /* Kolory */
            --color-primary: #000;
            --color-secondary: #666;
            --color-border: #e0e0e0;
            --color-background: #fff;
            --color-green: #28a745;
            --color-red: #dc3545;
        }

        /* Globalne ustawienia typografii - CAŁA STRONA */
        body,
        .woocommerce,
        .woocommerce-page {
            font-family: var(--font-primary);
            font-size: var(--font-size-base);
            line-height: 1.6;
        }

        /* Nagłówki H1-H6 */
        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: var(--font-primary);
            font-weight: 700;
            color: var(--color-primary);
        }

        h1 {
            font-size: 32px;
        }

        h2 {
            font-size: 28px;
        }

        h3 {
            font-size: 24px;
        }

        h4 {
            font-size: 20px;
        }

        h5 {
            font-size: 18px;
        }

        h6 {
            font-size: 16px;
        }

        /* Nagłówki checkout */
        .universal-checkout-review-title {
            font-family: var(--font-primary);
            font-size: var(--font-size-xxlarge);
            font-weight: 700;
            margin: 0 0 16px 0;
            color: var(--color-primary);
        }

        .universal-checkout-review-title .subtitle {
            font-family: var(--font-primary);
            font-size: var(--font-size-base);
            font-weight: 400;
            color: var(--color-secondary);
        }

        /* Produkty w checkout */
        .checkout-item-name {
            font-family: var(--font-primary);
            font-size: var(--font-size-base);
            font-weight: 600;
            color: var(--color-primary);
        }

        .checkout-item-unit-price {
            font-family: var(--font-primary);
            font-size: var(--font-size-large);
            font-weight: 700;
            color: var(--color-primary);
        }

        .checkout-item-total-price {
            font-family: var(--font-primary);
            font-size: var(--font-size-xlarge);
            font-weight: 700;
            color: var(--color-primary);
        }

        /* Ilość */
        .qty-label {
            font-family: var(--font-primary);
            font-size: var(--font-size-small);
            font-weight: 600;
            color: var(--color-secondary);
        }

        /* Coupon */
        .coupon-title {
            font-family: var(--font-primary);
            font-size: var(--font-size-large);
            font-weight: 700;
            color: var(--color-primary);
        }

        /* Billing form */
        .woocommerce-billing-fields h3,
        .woocommerce form .form-row label {
            font-family: var(--font-primary);
        }

        /* Produkty WooCommerce */
        .woocommerce ul.products li.product .woocommerce-loop-product__title,
        .woocommerce div.product .product_title {
            font-family: var(--font-primary);
        }

        /* Ceny produktów */
        .woocommerce ul.products li.product .price,
        .woocommerce div.product p.price,
        .woocommerce div.product span.price {
            font-family: var(--font-primary);
            font-weight: 700;
        }

        /* Przyciski */
        .woocommerce a.button,
        .woocommerce button.button,
        .woocommerce input.button {
            font-family: var(--font-primary);
        }

        /* Zapobiega FOUC */
        .universal-checkout-review-table-custom {
            opacity: 1;
        }
    </style>
<?php
}
add_action('wp_head', 'universal_critical_css', 1); // Priorytet 1 = ładuje się PIERWSZA

/**
 * Preconnect i Google Fonts
 */
function universal_preload_fonts()
{
    // Preconnect do Google Fonts dla szybszego ładowania
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';

    // Google Fonts - Inter
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">';
}
add_action('wp_head', 'universal_preload_fonts', 0);
