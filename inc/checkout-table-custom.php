<?php

/**
 * Custom Checkout Review Order Table
 * Przeprojektowuje wygląd tabeli produktów na checkout
 * Struktura: [Miniaturka] [Nazwa + Sterowniki ilości] [Cena jedn.] [Cena całkowita]
 */

// Zapobiegnij bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

function universal_render_checkout_applied_coupons()
{
    if (!function_exists('WC') || !WC()->cart) {
        return;
    }

    $applied_coupons = array_values(array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons()));

    if (empty($applied_coupons)) {
        return;
    }
?>
    <div class="universal-applied-coupons" aria-live="polite">
        <?php foreach ($applied_coupons as $applied_coupon_code) : ?>
            <div class="universal-applied-coupon-pill" data-coupon-code="<?php echo esc_attr($applied_coupon_code); ?>">
                <span class="universal-applied-coupon-code"><?php echo esc_html($applied_coupon_code); ?></span>
                <button
                    type="button"
                    class="universal-applied-coupon-remove"
                    data-coupon-code="<?php echo esc_attr($applied_coupon_code); ?>"
                    aria-label="<?php echo esc_attr(sprintf(__('Usuń kupon %s', 'universal-theme'), $applied_coupon_code)); ?>">
                    ×
                </button>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}

/**
 * Zmień nagłówek "Your order" na "Wybór metody dostawy"
 */
add_filter('woocommerce_order_review_heading', function () {
    return __('Wybór metody dostawy', 'universal-theme');
});

/**
 * Hook do wyświetlenia custom review order table
 * Użyj hook'a weocommerce_review_order_before_payment zamiast before_order_review
 * To umieszcza tabelę WEWNĄTRZ sekcji "Your order"
 */
add_action('woocommerce_review_order_before_payment', function () {
    // Jeśli koszyk jest pusty, nie wyświetlaj
    if (WC()->cart->is_empty()) {
        return;
    }

    $checkout_coupon_apply_attempt = array(
        'attempted' => false,
        'coupon_code' => '',
        'success' => false,
        'message' => '',
        'applied_coupons_before' => array_values(array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons())),
        'applied_coupons_after' => array_values(array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons())),
    );

    if (function_exists('jetlagz_get_selected_coupon_code')) {
        $selected_coupon_code = jetlagz_get_selected_coupon_code();

        if ($selected_coupon_code !== '') {
            $applied_coupons = array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons());

            if (!in_array(wc_format_coupon_code($selected_coupon_code), $applied_coupons, true)) {
                $checkout_coupon_apply_attempt['attempted'] = true;
                $checkout_coupon_apply_attempt['coupon_code'] = wc_format_coupon_code($selected_coupon_code);
                $checkout_coupon_apply_attempt['applied_coupons_before'] = array_values($applied_coupons);
                wc_clear_notices();
                $apply_result = WC()->cart->apply_coupon($selected_coupon_code);
                WC()->cart->calculate_totals();
                $apply_notices_html = wc_print_notices(true);
                $apply_message = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($apply_notices_html)));
                wc_clear_notices();

                $checkout_coupon_apply_attempt['success'] = (bool) $apply_result;
                $checkout_coupon_apply_attempt['message'] = $apply_message;
                $checkout_coupon_apply_attempt['applied_coupons_after'] = array_values(array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons()));
            }
        }
    }

    $checkout_coupon_debug = array(
        'selected_coupon_code' => function_exists('jetlagz_get_selected_coupon_code') ? jetlagz_get_selected_coupon_code() : '',
        'applied_coupons' => array_values(array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons())),
        'cart_discount_total' => (float) WC()->cart->get_discount_total() + (float) WC()->cart->get_discount_tax(),
        'cart_total' => wp_strip_all_tags((string) WC()->cart->get_cart_total()),
        'cart_items_count' => (int) WC()->cart->get_cart_contents_count(),
        'apply_attempt' => $checkout_coupon_apply_attempt,
        'cart_items' => array(),
    );

    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = isset($cart_item['data']) ? $cart_item['data'] : null;
        $checkout_coupon_debug['cart_items'][] = array(
            'cart_item_key' => $cart_item_key,
            'product_id' => isset($cart_item['product_id']) ? (int) $cart_item['product_id'] : 0,
            'product_name' => $product ? $product->get_name() : '',
            'quantity' => isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 0,
            'line_subtotal' => isset($cart_item['line_subtotal']) ? (float) $cart_item['line_subtotal'] : 0,
            'line_total' => isset($cart_item['line_total']) ? (float) $cart_item['line_total'] : 0,
        );
    }

