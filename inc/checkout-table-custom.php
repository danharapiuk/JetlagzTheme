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

?>
    <div class="universal-checkout-review-wrapper">
        <h3 class="universal-checkout-review-title"><?php echo __('Podsumowanie zamówienia', 'universal-theme'); ?></h3>
        <div class="universal-checkout-review-table-custom">
            <?php
            // Iteruj po produktach w koszyku
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];

                // Pobierz dane
                $product_id = $cart_item['product_id'];
                $product_name = $product->get_name();
                $product_image = $product->get_image('thumbnail');
                $product_price = $product->get_price();
                $product_total = $product_price * $quantity;

                // Formatuj ceny
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
    </div>

    <!-- Custom Coupon Form - Pod listą produktów -->
    <div class="universal-coupon-wrapper">
        <h4 class="coupon-title"><?php echo __('Kod rabatowy', 'universal-theme'); ?></h4>
        <div class="universal-coupon-form">
            <div class="coupon-input-wrapper">
                <svg class="coupon-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9 5H7C6.46957 5 5.96086 5.21071 5.58579 5.58579C5.21071 5.96086 5 6.46957 5 7V9C6.10457 9 7 9.89543 7 11C7 12.1046 6.10457 13 5 13V15C5 15.5304 5.21071 16.0391 5.58579 16.4142C5.96086 16.7893 6.46957 17 7 17H9M9 5V19M9 5H17C17.5304 5 18.0391 5.21071 18.4142 5.58579C18.7893 5.96086 19 6.46957 19 7V9C17.8954 9 17 9.89543 17 11C17 12.1046 17.8954 13 19 13V15C19 15.5304 18.7893 16.0391 18.4142 16.4142C18.0391 16.7893 17.5304 17 17 17H9M9 19H17C17.5304 19 18.0391 18.7893 18.4142 18.4142C18.7893 18.0391 19 17.5304 19 17V15M9 19H7C6.46957 19 5.96086 18.7893 5.58579 18.4142C5.21071 18.0391 5 17.5304 5 17V15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <input type="text" id="coupon_code" placeholder="<?php echo __('Wpisz kod rabatowy', 'universal-theme'); ?>" value="">
            </div>
            <button type="button" class="universal-coupon-apply-btn" id="apply-coupon-btn">
                <?php echo __('Zastosuj', 'universal-theme'); ?>
            </button>
        </div>
    </div>

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

                    console.log('✅ Shipping table przeniesiona do #customer_details');
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
