<?php

/**
 * Universal Storefront Child Theme
 *
 * Uniwersalny motyw potomny Storefront do szybkiego wdrażania
 * w różnych sklepach WooCommerce
 */

// Zapobieganie bezpośredniemu dostępowi
if (! defined('ABSPATH')) {
  exit;
}

// Definicje stałych motywu
define('THEME_VERSION', '1.0.0');
define('THEME_DIR', get_stylesheet_directory());
define('THEME_URI', get_stylesheet_directory_uri());

// Ładowanie konfiguracji motywu
require_once THEME_DIR . '/inc/theme-config.php';

// Ładowanie critical CSS i web styles
require_once THEME_DIR . '/inc/web-styles.php';

/**
 * Debug helper - guards error_log calls behind WP_DEBUG and administrator check
 * Use universal_debug_log($message) instead of direct error_log() to avoid
 * leaking debug output into AJAX responses or front-end HTML.
 */
function universal_debug_log($message)
{
  if (defined('WP_DEBUG') && WP_DEBUG && function_exists('current_user_can') && current_user_can('manage_options')) {
    if (is_array($message) || is_object($message)) {
      error_log(print_r($message, true));
    } else {
      error_log($message);
    }
  }
}

/**
 * WYMUSZENIE CLASSIC CHECKOUT (zamiast WooCommerce Blocks)
 * Rozwiązuje problemy z aktualizacją cen i skomplikowanymi selektorami
 */
function force_classic_checkout()
{
  // Wyłącz WooCommerce Blocks dla checkout
  add_filter('woocommerce_feature_enabled', function ($enabled, $feature) {
    if ($feature === 'checkout' || $feature === 'cart') {
      return false;
    }
    return $enabled;
  }, 10, 2);

  // Usuń bloki checkout z dozwolonych bloków
  add_filter('allowed_block_types_all', function ($allowed_blocks, $context) {
    if (isset($context->post) && $context->post->post_type === 'page') {
      if (is_array($allowed_blocks)) {
        $blocks_to_remove = [
          'woocommerce/checkout',
          'woocommerce/cart',
          'woocommerce/checkout-contact-information-block',
          'woocommerce/checkout-order-summary-block'
        ];
        $allowed_blocks = array_diff($allowed_blocks, $blocks_to_remove);
      }
    }
    return $allowed_blocks;
  }, 10, 2);
}
add_action('init', 'force_classic_checkout', 5);

/**
 * Utwórz stronę "Koszyk Pusty" przy aktywacji motywu
 */
function universal_create_empty_cart_page()
{
  // Sprawdź czy strona już istnieje
  $page = get_page_by_path('koszyk-pusty');

  if (!$page) {
    // Utwórz stronę
    $page_id = wp_insert_post([
      'post_title'    => 'Koszyk Pusty',
      'post_name'     => 'koszyk-pusty',
      'post_status'   => 'publish',
      'post_type'     => 'page',
      'post_content'  => '',
      'page_template' => 'page-empty-cart.php'
    ]);

    if ($page_id) {
      update_option('universal_empty_cart_page_id', $page_id);
    }
  }
}
add_action('after_switch_theme', 'universal_create_empty_cart_page');

/**
 * Automatycznie zastąp bloki checkout shortcode'em classic
 */
function replace_checkout_blocks_with_shortcode($content)
{
  if (is_checkout()) {
    // Debug: Log current content
    universal_debug_log('🔍 CHECKOUT CONTENT DEBUG: ' . substr($content, 0, 200) . '...');

    // Usuń wszystkie bloki checkout i zastąp klasycznym shortcode
    $content = preg_replace('/<!-- wp:woocommerce\/checkout.*?\/-->/', '[woocommerce_checkout]', $content);
    $content = preg_replace('/<!-- wp:woocommerce\/checkout.*?<!-- \/wp:woocommerce\/checkout -->/', '[woocommerce_checkout]', $content);

    // Jeśli nie ma żadnego shortcode, dodaj go
    if (strpos($content, '[woocommerce_checkout]') === false && strpos($content, 'wp:woocommerce/checkout') === false) {
      universal_debug_log('🔄 FORCED CLASSIC CHECKOUT: No shortcode found, adding [woocommerce_checkout]');
      $content = '[woocommerce_checkout]';
    }

    universal_debug_log('✅ FINAL CHECKOUT CONTENT: ' . substr($content, 0, 100) . '...');
  }

  // Ensure any inserted shortcode is executed here — some themes/processors
  // run do_shortcode earlier, so call it explicitly to render the checkout.
  $content = do_shortcode($content);
  return $content;
}
// Run early so we replace block markup before other content filters; also
// we call do_shortcode() inside to guarantee the inserted shortcode is rendered.
add_filter('the_content', 'replace_checkout_blocks_with_shortcode', 9);

/**
 * DEBUG: Pokaż informacje o checkout page
 */
function debug_checkout_page_info()
{
  // Only run on frontend checkout pages and keep logs minimal/controlled.
  if (is_admin() || ! is_checkout()) {
    return;
  }

  // Limit debug output: only when WP_DEBUG is enabled and for administrators.
  if (! defined('WP_DEBUG') || ! WP_DEBUG || ! function_exists('current_user_can') || ! current_user_can('manage_options')) {
    return;
  }

  universal_debug_log('🔍 CHECKOUT DEBUG INFO:');
  universal_debug_log('  - is_checkout(): ' . (is_checkout() ? 'YES' : 'NO'));
  universal_debug_log('  - Current page ID: ' . get_the_ID());

  // wc_feature_enabled() is defined by WooCommerce; check before calling to avoid fatals.
  if (function_exists('wc_feature_enabled')) {
    universal_debug_log('  - WC Feature checkout enabled: ' . (wc_feature_enabled('checkout') ? 'YES' : 'NO'));
    universal_debug_log('  - WC Feature cart enabled: ' . (wc_feature_enabled('cart') ? 'YES' : 'NO'));
  } else {
    universal_debug_log('  - wc_feature_enabled() not available (WooCommerce may not be loaded yet).');
  }

  // Sprawdź zawartość strony (bez zakładania, że helpery istnieją)
  $post = get_post(get_the_ID());
  if ($post) {
    $has_blocks = function_exists('has_blocks') ? has_blocks($post->post_content) : false;
    $has_shortcode = function_exists('has_shortcode') ? has_shortcode($post->post_content, 'woocommerce_checkout') : false;

    universal_debug_log('  - Has blocks: ' . ($has_blocks ? 'YES' : 'NO'));
    universal_debug_log('  - Has shortcode: ' . ($has_shortcode ? 'YES' : 'NO'));
    universal_debug_log('  - Content preview: ' . substr($post->post_content, 0, 100) . '...');
    $has_shortcode = has_shortcode($post->post_content, 'woocommerce_checkout');

    universal_debug_log('  - Has blocks: ' . ($has_blocks ? 'YES' : 'NO'));
    universal_debug_log('  - Has shortcode: ' . ($has_shortcode ? 'YES' : 'NO'));
    universal_debug_log('  - Content preview: ' . substr($post->post_content, 0, 100) . '...');
  }
}
add_action('wp_head', 'debug_checkout_page_info');

