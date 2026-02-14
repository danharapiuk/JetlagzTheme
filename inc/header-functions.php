<?php

/**
 * Header Functions - Modyfikacje header dla motywu Universal
 *
 * @package Universal_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modyfikuj layout header używając hooków Storefront
 */
function universal_theme_customize_header()
{
    // Usuń domyślne elementy Storefront header
    remove_action('storefront_header', 'storefront_site_branding', 20);
    remove_action('storefront_header', 'storefront_primary_navigation_wrapper', 42);
    remove_action('storefront_header', 'storefront_primary_navigation', 50);
    remove_action('storefront_header', 'storefront_primary_navigation_wrapper_close', 68);
    remove_action('storefront_header', 'storefront_header_cart', 60);

    // Usuń różne możliwe search hooki
    remove_action('storefront_header', 'storefront_product_search', 40);
    remove_action('storefront_header', 'storefront_search', 40);
    remove_action('storefront_header', 'storefront_site_search', 40);

    // Dodaj topbar przed headerem
    add_action('storefront_before_header', 'universal_theme_topbar', 5);

    // Dodaj custom header layout
    add_action('storefront_header', 'universal_theme_custom_header_content', 10);
}
add_action('init', 'universal_theme_customize_header');

/**
 * Top Bar - wyświetlany przed headerem
 */
function universal_theme_topbar()
{
?>
    <!-- Top Bar - tylko desktop -->
    <div class="universal-topbar">
        <div class="wrapper !py-0">
            <div class="topbar-content">
                <span class="topbar-item">Darmowa dostawa od 299 zł</span>
                <span class="topbar-item">Wysyłka w 24h!</span>
                <span class="topbar-item">Darmowy zwrot do 30 dni</span>
            </div>
        </div>
    </div>
<?php
}

/**
 * Custom header content - wszystkie elementy w jednym rzędzie
 */
