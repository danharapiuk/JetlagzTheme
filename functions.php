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

// ACF Options Pages dla Template Parts
require_once THEME_DIR . '/inc/acf-options-pages.php';

// ACF Product Fields (Stara nazwa, etc.)
require_once THEME_DIR . '/inc/acf-product-fields.php';

// Template Parts Helper Functions
require_once THEME_DIR . '/inc/template-parts-helper.php';

/**
 * Fix dla błędu tpay-woocommerce plugin
 * Naprawia TypeError: array_key_exists(): Argument #2 ($array) must be of type array, false given
 */
function jetlagz_fix_tpay_error()
{
  // Usuwamy hook tpay jeśli powoduje błędy
  if (function_exists('tpay_add_checkout_fee_for_gateway')) {
    remove_action('woocommerce_cart_calculate_fees', 'tpay_add_checkout_fee_for_gateway');
  }
}
add_action('init', 'jetlagz_fix_tpay_error', 5);

/**
 * Safe ACF get_field wrapper function
 * Returns empty string/array if ACF is not active
 */
function safe_get_field($selector, $post_id = false)
{
  if (!function_exists('get_field')) {
    return '';
  }
  return get_field($selector, $post_id);
}

/**
 * Safe ACF have_rows wrapper function
 * Returns false if ACF is not active
 */
function safe_have_rows($selector, $post_id = false)
{
  if (!function_exists('have_rows')) {
    return false;
  }
  return have_rows($selector, $post_id);
}

/**
 * Safe ACF get_sub_field wrapper function
 * Returns empty string if ACF is not active
 */
function safe_get_sub_field($selector)
{
  if (!function_exists('get_sub_field')) {
    return '';
  }
  return get_sub_field($selector);
}

/**
 * Safe ACF the_row wrapper function
 * Does nothing if ACF is not active
 */
function safe_the_row()
{
  if (function_exists('the_row')) {
    the_row();
  }
}

/**
 * Zezwól na upload plików SVG do biblioteki mediów
 */
function jetlagz_allow_svg_upload($mimes)
{
  $mimes['svg'] = 'image/svg+xml';
  $mimes['svgz'] = 'image/svg+xml';
  return $mimes;
}
add_filter('upload_mimes', 'jetlagz_allow_svg_upload');

/**
 * Napraw wyświetlanie SVG w bibliotece mediów
 */
function jetlagz_fix_svg_display()
{
  echo '<style>
    .attachment-266x266, .thumbnail img {
      width: 100% !important;
      height: auto !important;
    }
  </style>';
}
add_action('admin_head', 'jetlagz_fix_svg_display');

/**
 * Dodaj wymiary do SVG podczas uploadu
 */
function jetlagz_fix_svg_metadata($data, $file, $filename, $mimes)
{
  $ext = pathinfo($filename, PATHINFO_EXTENSION);

  if ($ext === 'svg' || $ext === 'svgz') {
    $data['type'] = 'image/svg+xml';
    $data['ext'] = 'svg';

    // Spróbuj pobrać wymiary z SVG
    if (file_exists($file)) {
      $svg = file_get_contents($file);
      if (preg_match('/width="([^"]+)"/', $svg, $width_match)) {
        $data['width'] = intval($width_match[1]);
      } else {
        $data['width'] = 150;
      }
      if (preg_match('/height="([^"]+)"/', $svg, $height_match)) {
        $data['height'] = intval($height_match[1]);
      } else {
        $data['height'] = 150;
      }
    }
  }

  return $data;
}
add_filter('wp_check_filetype_and_ext', 'jetlagz_fix_svg_metadata', 10, 4);

/**
 * Sanityzacja SVG podczas uploadu (bezpieczeństwo)
 */
function jetlagz_sanitize_svg($file)
{
  if ($file['type'] === 'image/svg+xml') {
    $contents = file_get_contents($file['tmp_name']);

    // Usuń potencjalnie niebezpieczne tagi
    $contents = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $contents);
    $contents = preg_replace('/on\w+="[^"]*"/i', '', $contents);

    file_put_contents($file['tmp_name'], $contents);
  }

  return $file;
}
add_filter('wp_handle_upload_prefilter', 'jetlagz_sanitize_svg');

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
 * Mark reviews added by admin with metadata
 */
add_action('comment_post', function ($comment_id) {
  if (current_user_can('manage_options')) {
    add_comment_meta($comment_id, 'review_source', 'admin_seed', true);
  }
});

/**
 * Hide review form on product pages but allow displaying existing reviews
 * This only hides the form, not the reviews list
 */
add_filter('woocommerce_product_review_comment_form_args', function ($comment_form) {
  // Return empty array to prevent form from displaying
  // But comments_open() will still return true so reviews can be displayed
  return array();
}, 99);

/**
 * ========================================
 * ADMIN RATING FIELD FOR PRODUCT REVIEWS
 * ========================================
 */

/**
 * Add rating metabox to comment edit screen in admin
 */
add_action('add_meta_boxes_comment', 'jetlagz_add_comment_rating_metabox');
function jetlagz_add_comment_rating_metabox()
{
  add_meta_box(
    'rating',
    __('Ocena produktu', 'jetlagz-theme'),
    'jetlagz_comment_rating_metabox_callback',
    'comment',
    'normal',
    'high'
  );
}

/**
 * Render rating metabox content
 */
function jetlagz_comment_rating_metabox_callback($comment)
{
  $rating = get_comment_meta($comment->comment_ID, 'rating', true);
  wp_nonce_field('update_comment_rating', 'update_comment_rating_nonce', false);
?>
  <p>
    <label for="rating"><?php _e('Ocena (1-5 gwiazdek):', 'jetlagz-theme'); ?></label>
    <select name="rating" id="rating" style="width: 100%; padding: 5px;">
      <option value=""><?php _e('Brak oceny', 'jetlagz-theme'); ?></option>
      <?php for ($i = 1; $i <= 5; $i++) : ?>
        <option value="<?php echo $i; ?>" <?php selected($rating, $i); ?>>
          <?php echo str_repeat('⭐', $i) . ' (' . $i . ' ' . ($i === 1 ? 'gwiazdka' : ($i < 5 ? 'gwiazdki' : 'gwiazdek')) . ')'; ?>
        </option>
      <?php endfor; ?>
    </select>
  </p>
  <p class="description">
    <?php _e('Wybierz ocenę od 1 do 5 gwiazdek dla tej opinii o produkcie.', 'jetlagz-theme'); ?>
  </p>
<?php
}