/**
 * Ładowanie stylów i skryptów
 */
function universal_theme_enqueue_assets()
{
  $theme_version = wp_get_theme()->get('Version');

  // Style rodzica (Storefront)
  wp_enqueue_style(
    'storefront-style',
    get_template_directory_uri() . '/style.css'
  );

  // Style dziecka
  wp_enqueue_style(
    'universal-theme-style',
    get_stylesheet_directory_uri() . '/style.css',
    array('storefront-style'), // Przywrócona zależność
    $theme_version
  );

  // Dodatkowe style
  wp_enqueue_style(
    'universal-theme-custom',
    get_stylesheet_directory_uri() . '/assets/css/custom.css',
    array('universal-theme-style'),
    $theme_version
  );

  // Tailwind CSS
  wp_enqueue_style(
    'universal-theme-tailwind',
    get_stylesheet_directory_uri() . '/assets/css/tailwind-compiled.css',
    array('universal-theme-custom'),
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
    // Order Review Script - obsługa usuwania produktów
    wp_enqueue_script(
      'universal-checkout-order-review',
      get_stylesheet_directory_uri() . '/assets/js/checkout-order-review.js',
      array('jquery', 'wc-checkout'),
      $theme_version,
      true
    );

    // Localize script for order review
    wp_localize_script('universal-checkout-order-review', 'checkoutOrderConfig', array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('wc-checkout-nonce'),
      'messages' => array(
        'removing' => __('Usuwanie...', 'textdomain'),
        'updating' => __('Aktualizacja...', 'textdomain'),
        'removed' => __('Produkt został usunięty', 'textdomain'),
        'updated' => __('Ilość zaktualizowana', 'textdomain'),
        'error' => __('Wystąpił błąd', 'textdomain')
      )
    ));

    // Cross-sell Script dla Classic WooCommerce Checkout (zamiast problematycznych Blocks)
    wp_enqueue_script(
      'universal-checkout-crosssell-classic',
      get_stylesheet_directory_uri() . '/assets/js/checkout-crosssell-classic.js',
      array('jquery', 'wc-checkout'),
      $theme_version . '-classic-v1',
      true
    );

    // Quantity Controls dla Classic Checkout - NOWY LEPSZY SYSTEM!
    wp_enqueue_script(
      'universal-checkout-quantity-classic',
      get_stylesheet_directory_uri() . '/assets/js/checkout-quantity-classic.js',
      array('jquery', 'wc-checkout'),
      $theme_version . '-qty-classic-v1', // Force cache refresh
      true
    );

    // Lokalizacja dla quantity controls
    wp_localize_script('universal-checkout-quantity-classic', 'universal_ajax', array(
      'ajax_url' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('universal_cart_nonce'),
      'empty_cart_url' => home_url('/koszyk-pusty/')
    ));

    // Lokalizacja dla cross-sell classic script
    wp_localize_script('universal-checkout-crosssell-classic', 'themeConfig', array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce' => wp_create_nonce('crosssell_nonce'),
      'currency' => get_woocommerce_currency_symbol(),
      'messages' => array(
        'addedToCart' => __('Produkt dodany do koszyka!', 'textdomain'),
        'errorOccurred' => __('Wystąpił błąd. Spróbuj ponownie.', 'textdomain')
      )
    ));

    // *** WYŁĄCZONE - PROBLEMATYCZNE BLOCKS SCRIPTS ***
    /*
    wp_enqueue_script(
      'universal-checkout-crosssell-blocks',
      get_stylesheet_directory_uri() . '/assets/js/checkout-crosssell-blocks.js',
      array('jquery'),
      $theme_version . '-qty-v12',
      true
    );
    */

    wp_enqueue_script(
      'universal-checkout-enhanced',
      get_stylesheet_directory_uri() . '/assets/js/checkout-enhanced.js',
      array('jquery', 'wc-checkout'),
      $theme_version,
      true
    );

    // Cross-sell Script dla checkout - BACKUP (jeśli classic nie zadziała)
    /*
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
    */ // zamknięcie komentarza backup scripts
  } // zamknięcie if (is_checkout())

  // Przekazanie danych do JS
  wp_localize_script('universal-theme-script', 'themeConfig', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('theme_nonce'),
    'colors' => get_theme_option('colors')
  ));
}
add_action('wp_enqueue_scripts', 'universal_theme_enqueue_assets');

/**
 * Page-specific styles - ładują się Z NAJWYŻSZYM PRIORYTETEM (999)
 * Dzięki temu nadpisują WooCommerce CSS
 */
function universal_page_specific_styles()
{
  $theme_version = wp_get_theme()->get('Version');

  // Checkout page styles
  if (is_checkout() && !is_wc_endpoint_url()) {
    wp_enqueue_style(
      'universal-checkout-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/checkout.css',
      array(),
      $theme_version . '-v3' // Cache refresh
    );
  }

  // Cart page styles
  if (is_cart()) {
    wp_enqueue_style(
      'universal-cart-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/cart.css',
      array(),
      $theme_version . '-v3'
    );
  }

  // Single product page styles
  if (is_product()) {
    wp_enqueue_style(
      'universal-product-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/product.css',
      array(),
      $theme_version . '-v3'
    );
  }

  // Shop & archive pages styles
  if (is_shop() || is_product_category() || is_product_tag()) {
    wp_enqueue_style(
      'universal-shop-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/shop.css',
      array(),
      $theme_version . '-v3'
    );
  }

  // Login page styles (My Account when not logged in)
  if (is_account_page() && !is_user_logged_in()) {
    wp_enqueue_style(
      'universal-login-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/login.css',
      array(),
      $theme_version . '-v1'
    );
  }
}
add_action('wp_enqueue_scripts', 'universal_page_specific_styles', 999); // PRIORYTET 999 = ładuje się OSTATNI