function universal_theme_custom_header_content()
{
?>
    <div id="site-header" class="wrapper !py-0">
        <div class="universal-header-row">

            <!-- Logo/Branding -->
            <div class="universal-header-logo">
                <?php universal_theme_display_custom_logo(); ?>
            </div>

            <!-- Primary Navigation -->
            <div class="universal-header-navigation">
                <?php
                if (has_nav_menu('primary')) {
                    wp_nav_menu(array(
                        'theme_location' => 'primary',
                        'container' => false,
                        'menu_class' => 'universal-primary-menu',
                        'fallback_cb' => false,
                        'depth' => 4,
                    ));
                }
                ?>
            </div>

            <!-- Search -->
            <div class="universal-header-search">
                <?php if (class_exists('WooCommerce')) : ?>
                    <button class="search-toggle" aria-label="<?php echo esc_attr__('Szukaj', 'universal-theme'); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M21 21L16.514 16.506M19 10.5C19 15.194 15.194 19 10.5 19S2 15.194 2 10.5 5.806 2 10.5 2 19 5.806 19 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <form role="search" method="get" class="universal-search-form" action="<?php echo esc_url(home_url('/')); ?>">
                        <input type="search" class="search-field" placeholder="<?php echo esc_attr__('Szukaj produktów...', 'universal-theme'); ?>" value="<?php echo get_search_query(); ?>" name="s">
                        <input type="hidden" name="post_type" value="product">
                        <button type="submit" class="search-submit">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M21 21L16.514 16.506M19 10.5C19 15.194 15.194 19 10.5 19S2 15.194 2 10.5 5.806 2 10.5 2 19 5.806 19 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- My Account -->
            <div class="universal-header-account">
                <?php if (class_exists('WooCommerce')) : ?>
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('dashboard')); ?>" class="universal-account-link" title="<?php echo is_user_logged_in() ? esc_attr__('Moje konto', 'universal-theme') : esc_attr__('Zaloguj się', 'universal-theme'); ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Wishlist -->
            <div class="universal-header-wishlist">
                <?php if (class_exists('WooCommerce')) : ?>
                    <a href="<?php echo esc_url(home_url('/wishlist/')); ?>" class="universal-wishlist-link" title="<?php esc_attr_e('Ulubione', 'universal-theme'); ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Cart -->
            <div class="universal-header-cart">
                <?php if (class_exists('WooCommerce')) : ?>
                    <div class="universal-cart-dropdown">
                        <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="universal-cart-link">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.7 15.3C4.3 15.7 4.6 16.5 5.1 16.5H17M17 13V17C17 18.1 16.1 19 15 19H9C7.9 19 7 18.1 7 17V13M17 13H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
                            <span class="cart-total"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
                        </a>

                        <!-- Cart Dropdown Content -->
                        <div class="universal-cart-dropdown-content">
                            <?php if (!WC()->cart->is_empty()) : ?>
                                <div class="cart-items">
                                    <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                                        $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                                        $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                                        if ($_product && $_product->exists() && $cart_item['quantity'] > 0) :
                                            $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                                            $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                                    ?>
                                            <div class="cart-item">
                                                <div class="cart-item-image">
                                                    <?php echo $_product->get_image('woocommerce_gallery_thumbnail'); ?>
                                                </div>
                                                <div class="cart-item-details">
                                                    <h4 class="cart-item-title">
                                                        <?php if ($product_permalink) : ?>
                                                            <a href="<?php echo esc_url($product_permalink); ?>"><?php echo wp_kses_post($product_name); ?></a>
                                                        <?php else : ?>
                                                            <?php echo wp_kses_post($product_name); ?>
                                                        <?php endif; ?>
                                                    </h4>
                                                    <div class="cart-item-quantity">
                                                        <?php if (!empty($cart_item['jetlagz_is_gift'])) :
                                                            $orig_product = wc_get_product($product_id);
                                                            $regular_price = $orig_product ? $orig_product->get_regular_price() : 0;
                                                            if (empty($regular_price) && $orig_product) {
                                                                $regular_price = $orig_product->get_price();
                                                            }
                                                            $gift_price = floatval($cart_item['jetlagz_gift_rule']['price'] ?? 0.10);
                                                            if ($regular_price && floatval($regular_price) > $gift_price) : ?>
                                                                1 × <del class="gift-original-price"><?php echo wc_price($regular_price); ?></del> <ins class="gift-price"><?php echo wc_price($gift_price); ?></ins>
                                                            <?php else : ?>
                                                                1 × <?php echo wc_price($gift_price); ?>
                                                            <?php endif; ?>
                                                        <?php else : ?>
                                                            <?php echo sprintf('%s × %s', $cart_item['quantity'], WC()->cart->get_product_price($_product)); ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="cart-item-remove">
                                                    <a href="#" class="remove-item" data-cart_item_key="<?php echo esc_attr($cart_item_key); ?>" title="<?php esc_attr_e('Remove this item', 'woocommerce'); ?>">&times;</a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <div class="cart-dropdown-footer">
                                    <div class="cart-total-amount">
                                        <strong><?php esc_html_e('Suma: ', 'universal-theme');
                                                echo WC()->cart->get_cart_subtotal(); ?></strong>
                                    </div>
                                    <div class="cart-actions">
                                        <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="btn-checkout-single"><?php esc_html_e('Do kasy', 'universal-theme'); ?></a>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="cart-empty">
                                    <p><?php esc_html_e('Twój koszyk jest pusty.', 'universal-theme'); ?></p>
                                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn-continue-shopping">
                                        <?php esc_html_e('Kontynuuj zakupy', 'universal-theme'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <div class="universal-header-mobile-toggle">
                <button class="universal-mobile-menu-toggle" aria-label="<?php esc_attr_e('Toggle navigation', 'universal-theme'); ?>">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

        </div>
    </div>
<?php
}

/**
 * Display custom logo (używamy funkcję z admin-panel.php)
 * Ta funkcja już istnieje w admin-panel.php, więc tutaj tylko wywołujemy
 */
// function universal_theme_display_custom_logo() - już zdefiniowana w admin-panel.php

/**
 * Enqueue header styles
 */
function universal_theme_header_styles()
{
    wp_enqueue_style(
        'universal-header-styles',
        get_stylesheet_directory_uri() . '/assets/css/pages/header.css',
        array(),
        filemtime(get_stylesheet_directory() . '/assets/css/pages/header.css')
    );
}
add_action('wp_enqueue_scripts', 'universal_theme_header_styles');

/**
 * Global responsive container styles
 */
