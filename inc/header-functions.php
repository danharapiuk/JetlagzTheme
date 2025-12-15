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

    // Dodaj custom header layout
    add_action('storefront_header', 'universal_theme_custom_header_content', 10);
}
add_action('init', 'universal_theme_customize_header');

/**
 * Custom header content - wszystkie elementy w jednym rzędzie
 */
function universal_theme_custom_header_content()
{
?>
    <div class="universal-header-layout">
        <div class="col-full">
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
                                                            <?php echo sprintf('%s × %s', $cart_item['quantity'], WC()->cart->get_product_price($_product)); ?>
                                                        </div>
                                                    </div>
                                                    <div class="cart-item-remove">
                                                        <?php
                                                        echo apply_filters(
                                                            'woocommerce_cart_item_remove_link',
                                                            sprintf(
                                                                '<a href="%s" class="remove-item" title="%s" data-product_id="%s" data-cart_item_key="%s" data-product_sku="%s">&times;</a>',
                                                                esc_url(wc_get_cart_remove_url($cart_item_key)),
                                                                esc_attr__('Remove this item', 'woocommerce'),
                                                                esc_attr($product_id),
                                                                esc_attr($cart_item_key),
                                                                esc_attr($_product->get_sku())
                                                            ),
                                                            $cart_item_key
                                                        );
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <div class="cart-dropdown-footer">
                                        <div class="cart-total-amount">
                                            <strong><?php esc_html_e('Total: ', 'universal-theme');
                                                    echo WC()->cart->get_cart_subtotal(); ?></strong>
                                        </div>
                                        <div class="cart-actions">
                                            <a href="<?php echo esc_url(wc_get_checkout_url()); ?>" class="btn-checkout-single"><?php esc_html_e('Checkout', 'universal-theme'); ?></a>
                                        </div>
                                    </div>
                                <?php else : ?>
                                    <div class="cart-empty">
                                        <p><?php esc_html_e('Your cart is currently empty.', 'universal-theme'); ?></p>
                                        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn-continue-shopping">
                                            <?php esc_html_e('Continue Shopping', 'universal-theme'); ?>
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
?>
    <style>
        .universal-header-layout {
            padding: 1rem 0;
            overflow: visible !important;
            /* Pozwól dropdownowi wychodzić poza header */
        }

        /* Bardzo specyficzne selektory żeby nadpisać wszystko */
        .site-header .universal-header-layout,
        .storefront-primary-navigation .universal-header-layout,
        header .universal-header-layout,
        .universal-header-layout.universal-header-layout {
            overflow: visible !important;
        }

        /* Zapewnij że header parent nie ogranicza dropdown */
        .site-header,
        .storefront-primary-navigation,
        header {
            overflow: visible !important;
        }

        /* Mega agresywny fix dla overflow */
        :where(.universal-header-layout),
        :where(.site-header) .universal-header-layout,
        :where(header) .universal-header-layout,
        :where(.storefront-primary-navigation) .universal-header-layout {
            overflow: visible !important;
            overflow-x: visible !important;
            overflow-y: visible !important;
        }

        .universal-header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .universal-header-logo {
            flex: 0 0 auto;
        }

        /* Logo responsive behavior */
        .custom-logo-desktop {
            display: block;
        }

        .custom-logo-mobile {
            display: none;
        }

        /* Site title styling jeśli nie ma logo */
        .site-title {
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }

        .site-title a {
            color: white;
            text-decoration: none;
        }

        /* Na mobile - pokaż mobile logo i ukryj desktop */
        @media (max-width: 768px) {
            .custom-logo-desktop {
                display: none !important;
            }

            .custom-logo-mobile {
                display: block !important;
                max-height: 50px !important;
                width: auto !important;
                object-fit: contain !important;
            }

            .site-title {
                font-size: 1.2rem;
            }
        }

        .universal-header-navigation {
            flex: 1 1 auto;
            margin-left: 2rem;
        }

        .universal-primary-menu {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 2rem;
        }

        .universal-primary-menu a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .universal-primary-menu a:hover {
            color: var(--accent-color, #ff6b35);
        }

        .universal-header-search {
            flex: 0 0 auto;
        }

        .universal-search-form {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .universal-search-form .search-field {
            border: none;
            background: transparent;
            color: white;
            padding: 0.5rem 1rem;
            width: 200px;
        }

        .universal-search-form .search-field::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .universal-search-form .search-submit {
            border: none;
            background: transparent;
            color: white;
            padding: 0.5rem;
            cursor: pointer;
        }

        .universal-header-account {
            flex: 0 0 auto;
        }

        .universal-account-link {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .universal-account-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .universal-account-link svg {
            width: 24px;
            height: 24px;
        }

        .universal-header-cart {
            flex: 0 0 auto;
            position: relative;
        }

        .universal-cart-dropdown {
            position: relative;
            overflow: visible !important;
            /* Pozwól dropdownowi wychodzić poza kontener */
        }

        .universal-cart-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
        }

        .universal-cart-link:hover {
            color: var(--accent-color, #ff6b35);
        }

        /* Cart Dropdown Styles */
        .universal-cart-dropdown-content {
            position: fixed;
            top: 70px;
            /* Odległość od górnej krawędzi strony */
            right: 16px;
            /* Odległość od prawej krawędzi */
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            z-index: 99999;
            /* Wyższy z-index żeby był nad wszystkim */
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            max-height: 400px;
            overflow-y: auto;
        }

        .universal-cart-dropdown:hover .universal-cart-dropdown-content {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Responsive positioning dla cart dropdown */
        @media (min-width: 640px) and (max-width: 1023px) {
            .universal-cart-dropdown-content {
                right: 24px;
                /* Dopasuj do responsive paddingu */
            }
        }

        @media (min-width: 1024px) and (max-width: 1535px) {
            .universal-cart-dropdown-content {
                right: 32px;
                /* Dopasuj do responsive paddingu */
            }
        }

        @media (min-width: 1536px) {
            .universal-cart-dropdown-content {
                right: calc((100vw - 1536px) / 2);
                /* Wycentruj względem max-width container */
            }
        }

        .cart-items {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }

        .cart-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            flex: 0 0 50px;
        }

        .cart-item-image img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-title {
            margin: 0 0 0.25rem 0;
            font-size: 14px;
            font-weight: 500;
        }

        .cart-item-title a {
            color: #333;
            text-decoration: none;
        }

        .cart-item-title a:hover {
            color: var(--accent-color, #ff6b35);
        }

        .cart-item-quantity {
            font-size: 12px;
            color: #666;
        }

        .cart-item-remove {
            flex: 0 0 auto;
        }

        .remove-item {
            color: #999;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .remove-item:hover {
            background: #f5f5f5;
            color: #ff4444;
        }

        .cart-dropdown-footer {
            padding: 1rem;
        }

        .cart-total-amount {
            margin-bottom: 1rem;
            text-align: center;
            font-size: 16px;
            color: #333;
        }

        .cart-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-checkout-single {
            width: 100%;
            padding: 0.75rem;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
            background: var(--accent-color, #ff6b35);
            color: white;
            font-size: 16px;
        }

        .btn-checkout-single:hover {
            background: var(--accent-color-dark, #e55a2b);
            transform: translateY(-1px);
        }

        /* Legacy styles dla kompatybilności */
        .btn-view-cart,
        .btn-checkout {
            flex: 1;
            padding: 0.75rem;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-view-cart {
            background: #f5f5f5;
            color: #333;
        }

        .btn-view-cart:hover {
            background: #eee;
        }

        .btn-checkout {
            background: var(--accent-color, #ff6b35);
            color: white;
        }

        .btn-checkout:hover {
            background: var(--accent-color-dark, #e55a2b);
        }

        .cart-empty {
            padding: 2rem 1rem;
            text-align: center;
        }

        .cart-empty p {
            margin: 0 0 1rem 0;
            color: #666;
        }

        .btn-continue-shopping {
            background: var(--accent-color, #ff6b35);
            color: white;
            padding: 0.75rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-continue-shopping:hover {
            background: var(--accent-color-dark, #e55a2b);
        }

        /* Ukryj oryginalne pole wyszukiwania Storefront */
        .site-search,
        .storefront-product-search,
        .widget_product_search {
            display: none !important;
        }

        /* Upewnij się że nasze pole search jest widoczne */
        .universal-header-search {
            display: flex !important;
        }

        /* Mobile toggle - ukryty na desktop, widoczny na mobile */
        .universal-header-mobile-toggle {
            display: none;
            flex: 0 0 auto;
        }

        /* Ukryj stary/duplikat universal-mobile-toggle */
        .universal-mobile-toggle {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }

        .universal-mobile-menu-toggle {
            background: none;
            border: none;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .universal-mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: white;
            transition: all 0.3s ease;
        }

        .cart-count {
            background: var(--accent-color, #ff6b35);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .universal-header-row {
                flex-wrap: wrap;
            }

            .universal-header-navigation {
                order: 5;
                flex-basis: 100%;
                margin-left: 0;
                margin-top: 1rem;
                display: none;
                /* Ukryj domyślnie, pokaż po kliknięciu */
            }

            .universal-header-navigation.active {
                display: block;
            }

            .universal-primary-menu {
                flex-direction: column;
                gap: 1rem;
            }

            .universal-header-search {
                order: 3;
                flex: 1 1 auto;
                margin: 0 1rem;
            }

            .universal-search-form .search-field {
                width: 100%;
            }

            /* Pokaż mobile toggle na mobile */
            .universal-header-mobile-toggle {
                display: block !important;
                order: 4;
            }
        }

        @media (max-width: 480px) {
            .universal-header-search {
                display: none;
            }
        }
    </style>
<?php
}
add_action('wp_head', 'universal_theme_header_styles');

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
            padding-left: 16px !important;
            padding-right: 16px !important;
            box-sizing: border-box !important;
            width: 100% !important;
        }

        /* Header też dostaje responsive padding */
        .universal-header-layout {
            padding-left: 16px !important;
            padding-right: 16px !important;
            box-sizing: border-box !important;
            max-width: 1536px !important;
            margin: 0 auto !important;
            width: 100% !important;
            overflow: visible !important;
            /* Fix scrollowania */
        }

        /* Small tablets - 640px to 1023px = 24px padding */
        @media (min-width: 640px) and (max-width: 1023px) {
            .col-full {
                padding-left: 24px !important;
                padding-right: 24px !important;
            }

            .universal-header-layout {
                padding-left: 24px !important;
                padding-right: 24px !important;
                max-width: 1536px !important;
                margin: 0 auto !important;
                overflow: visible !important;
                /* Fix scrollowania */
            }
        }

        /* Large tablets/Desktop - 1024px to 1535px = 32px padding */
        @media (min-width: 1024px) and (max-width: 1535px) {
            .col-full {
                padding-left: 32px !important;
                padding-right: 32px !important;
            }

            .universal-header-layout {
                padding-left: 32px !important;
                padding-right: 32px !important;
                max-width: 1536px !important;
                margin: 0 auto !important;
                overflow: visible !important;
                /* Fix scrollowania */
            }
        }

        /* Large screens - 1536px+ = no extra padding */
        @media (min-width: 1536px) {
            .col-full {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            .universal-header-layout {
                padding-left: 0 !important;
                padding-right: 0 !important;
                max-width: 1536px !important;
                margin: 0 auto !important;
                overflow: visible !important;
                /* Fix scrollowania */
            }
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

        /* Zagnieżdżone col-full - mniejszy padding */
        .col-full .col-full {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }

        @media (min-width: 640px) and (max-width: 1023px) {
            .col-full .col-full {
                padding-left: 15px !important;
                padding-right: 15px !important;
            }
        }

        @media (min-width: 1024px) and (max-width: 1535px) {
            .col-full .col-full {
                padding-left: 15px !important;
                padding-right: 15px !important;
            }
        }

        @media (min-width: 1536px) {
            .col-full .col-full {
                padding-left: 15px !important;
                padding-right: 15px !important;
            }
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
        });
    </script>
<?php
}
add_action('wp_footer', 'universal_theme_header_scripts');