/**
 * Save rating when comment is updated in admin
 */
add_action('edit_comment', 'jetlagz_save_comment_rating');
function jetlagz_save_comment_rating($comment_id)
{
  if (
    !isset($_POST['update_comment_rating_nonce']) ||
    !wp_verify_nonce($_POST['update_comment_rating_nonce'], 'update_comment_rating')
  ) {
    return;
  }

  if (isset($_POST['rating']) && $_POST['rating'] !== '') {
    $rating = intval($_POST['rating']);
    if ($rating >= 1 && $rating <= 5) {
      update_comment_meta($comment_id, 'rating', $rating);
    }
  } else {
    delete_comment_meta($comment_id, 'rating');
  }
}

/**
 * Wyłącz sticky add-to-cart Storefront
 */
add_action('wp', function () {
  remove_action('storefront_after_header', 'storefront_sticky_single_add_to_cart', 999);
}, 99);

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


  // Universal Product Card Styles (używane wszędzie: sliders, shop, archive)
  wp_enqueue_style(
    'universal-product-card',
    get_stylesheet_directory_uri() . '/assets/css/components/product-card.css',
    array('universal-theme-custom'),
    $theme_version
  );

  // Universal Footer Styles (site-wide)
  wp_enqueue_style(
    'universal-footer',
    get_stylesheet_directory_uri() . '/assets/css/components/footer.css',
    array('universal-product-card'),
    $theme_version
  );

  // Mobile Bottom Navigation Bar
  wp_enqueue_style(
    'mobile-bottom-bar',
    get_stylesheet_directory_uri() . '/assets/css/components/mobile-bottom-bar.css',
    array('universal-footer'),
    $theme_version
  );

  // Tailwind CSS
  wp_enqueue_style(
    'universal-theme-tailwind',
    get_stylesheet_directory_uri() . '/assets/css/tailwind-compiled.css',
    array('universal-product-card'),
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
      $theme_version . '-qty-classic-v4', // Force cache refresh
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

    // Checkout enhanced - disabled (file moved to backup)
    /*
    wp_enqueue_script(
      'universal-checkout-enhanced',
      get_stylesheet_directory_uri() . '/assets/js/checkout-enhanced.js',
      array('jquery', 'wc-checkout'),
      $theme_version,
      true
    );
    */

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

  // Wishlist AJAX configuration
  wp_localize_script('universal-theme-script', 'universalThemeAjax', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('wishlist_toggle_nonce')
  ));

  // Universal sliders styles - loaded on all pages that use product sliders
  $sliders_css_path = get_stylesheet_directory() . '/assets/css/sliders.css';
  wp_enqueue_style(
    'universal-sliders-styles',
    get_stylesheet_directory_uri() . '/assets/css/sliders.css',
    array('storefront-style'),
    filemtime($sliders_css_path) . '.' . substr(md5_file($sliders_css_path), 0, 8)
  );
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
      array('storefront-style'),
      $theme_version . '-v3' // Cache refresh
    );
  }

  // Cart page styles
  if (is_cart()) {
    wp_enqueue_style(
      'universal-cart-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/cart.css',
      array('storefront-style'),
      $theme_version . '-v3'
    );
  }

  // Single product page styles
  if (is_product()) {
    // Swiper CSS
    wp_enqueue_style(
      'swiper-css',
      get_stylesheet_directory_uri() . '/assets/css/swiper-bundle.min.css',
      array(),
      '11.0.0'
    );

    wp_enqueue_style(
      'universal-product-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/product.css',
      array('storefront-style'),
      filemtime(get_stylesheet_directory() . '/assets/css/pages/product.css')
    );

    // Swiper JS
    wp_enqueue_script(
      'swiper-js',
      get_stylesheet_directory_uri() . '/assets/js/swiper-bundle.min.js',
      array(),
      '11.0.0',
      true
    );
  }

  // Shop & archive pages styles
  if (is_shop() || is_product_category() || is_product_tag()) {
    wp_enqueue_style(
      'universal-shop-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/shop.css',
      array('storefront-style'),
      $theme_version . '-v3'
    );

    // Shop filters JavaScript
    wp_enqueue_script(
      'universal-shop-filters',
      get_stylesheet_directory_uri() . '/assets/js/shop-filters.js',
      array('jquery'),
      $theme_version . '-v6', // Cache bust
      true
    );
  }

  // Login page styles (My Account when not logged in)
  if (is_account_page() && !is_user_logged_in()) {
    wp_enqueue_style(
      'universal-login-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/login.css',
      array('storefront-style'),
      $theme_version . '-v1'
    );
  }

  // My Account page styles (when logged in)
  if (is_account_page() && is_user_logged_in()) {
    wp_enqueue_style(
      'universal-myaccount-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/myaccount.css',
      array('storefront-style'),
      $theme_version . '-v1'
    );
  }

  // Thank You page styles
  if (is_order_received_page() || isset($_GET['preview_thankyou'])) {
    wp_enqueue_style(
      'universal-thankyou-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/thankyou.css',
      array('storefront-style'),
      $theme_version . '-v1'
    );
  }

  // Order Pay page styles
  if (is_checkout_pay_page()) {
    wp_enqueue_style(
      'universal-order-pay-page-styles',
      get_stylesheet_directory_uri() . '/assets/css/pages/order-pay.css',
      array('storefront-style'),
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

  // Wysokiej jakości rozmiary obrazków produktów
  add_image_size('product_thumbnail_hq', 600, 800, true); // Wysoka jakość dla kart produktów (3:4 ratio)
  add_image_size('product_single_hq', 1200, 1600, true);  // Bardzo wysoka jakość dla strony produktu
}
add_action('after_setup_theme', 'universal_woocommerce_support');

// Zwiększ domyślne rozmiary WooCommerce
add_filter('woocommerce_get_image_size_gallery_thumbnail', function ($size) {
  return array(
    'width'  => 300,
    'height' => 400,
    'crop'   => 1,
  );
});

add_filter('woocommerce_get_image_size_single', function ($size) {
  return array(
    'width'  => 1200,
    'height' => 1600,
    'crop'   => 1,
  );
});

add_filter('woocommerce_get_image_size_thumbnail', function ($size) {
  return array(
    'width'  => 600,
    'height' => 800,
    'crop'   => 1,
  );
});

// Zwiększ jakość kompresji JPEG
add_filter('jpeg_quality', function ($quality) {
  return 90; // Domyślnie WordPress używa 82%
});

add_filter('wp_editor_set_quality', function ($quality) {
  return 90;
});

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
    'dpd'           => get_stylesheet_directory_uri() . '/assets/images/shipping/dpd.png',
    'apaczka'       => get_stylesheet_directory_uri() . '/assets/images/shipping/apaczka.png',
    'free_shipping' => get_stylesheet_directory_uri() . '/assets/images/shipping/free-shipping.png',
    'local_pickup'  => get_stylesheet_directory_uri() . '/assets/images/shipping/pickup.png',
    'flat_rate'     => get_stylesheet_directory_uri() . '/assets/images/shipping/flat-rate.png',
  );

  // Słowa kluczowe, dla których logo jest ZA nazwą (nie przed)
  $logo_after = array('dpd');

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
        $logo_html = '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($method->get_label()) . '" class="shipping-method-logo" style="width: 40px; height: 40px; object-fit: contain; margin-right: 10px; margin-left: 10px; vertical-align: middle;">';
        if (in_array($keyword, $logo_after)) {
          $label = $label . $logo_html;
        } else {
          $label = $logo_html . $label;
        }
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
require_once THEME_DIR . '/inc/checkout-custom-fields.php';
// Disabled: checkout crosssell functions moved to backup (not used).
// require_once THEME_DIR . '/inc/checkout-crosssell-functions.php';
// Disabled: checkout blocks / layout includes (aggressive cleanup). Backups kept.
// require_once THEME_DIR . '/inc/checkout-blocks-functions.php';
// Disabled: test crosssell endpoint moved to backup (not used).
// require_once THEME_DIR . '/inc/simple-crosssell-test.php';
require_once THEME_DIR . '/inc/admin-panel.php';
require_once THEME_DIR . '/inc/header-functions.php';
require_once THEME_DIR . '/inc/checkout-remove-products.php';
require_once THEME_DIR . '/inc/slide-in-cart.php';
require_once THEME_DIR . '/inc/gift-products.php';
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
    <div class="woocommerce-info checkout-login-notice text-white" style="margin-bottom: 20px;">
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