// Włącz wsparcie WooCommerce
function universal_woocommerce_support()
{
  add_theme_support('woocommerce');
  add_theme_support('wc-product-gallery-zoom');
  add_theme_support('wc-product-gallery-lightbox');
  add_theme_support('wc-product-gallery-slider');
}
add_action('after_setup_theme', 'universal_woocommerce_support');

/**
 * ========================================
 * WYSYŁKA - APACZKA INTEGRATION
 * ========================================
 */

// FIX: Apaczka plugin rejestruje metodę tylko w admin, musimy to naprawić
add_filter('woocommerce_shipping_methods', function ($methods) {
  // Sprawdź czy klasa Apaczka istnieje
  if (class_exists('Inspire_Labs\Apaczka_Woocommerce\Shipping_Method_Apaczka')) {
    $methods['apaczka'] = 'Inspire_Labs\Apaczka_Woocommerce\Shipping_Method_Apaczka';
  }
  return $methods;
}, 20);

/**
 * Dodaj logo przewoźnika do metod wysyłki
 */
add_filter('woocommerce_cart_shipping_method_full_label', function ($label, $method) {
  // Mapowanie słów kluczowych → URL logo
  // UWAGA: Bardziej specyficzne słowa kluczowe (np. inpost, dpd) muszą być PRZED ogólnymi (flat_rate)
  $shipping_logos = array(
    'inpost'        => get_stylesheet_directory_uri() . '/assets/images/shipping/inpost.png',
    'dpd'           => get_stylesheet_directory_uri() . '/assets/images/shipping/dpd.png',
    'apaczka'       => get_stylesheet_directory_uri() . '/assets/images/shipping/apaczka.png',
    'free_shipping' => get_stylesheet_directory_uri() . '/assets/images/shipping/free-shipping.png',
    'local_pickup'  => get_stylesheet_directory_uri() . '/assets/images/shipping/pickup.png',
    'flat_rate'     => get_stylesheet_directory_uri() . '/assets/images/shipping/flat-rate.png',
  );

  // Pobierz ID metody i label
  $method_id = $method->get_method_id();
  $method_label = strtolower($method->get_label());
  $full_id = strtolower($method->get_id()); // pełne ID z instance

  // Sprawdź każde słowo kluczowe
  foreach ($shipping_logos as $keyword => $logo_url) {
    // Sprawdź czy keyword występuje w ID, pełnym ID lub nazwie metody
    if (
      stripos($method_id, $keyword) !== false ||
      stripos($full_id, $keyword) !== false ||
      stripos($method_label, $keyword) !== false
    ) {
      // Sprawdź czy plik istnieje
      $logo_path = str_replace(get_stylesheet_directory_uri(), get_stylesheet_directory(), $logo_url);

      if (file_exists($logo_path)) {
        // Dodaj logo przed tekstem
        $logo_html = '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($method->get_label()) . '" class="shipping-method-logo" style="width: 40px; height: 40px; object-fit: contain; margin-right: 10px; vertical-align: middle;">';
        $label = $logo_html . $label;
        break; // Znaleziono logo, przerwij pętlę
      }
    }
  }
  return $label;
}, 10, 2);

/**
 * ========================================
 * CUSTOMIZACJA PÓL BILLING NA CHECKOUT
 * ========================================
 */
add_filter('woocommerce_checkout_fields', function ($fields) {

  // 1. Ustaw kolejność i wymagalność pól
  $fields['billing']['billing_first_name']['priority'] = 10;
  $fields['billing']['billing_first_name']['required'] = true;
  $fields['billing']['billing_first_name']['class'] = array('form-row-first');

  $fields['billing']['billing_last_name']['priority'] = 20;
  $fields['billing']['billing_last_name']['required'] = true;
  $fields['billing']['billing_last_name']['class'] = array('form-row-last');

  $fields['billing']['billing_email']['priority'] = 30;
  $fields['billing']['billing_email']['required'] = true;
  $fields['billing']['billing_email']['class'] = array('form-row-first');

  $fields['billing']['billing_phone']['priority'] = 40;
  $fields['billing']['billing_phone']['required'] = true;
  $fields['billing']['billing_phone']['class'] = array('form-row-last');

  // Nagłówek "Adres dostawy" - dodamy przez CSS/JS

  $fields['billing']['billing_country']['priority'] = 50;
  $fields['billing']['billing_country']['required'] = true;
  $fields['billing']['billing_country']['class'] = array('form-row-wide');

  $fields['billing']['billing_address_1']['priority'] = 60;
  $fields['billing']['billing_address_1']['required'] = true;
  $fields['billing']['billing_address_1']['class'] = array('form-row-first');
  $fields['billing']['billing_address_1']['label'] = __('Ulica i numer domu', 'universal-theme');
  $fields['billing']['billing_address_1']['placeholder'] = __('Ulica i numer domu', 'universal-theme');
  $fields['billing']['billing_address_1']['label_class'] = array('screen-reader-text');

  $fields['billing']['billing_address_2']['priority'] = 70;
  $fields['billing']['billing_address_2']['required'] = true;
  $fields['billing']['billing_address_2']['class'] = array('form-row-last');
  $fields['billing']['billing_address_2']['label'] = __('Numer mieszkania/domu', 'universal-theme');
  $fields['billing']['billing_address_2']['placeholder'] = __('Numer mieszkania/domu', 'universal-theme');
  $fields['billing']['billing_address_2']['label_class'] = array('screen-reader-text');

  $fields['billing']['billing_postcode']['priority'] = 80;
  $fields['billing']['billing_postcode']['required'] = true;
  $fields['billing']['billing_postcode']['class'] = array('form-row-first');
  $fields['billing']['billing_postcode']['placeholder'] = __('Kod pocztowy', 'universal-theme');
  $fields['billing']['billing_postcode']['label_class'] = array('screen-reader-text');

  $fields['billing']['billing_city']['priority'] = 90;
  $fields['billing']['billing_city']['required'] = true;
  $fields['billing']['billing_city']['class'] = array('form-row-last');
  $fields['billing']['billing_city']['placeholder'] = __('Miasto', 'universal-theme');
  $fields['billing']['billing_city']['label_class'] = array('screen-reader-text');

  // Ukryj pole województwa/stanu (nie używamy)
  if (isset($fields['billing']['billing_state'])) {
    unset($fields['billing']['billing_state']);
  }

  // Ukryj pole company (można odkomentować jeśli potrzebujesz)
  if (isset($fields['billing']['billing_company'])) {
    unset($fields['billing']['billing_company']);
  }

  // ========================================
  // SHIPPING FIELDS - identyczne ustawienie jak billing
  // ========================================

  $fields['shipping']['shipping_first_name']['priority'] = 10;
  $fields['shipping']['shipping_first_name']['class'] = array('form-row-first');

  $fields['shipping']['shipping_last_name']['priority'] = 20;
  $fields['shipping']['shipping_last_name']['class'] = array('form-row-last');

  $fields['shipping']['shipping_country']['priority'] = 50;
  $fields['shipping']['shipping_country']['class'] = array('form-row-wide');

  $fields['shipping']['shipping_address_1']['priority'] = 60;
  $fields['shipping']['shipping_address_1']['class'] = array('form-row-first');
  $fields['shipping']['shipping_address_1']['label'] = __('Ulica i numer domu', 'universal-theme');
  $fields['shipping']['shipping_address_1']['placeholder'] = __('Ulica i numer domu', 'universal-theme');
  $fields['shipping']['shipping_address_1']['label_class'] = array('screen-reader-text');

  $fields['shipping']['shipping_address_2']['priority'] = 70;
  $fields['shipping']['shipping_address_2']['required'] = true;
  $fields['shipping']['shipping_address_2']['class'] = array('form-row-last');
  $fields['shipping']['shipping_address_2']['label'] = __('Numer mieszkania/domu', 'universal-theme');
  $fields['shipping']['shipping_address_2']['placeholder'] = __('Numer mieszkania/domu', 'universal-theme');
  $fields['shipping']['shipping_address_2']['label_class'] = array('screen-reader-text');

  $fields['shipping']['shipping_postcode']['priority'] = 80;
  $fields['shipping']['shipping_postcode']['class'] = array('form-row-first');
  $fields['shipping']['shipping_postcode']['placeholder'] = __('Kod pocztowy', 'universal-theme');
  $fields['shipping']['shipping_postcode']['label_class'] = array('screen-reader-text');

  $fields['shipping']['shipping_city']['priority'] = 90;
  $fields['shipping']['shipping_city']['class'] = array('form-row-last');
  $fields['shipping']['shipping_city']['placeholder'] = __('Miasto', 'universal-theme');
  $fields['shipping']['shipping_city']['label_class'] = array('screen-reader-text');

  // Ukryj state dla shipping
  if (isset($fields['shipping']['shipping_state'])) {
    unset($fields['shipping']['shipping_state']);
  }

  // Ukryj company dla shipping
  if (isset($fields['shipping']['shipping_company'])) {
    unset($fields['shipping']['shipping_company']);
  }

  return $fields;
});

