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
 * Usuń domyślną tabelę i zastąp custom version
 */
add_filter('woocommerce_order_review_heading', function () {
    // Zwróć pusty string - ukryj domyślny heading
    return '';
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
                    <!-- Kolumna 1: Miniaturka -->
                    <div class="checkout-item-thumbnail">
                        <div class="checkout-thumbnail-wrapper">
                            <?php echo $product_image; ?>
                            <button type="button" class="checkout-item-remove-btn" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Usuń z koszyka', 'universal-theme'); ?>">×</button>
                        </div>
                    </div>

                    <!-- Kolumna 2: Nazwa + Sterowniki ilości -->
                    <div class="checkout-item-info">
                        <div class="checkout-item-name">
                            <?php echo esc_html($product_name); ?>
                        </div>
                        <div class="checkout-item-quantity-controls">
                            <button type="button" class="qty-btn minus" data-action="minus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Zmniejsz ilość', 'universal-theme'); ?>">−</button>
                            <span class="qty-display" data-qty="<?php echo esc_attr($quantity); ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Kliknij aby edytować ilość', 'universal-theme'); ?>"><?php echo esc_html($quantity); ?></span>
                            <button type="button" class="qty-btn plus" data-action="plus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="<?php echo __('Zwiększ ilość', 'universal-theme'); ?>">+</button>
                        </div>
                    </div>

                    <!-- Kolumna 3: Ceny -->
                    <div class="checkout-item-prices">
                        <div class="checkout-item-price-unit" data-unit-price="<?php echo esc_attr($product_price); ?>">
                            <span class="label"><?php echo __('Jedn.:', 'universal-theme'); ?></span>
                            <span class="price"><?php echo wp_kses_post($price_formatted); ?></span>
                        </div>
                        <div class="checkout-item-price-total" data-unit-price="<?php echo esc_attr($product_price); ?>" style="display: <?php echo $quantity > 1 ? 'flex' : 'none'; ?>;">
                            <span class="label"><?php echo __('Razem:', 'universal-theme'); ?></span>
                            <span class="price"><?php echo wp_kses_post($total_formatted); ?></span>
                        </div>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

    <!-- Custom Coupon Form - Pod listą produktów -->
    <div class="universal-coupon-form">
        <div class="universal-coupon-form-group">
            <label for="coupon_code"><?php echo __('Masz kod promocyjny?', 'universal-theme'); ?></label>
            <input type="text" id="coupon_code" placeholder="<?php echo __('Wpisz kod kuponu', 'universal-theme'); ?>" value="">
        </div>
        <button type="button" class="universal-coupon-apply-btn" id="apply-coupon-btn">
            <?php echo __('Zastosuj', 'universal-theme'); ?>
        </button>
    </div>

    <!-- Podsumowanie koszyka (Totals) -->
    <?php universal_render_checkout_totals(); ?>
<?php
});

/**
 * Ukryj domyślną tabelę WooCommerce
 * CSS wyłączy widoczność DOMYŚLNEJ tabeli (woocommerce-checkout-review-order-table)
 * Nasza custom tabela będzie wyświetlona w jej miejscu przez hook woocommerce_review_order_before_payment
 */
add_action('wp_head', function () {
?>
    <style>
        /* Ukryj TYLKO domyślną tabelę checkout review */
        .woocommerce-checkout-review-order-table {
            display: none !important;
        }
    </style>
<?php
});