?>
    <script>
        window.jetlagzCheckoutCouponDebug = <?php echo wp_json_encode($checkout_coupon_debug); ?>;
        if (window.console && console.log) {
            console.log('[Jetlagz coupon debug][checkout][render]', window.jetlagzCheckoutCouponDebug);
        }
    </script>
    <?php

    ?>
    <div class="universal-checkout-review-wrapper">
        <h3 class="universal-checkout-review-title"><?php echo __('Podsumowanie koszyka', 'universal-theme'); ?></h3>
        <div class="universal-checkout-review-table-custom">
            <?php
            // Iteruj po produktach w koszyku
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];

                // Pobierz dane
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
                $price_data = function_exists('universal_get_checkout_cart_item_price_data')
                    ? universal_get_checkout_cart_item_price_data($cart_item)
                    : array(
                        'has_discount' => false,
                        'base_unit_price' => (float) $product->get_price(),
                        'discounted_unit_price' => (float) $product->get_price(),
                        'base_line_total' => (float) $product->get_price() * $quantity,
                        'discounted_line_total' => (float) $product->get_price() * $quantity,
                    );
                $product_price = $price_data['discounted_unit_price'];
                $product_total = $price_data['discounted_line_total'];
                $product_url = get_permalink($product_id);
                $is_gift = !empty($cart_item['jetlagz_is_gift']);
                $discount_percentage = function_exists('universal_get_discount_percentage')
                    ? universal_get_discount_percentage($price_data['base_unit_price'], $price_data['discounted_unit_price'])
                    : 0;

                // Formatuj ceny
                $price_formatted = function_exists('universal_render_checkout_price_html')
                    ? universal_render_checkout_price_html(
                        $price_data['base_unit_price'],
                        $price_data['discounted_unit_price'],
                        $price_data['has_discount'],
                        'checkout-item-unit-price'
                    )
                    : wc_price($product_price);
                $total_formatted = function_exists('universal_render_checkout_price_html')
                    ? universal_render_checkout_price_html(
                        $price_data['base_line_total'],
                        $price_data['discounted_line_total'],
                        $price_data['has_discount'],
                        'checkout-item-total-price'
                    )
                    : wc_price($product_total);
            ?>
                <div class="universal-checkout-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                    <!-- Lewa część: Miniaturka + Nazwa + Cena jednostkowa -->
                    <div class="checkout-item-left">
                        <div class="checkout-item-thumbnail">
                            <div class="cart-item-image">
                                <?php if ($discount_percentage > 0) : ?>
                                    <span class="cart-item-discount-badge">-<?php echo esc_html($discount_percentage); ?>%</span>
                                <?php endif; ?>
                                <?php if ($is_gift) : ?>
                                    <?php echo $product_image; ?>
                                <?php else : ?>
                                    <a href="<?php echo esc_url($product_url); ?>">
                                        <?php echo $product_image; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="checkout-item-remove-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Usuń z koszyka', 'universal-theme'); ?>">×</button>
                        </div>
                        <div class="checkout-item-details">
                            <div class="checkout-item-name">
                                <?php if ($is_gift) : ?>
                                    <span class="gift-badge">🎁 PREZENT</span> <?php echo esc_html($product_name); ?>
                                <?php else : ?>
                                    <a href="<?php echo esc_url($product_url); ?>"><?php echo esc_html($product_name); ?></a>
                                <?php endif; ?>
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

        <!-- Global Gift Wrapping Section -->
        <?php
        if (function_exists('get_field')) {
            $gift_wrapping_group = get_field('gift_wrapping_field', 'option');
            if ($gift_wrapping_group && isset($gift_wrapping_group['gift_wrapping_enabled']) && $gift_wrapping_group['gift_wrapping_enabled']) {
                $gift_wrapping_label = $gift_wrapping_group['gift_wrapping_label'] ?: 'Zapakować na prezent?';
                $gift_wrapping_price = $gift_wrapping_group['gift_wrapping_price'] ?: 12;
                $is_checked = WC()->session->get('global_gift_wrapping_enabled', false);
        ?>
                <div class="universal-gift-wrapping-section">
                    <label class="gift-wrapping-global-label">
                        <input
                            type="checkbox"
                            id="global-gift-wrapping-checkbox"
                            class="gift-wrapping-global-checkbox"
                            <?php checked($is_checked, true); ?> />
                        <span class="gift-wrapping-global-text">
                            🎁 <?php echo esc_html($gift_wrapping_label); ?>
                            <span class="gift-wrapping-global-price">+<?php echo number_format($gift_wrapping_price, 0, ',', ' '); ?> zł</span>
                        </span>
                    </label>
                </div>
        <?php
            }
        } // End function_exists check
        ?>
    </div>

    <!-- Custom Coupon Form - Pod listą produktów -->
    <div class="universal-coupon-wrapper">
        <h4 class="coupon-title"><?php echo __('Kod rabatowy', 'universal-theme'); ?></h4>
        <?php $prefilled_coupon_code = function_exists('jetlagz_get_selected_coupon_code') ? jetlagz_get_selected_coupon_code() : ''; ?>
        <div class="universal-coupon-form">
            <div class="coupon-input-wrapper">
                <svg class="coupon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="https://www.w3.org/2000/svg">
                    <path d="M9 5H7C6.46957 5 5.96086 5.21071 5.58579 5.58579C5.21071 5.96086 5 6.46957 5 7V9C6.10457 9 7 9.89543 7 11C7 12.1046 6.10457 13 5 13V15C5 15.5304 5.21071 16.0391 5.58579 16.4142C5.96086 16.7893 6.46957 17 7 17H9M9 5V19M9 5H17C17.5304 5 18.0391 5.21071 18.4142 5.58579C18.7893 5.96086 19 6.46957 19 7V9C17.8954 9 17 9.89543 17 11C17 12.1046 17.8954 13 19 13V15C19 15.5304 18.7893 16.0391 18.4142 16.4142C18.0391 16.7893 17.5304 17 17 17H9M9 19H17C17.5304 19 18.0391 18.7893 18.4142 18.4142C18.7893 18.0391 19 17.5304 19 17V15M9 19H7C6.46957 19 5.96086 18.7893 5.58579 18.4142C5.21071 18.0391 5 17.5304 5 17V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <input type="text" id="coupon_code" placeholder="<?php echo __('Wpisz kod rabatowy', 'universal-theme'); ?>" value="<?php echo esc_attr($prefilled_coupon_code); ?>">
            </div>
            <button type="button" class="universal-coupon-apply-btn" id="apply-coupon-btn">
                <?php echo __('Zastosuj', 'universal-theme'); ?>
            </button>
        </div>
    </div>
    <?php universal_render_checkout_applied_coupons(); ?>

    <!-- Podsumowanie koszyka (Totals) -->
    <?php universal_render_checkout_totals(); ?>