// Dodaj nagłówki i wymuszenie layoutu przez JavaScript
add_action('woocommerce_before_checkout_billing_form', function () {
?>
  <script>
    jQuery(document).ready(function($) {
      function addShippingAddressHeading() {
        var $countryField = $('#billing_country_field');

        if ($countryField.length && !$countryField.prev('.shipping-address-heading').length) {
          $countryField.before('<h3 class="shipping-address-heading">Adres dostawy</h3>');
        }

        // Dodaj <p>Adres:</p> po billing_country_field
        if ($countryField.length && !$countryField.next('.address-label').length) {
          $countryField.after('<p class="address-label">Adres:</p>');
        }
      }

      // Wymuszenie layoutu inline style (najsilniejsze)
      function forceFieldLayout() {
        $('.form-row-first').css({
          'width': '48%',
          'display': 'inline-block',
          'vertical-align': 'top',
          'float': 'none',
          'margin-right': '4%'
        });

        $('.form-row-last').css({
          'width': '48%',
          'display': 'inline-block',
          'vertical-align': 'top',
          'float': 'none',
          'margin-right': '0'
        });
      }

      addShippingAddressHeading();
      forceFieldLayout();

      $(document.body).on('updated_checkout', function() {
        addShippingAddressHeading();
        setTimeout(forceFieldLayout, 100); // Wymuszenie po AJAX
      });
    });
  </script>
  <?php
});

/**
 * Ładowanie pozostałych plików motywu
 */
require_once THEME_DIR . '/inc/woocommerce-functions.php';
require_once THEME_DIR . '/inc/theme-functions.php';
require_once THEME_DIR . '/inc/woocommerce-checkout-functions.php';
require_once THEME_DIR . '/inc/checkout-table-custom.php';
// Disabled: checkout crosssell functions moved to backup (not used).
// require_once THEME_DIR . '/inc/checkout-crosssell-functions.php';
// Disabled: checkout blocks / layout includes (aggressive cleanup). Backups kept.
// require_once THEME_DIR . '/inc/checkout-blocks-functions.php';
// Disabled: test crosssell endpoint moved to backup (not used).
// require_once THEME_DIR . '/inc/simple-crosssell-test.php';
require_once THEME_DIR . '/inc/admin-panel.php';
require_once THEME_DIR . '/inc/header-functions.php';
require_once THEME_DIR . '/inc/checkout-remove-products.php';
// require_once THEME_DIR . '/inc/checkout-layout-hooks.php'; // Ponownie włączamy dla blocks

// Temporary test removed during aggressive cleanup. Backup available at functions.php.bak

// Skip cart page - redirect straight to checkout for better conversion
function universal_skip_cart_page()
{
  // Only redirect when on cart and cart isn't empty
  if (function_exists('is_cart') && is_cart()) {
    if (WC()->cart && ! WC()->cart->is_empty()) {
      wp_redirect(wc_get_checkout_url());
      exit;
    }
  }
}
add_action('template_redirect', 'universal_skip_cart_page', 5);

/**
 * Przekieruj na stronę pustego koszyka gdy użytkownik próbuje wejść na pusty checkout
 */
function universal_redirect_empty_checkout()
{
  // Sprawdź czy jesteśmy na checkout i czy nie jesteśmy już na stronie pustego koszyka
  if (is_checkout() && !is_wc_endpoint_url() && !is_page('koszyk-pusty')) {
    if (WC()->cart && WC()->cart->is_empty()) {
      wp_redirect(home_url('/koszyk-pusty/'));
      exit;
    }
  }
}
add_action('template_redirect', 'universal_redirect_empty_checkout', 10);

