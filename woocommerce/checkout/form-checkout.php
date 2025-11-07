<?php

/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 */

defined('ABSPATH') || exit;

// Jeśli checkout jest wyłączony, pokaż komunikat
if (! wc_checkout_cart_has_errors()) :
?>

    <div class="universal-checkout-container">
        <div class="universal-checkout-header">
            <h1 class="checkout-title"><?php echo get_theme_option('checkout.checkout_title', 'Finalizuj zamówienie'); ?></h1>
            <div class="checkout-progress">
                <div class="progress-step active">
                    <span class="step-number">1</span>
                    <span class="step-title">Dane</span>
                </div>
                <div class="progress-step">
                    <span class="step-number">2</span>
                    <span class="step-title">Płatność</span>
                </div>
                <div class="progress-step">
                    <span class="step-number">3</span>
                    <span class="step-title">Potwierdzenie</span>
                </div>
            </div>
        </div>

        <div class="universal-checkout-layout">
            <!-- Left Column: Form -->
            <div class="checkout-form-column">

                <?php if ($checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) : ?>
                    <div class="checkout-section">
                        <h3><?php esc_html_e('Rejestracja konta', 'woocommerce'); ?></h3>
                        <p><?php esc_html_e('Zarejestruj się aby finalizować zakupy.', 'woocommerce'); ?></p>
                    </div>
                <?php endif; ?>

                <?php
                // If checkout registration is disabled and not logged in, the user cannot checkout.
                if (! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in()) {
                    echo esc_html(apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in to checkout.', 'woocommerce')));
                    return;
                }
                ?>

                <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

                    <!-- Customer Information -->
                    <div class="checkout-section customer-info">
                        <h3>
                            <i class="checkout-icon">👤</i>
                            <?php esc_html_e('Informacje kontaktowe', 'woocommerce'); ?>
                        </h3>

                        <?php if (is_user_logged_in()) : ?>
                            <div class="logged-in-info">
                                <p>Zalogowany jako <strong><?php echo wp_get_current_user()->display_name; ?></strong></p>
                                <a href="<?php echo esc_url(wp_logout_url(wc_get_checkout_url())); ?>" class="logout-link">Wyloguj się</a>
                            </div>
                        <?php endif; ?>

                        <div class="form-row form-row-wide">
                            <label for="billing_email">
                                <?php esc_html_e('Email address', 'woocommerce'); ?> <span class="required">*</span>
                            </label>
                            <input type="email" class="input-text" name="billing_email" id="billing_email"
                                placeholder="<?php esc_attr_e('Twój email', 'woocommerce'); ?>"
                                value="<?php echo esc_attr($checkout->get_value('billing_email')); ?>"
                                autocomplete="email" />
                        </div>
                    </div>

                    <!-- Billing Details -->
                    <div class="checkout-section billing-fields">
                        <h3>
                            <i class="checkout-icon">📍</i>
                            <?php esc_html_e('Adres rozliczeniowy', 'woocommerce'); ?>
                        </h3>

                        <div class="billing-fields-wrapper">
                            <?php do_action('woocommerce_checkout_billing'); ?>
                        </div>
                    </div>

                    <!-- Shipping -->
                    <?php if (WC()->cart->needs_shipping_address()) : ?>
                        <div class="checkout-section shipping-fields">
                            <h3>
                                <i class="checkout-icon">🚚</i>
                                <?php esc_html_e('Adres dostawy', 'woocommerce'); ?>
                            </h3>

                            <div class="shipping-different">
                                <label for="ship-to-different-address-checkbox">
                                    <input id="ship-to-different-address-checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox" name="ship_to_different_address" value="1" />
                                    <span><?php esc_html_e('Dostawa na inny adres?', 'woocommerce'); ?></span>
                                </label>
                            </div>

                            <div class="shipping-address" style="display: none;">
                                <?php do_action('woocommerce_checkout_shipping'); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Order Notes -->
                    <?php if (apply_filters('woocommerce_enable_order_notes_field', 'yes' === get_option('woocommerce_enable_order_comments', 'yes'))) : ?>
                        <div class="checkout-section order-notes">
                            <h3>
                                <i class="checkout-icon">📝</i>
                                <?php esc_html_e('Dodatkowe informacje', 'woocommerce'); ?>
                            </h3>

                            <div class="form-row notes">
                                <label for="order_comments" class="screen-reader-text">
                                    <?php esc_html_e('Order notes', 'woocommerce'); ?>
                                </label>
                                <textarea name="order_comments" class="input-text" id="order_comments"
                                    placeholder="<?php esc_attr_e('Uwagi do zamówienia, np. specjalne informacje dotyczące dostawy.', 'woocommerce'); ?>"
                                    rows="4"><?php echo esc_textarea($checkout->get_value('order_comments')); ?></textarea>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Payment Methods -->
                    <div class="checkout-section payment-methods">
                        <h3>
                            <i class="checkout-icon">💳</i>
                            <?php esc_html_e('Metoda płatności', 'woocommerce'); ?>
                        </h3>

                        <div id="payment" class="woocommerce-checkout-payment">
                            <?php if (WC()->cart->needs_payment()) : ?>
                                <ul class="wc_payment_methods payment_methods methods">
                                    <?php
                                    if (! empty($available_gateways)) {
                                        foreach ($available_gateways as $gateway) {
                                            wc_get_template('checkout/payment-method.php', array('gateway' => $gateway), '', '');
                                        }
                                    } else {
                                        echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' .
                                            apply_filters('woocommerce_no_available_payment_methods_message', WC()->customer->get_billing_country() ?
                                                esc_html__('Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce') :
                                                esc_html__('Please fill in your details above to see available payment methods.', 'woocommerce')) .
                                            '</li>';
                                    }
                                    ?>
                                </ul>
                            <?php endif; ?>
                            <div class="form-row place-order">
                                <noscript>
                                    <?php esc_html_e('Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce'); ?>
                                    <br /><button type="submit" class="button" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e('Update totals', 'woocommerce'); ?>"><?php esc_html_e('Update totals', 'woocommerce'); ?></button>
                                </noscript>

                                <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>
                                <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
                            </div>
                        </div>
                    </div>

                    <?php do_action('woocommerce_checkout_before_order_review'); ?>
                    <?php do_action('woocommerce_checkout_after_order_review'); ?>

                </form>

            </div>

            <!-- Right Column: Order Review -->
            <div class="checkout-review-column">
                <div class="order-review-wrapper">
                    <div class="checkout-section order-review">
                        <h3>
                            <i class="checkout-icon">🛒</i>
                            <?php esc_html_e('Twoje zamówienie', 'woocommerce'); ?>
                        </h3>

                        <div id="order_review" class="woocommerce-checkout-review-order">
                            <?php do_action('woocommerce_checkout_order_review'); ?>
                        </div>
                    </div>

                    <!-- Cross-sell Products Section -->
                    <?php if (function_exists('universal_display_checkout_crosssell')) : ?>
                        <div class="checkout-crosssell-section">
                            <?php universal_display_checkout_crosssell(); ?>
                        </div>
                    <?php endif; ?>

                    <div class="checkout-security-badges">
                        <div class="security-badge">
                            <i class="security-icon">🔒</i>
                            <span>Bezpieczna płatność</span>
                        </div>
                        <div class="security-badge">
                            <i class="security-icon">🚚</i>
                            <span>Szybka dostawa</span>
                        </div>
                        <div class="security-badge">
                            <i class="security-icon">↩️</i>
                            <span>Łatwy zwrot</span>
                        </div>
                    </div>

                    <div class="place-order-button">
                        <button type="submit" class="button alt wp-element-button"
                            name="woocommerce_checkout_place_order"
                            id="place_order"
                            value="<?php esc_attr_e('Place order', 'woocommerce'); ?>"
                            data-value="<?php esc_attr_e('Place order', 'woocommerce'); ?>">
                            <span class="button-text"><?php esc_html_e('Złóż zamówienie', 'woocommerce'); ?></span>
                            <span class="button-total"><?php echo WC()->cart->get_total(); ?></span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

<?php endif; ?>

<?php do_action('woocommerce_after_checkout_form', $checkout); ?>