// Polskie tłumaczenie tekstu polityki prywatności na checkout
add_filter('woocommerce_get_privacy_policy_text', function ($text, $type) {
  if ($type === 'checkout') {
    $privacy_page_id = get_option('wp_page_for_privacy_policy', 0);
    $privacy_link    = $privacy_page_id ? '<a href="' . esc_url(get_permalink($privacy_page_id)) . '" class="woocommerce-privacy-policy-link" target="_blank">politykę prywatności</a>' : 'politykę prywatności';
    return 'Twoje dane osobowe będą wykorzystywane do obsługi zamówienia, wsparcia Twojego doświadczenia na tej stronie oraz do innych celów opisanych w naszej ' . $privacy_link . '.';
  }
  return $text;
}, 10, 2);

// AJAX Handlers for Cart Quantity Updates
function universal_update_cart_quantity()
{
  // Check nonce for security
  if (!wp_verify_nonce($_POST['nonce'], 'universal_cart_nonce')) {
    wp_die('Unauthorized');
  }

  $cart_item_key = isset($_POST['cart_item_key']) ? sanitize_text_field($_POST['cart_item_key']) : '';
  $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
  $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
  $product_name = isset($_POST['product_name']) ? sanitize_text_field($_POST['product_name']) : '';
  $item_index = isset($_POST['item_index']) ? intval($_POST['item_index']) : 0;
  $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

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
    $message = 'Ilość zaktualizowana: ' . $quantity;
    universal_debug_log("Universal Cart: Setting quantity {$quantity} for item {$cart_item_key}, result: " . ($result ? 'success' : 'failed'));
  }

  if ($result !== false) {
    // Clear cached shipping rates so WC recalculates available methods
    // (important when cart total crosses free shipping threshold)
    $packages = WC()->cart->get_shipping_packages();
    foreach (array_keys($packages) as $package_key) {
      WC()->session->set('shipping_for_package_' . $package_key, false);
    }

    // Recalculate totals (will also recalculate shipping with fresh cache)
    WC()->cart->calculate_totals();
    WC()->cart->calculate_shipping();

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

function universal_collect_wc_notice_messages()
{
  $notices_html = wc_print_notices(true);

  if (!is_string($notices_html) || trim($notices_html) === '') {
    return array();
  }

  $message = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($notices_html)));

  return $message !== '' ? array($message) : array();
}

function universal_get_wc_notice_text_from_html($notices_html)
{
  if (!is_string($notices_html) || trim($notices_html) === '') {
    return '';
  }

  return trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($notices_html)));
}