/**
 * Krótki komunikat o możliwości logowania na checkout z linkiem do strony logowania
 * Wyświetla się w lewej kolumnie (col2-set) przed polami billing
 */
function universal_checkout_login_notice()
{
  if (!is_user_logged_in()) {
    // URL logowania z przekierowaniem z powrotem na checkout
    $login_url = wp_login_url(wc_get_checkout_url());
  ?>
    <style>
      /* Ukryj komunikat przed przeniesieniem */
      .checkout-login-notice:not(.moved) {
        opacity: 0;
        visibility: hidden;
      }

      /* Pokaż z animacją po przeniesieniu */
      .checkout-login-notice.moved {
        opacity: 1;
        visibility: visible;
        transition: opacity 0.3s ease;
      }
    </style>
    <div class="woocommerce-info checkout-login-notice" style="margin-bottom: 20px;">
      Masz już konto? <a href="<?php echo esc_url($login_url); ?>">Kliknij tutaj aby się zalogować</a> i automatycznie uzupełnić dane.
    </div>
    <script>
      jQuery(document).ready(function($) {
        function moveLoginNotice() {
          var $notice = $('.checkout-login-notice');
          var $col2set = $('.col2-set');

          if ($notice.length && $col2set.length && !$notice.parent().hasClass('col2-set')) {
            $notice.prependTo($col2set).addClass('moved');
          }
        }

        moveLoginNotice();

        // Przenieś ponownie po aktualizacji checkout
        $(document.body).on('updated_checkout', function() {
          moveLoginNotice();
        });
      });
    </script>
  <?php
  }
}
add_action('woocommerce_before_checkout_form', 'universal_checkout_login_notice', 5);

// Zmień wszystkie linki "View Cart" na "Checkout"
add_filter('woocommerce_get_cart_url', function ($url) {
  return wc_get_checkout_url();
});

// Zmień text "View Cart" na "Checkout" w mini cart
function universal_change_cart_button_text($text, $domain)
{
  switch ($text) {
    case 'View Cart':
    case 'View cart':
    case 'Zobacz koszyk':
      return __('Checkout', 'universal-theme');
    case 'Go to checkout':
    case 'Przejdź do kasy':
      return __('Checkout', 'universal-theme');
    default:
      return $text;
  }
}
add_filter('gettext', 'universal_change_cart_button_text', 10, 2);
add_filter('ngettext', 'universal_change_cart_button_text', 10, 2);

// AJAX Handlers for Cart Quantity Updates
function universal_update_cart_quantity()
{
  // Check nonce for security
  if (!wp_verify_nonce($_POST['nonce'], 'universal_cart_nonce')) {
    wp_die('Unauthorized');
  }

  $cart_item_key = sanitize_text_field($_POST['cart_item_key']);
  $product_id = intval($_POST['product_id']);
  $variation_id = intval($_POST['variation_id']);
  $product_name = sanitize_text_field($_POST['product_name']);
  $item_index = intval($_POST['item_index']);
  $quantity = intval($_POST['quantity']);

  // CRITICAL: Validate quantity to prevent corruption
  if ($quantity < 0 || $quantity > 999) {
    universal_debug_log("Universal Cart: Invalid quantity {$quantity}, rejecting request");
    wp_send_json_error('Invalid quantity: ' . $quantity);
  }

  // If we don't have cart_item_key, try to find it
  if (empty($cart_item_key) && (!empty($product_id) || !empty($product_name))) {

    $cart_contents = WC()->cart->get_cart();
    $found_key = null;
    $match_count = 0;

    foreach ($cart_contents as $key => $cart_item) {
      $match = false;

      // Try to match by product ID
      if (!empty($product_id) && $cart_item['product_id'] == $product_id) {
        // Also check variation ID if provided
        if (!empty($variation_id)) {
          if (isset($cart_item['variation_id']) && $cart_item['variation_id'] == $variation_id) {
            $match = true;
          }
        } else {
          $match = true;
        }
      }

      // Fallback: try to match by product name
      if (!$match && !empty($product_name)) {
        $product = $cart_item['data'];
        if ($product && method_exists($product, 'get_name')) {
          $cart_product_name = $product->get_name();
          if (stripos($cart_product_name, $product_name) !== false || stripos($product_name, $cart_product_name) !== false) {
            $match = true;
          }
        }
      }

      if ($match) {
        $found_key = $key;
        $match_count++;

        // If we have item index, use it to select the right match
        if ($match_count - 1 == $item_index) {
          break;
        }
      }
    }

    if ($found_key) {
      $cart_item_key = $found_key;
      universal_debug_log("Universal Cart: Found cart key {$cart_item_key} for product ID {$product_id}, name '{$product_name}', index {$item_index}");
    }
  }

  if (empty($cart_item_key)) {
    wp_send_json_error('Could not find cart item. Product ID: ' . $product_id . ', Name: ' . $product_name);
  }

  // Update quantity in cart
  if ($quantity === 0) {
    // Remove item if quantity is 0
    $result = WC()->cart->remove_cart_item($cart_item_key);
    $message = 'Product removed from cart';
    universal_debug_log("Universal Cart: Removing item {$cart_item_key}, result: " . ($result ? 'success' : 'failed'));
  } else {
    // Update quantity
    $result = WC()->cart->set_quantity($cart_item_key, $quantity, true);
    $message = 'Quantity updated to ' . $quantity;
    universal_debug_log("Universal Cart: Setting quantity {$quantity} for item {$cart_item_key}, result: " . ($result ? 'success' : 'failed'));
  }

  if ($result !== false) {
    // Recalculate totals
    WC()->cart->calculate_totals();

    // Force persistent save to database
    WC()->cart->persistent_cart_update();

    // Also force session save
    if (WC()->session) {
      WC()->session->save_data();
    }

    // Get updated cart data
    $cart_total = WC()->cart->get_cart_total();
    $cart_subtotal = WC()->cart->get_cart_subtotal();
    $cart_count = WC()->cart->get_cart_contents_count();
    $cart_empty = WC()->cart->is_empty();

    universal_debug_log("Universal Cart: Success! New totals - Count: {$cart_count}, Total: {$cart_total}, Empty: " . ($cart_empty ? 'YES' : 'NO'));

    wp_send_json_success(array(
      'cart_total' => $cart_total,
      'cart_subtotal' => $cart_subtotal,
      'cart_count' => $cart_count,
      'cart_empty' => $cart_empty,
      'message' => $message,
      'cart_item_key' => $cart_item_key
    ));
  } else {
    universal_debug_log("Universal Cart: Failed to update cart for key {$cart_item_key}");
    wp_send_json_error('Failed to update cart');
  }
}

