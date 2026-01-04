<?php

/**
 * Pay for order form - Custom Template
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.2.0
 */

defined('ABSPATH') || exit;

$totals = $order->get_order_item_totals();
?>

<div class="order-pay-page-custom">
    <div class="order-pay-header">
        <p class="order-pay-subtitle">
            Zamówienie nr <strong><?php echo esc_html($order->get_order_number()); ?></strong>
        </p>
    </div>

    <form id="order_review" method="post" class="order-pay-form">

        <!-- Podsumowanie zamówienia -->
        <div class="order-pay-summary">
            <h2 class="summary-title">Podsumowanie zamówienia</h2>

            <div class="order-items-list">
                <?php if (count($order->get_items()) > 0) : ?>
                    <?php foreach ($order->get_items() as $item_id => $item) : ?>
                        <?php
                        if (!apply_filters('woocommerce_order_item_visible', true, $item)) {
                            continue;
                        }

                        $product = $item->get_product();
                        $image_id = $product ? $product->get_image_id() : 0;
                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src();
                        ?>
                        <div class="order-item">
                            <div class="item-image">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($item->get_name()); ?>">
                            </div>
                            <div class="item-details">
                                <h3 class="item-name"><?php echo wp_kses_post($item->get_name()); ?></h3>
                                <div class="item-meta">
                                    <?php wc_display_item_meta($item); ?>
                                </div>
                                <div class="item-quantity">Ilość: <?php echo esc_html($item->get_quantity()); ?></div>
                            </div>
                            <div class="item-price">
                                <?php echo $order->get_formatted_line_subtotal($item); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Totals -->
            <div class="order-totals">
                <?php if ($totals) : ?>
                    <?php foreach ($totals as $total) : ?>
                        <div class="total-row">
                            <span class="total-label"><?php echo wp_kses_post($total['label']); ?></span>
                            <span class="total-value"><?php echo wp_kses_post($total['value']); ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php
        /**
         * Hook: woocommerce_pay_order_before_payment
         */
        do_action('woocommerce_pay_order_before_payment');
        ?>

        <!-- Payment Section -->
        <div id="payment" class="order-pay-payment">
            <?php if ($order->needs_payment()) : ?>
                <h2 class="payment-title">Wybierz metodę płatności</h2>

                <ul class="wc_payment_methods payment_methods methods">
                    <?php
                    if (!empty($available_gateways)) {
                        foreach ($available_gateways as $gateway) {
                            wc_get_template('checkout/payment-method.php', array('gateway' => $gateway));
                        }
                    } else {
                        echo '<li class="no-payment-methods">';
                        wc_print_notice(
                            apply_filters(
                                'woocommerce_no_available_payment_methods_message',
                                esc_html__('Przepraszamy, nie ma dostępnych metod płatności dla Twojej lokalizacji. Skontaktuj się z nami.', 'woocommerce')
                            ),
                            'notice'
                        );
                        echo '</li>';
                    }
                    ?>
                </ul>
            <?php endif; ?>

            <div class="form-row payment-actions">
                <input type="hidden" name="woocommerce_pay" value="1" />

                <?php wc_get_template('checkout/terms.php'); ?>

                <?php do_action('woocommerce_pay_order_before_submit'); ?>

                <button type="submit" class="button order-pay-submit-button" id="place_order" value="<?php echo esc_attr($order_button_text); ?>">
                    <?php echo esc_html($order_button_text); ?>
                </button>

                <?php do_action('woocommerce_pay_order_after_submit'); ?>

                <?php wp_nonce_field('woocommerce-pay', 'woocommerce-pay-nonce'); ?>
            </div>
        </div>
    </form>
</div>