function universal_apply_checkout_coupon()
{
  if (!wp_verify_nonce($_POST['nonce'], 'universal_cart_nonce')) {
    wp_send_json_error(array(
      'message' => __('Unauthorized', 'jetlagz-theme'),
    ), 403);
  }

  if (!function_exists('WC') || !WC()->cart) {
    wp_send_json_error(array(
      'message' => __('Koszyk nie jest dostępny.', 'jetlagz-theme'),
    ), 500);
  }

  $coupon_code = isset($_POST['coupon_code']) ? wc_format_coupon_code(wc_clean(wp_unslash($_POST['coupon_code']))) : '';

  if ($coupon_code === '') {
    wp_send_json_error(array(
      'message' => __('Wpisz kod kuponu', 'jetlagz-theme'),
    ), 400);
  }

  $debug_payload = function_exists('jetlagz_get_coupon_debug_payload')
    ? jetlagz_get_coupon_debug_payload($coupon_code)
    : array(
      'requested_coupon_code' => $coupon_code,
      'applied_coupons' => array_values((array) WC()->cart->get_applied_coupons()),
      'cart_discount_total' => (float) WC()->cart->get_discount_total() + (float) WC()->cart->get_discount_tax(),
      'cart_total' => wp_strip_all_tags((string) WC()->cart->get_cart_total()),
      'cart_is_empty' => (bool) WC()->cart->is_empty(),
    );

  $applied_coupons = array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons());

  if (in_array($coupon_code, $applied_coupons, true)) {
    WC()->cart->calculate_totals();

    ob_start();
    universal_render_checkout_totals();
    $totals_html = ob_get_clean();

    wc_clear_notices();

    wp_send_json_success(array(
      'message' => sprintf(__('Kupon "%s" został już zastosowany.', 'jetlagz-theme'), $coupon_code),
      'coupon_code' => $coupon_code,
      'totals_html' => $totals_html,
      'cart_total' => WC()->cart->get_cart_total(),
      'already_applied' => true,
      'applied_coupons_html' => universal_get_checkout_applied_coupons_html(),
      'debug' => function_exists('jetlagz_get_coupon_debug_payload') ? jetlagz_get_coupon_debug_payload($coupon_code) : $debug_payload,
    ));
  }

  wc_clear_notices();

  $applied = WC()->cart->apply_coupon($coupon_code);
  WC()->cart->calculate_totals();

  $notices_html = wc_print_notices(true);
  $message = universal_get_wc_notice_text_from_html($notices_html);

  if ($message === '' || $message === 'coupon_code') {
    $messages = universal_collect_wc_notice_messages();
    $message = !empty($messages) ? implode(' ', $messages) : '';
  }

  ob_start();
  universal_render_checkout_totals();
  $totals_html = ob_get_clean();

  wc_clear_notices();

  if (!$applied) {
    wp_send_json_error(array(
      'message' => $message !== '' ? $message : __('Kod kuponu jest nieprawidłowy.', 'jetlagz-theme'),
      'notices_html' => $notices_html,
      'totals_html' => $totals_html,
      'applied_coupons_html' => universal_get_checkout_applied_coupons_html(),
      'debug' => function_exists('jetlagz_get_coupon_debug_payload') ? jetlagz_get_coupon_debug_payload($coupon_code) : $debug_payload,
    ), 400);
  }

  wp_send_json_success(array(
    'message' => $message !== '' ? $message : sprintf(__('Kupon "%s" został zastosowany.', 'jetlagz-theme'), $coupon_code),
    'coupon_code' => $coupon_code,
    'notices_html' => $notices_html,
    'totals_html' => $totals_html,
    'cart_total' => WC()->cart->get_cart_total(),
    'applied_coupons_html' => function_exists('universal_get_checkout_applied_coupons_html') ? universal_get_checkout_applied_coupons_html() : '',
    'debug' => function_exists('jetlagz_get_coupon_debug_payload') ? jetlagz_get_coupon_debug_payload($coupon_code) : $debug_payload,
  ));
}

function universal_get_checkout_applied_coupons_html()
{
  if (!function_exists('universal_render_checkout_applied_coupons')) {
    return '';
  }

  ob_start();
  universal_render_checkout_applied_coupons();

  return ob_get_clean();
}

