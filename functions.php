<?php

/**
 * Universal Storefront Child Theme
 * 
 * Uniwersalny motyw potomny Storefront do szybkiego wdrażania
 * w różnych sklepach WooCommerce
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
  exit;
}

// Definicje stałych motywu
define('THEME_VERSION', '1.0.0');
define('THEME_DIR', get_stylesheet_directory());
define('THEME_URI', get_stylesheet_directory_uri());

// Ładowanie konfiguracji motywu
require_once THEME_DIR . '/inc/theme-config.php';

/**
 * Ładowanie stylów i skryptów
 */
function universal_theme_enqueue_assets()
{
  $theme_version = wp_get_theme()->get('Version');

  // Style rodzica
  wp_enqueue_style(
    'storefront-style',
    get_template_directory_uri() . '/style.css'
  );

  // Style dziecka
  wp_enqueue_style(
    'universal-theme-style',
    get_stylesheet_directory_uri() . '/style.css',
    array('storefront-style'),
    $theme_version
  );

  // Dodatkowe style
  wp_enqueue_style(
    'universal-theme-custom',
    get_stylesheet_directory_uri() . '/assets/css/custom.css',
    array('universal-theme-style'),
    $theme_version
  );

  // Skrypty
  wp_enqueue_script(
    'universal-theme-script',
    get_stylesheet_directory_uri() . '/assets/js/theme.js',
    array('jquery'),
    $theme_version,
    true
  );

  // Enhanced Checkout Script (tylko na stronie checkout)
  if (is_checkout() && !is_wc_endpoint_url()) {
    wp_enqueue_script(
      'universal-checkout-enhanced',
      get_stylesheet_directory_uri() . '/assets/js/checkout-enhanced.js',
      array('jquery', 'wc-checkout'),
      $theme_version,
      true
    );

    // Cross-sell Script dla checkout
    wp_enqueue_script(
      'universal-checkout-crosssell',
      get_stylesheet_directory_uri() . '/assets/js/checkout-crosssell.js',
      array('jquery', 'universal-checkout-enhanced'),
      $theme_version,
      true
    );

    // Lokalizacja dla cross-sell script
    wp_localize_script('universal-checkout-crosssell', 'crosssellConfig', array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('crosssell_nonce'),
      'freeShippingThreshold' => get_theme_option('free_shipping_threshold'),
      'currency' => get_woocommerce_currency_symbol(),
      'currencyPosition' => get_option('woocommerce_currency_pos', 'left'),
      'messages' => array(
        'addedToCart' => __('Produkt dodany do koszyka!', 'textdomain'),
        'errorOccurred' => __('Wystąpił błąd. Spróbuj ponownie.', 'textdomain'),
        'alreadyInCart' => __('Produkt już jest w koszyku.', 'textdomain'),
        'freeShippingUnlocked' => __('Gratulacje! Odblokowano darmową dostawę!', 'textdomain')
      )
    ));
  }

  // Przekazanie danych do JS
  wp_localize_script('universal-theme-script', 'themeConfig', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('theme_nonce'),
    'colors' => get_theme_option('colors')
  ));
}
add_action('wp_enqueue_scripts', 'universal_theme_enqueue_assets');

/**
 * Ładowanie pozostałych plików motywu
 */
require_once THEME_DIR . '/inc/woocommerce-functions.php';
require_once THEME_DIR . '/inc/theme-functions.php';
require_once THEME_DIR . '/inc/woocommerce-checkout-functions.php';
require_once THEME_DIR . '/inc/checkout-crosssell-functions.php';