/**
 * AJAX: Pobierz nowy HTML totals (podsumowanie koszyka)
 * Zwraca HTML z najnowszymi wartościami subtotal, shipping, tax, total
 */
function universal_get_checkout_totals()
{
  // Check nonce for security
  if (!wp_verify_nonce($_POST['nonce'], 'universal_cart_nonce')) {
    wp_send_json_error('Unauthorized');
  }

  // Recalculate totals jeśli trzeba
  WC()->cart->calculate_totals();

  // Get HTML for each totals row
  ob_start();
  universal_render_checkout_totals();
  $totals_html = ob_get_clean();

  wp_send_json_success(array(
    'totals_html' => $totals_html,
    'cart_total' => WC()->cart->get_cart_total(),
    'cart_subtotal' => WC()->cart->get_cart_subtotal(),
    'cart_count' => WC()->cart->get_cart_contents_count()
  ));
}

/**
 * Render totals w naszym custom formacie
 * Format: Sub total: Kwota | Shipping: Kwota | Total: Kwota
 */
function universal_render_checkout_totals()
{
  $subtotal = WC()->cart->get_cart_subtotal();
  $total = WC()->cart->get_total();

  // Pobierz shipping
  $shipping_total = WC()->cart->get_shipping_total();
  $shipping_formatted = wc_price($shipping_total);

  // Pobierz tax
  $tax_total = WC()->cart->get_total_tax();
  $tax_formatted = wc_price($tax_total);

  ?>
  <div class="universal-checkout-totals">
    <table class="woocommerce-table--totals">
      <tbody>
        <!-- Sub total -->
        <tr class="cart-subtotal">
          <th><?php echo __('Sub total:', 'woocommerce'); ?></th>
          <td><?php echo wp_kses_post($subtotal); ?></td>
        </tr>

        <!-- Shipping -->
        <tr class="shipping-totals">
          <th><?php echo __('Shipping:', 'woocommerce'); ?></th>
          <td><?php echo wp_kses_post($shipping_formatted); ?></td>
        </tr>

        <!-- Tax (jeśli jest) -->
        <?php if ($tax_total > 0) : ?>
          <tr class="tax-total">
            <th><?php echo __('Tax:', 'woocommerce'); ?></th>
            <td><?php echo wp_kses_post($tax_formatted); ?></td>
          </tr>
        <?php endif; ?>

        <!-- Order Total -->
        <tr class="order-total">
          <th><?php echo __('Total:', 'woocommerce'); ?></th>
          <td><?php echo wp_kses_post($total); ?></td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php
}

add_action('wp_ajax_universal_get_checkout_totals', 'universal_get_checkout_totals');
add_action('wp_ajax_nopriv_universal_get_checkout_totals', 'universal_get_checkout_totals'); // Zarejestruj AJAX handler - action musi być 'universal_update_cart_quantity'
add_action('wp_ajax_universal_update_cart_quantity', 'universal_update_cart_quantity');
add_action('wp_ajax_nopriv_universal_update_cart_quantity', 'universal_update_cart_quantity');

// Dodatkowa funkcja do całkowitego czyszczenia koszyka
function universal_clear_cart()
{
  // Check nonce for security
  if (!wp_verify_nonce($_POST['nonce'], 'universal_cart_nonce')) {
    wp_send_json_error('Invalid nonce');
  }

  try {
    // Clear the entire cart
    WC()->cart->empty_cart();

    // Force recalculation
    WC()->cart->calculate_totals();

    // Try to save session if available
    if (WC()->session && method_exists(WC()->session, 'save_data')) {
      WC()->session->save_data();
    }

    // Try alternative persistent cart save
    if (method_exists(WC()->cart, 'persistent_cart_update')) {
      WC()->cart->persistent_cart_update();
    }

    universal_debug_log("Universal Cart: Cart completely cleared!");

    wp_send_json_success(array(
      'message' => 'Cart cleared successfully',
      'cart_count' => 0,
      'cart_total' => '0 zł',
      'cart_subtotal' => '0 zł'
    ));
  } catch (Exception $e) {
    universal_debug_log("Universal Cart: Error clearing cart: " . $e->getMessage());
    wp_send_json_error('Error clearing cart: ' . $e->getMessage());
  }
}

add_action('wp_ajax_clear_cart', 'universal_clear_cart');
add_action('wp_ajax_nopriv_clear_cart', 'universal_clear_cart');

// Add AJAX URL and nonce for frontend
function universal_add_ajax_data()
{
  if (is_checkout()) {
  ?>
    <script type="text/javascript">
      var universal_ajax = {
        'ajax_url': '<?php echo admin_url('admin-ajax.php'); ?>',
        'nonce': '<?php echo wp_create_nonce('universal_cart_nonce'); ?>'
      };
    </script>
  <?php
  }
}
add_action('wp_head', 'universal_add_ajax_data');

// Handle empty-cart URL parameter
function universal_handle_empty_cart()
{
  if (isset($_GET['empty-cart']) && $_GET['empty-cart'] == '1') {
    if (WC()->cart) {
      WC()->cart->empty_cart();
      WC()->cart->calculate_totals();

      // Clear all WooCommerce sessions and cookies
      if (WC()->session) {
        WC()->session->destroy_session();
      }

      // Clear persistent cart
      if (is_user_logged_in()) {
        delete_user_meta(get_current_user_id(), '_woocommerce_persistent_cart_' . get_current_blog_id());
      }

      // Redirect to shop page instead of checkout (since cart is empty)
      wp_redirect(wc_get_page_permalink('shop'));
      exit;
    }
  }
}
add_action('wp_loaded', 'universal_handle_empty_cart');

// Clean up corrupted cart items with invalid quantities
function universal_cleanup_cart()
{
  if (is_checkout() && WC()->cart) {
    $cart_contents = WC()->cart->get_cart();
    $cleaned = false;

    foreach ($cart_contents as $cart_item_key => $cart_item) {
      $quantity = $cart_item['quantity'];

      // If quantity is corrupted (too large), fix it
      if ($quantity > 100) {
        universal_debug_log("Universal Cart: Found corrupted quantity {$quantity} for item {$cart_item_key}, fixing to 1");
        WC()->cart->set_quantity($cart_item_key, 1, true);
        $cleaned = true;
      }
    }

    if ($cleaned) {
      WC()->cart->calculate_totals();
      universal_debug_log("Universal Cart: Cleaned corrupted cart items");
    }
  }
}
add_action('wp', 'universal_cleanup_cart');