<?php
});

/**
 * Ukryj domyślną tabelę WooCommerce (tylko produkty, NIE shipping!)
 * CSS wyłączy widoczność TYLKO wierszy z produktami
 * Nasza custom tabela produktów będzie wyświetlona przez hook woocommerce_review_order_before_payment
 * ALE zachowamy shipping, totals i inne sekcje!
 */
add_action('wp_head', function () {
?>
    <style>
        /* Ukryj wiersze z produktami, thead i order-total (mamy już custom totals) */
        .woocommerce-checkout-review-order-table thead,
        .woocommerce-checkout-review-order-table .cart_item,
        .woocommerce-checkout-review-order-table .cart-subtotal,
        .woocommerce-checkout-review-order-table .cart-discount,
        .woocommerce-checkout-review-order-table .order-total {
            display: none !important;
        }

        /* Pokaż TYLKO shipping i Apaczka (reszta w custom totals) */
        .woocommerce-checkout-review-order-table .woocommerce-shipping-totals,
        .woocommerce-checkout-review-order-table .shipping,
        .woocommerce-checkout-review-order-table .apaczka-parcel-machine {
            display: table-row !important;
        }

        /* Wymuś widoczność ul#shipping_method i widgetu Apaczka */
        .woocommerce-checkout-review-order-table ul#shipping_method,
        .woocommerce-checkout-review-order-table .apaczka-parcel-machine-selector,
        .woocommerce-checkout-review-order-table .apaczka-map-widget {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Ukryj pierwszą kolumnę z nazwą "Shipping" */
        .woocommerce-checkout-review-order-table .shipping th,
        .woocommerce-checkout-review-order-table .woocommerce-shipping-totals th {
            display: none !important;
        }

        /* Rozszerz drugą kolumnę na pełną szerokość */
        .woocommerce-checkout-review-order-table .shipping td,
        .woocommerce-checkout-review-order-table .woocommerce-shipping-totals td {
            width: 100% !important;
            display: block !important;
        }

        /* Ukryj label Apaczka */
        .apaczka-parcel-machine-label {
            display: none !important;
        }

        /* Zapobiegaj FOUC - ukryj elementy przed przeniesieniem */
        .shop_table.woocommerce-checkout-review-order-table:not(.moved),
        #shipping-method-heading:not(.moved) {
            opacity: 0 !important;
            visibility: hidden !important;
        }

        /* Pokaż płynnie po przeniesieniu */
        .shop_table.woocommerce-checkout-review-order-table.moved,
        #shipping-method-heading.moved {
            opacity: 1 !important;
            visibility: visible !important;
            transition: opacity 0.2s ease !important;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            function moveShippingTable() {
                var $shippingTable = $('.shop_table.woocommerce-checkout-review-order-table');
                var $customerDetails = $('#customer_details');
                var $orderReviewHeading = $('h3#order_review_heading');

                // Usuń domyślny nagłówek "Your order"
                if ($orderReviewHeading.length) {
                    $orderReviewHeading.remove();
                }

                // Sprawdź czy elementy istnieją
                if ($shippingTable.length && $customerDetails.length) {
                    // Przenieś tabelę shipping WEWNĄTRZ #customer_details (na końcu)
                    $shippingTable.appendTo($customerDetails).addClass('moved');

                    // Dodaj nagłówek "Wybór metody dostawy" NAD tabelą (po przeniesieniu)
                    if (!$('#shipping-method-heading').length) {
                        $('<h3 id="shipping-method-heading" class="moved" style="margin-top: 30px; margin-bottom: 15px; font-weight: 600;">Wybór metody dostawy</h3>')
                            .insertBefore($shippingTable);
                    } else {
                        $('#shipping-method-heading').addClass('moved');
                    }

                }
            }

            // Wykonaj po załadowaniu strony
            moveShippingTable();

            // Ponów po aktualizacji checkout
            $(document.body).on('updated_checkout', function() {
                moveShippingTable();
            });
        });
    </script>
<?php
});