function universal_remove_checkout_coupon()
{
  if (!wp_verify_nonce($_POST['nonce'] ?? '', 'universal_cart_nonce')) {
    wp_send_json_error(array(
      'message' => __('Unauthorized', 'jetlagz-theme'),
    ), 403);
  }

  if (!function_exists('WC') || !WC()->cart) {
    wp_send_json_error(array(
      'message' => __('Koszyk nie jest dostępny.', 'jetlagz-theme'),
    ), 500);
  }

  $coupon_code = isset($_POST['coupon_code']) ? wc_format_coupon_code(wc_clean(wp_unslash($_POST['coupon_code']))) : '';

  if ($coupon_code === '') {
    wp_send_json_error(array(
      'message' => __('Brak kodu kuponu do usunięcia.', 'jetlagz-theme'),
    ), 400);
  }

  $applied_coupons = array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons());

  if (!in_array($coupon_code, $applied_coupons, true)) {
    wp_send_json_error(array(
      'message' => __('Ten kupon nie jest aktualnie zastosowany.', 'jetlagz-theme'),
      'applied_coupons_html' => universal_get_checkout_applied_coupons_html(),
    ), 400);
  }

  WC()->cart->remove_coupon($coupon_code);
  WC()->cart->calculate_totals();
  wc_clear_notices();

  if (function_exists('jetlagz_get_selected_coupon_code') && function_exists('jetlagz_clear_selected_coupon_code')) {
    $selected_coupon_code = jetlagz_get_selected_coupon_code();

    if ($selected_coupon_code !== '' && wc_format_coupon_code($selected_coupon_code) === $coupon_code) {
      jetlagz_clear_selected_coupon_code();
    }
  }

  ob_start();
  universal_render_checkout_totals();
  $totals_html = ob_get_clean();

  wp_send_json_success(array(
    'message' => sprintf(__('Kupon "%s" został usunięty.', 'jetlagz-theme'), $coupon_code),
    'coupon_code' => $coupon_code,
    'totals_html' => $totals_html,
    'cart_total' => WC()->cart->get_cart_total(),
    'applied_coupons_html' => universal_get_checkout_applied_coupons_html(),
    'debug' => function_exists('jetlagz_get_coupon_debug_payload') ? jetlagz_get_coupon_debug_payload($coupon_code) : array(),
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
  $coupon_discount_total = (float) WC()->cart->get_discount_total() + (float) WC()->cart->get_discount_tax();
  $coupon_discount_formatted = wc_price($coupon_discount_total);

  // Pobierz shipping
  $shipping_total = WC()->cart->get_shipping_total();
  $shipping_formatted = wc_price($shipping_total);

  // Pobierz tax
  $tax_total = WC()->cart->get_total_tax();
  $tax_formatted = wc_price($tax_total);

  // Pobierz fees (np. pakowanie na prezent)
  $fees = WC()->cart->get_fees();
  $gift_wrapping_fee = null;
  $gift_wrapping_count = 0;

  foreach ($fees as $fee) {
    if (strpos($fee->name, '🎁') !== false) {
      $gift_wrapping_fee = $fee;
      // Extract count from name if exists (e.g., "x3")
      if (preg_match('/\(x(\d+)\)/', $fee->name, $matches)) {
        $gift_wrapping_count = intval($matches[1]);
      }
      break;
    }
  }

  ?>
  <div class="universal-checkout-totals">
    <table class="woocommerce-table--totals">
      <tbody>
        <!-- Sub total -->
        <tr class="cart-subtotal">
          <th><?php echo __('Wartość produktów:', 'jetlagz-theme'); ?></th>
          <td><?php echo wp_kses_post($subtotal); ?></td>
        </tr>

        <?php if ($coupon_discount_total > 0) : ?>
          <tr class="cart-coupons-total">
            <th><?php echo __('Wartość kuponów:', 'jetlagz-theme'); ?></th>
            <td>-<?php echo wp_kses_post($coupon_discount_formatted); ?></td>
          </tr>
        <?php endif; ?>

        <!-- Gift Wrapping Fee (if exists) -->
        <?php if ($gift_wrapping_fee) : ?>
          <tr class="gift-wrapping-fee">
            <th><?php echo esc_html($gift_wrapping_fee->name); ?></th>
            <td><?php echo wc_price($gift_wrapping_fee->amount); ?></td>
          </tr>
        <?php endif; ?>

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

function universal_get_checkout_cart_item_amount($cart_item, $amount_key, $tax_key)
{
  $amount = isset($cart_item[$amount_key]) ? (float) $cart_item[$amount_key] : 0.0;

  if (WC()->cart && WC()->cart->display_prices_including_tax()) {
    $amount += isset($cart_item[$tax_key]) ? (float) $cart_item[$tax_key] : 0.0;
  }

  return $amount;
}

function universal_get_gift_cart_item_price_data($cart_item)
{
  $quantity = max(1, (int) ($cart_item['quantity'] ?? 1));
  $product_id = (int) ($cart_item['product_id'] ?? 0);
  $variation_id = (int) ($cart_item['variation_id'] ?? 0);
  $gift_rule = isset($cart_item['jetlagz_gift_rule']) && is_array($cart_item['jetlagz_gift_rule'])
    ? $cart_item['jetlagz_gift_rule']
    : array();
  $source_product_id = $variation_id > 0 ? $variation_id : $product_id;
  $original_product = $source_product_id > 0 ? wc_get_product($source_product_id) : null;
  $regular_price = 0.0;

  if ($source_product_id > 0) {
    $raw_regular_price = get_post_meta($source_product_id, '_regular_price', true);
    if ($raw_regular_price !== '') {
      $regular_price = (float) $raw_regular_price;
    }
  }

  if ($regular_price <= 0 && $original_product instanceof WC_Product) {
    $regular_price = (float) $original_product->get_regular_price();
  }

  if ($regular_price <= 0 && $original_product instanceof WC_Product) {
    $regular_price = (float) $original_product->get_price();
  }

  $gift_price = isset($gift_rule['price']) ? (float) $gift_rule['price'] : 0.10;
  $base_unit_price = $regular_price > 0 ? $regular_price : $gift_price;
  $has_discount = $base_unit_price > $gift_price;

  return array(
    'has_discount' => $has_discount,
    'base_unit_price' => $base_unit_price,
    'discounted_unit_price' => $gift_price,
    'base_line_total' => $base_unit_price * $quantity,
    'discounted_line_total' => $gift_price * $quantity,
  );
}

function universal_get_checkout_cart_item_price_data($cart_item)
{
  if (!empty($cart_item['jetlagz_is_gift']) && !empty($cart_item['jetlagz_gift_rule'])) {
    return universal_get_gift_cart_item_price_data($cart_item);
  }

  $quantity = max(1, (int) ($cart_item['quantity'] ?? 1));
  $base_line_total = universal_get_checkout_cart_item_amount($cart_item, 'line_subtotal', 'line_subtotal_tax');
  $discounted_line_total = universal_get_checkout_cart_item_amount($cart_item, 'line_total', 'line_tax');
  $base_unit_price = $base_line_total / $quantity;
  $discounted_unit_price = $discounted_line_total / $quantity;
  $has_discount = $discounted_line_total + 0.0001 < $base_line_total;

  return array(
    'has_discount' => $has_discount,
    'base_unit_price' => $base_unit_price,
    'discounted_unit_price' => $has_discount ? $discounted_unit_price : $base_unit_price,
    'base_line_total' => $base_line_total,
    'discounted_line_total' => $has_discount ? $discounted_line_total : $base_line_total,
  );
}

function universal_render_checkout_price_html($regular_amount, $discounted_amount, $has_discount, $wrapper_class)
{
  $wrapper_class = trim((string) $wrapper_class);
  $wrapper_attr = $wrapper_class !== '' ? ' ' . $wrapper_class : '';

  if (!$has_discount) {
    return '<div class="' . esc_attr(trim('checkout-price-stack' . $wrapper_attr)) . '">' . wp_kses_post(wc_price($discounted_amount)) . '</div>';
  }

  return '<div class="' . esc_attr(trim('checkout-price-stack checkout-price-stack--discounted' . $wrapper_attr)) . '">'
    . '<span class="checkout-price-original">' . wp_kses_post(wc_price($regular_amount)) . '</span>'
    . '<span class="checkout-price-discounted">' . wp_kses_post(wc_price($discounted_amount)) . '</span>'
    . '</div>';
}

function universal_get_discount_percentage($regular_amount, $discounted_amount)
{
  $regular_amount = (float) $regular_amount;
  $discounted_amount = (float) $discounted_amount;

  if ($regular_amount <= 0 || $discounted_amount >= $regular_amount) {
    return 0;
  }

  return (int) round((($regular_amount - $discounted_amount) / $regular_amount) * 100);
}

add_action('wp_ajax_universal_get_checkout_totals', 'universal_get_checkout_totals');
add_action('wp_ajax_nopriv_universal_get_checkout_totals', 'universal_get_checkout_totals'); // Zarejestruj AJAX handler - action musi być 'universal_update_cart_quantity'
add_action('wp_ajax_universal_remove_checkout_coupon', 'universal_remove_checkout_coupon');
add_action('wp_ajax_nopriv_universal_remove_checkout_coupon', 'universal_remove_checkout_coupon');
add_action('wp_ajax_universal_apply_checkout_coupon', 'universal_apply_checkout_coupon');
add_action('wp_ajax_nopriv_universal_apply_checkout_coupon', 'universal_apply_checkout_coupon');
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
    echo '<div class="checkout-crosssell-section hidden"></div>';
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
          // Skip gift products
          if (function_exists('jetlagz_is_gift_source_product') && jetlagz_is_gift_source_product($csid)) {
            continue;
          }
          // avoid duplicates
          $existing_ids = array_column($products, 'id');
          if (in_array($csid, $existing_ids, true)) {
            continue;
          }
          $p = wc_get_product($csid);
          if ($p) {
            // Sprawdź czy produkt ma warianty (variable product)
            $has_variants = $p->get_type() === 'variable' && !empty($p->get_children());
            $gallery_image_ids = $p->get_gallery_image_ids();
            $secondary_image = !empty($gallery_image_ids)
              ? wp_get_attachment_image_url($gallery_image_ids[0], 'woocommerce_single')
              : '';
            $acf_name = function_exists('get_field') ? get_field('product_name', $p->get_id()) : '';
            $acf_description = function_exists('get_field') ? get_field('product_description', $p->get_id()) : '';

            $products[] = array(
              'id' => $p->get_id(),
              'name' => $p->get_name(),
              'acf_name' => is_string($acf_name) ? trim($acf_name) : '',
              'acf_description' => is_string($acf_description) ? trim($acf_description) : '',
              'price_formatted' => wc_price($p->get_price()),
              'image' => wp_get_attachment_image_url($p->get_image_id(), 'woocommerce_single'),
              'secondary_image' => $secondary_image,
              'permalink' => get_permalink($p->get_id()),
              'has_variants' => $has_variants,
              'is_on_sale' => $p->is_on_sale(),
              'rating_count' => (int) $p->get_rating_count(),
              'average_rating' => (float) $p->get_average_rating(),
            );
          }
        }
      }
    }
  }

  // Fallback: if no cross-sells, get recent products
  if (empty($products)) {
    $exclude_ids = function_exists('jetlagz_get_all_gift_product_ids') ? jetlagz_get_all_gift_product_ids() : array();
    $recent = wc_get_products(array(
      'limit' => 4,
      'orderby' => 'date',
      'order' => 'DESC',
      'status' => 'publish',
      'exclude' => $exclude_ids,
    ));
    foreach ($recent as $p) {
      $has_variants = $p->get_type() === 'variable' && !empty($p->get_children());
      $gallery_image_ids = $p->get_gallery_image_ids();
      $secondary_image = !empty($gallery_image_ids)
        ? wp_get_attachment_image_url($gallery_image_ids[0], 'woocommerce_single')
        : '';
      $acf_name = function_exists('get_field') ? get_field('product_name', $p->get_id()) : '';
      $acf_description = function_exists('get_field') ? get_field('product_description', $p->get_id()) : '';

      $products[] = array(
        'id' => $p->get_id(),
        'name' => $p->get_name(),
        'acf_name' => is_string($acf_name) ? trim($acf_name) : '',
        'acf_description' => is_string($acf_description) ? trim($acf_description) : '',
        'price_formatted' => wc_price($p->get_price()),
        'image' => wp_get_attachment_image_url($p->get_image_id(), 'woocommerce_single'),
        'secondary_image' => $secondary_image,
        'permalink' => get_permalink($p->get_id()),
        'has_variants' => $has_variants,
        'is_on_sale' => $p->is_on_sale(),
        'rating_count' => (int) $p->get_rating_count(),
        'average_rating' => (float) $p->get_average_rating(),
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
  if (function_exists('WC') && WC()->cart) {
    WC()->cart->calculate_totals();
  }

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
        $acf_product_name = '';
        if (function_exists('get_field')) {
          $acf_product_name = get_field('product_name', $product_id);

          if ((!is_string($acf_product_name) || trim($acf_product_name) === '') && $product) {
            $runtime_product_id = $product->get_id();
            $acf_product_name = get_field('product_name', $runtime_product_id);
          }

          if ((!is_string($acf_product_name) || trim($acf_product_name) === '') && $product && method_exists($product, 'get_parent_id')) {
            $parent_id = (int) $product->get_parent_id();
            if ($parent_id > 0) {
              $acf_product_name = get_field('product_name', $parent_id);
            }
          }
        }
        $product_name = is_string($acf_product_name) && trim($acf_product_name) !== ''
          ? trim($acf_product_name)
          : $product->get_name();
        $product_image = $product->get_image('thumbnail');
        $price_data = universal_get_checkout_cart_item_price_data($cart_item);
        $product_price = $price_data['discounted_unit_price'];
        $product_total = $price_data['discounted_line_total'];
        $is_gift = !empty($cart_item['jetlagz_is_gift']);
        $discount_percentage = universal_get_discount_percentage(
          $price_data['base_unit_price'],
          $price_data['discounted_unit_price']
        );
        $price_formatted = universal_render_checkout_price_html(
          $price_data['base_unit_price'],
          $price_data['discounted_unit_price'],
          $price_data['has_discount'],
          'checkout-item-unit-price'
        );
        $total_formatted = universal_render_checkout_price_html(
          $price_data['base_line_total'],
          $price_data['discounted_line_total'],
          $price_data['has_discount'],
          'checkout-item-total-price'
        );
      ?>
        <div class="universal-checkout-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
          <!-- Lewa część: Miniaturka + Nazwa + Cena jednostkowa -->
          <div class="checkout-item-left">
            <div class="checkout-item-thumbnail relative">
              <div class="cart-item-image">
                <?php if ($discount_percentage > 0) : ?>
                  <span class="cart-item-discount-badge absolute right-1 top-1">-<?php echo esc_html($discount_percentage); ?>%</span>
                <?php endif; ?>
                <?php echo $product_image; ?>
              </div>
              <button type="button" class="checkout-item-remove-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Usuń z koszyka', 'universal-theme'); ?>">×</button>
            </div>
            <div class="checkout-item-details">
              <div class="checkout-item-name">
                <?php if ($is_gift) : ?><span class="gift-badge">🎁 PREZENT</span> <?php endif; ?>
                <?php echo esc_html($product_name); ?>
              </div>
              <div class="checkout-item-unit-price" data-unit-price="<?php echo esc_attr($product_price); ?>">
                <?php echo wp_kses_post($price_formatted); ?>
              </div>
            </div>
          </div>

          <!-- Środek: Ilość +/- -->
          <div class="checkout-item-quantity-wrapper">
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

/**
 * Temporary preview function for Thank You page
 * Access: 
 * - Paid logged in: https://localhost:10109/?preview_thankyou=1&force_status=paid
 * - Paid guest: https://localhost:10109/?preview_thankyou=1&force_status=paid&force_guest=1
 * - Unpaid version: https://localhost:10109/?preview_thankyou=1&force_status=unpaid
 * - Actual status: https://localhost:10109/?preview_thankyou=1
 */
function preview_thankyou_page()
{
  if (isset($_GET['preview_thankyou']) && current_user_can('manage_options')) {
    // Get latest order or create fake order
    $orders = wc_get_orders(array('limit' => 1, 'orderby' => 'date', 'order' => 'DESC'));

    if (!empty($orders)) {
      $order = $orders[0];

      // Check if we should force guest mode (simulate logged out user)
      $force_guest = isset($_GET['force_guest']) && $_GET['force_guest'] == '1';

      if ($force_guest) {
        // Set global variable to override logged in check
        global $preview_force_guest;
        $preview_force_guest = true;
      }

      // Check if we should force a specific status for preview
      if (isset($_GET['force_status'])) {
        $force_status = sanitize_text_field($_GET['force_status']);

        // Create a custom order object wrapper to override status methods
        if ($force_status === 'paid') {
          // Override the methods to simulate a paid order
          add_filter('woocommerce_order_needs_payment', '__return_false', 999);
          add_filter('woocommerce_order_has_status', function ($has_status, $order_obj, $status) use ($order) {
            if ($order_obj->get_id() === $order->get_id() && $status === 'failed') {
              return false;
            }
            return $has_status;
          }, 999, 3);
        } elseif ($force_status === 'unpaid') {
          // Override to simulate an unpaid order
          add_filter('woocommerce_order_needs_payment', '__return_true', 999);
        }
      }
    } else {
      // Create a temporary fake order for preview
      wp_die('Brak zamówień. Najpierw złóż testowe zamówienie lub użyj: https://localhost:10109/checkout/order-received/ID/?key=ORDER_KEY');
    }

    get_header();
    wc_get_template('checkout/thankyou.php', array('order' => $order));
    get_footer();
    exit;
  }
}
add_action('template_redirect', 'preview_thankyou_page');

/**
 * Automatyczne tworzenie konta z danymi z zamówienia
 */
function create_account_from_order_handler()
{
  // Sprawdź nonce
  if (!isset($_POST['account_nonce']) || !wp_verify_nonce($_POST['account_nonce'], 'create_account_from_order')) {
    wp_die('Błąd bezpieczeństwa. Spróbuj ponownie.');
  }

  // Sprawdź czy order_id został przekazany
  if (!isset($_POST['order_id'])) {
    wp_die('Brak ID zamówienia.');
  }

  $order_id = intval($_POST['order_id']);
  $order = wc_get_order($order_id);

  if (!$order) {
    wp_die('Nie znaleziono zamówienia.');
  }

  // Pobierz dane z zamówienia
  $email = $order->get_billing_email();
  $first_name = $order->get_billing_first_name();
  $last_name = $order->get_billing_last_name();

  // Sprawdź czy użytkownik z tym emailem już istnieje
  if (email_exists($email)) {
    wp_redirect(add_query_arg('account_error', 'exists', $order->get_checkout_order_received_url()));
    exit;
  }

  // Wygeneruj losowe hasło
  $password = wp_generate_password(12, false);

  // Utwórz użytkownika
  $user_id = wc_create_new_customer($email, '', $password, array(
    'first_name' => $first_name,
    'last_name' => $last_name,
  ));

  if (is_wp_error($user_id)) {
    wp_redirect(add_query_arg('account_error', 'failed', $order->get_checkout_order_received_url()));
    exit;
  }

  // Przypisz zamówienie do nowo utworzonego użytkownika
  $order->set_customer_id($user_id);
  $order->save();

  // Skopiuj dane adresowe do konta użytkownika
  update_user_meta($user_id, 'billing_first_name', $order->get_billing_first_name());
  update_user_meta($user_id, 'billing_last_name', $order->get_billing_last_name());
  update_user_meta($user_id, 'billing_company', $order->get_billing_company());
  update_user_meta($user_id, 'billing_address_1', $order->get_billing_address_1());
  update_user_meta($user_id, 'billing_address_2', $order->get_billing_address_2());
  update_user_meta($user_id, 'billing_city', $order->get_billing_city());
  update_user_meta($user_id, 'billing_postcode', $order->get_billing_postcode());
  update_user_meta($user_id, 'billing_country', $order->get_billing_country());
  update_user_meta($user_id, 'billing_state', $order->get_billing_state());
  update_user_meta($user_id, 'billing_phone', $order->get_billing_phone());

  if ($order->has_shipping_address()) {
    update_user_meta($user_id, 'shipping_first_name', $order->get_shipping_first_name());
    update_user_meta($user_id, 'shipping_last_name', $order->get_shipping_last_name());
    update_user_meta($user_id, 'shipping_company', $order->get_shipping_company());
    update_user_meta($user_id, 'shipping_address_1', $order->get_shipping_address_1());
    update_user_meta($user_id, 'shipping_address_2', $order->get_shipping_address_2());
    update_user_meta($user_id, 'shipping_city', $order->get_shipping_city());
    update_user_meta($user_id, 'shipping_postcode', $order->get_shipping_postcode());
    update_user_meta($user_id, 'shipping_country', $order->get_shipping_country());
    update_user_meta($user_id, 'shipping_state', $order->get_shipping_state());
  }

  // Zaloguj użytkownika automatycznie
  wp_set_current_user($user_id);
  wp_set_auth_cookie($user_id);

  // Wyślij email z danymi do logowania
  wc_send_new_customer_email($user_id, $password);

  // WPLoyalty automatycznie przypisze punkty gdy zamówienie jest przypisane do użytkownika
  // Możesz też ręcznie wywołać akcję jeśli potrzeba
  do_action('woocommerce_order_status_completed', $order_id);

  // Przekieruj do strony potwierdzenia z informacją o sukcesie
  wp_redirect(add_query_arg('account_created', '1', $order->get_checkout_order_received_url()));
  exit;
}
add_action('admin_post_create_account_from_order', 'create_account_from_order_handler');
add_action('admin_post_nopriv_create_account_from_order', 'create_account_from_order_handler');

/**
 * Przypisz zamówienie do użytkownika po zalogowaniu
 * Jeśli użytkownik loguje się z linku w thank you page i email się zgadza
 */
function assign_order_after_login($user_login, $user)
{
  // Sprawdź czy mamy order_id w parametrach
  if (isset($_REQUEST['order_id'])) {
    $order_id = intval($_REQUEST['order_id']);
    $order = wc_get_order($order_id);

    if ($order && $order->get_billing_email() === $user->user_email) {
      // Email się zgadza - przypisz zamówienie do użytkownika
      $order->set_customer_id($user->ID);
      $order->save();

      // WPLoyalty automatycznie przeliczy punkty
      do_action('woocommerce_order_status_completed', $order_id);
    }
  }
}
add_action('wp_login', 'assign_order_after_login', 10, 2);

/**
 * Dodaj przycisk Wishlist obok przycisku "Dodaj do koszyka" (tylko na stronie produktu)
 */
function add_wishlist_button_after_add_to_cart()
{
  echo do_shortcode('[yith_wcwl_add_to_wishlist]');
}
// Na stronie pojedynczego produktu
add_action('woocommerce_after_add_to_cart_button', 'add_wishlist_button_after_add_to_cart', 10);
// Usunięto hook dla shop loop - teraz używamy serduszka na zdjęciu produktu

/**
 * AJAX handler for wishlist toggle (add/remove)
 */
function toggle_wishlist_product_ajax()
{
  // Verify nonce - accept both possible nonces
  $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
  $valid_nonce = wp_verify_nonce($nonce, 'wishlist_toggle_nonce') || wp_verify_nonce($nonce, 'theme_nonce');

  if (!$valid_nonce) {
    wp_send_json_error(array('message' => 'Błąd bezpieczeństwa', 'debug' => 'nonce_invalid'));
  }

  $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
  $remove = isset($_POST['remove']) ? (bool) $_POST['remove'] : false;

  if (!$product_id) {
    wp_send_json_error(array('message' => 'Brak ID produktu'));
  }

  // Check if YITH Wishlist is active
  if (!function_exists('YITH_WCWL')) {
    wp_send_json_error(array('message' => 'Wishlist plugin nie jest aktywny'));
  }

  $wishlist = YITH_WCWL();

  if ($remove) {
    // Remove from wishlist - try multiple methods
    $removed = false;

    // Method 1: Get default wishlist and remove
    if (class_exists('YITH_WCWL_Wishlist_Factory')) {
      $default_wishlist = YITH_WCWL_Wishlist_Factory::get_default_wishlist();
      if ($default_wishlist) {
        $wishlist_id = $default_wishlist->get_id();
        $removed = $wishlist->remove(array(
          'product_id' => $product_id,
          'wishlist_id' => $wishlist_id
        ));
      }
    }

    // Method 2: Try with user_id for logged in users
    if (!$removed && is_user_logged_in()) {
      $user_id = get_current_user_id();
      $removed = $wishlist->remove(array(
        'product_id' => $product_id,
        'user_id' => $user_id
      ));
    }

    // Method 3: Simple remove without extra params
    if (!$removed) {
      $removed = $wishlist->remove(array('product_id' => $product_id));
    }

    // Final check - if product is no longer in wishlist, consider it removed
    if ($removed || !$wishlist->is_product_in_wishlist($product_id)) {
      $count = $wishlist->count_products();
      wp_send_json_success(array('action' => 'removed', 'count' => $count));
    } else {
      wp_send_json_error(array('message' => 'Nie udało się usunąć z ulubionych'));
    }
  } else {
    // Add to wishlist
    $result = $wishlist->add(array('product_id' => $product_id));
    if ($result) {
      $count = $wishlist->count_products();
      wp_send_json_success(array('action' => 'added', 'count' => $count));
    } else {
      // Maybe already in wishlist
      if ($wishlist->is_product_in_wishlist($product_id)) {
        $count = $wishlist->count_products();
        wp_send_json_success(array('action' => 'added', 'note' => 'already_in_wishlist', 'count' => $count));
      }
      wp_send_json_error(array('message' => 'Nie udało się dodać do ulubionych'));
    }
  }
}
add_action('wp_ajax_toggle_wishlist_product', 'toggle_wishlist_product_ajax');
add_action('wp_ajax_nopriv_toggle_wishlist_product', 'toggle_wishlist_product_ajax');


/**
 * Nadpisanie głównego zdjęcia w CTX Feed Pro zdjęciem z pola ACF 'zjecie_do_reklam'
 */
add_filter('woo_feed_filter_product_image_link', 'custom_acf_image_for_meta_ads', 10, 2);

function custom_acf_image_for_meta_ads($image_link, $product)
{
  // Sprawdzamy, czy produkt istnieje
  if (! $product) {
    return $image_link;
  }

  // Pobieramy ID produktu (obsługuje też warianty)
  $product_id = $product->get_id();

  // Pobieramy URL z Twojego pola ACF
  $custom_image_url = get_field('zjecie_do_reklam', $product_id);

  // Jeśli pole nie jest puste, zwracamy URL z ACF zamiast standardowego
  if (! empty($custom_image_url)) {
    return $custom_image_url;
  }

  // Jeśli pole ACF jest puste, wracamy do standardowego zdjęcia produktowego
  return $image_link;
}

/**
 * Tłumaczenia dla YITH Wishlist
 */
function custom_yith_wishlist_translations($translated_text, $text, $domain)
{
  if ($domain === 'yith-woocommerce-wishlist') {
    $translations = array(
      'Unit price' => 'Cena',
      'Product name' => 'Nazwa produktu',
      'Stock status' => 'Dostępność',
      'Stock Status' => 'Dostępność',
      'No products added to the wishlist' => 'Brak produktów na liście życzeń',
      'No products were added to the wishlist' => 'Nie dodano żadnych produktów do listy życzeń',
      'Add to cart' => 'Dodaj do koszyka',
      'Add to Cart' => 'Dodaj do koszyka',
      'Remove' => 'Usuń',
      'Add to Wishlist' => 'Dodaj do ulubionych',
      'Browse Wishlist' => 'Zobacz ulubione',
      'Product added!' => 'Produkt dodany!',
      'In stock' => 'Dostępny',
      'Out of stock' => 'Niedostępny',
      'Wishlist' => 'Ulubione',
      'My wishlist' => 'Moje ulubione',
      'View' => 'Zobacz',
      'Date added' => 'Data dodania',
      'Price' => 'Cena',
    );

    if (isset($translations[$text])) {
      return $translations[$text];
    }
  }

  return $translated_text;
}
add_filter('gettext', 'custom_yith_wishlist_translations', 20, 3);

// === KONIEC FUNCTIONS.PHP ===
// Wszystkie funkcje template checkout zostały usunięte - powrót do Storefront + CSS hooks