// Handle cart-cleared parameter for shop page
function universal_handle_cart_cleared()
{
  if (isset($_GET['cart-cleared']) && $_GET['cart-cleared'] == '1') {
    if (WC()->cart) {
      WC()->cart->empty_cart();
      WC()->cart->calculate_totals();

      // Clear all sessions
      if (WC()->session) {
        WC()->session->destroy_session();
      }

      // Set a notice
      wc_add_notice('Koszyk został wyczyszczony.', 'success');
    }
  }
}
add_action('wp_loaded', 'universal_handle_cart_cleared');

/**
 * Display placeholder for cross-sell section on checkout
 */
function universal_display_crosssell_placeholder()
{
  if (is_checkout() && !is_wc_endpoint_url()) {
    echo '<div class="checkout-crosssell-section"></div>';
  }
}
add_action('woocommerce_checkout_after_order_review', 'universal_display_crosssell_placeholder', 15);

/**
 * AJAX endpoint: return rendered cross-sell HTML for classic checkout
 */
add_action('wp_ajax_universal_get_crosssells', 'universal_get_crosssells_ajax');
add_action('wp_ajax_nopriv_universal_get_crosssells', 'universal_get_crosssells_ajax');
function universal_get_crosssells_ajax()
{
  // Optional: verify nonce if sent
  if (!empty($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'crosssell_nonce')) {
    wp_send_json_error('Invalid nonce');
  }

  $products = array();

  if (function_exists('WC') && WC()->cart) {
    $cart = WC()->cart->get_cart();
    $ids = array();
    foreach ($cart as $item) {
      $ids[] = $item['product_id'];
    }

    foreach ($ids as $pid) {
      $prod = wc_get_product($pid);
      if ($prod && $prod->get_cross_sell_ids()) {
        foreach ($prod->get_cross_sell_ids() as $csid) {
          // avoid duplicates
          $existing_ids = array_column($products, 'id');
          if (in_array($csid, $existing_ids, true)) {
            continue;
          }
          $p = wc_get_product($csid);
          if ($p) {
            // Sprawdź czy produkt ma warianty (variable product)
            $has_variants = $p->get_type() === 'variable' && !empty($p->get_children());

            $products[] = array(
              'id' => $p->get_id(),
              'name' => $p->get_name(),
              'price_formatted' => wc_price($p->get_price()),
              'image' => wp_get_attachment_image_url($p->get_image_id(), 'thumbnail'),
              'permalink' => get_permalink($p->get_id()),
              'has_variants' => $has_variants,
            );
          }
        }
      }
    }
  }

  // Fallback: if no cross-sells, get recent products
  if (empty($products)) {
    $recent = wc_get_products(array(
      'limit' => 4,
      'orderby' => 'date',
      'order' => 'DESC',
      'status' => 'publish',
    ));
    foreach ($recent as $p) {
      $has_variants = $p->get_type() === 'variable' && !empty($p->get_children());

      $products[] = array(
        'id' => $p->get_id(),
        'name' => $p->get_name(),
        'price_formatted' => wc_price($p->get_price()),
        'image' => wp_get_attachment_image_url($p->get_image_id(), 'thumbnail'),
        'permalink' => get_permalink($p->get_id()),
        'has_variants' => $has_variants,
      );
    }
  }

  // Render partial
  ob_start();
  $tpl = get_stylesheet_directory() . '/templates/partials/checkout-crosssell.php';
  if (file_exists($tpl)) {
    include $tpl; // it will use $products
  } else {
    echo '<div class="universal-crosssell-wrapper">';
    echo '<p>' . esc_html__('No cross-sells available', 'universal-theme') . '</p>';
    echo '</div>';
  }
  $html = ob_get_clean();

  wp_send_json_success(array('html' => $html));
}

/**
 * AJAX: Dodaj cross-sell produkt do koszyka
 */