function universal_theme_responsive_container_styles()
{
?>
    <style>
        /* === RESPONSIVE CONTAINER SYSTEM === */
        /* System responsive paddingów dla głównych kontenerów Storefront */

        /* Globalny box-sizing dla wszystkich elementów */
        *,
        *:before,
        *:after {
            box-sizing: border-box;
        }

        /* Mobile First - 0 to 639px = 16px padding */
        .col-full {
            box-sizing: border-box !important;
            width: 100% !important;
        }


        /* Checkout strona specjalne ustawienia */
        .woocommerce-checkout .col-full,
        .woocommerce-page .col-full {
            max-width: 100% !important;
            overflow-x: hidden;
        }

        /* Ukryj przycisk Return to Cart na checkout - już nie potrzebny */
        .wc-block-components-checkout-return-to-cart-button,
        .wc-block-checkout-return-to-cart-button,
        .return-to-cart,
        .back-to-cart,
        a[href*="cart"]:not(.btn-checkout):not(.universal-cart-link) {
            display: none !important;
        }

        /* Ukryj także breadcrumbs z cart step jeśli istnieją */
        .woocommerce-breadcrumb a[href*="cart"],
        .checkout-breadcrumb .cart-step {
            display: none !important;
        }

        /* Formularze checkout - nie mogą być szersze niż container */
        .woocommerce-checkout .checkout,
        .woocommerce-checkout .woocommerce,
        .checkout-form,
        #customer_details,
        #order_review {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        /* Kolumny checkout responsive */
        .woocommerce-checkout .col2-set .col-1,
        .woocommerce-checkout .col2-set .col-2 {
            max-width: 100% !important;
            box-sizing: border-box !important;
        }

        /* Specjalne kontenery które nie powinny mieć dodatkowego paddingu */
        .woocommerce .product,
        .woocommerce-page .product,
        .product-summary,
        .woocommerce-tabs {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
    </style>
<?php
}
add_action('wp_head', 'universal_theme_responsive_container_styles', 5);

/**
 * Enqueue header JavaScript
 */
function universal_theme_header_scripts()
{
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mobileToggle = document.querySelector('.universal-mobile-menu-toggle');
            const navigation = document.querySelector('.universal-header-navigation');

            if (mobileToggle && navigation) {
                mobileToggle.addEventListener('click', function() {
                    navigation.classList.toggle('active');
                    this.classList.toggle('active');
                });
            }

            // Search toggle functionality
            const searchToggle = document.querySelector('.search-toggle');
            const searchForm = document.querySelector('.universal-search-form');
            const searchField = document.querySelector('.universal-search-form .search-field');

            if (searchToggle && searchForm && searchField) {
                searchToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    searchForm.classList.toggle('active');

                    if (searchForm.classList.contains('active')) {
                        setTimeout(() => searchField.focus(), 300);
                    }
                });

                // Close search when clicking outside
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.universal-header-search')) {
                        searchForm.classList.remove('active');
                    }
                });

                // Prevent closing when clicking inside the search
                searchForm.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            // Update cart count via AJAX
            if (typeof wc_cart_fragments_params !== 'undefined') {
                jQuery(document.body).on('added_to_cart removed_from_cart updated_wc_div', function() {
                    jQuery.ajax({
                        url: wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'),
                        type: 'POST',
                        success: function(data) {
                            if (data && data.fragments) {
                                const cartCount = jQuery(data.fragments['.cart-count']).text();
                                const cartTotal = jQuery(data.fragments['.cart-total']).html();

                                jQuery('.cart-count').text(cartCount);
                                jQuery('.cart-total').html(cartTotal);
                            }
                        }
                    });
                });
            }

            // Handle remove item from cart dropdown
            jQuery(document).on('click', '.universal-cart-dropdown-content .remove-item', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                const removeLink = jQuery(this);
                const cartItemKey = removeLink.data('cart_item_key');

                if (!cartItemKey) {
                    console.log('No cart item key found');
                    return;
                }

                console.log('Removing cart item:', cartItemKey);

                // Disable the button temporarily
                removeLink.css('pointer-events', 'none').css('opacity', '0.5');

                jQuery.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'remove_from_cart',
                        cart_item_key: cartItemKey
                    },
                    success: function(response) {
                        console.log('Remove response:', response);
                        if (response && response.success) {
                            // Refresh cart fragments to update all cart displays
                            jQuery.ajax({
                                url: wc_cart_fragments_params.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments'),
                                type: 'POST',
                                success: function(data) {
                                    console.log('Fragments refreshed:', data);
                                    if (data && data.fragments) {
                                        // Update all fragments
                                        jQuery.each(data.fragments, function(key, value) {
                                            jQuery(key).replaceWith(value);
                                        });

                                        // Trigger events
                                        jQuery(document.body).trigger('wc_fragments_refreshed');
                                        jQuery(document.body).trigger('removed_from_cart');
                                    }
                                }
                            });
                        } else {
                            console.log('Error removing item');
                            // Re-enable the button on error
                            removeLink.css('pointer-events', 'auto').css('opacity', '1');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX error:', error);
                        // Re-enable the button on error
                        removeLink.css('pointer-events', 'auto').css('opacity', '1');
                    }
                });
            });
        });
    </script>
<?php
}
add_action('wp_footer', 'universal_theme_header_scripts');

/**
 * AJAX handler for removing item from cart (for header dropdown)
 */
function ajax_remove_from_cart_handler()
{
    // Check if cart_item_key is set
    if (!isset($_POST['cart_item_key'])) {
        wp_send_json_error(array('message' => 'Missing cart item key'));
        return;
    }

    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);

    // Remove item from cart
    if (WC()->cart->remove_cart_item($cart_item_key)) {
        // Recalculate totals
        WC()->cart->calculate_totals();

        wp_send_json_success(array(
            'message' => 'Item removed',
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => WC()->cart->get_cart_subtotal()
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to remove item'));
    }
}
add_action('wc_ajax_remove_from_cart', 'ajax_remove_from_cart_handler');
add_action('wp_ajax_remove_from_cart', 'ajax_remove_from_cart_handler');
add_action('wp_ajax_nopriv_remove_from_cart', 'ajax_remove_from_cart_handler');

/**
 * Add cart dropdown content to WooCommerce fragments for AJAX updates
 */
function header_cart_dropdown_fragments($fragments)
{
    ob_start();
?>
    <div class="universal-cart-dropdown-content">
        <?php if (!WC()->cart->is_empty()) : ?>
            <div class="cart-items">
                <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                    $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                    $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0) :
                        $product_name = apply_filters('woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key);
                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                ?>
                        <div class="cart-item">
                            <div class="cart-item-image">
                                <?php echo $_product->get_image('woocommerce_gallery_thumbnail'); ?>
                            </div>
                            <div class="cart-item-details">
                                <h4 class="cart-item-title">
                                    <?php if ($product_permalink) : ?>
                                        <a href="<?php echo esc_url($product_permalink); ?>"><?php echo wp_kses_post($product_name); ?></a>
                                    <?php else : ?>
                                        <?php echo wp_kses_post($product_name); ?>
                                    <?php endif; ?>
                                </h4>
                                <div class="cart-item-quantity">
                                    <?php if (!empty($cart_item['jetlagz_is_gift'])) :
                                        $orig_product = wc_get_product($product_id);
                                        $regular_price = $orig_product ? $orig_product->get_regular_price() : 0;
                                        if (empty($regular_price) && $orig_product) {
                                            $regular_price = $orig_product->get_price();
                                        }
                                        $gift_price = floatval($cart_item['jetlagz_gift_rule']['price'] ?? 0.10);
                                        if ($regular_price && floatval($regular_price) > $gift_price) : ?>
                                            1 × <del class="gift-original-price"><?php echo wc_price($regular_price); ?></del> <ins class="gift-price"><?php echo wc_price($gift_price); ?></ins>
                                        <?php else : ?>
                                            1 × <?php echo wc_price($gift_price); ?>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <?php echo sprintf('%s × %s', $cart_item['quantity'], WC()->cart->get_product_price($_product)); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="cart-item-remove">
                                <a href="#" class="remove-item" data-cart_item_key="<?php echo esc_attr($cart_item_key); ?>" title="<?php esc_attr_e('Remove this item', 'woocommerce'); ?>">&times;</a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="cart-dropdown-footer">
                <div class="cart-total-amount">
                    <strong><?php esc_html_e('Suma: ', 'universal-theme');
                            echo WC()->cart->get_cart_subtotal(); ?></strong>
                </div>
                <div class="cart-actions">
                    <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="btn-checkout-single"><?php esc_html_e('Do kasy', 'universal-theme'); ?></a>
                </div>
            </div>
        <?php else : ?>
            <div class="cart-empty">
                <p><?php esc_html_e('Twój koszyk jest pusty.', 'universal-theme'); ?></p>
                <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn-continue-shopping">
                    <?php esc_html_e('Kontynuuj zakupy', 'universal-theme'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
    <?php
    $fragments['.universal-cart-dropdown-content'] = ob_get_clean();

    // Add cart count fragment
    ob_start();
    ?>
    <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
    <?php
    $fragments['.cart-count'] = ob_get_clean();

    // Add cart total fragment
    ob_start();
    ?>
    <span class="cart-total"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
<?php
    $fragments['.cart-total'] = ob_get_clean();

    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'header_cart_dropdown_fragments');

/**
 * Add Microsoft Clarity tracking script to head
 */
function universal_theme_add_clarity_script()
{
?>
    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "uyqoitwcio");
    </script>
<?php
}
add_action('wp_head', 'universal_theme_add_clarity_script');