function universal_handle_add_crosssell_product()
{
  // Verify nonce - używamy 'crosssell_nonce' bo taki jest w localization
  if (empty($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crosssell_nonce')) {
    wp_send_json_error(__('Security check failed', 'universal-theme'));
  }

  $product_id = intval($_POST['product_id']);
  $quantity = intval($_POST['quantity']) ?: 1;

  if ($product_id <= 0) {
    wp_send_json_error(__('Invalid product', 'universal-theme'));
  }

  // Sprawdź czy produkt już jest w koszyku
  $cart = WC()->cart->get_cart();
  $found_in_cart = false;
  $cart_item_key = null;

  foreach ($cart as $key => $cart_item) {
    if ($cart_item['product_id'] == $product_id) {
      $found_in_cart = true;
      $cart_item_key = $key;
      break;
    }
  }

  if ($found_in_cart && $cart_item_key) {
    // Zaktualizuj ilość zamiast dodawać nowy item
    $new_qty = $cart[$cart_item_key]['quantity'] + $quantity;
    WC()->cart->set_quantity($cart_item_key, $new_qty);
  } else {
    // Dodaj nowy produkt do koszyka
    WC()->cart->add_to_cart($product_id, $quantity);
  }

  WC()->cart->calculate_totals();

  wp_send_json_success(array(
    'added' => true,
    'added_label' => __('Added to cart', 'universal-theme'),
    'cart_count' => WC()->cart->get_cart_contents_count(),
    'message' => sprintf(__('%s added to cart', 'universal-theme'), get_the_title($product_id))
  ));
}
add_action('wp_ajax_universal_handle_add_crosssell_product', 'universal_handle_add_crosssell_product');
add_action('wp_ajax_nopriv_universal_handle_add_crosssell_product', 'universal_handle_add_crosssell_product');

// Alias dla backward compatibility
add_action('wp_ajax_universal_add_crosssell_product', 'universal_handle_add_crosssell_product');
add_action('wp_ajax_nopriv_universal_add_crosssell_product', 'universal_handle_add_crosssell_product');

/**
 * AJAX: Przeładuj custom checkout table (dla cross-sell)
 */
function universal_refresh_checkout_table()
{
  ob_start();

  // Render checkout review table (TYLKO tabela z produktami)
  if (!WC()->cart->is_empty()) {
  ?>
    <div class="universal-checkout-review-table-custom">
      <?php
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        $quantity = $cart_item['quantity'];
        $product_id = $cart_item['product_id'];
        $product_name = $product->get_name();
        $product_image = $product->get_image('thumbnail');
        $product_price = $product->get_price();
        $product_total = $product_price * $quantity;
        $price_formatted = wc_price($product_price);
        $total_formatted = wc_price($product_total);
      ?>
        <div class="universal-checkout-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
          <!-- Lewa część: Miniaturka + Nazwa + Cena jednostkowa -->
          <div class="checkout-item-left">
            <div class="checkout-item-thumbnail">
              <?php echo $product_image; ?>
              <button type="button" class="checkout-item-remove-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Usuń z koszyka', 'universal-theme'); ?>">×</button>
            </div>
            <div class="checkout-item-details">
              <div class="checkout-item-name">
                <?php echo esc_html($product_name); ?>
              </div>
              <div class="checkout-item-unit-price" data-unit-price="<?php echo esc_attr($product_price); ?>">
                <?php echo wp_kses_post($price_formatted); ?>
              </div>
            </div>
          </div>

          <!-- Środek: Ilość +/- -->
          <div class="checkout-item-quantity-wrapper">
            <span class="qty-label"><?php echo __('Ilość:', 'universal-theme'); ?></span>
            <div class="checkout-item-quantity-controls">
              <button type="button" class="qty-btn minus" data-action="minus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Zmniejsz ilość', 'universal-theme'); ?>">−</button>
              <span class="qty-display" data-qty="<?php echo esc_attr($quantity); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Kliknij aby edytować ilość', 'universal-theme'); ?>"><?php echo esc_html($quantity); ?></span>
              <button type="button" class="qty-btn plus" data-action="plus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Zwiększ ilość', 'universal-theme'); ?>">+</button>
            </div>
          </div>

          <!-- Prawa część: Cena całkowita -->
          <div class="checkout-item-right">
            <div class="checkout-item-total-price" data-unit-price="<?php echo esc_attr($product_price); ?>">
              <?php echo wp_kses_post($total_formatted); ?>
            </div>
          </div>
        </div>
      <?php
      }
      ?>
    </div>
<?php
  }

  $html = ob_get_clean();
  wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_universal_refresh_checkout_table', 'universal_refresh_checkout_table');
add_action('wp_ajax_nopriv_universal_refresh_checkout_table', 'universal_refresh_checkout_table');

/**
 * ========================================
 * REDIRECT WP-LOGIN TO WOOCOMMERCE MY ACCOUNT
 * ========================================
 */
function universal_redirect_wp_login_to_myaccount()
{
  // Sprawdź czy jesteśmy na wp-login.php (ale NIE na logout ani reset hasła)
  if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
    // Nie przekierowuj jeśli to logout lub reset hasła
    if (isset($_GET['action']) && in_array($_GET['action'], array('logout', 'lostpassword', 'rp', 'resetpass'))) {
      return;
    }

    // Nie przekierowuj w adminie
    if (is_admin()) {
      return;
    }

    // Pobierz redirect_to jeśli istnieje
    $redirect_to = isset($_GET['redirect_to']) ? $_GET['redirect_to'] : '';

    // Przekieruj na stronę WooCommerce My Account
    $myaccount_url = wc_get_page_permalink('myaccount');

    if ($redirect_to) {
      // Zachowaj parametr redirect_to
      $myaccount_url = add_query_arg('redirect_to', urlencode($redirect_to), $myaccount_url);
    }

    wp_redirect($myaccount_url);
    exit;
  }
}
add_action('init', 'universal_redirect_wp_login_to_myaccount', 1);

/**
 * ========================================
 * CUSTOM LOGIN & REGISTER AJAX HANDLERS
 * ========================================
 */

// Handle custom login
function universal_custom_login_handler()
{
  check_ajax_referer('custom_login_action', 'custom_login_nonce');

  $username = sanitize_text_field($_POST['username']);
  $password = $_POST['password'];
  $remember = isset($_POST['rememberme']);

  $credentials = array(
    'user_login'    => $username,
    'user_password' => $password,
    'remember'      => $remember
  );

  $user = wp_signon($credentials, false);

  if (is_wp_error($user)) {
    wp_send_json_error(array(
      'message' => __('Nieprawidłowa nazwa użytkownika lub hasło.', 'universal-theme')
    ));
  }

  wp_send_json_success(array(
    'message' => __('Logowanie pomyślne! Przekierowywanie...', 'universal-theme'),
    'redirect' => wc_get_page_permalink('myaccount')
  ));
}
add_action('wp_ajax_nopriv_custom_login', 'universal_custom_login_handler');

// Handle custom registration
function universal_custom_register_handler()
{
  check_ajax_referer('custom_register_action', 'custom_register_nonce');

  $email = sanitize_email($_POST['email']);
  $username = sanitize_user($_POST['username']);
  $password = $_POST['password'];

  // Validate email
  if (!is_email($email)) {
    wp_send_json_error(array(
      'message' => __('Podaj prawidłowy adres email.', 'universal-theme')
    ));
  }

  // Check if email exists
  if (email_exists($email)) {
    wp_send_json_error(array(
      'message' => __('Ten adres email jest już zarejestrowany.', 'universal-theme')
    ));
  }

  // Check if username exists
  if (username_exists($username)) {
    wp_send_json_error(array(
      'message' => __('Ta nazwa użytkownika jest już zajęta.', 'universal-theme')
    ));
  }

  // Create user
  $user_id = wp_create_user($username, $password, $email);

  if (is_wp_error($user_id)) {
    wp_send_json_error(array(
      'message' => $user_id->get_error_message()
    ));
  }

  // Set role to customer
  $user = new WP_User($user_id);
  $user->set_role('customer');

  // Auto login after registration
  wp_set_current_user($user_id);
  wp_set_auth_cookie($user_id);

  // Send welcome email (optional)
  wp_new_user_notification($user_id, null, 'user');

  wp_send_json_success(array(
    'message' => __('Rejestracja pomyślna! Przekierowywanie...', 'universal-theme'),
    'redirect' => wc_get_page_permalink('myaccount')
  ));
}
add_action('wp_ajax_nopriv_custom_register', 'universal_custom_register_handler');

// === KONIEC FUNCTIONS.PHP ===
// Wszystkie funkcje template checkout zostały usunięte - powrót do Storefront + CSS hooks
