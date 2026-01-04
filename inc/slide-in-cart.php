<?php

/**
 * Slide-in Cart Panel
 * Wysuwany koszyk z prawej strony po dodaniu produktu
 */

// Zapobiegnij bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modyfikuj domyślny alert WooCommerce - dodaj klasę trigger dla slide-in cart
 */
add_filter('wc_add_to_cart_message_html', 'modify_add_to_cart_message', 10, 2);
function modify_add_to_cart_message($message, $products)
{
    // Dodaj ukrytą klasę która będzie triggerem dla JS
    $message = str_replace('woocommerce-message', 'woocommerce-message slide-in-cart-trigger', $message);
    // Ukryj wizualnie ale zostaw w DOM
    $message = '<div style="display:none!important;">' . $message . '</div>';
    return $message;
}

/**
 * Rejestruj ACF fields dla slide-in cart w Template Parts
 */
add_action('acf/init', 'register_slide_in_cart_acf_fields');
function register_slide_in_cart_acf_fields()
{
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_slide_in_cart',
            'title' => 'Slide-in Cart (Wysuwany Koszyk)',
            'fields' => array(
                array(
                    'key' => 'field_cart_title',
                    'label' => 'Tytuł koszyka',
                    'name' => 'cart_title',
                    'type' => 'text',
                    'default_value' => 'Zawartość koszyka',
                    'placeholder' => 'Zawartość koszyka',
                ),
                array(
                    'key' => 'field_free_shipping_enabled',
                    'label' => 'Włącz informację o darmowej wysyłce',
                    'name' => 'free_shipping_enabled',
                    'type' => 'true_false',
                    'default_value' => 1,
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_free_shipping_threshold',
                    'label' => 'Próg darmowej wysyłki (zł)',
                    'name' => 'free_shipping_threshold',
                    'type' => 'number',
                    'default_value' => 200,
                    'min' => 0,
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_free_shipping_enabled',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_free_shipping_text',
                    'label' => 'Tekst darmowej wysyłki (użyj {amount} dla kwoty)',
                    'name' => 'free_shipping_text',
                    'type' => 'text',
                    'default_value' => 'Brakuje Ci tylko {amount} zł do darmowej wysyłki!',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_free_shipping_enabled',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_free_shipping_achieved_text',
                    'label' => 'Tekst gdy osiągnięto darmową wysyłkę',
                    'name' => 'free_shipping_achieved_text',
                    'type' => 'text',
                    'default_value' => '🎉 Gratulacje! Masz darmową wysyłkę!',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_free_shipping_enabled',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_free_shipping_link_text',
                    'label' => 'Tekst linku do akcesoriów',
                    'name' => 'free_shipping_link_text',
                    'type' => 'text',
                    'default_value' => 'Zobacz akcesoria',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_free_shipping_enabled',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_accessories_category_url',
                    'label' => 'Link do kategorii akcesoria',
                    'name' => 'accessories_category_url',
                    'type' => 'url',
                    'placeholder' => '/kategoria/akcesoria/',
                    'conditional_logic' => array(
                        array(
                            array(
                                'field' => 'field_free_shipping_enabled',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array(
                    'key' => 'field_checkout_button_text',
                    'label' => 'Tekst przycisku "Przejdź do kasy"',
                    'name' => 'checkout_button_text',
                    'type' => 'text',
                    'default_value' => 'Przejdź do kasy',
                ),
                array(
                    'key' => 'field_shipping_info',
                    'label' => 'Informacja o czasie wysyłki',
                    'name' => 'shipping_info',
                    'type' => 'textarea',
                    'rows' => 3,
                    'default_value' => '📦 Wysyłka w 24h',
                    'placeholder' => '📦 Wysyłka w 24h',
                ),
                array(
                    'key' => 'field_trust_badges',
                    'label' => 'Logotypy zaufania',
                    'name' => 'trust_badges',
                    'type' => 'repeater',
                    'button_label' => 'Dodaj logo',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_badge_image',
                            'label' => 'Logo',
                            'name' => 'badge_image',
                            'type' => 'image',
                            'return_format' => 'array',
                            'preview_size' => 'thumbnail',
                        ),
                        array(
                            'key' => 'field_badge_alt',
                            'label' => 'Tekst alternatywny',
                            'name' => 'badge_alt',
                            'type' => 'text',
                        ),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'template-parts-settings',
                    ),
                ),
            ),
            'menu_order' => 40,
        ));
    }
}

/**
 * Render slide-in cart HTML w footer
 */
add_action('wp_footer', 'render_slide_in_cart');
function render_slide_in_cart()
{
    if (is_admin()) {
        return;
    }

    // Pobierz ustawienia z ACF
    $cart_title = get_field('cart_title', 'option') ?: 'Zawartość koszyka';
    $free_shipping_enabled = get_field('free_shipping_enabled', 'option');
    $free_shipping_threshold = floatval(get_field('free_shipping_threshold', 'option') ?: 200);
    $free_shipping_text = get_field('free_shipping_text', 'option') ?: 'Brakuje Ci tylko {amount} zł do darmowej wysyłki!';
    $free_shipping_achieved = get_field('free_shipping_achieved_text', 'option') ?: '🎉 Gratulacje! Masz darmową wysyłkę!';
    $free_shipping_link_text = get_field('free_shipping_link_text', 'option') ?: 'Zobacz akcesoria';
    $accessories_url = get_field('accessories_category_url', 'option') ?: '/shop/';
    $checkout_button_text = get_field('checkout_button_text', 'option') ?: 'Przejdź do kasy';
    $shipping_info = get_field('shipping_info', 'option') ?: '📦 Wysyłka w 24h';
    $trust_badges = get_field('trust_badges', 'option');

    // Oblicz brakującą kwotę do darmowej wysyłki
    $cart_total = WC()->cart->get_subtotal();
    $amount_left = max(0, $free_shipping_threshold - $cart_total);
    $free_shipping_reached = $cart_total >= $free_shipping_threshold;

?>
    <div id="slide-in-cart-overlay" class="slide-in-cart-overlay"></div>
    <div id="slide-in-cart" class="slide-in-cart">
        <div class="slide-in-cart-header">
            <h3 class="slide-in-cart-title"><?php echo esc_html($cart_title); ?></h3>
            <button class="slide-in-cart-close" aria-label="Zamknij koszyk">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </div>

        <div class="slide-in-cart-content">
            <?php if (WC()->cart->is_empty()) : ?>
                <div class="slide-in-cart-empty">
                    <p>Twój koszyk jest pusty</p>
                </div>
            <?php else : ?>
                <div class="slide-in-cart-items">
                    <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                        $product = $cart_item['data'];
                        $product_id = $cart_item['product_id'];
                        $quantity = $cart_item['quantity'];
                        $product_name = $product->get_name();
                        $product_price = $product->get_price();
                        $product_image = $product->get_image('thumbnail');
                        $product_url = get_permalink($product_id);
                        $item_total = $product_price * $quantity;
                    ?>
                        <div class="slide-in-cart-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                            <div class="cart-item-image">
                                <a href="<?php echo esc_url($product_url); ?>">
                                    <?php echo $product_image; ?>
                                </a>
                            </div>
                            <div class="cart-item-details">
                                <a href="<?php echo esc_url($product_url); ?>" class="cart-item-name">
                                    <?php echo esc_html($product_name); ?>
                                </a>
                                <div class="cart-item-price">
                                    <?php echo wc_price($product_price); ?>
                                </div>
                                <div class="cart-item-quantity">
                                    <button class="qty-btn qty-minus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">−</button>
                                    <span class="qty-value"><?php echo esc_html($quantity); ?></span>
                                    <button class="qty-btn qty-plus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                </div>
                            </div>
                            <div class="cart-item-total">
                                <?php echo wc_price($item_total); ?>
                            </div>
                            <button class="cart-item-remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="Usuń">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="slide-in-cart-totals">
                    <div class="cart-total-row">
                        <span>Razem:</span>
                        <strong><?php echo WC()->cart->get_cart_subtotal(); ?></strong>
                    </div>
                </div>

                <?php if ($free_shipping_enabled) : ?>
                    <div class="slide-in-cart-free-shipping">
                        <?php if ($free_shipping_reached) : ?>
                            <div class="free-shipping-achieved">
                                <?php echo esc_html($free_shipping_achieved); ?>
                            </div>
                        <?php else : ?>
                            <div class="free-shipping-progress">
                                <p><?php echo str_replace('{amount}', number_format($amount_left, 0, ',', ' '), esc_html($free_shipping_text)); ?></p>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min(100, ($cart_total / $free_shipping_threshold) * 100); ?>%"></div>
                                </div>
                                <a href="<?php echo esc_url($accessories_url); ?>" class="free-shipping-link">
                                    <?php echo esc_html($free_shipping_link_text); ?> →
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <a href="<?php echo wc_get_checkout_url(); ?>" class="slide-in-cart-checkout-btn">
                    <?php echo esc_html($checkout_button_text); ?>
                </a>

                <div class="slide-in-cart-shipping-info">
                    <?php echo nl2br(esc_html($shipping_info)); ?>
                </div>

                <?php if ($trust_badges && is_array($trust_badges)) : ?>
                    <div class="slide-in-cart-trust-badges">
                        <?php foreach ($trust_badges as $badge) :
                            if (isset($badge['badge_image'])) :
                                $image = $badge['badge_image'];
                                $alt = isset($badge['badge_alt']) ? $badge['badge_alt'] : 'Trust badge';

                                // Debug - sprawdź strukturę
                                if (current_user_can('administrator')) {
                                    echo '<!-- Badge data: ' . print_r($badge, true) . ' -->';
                                }

                                // Sprawdź czy $image to array czy ID
                                if (is_array($image) && isset($image['url'])) {
                                    $image_url = $image['url'];
                                } elseif (is_numeric($image)) {
                                    $image_url = wp_get_attachment_url($image);
                                } else {
                                    $image_url = $image;
                                }
                        ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
                        <?php
                            endif;
                        endforeach;
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php
}

/**
 * Enqueue CSS i JavaScript dla slide-in cart
 */
add_action('wp_enqueue_scripts', 'enqueue_slide_in_cart_assets', 20); // Lower priority to load after other scripts
function enqueue_slide_in_cart_assets()
{
    // Load CSS file - lightweight, can load early
    wp_enqueue_style(
        'slide-in-cart-styles',
        get_stylesheet_directory_uri() . '/assets/css/slide-in-cart.css',
        array(),
        '1.0.2' // Cache bust - hide WC message immediately
    );

    // Defer JavaScript execution to improve page load
    wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            // Define functions in scope accessible to events
            function openSlideInCart() {
                $('#slide-in-cart-overlay').addClass('active');
                $('#slide-in-cart').addClass('active');
                $('body').css('overflow', 'hidden');
            }

            function closeSlideInCart() {
                $('#slide-in-cart-overlay').removeClass('active');
                $('#slide-in-cart').removeClass('active');
                $('body').css('overflow', '');
            }

            function updateCartQuantity(cartKey, action) {
                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'update_slide_cart_quantity',
                        cart_key: cartKey,
                        qty_action: action,
                        security: '" . wp_create_nonce('slide-cart-nonce') . "'
                    },
                    success: function(response) {
                        if (response.success && response.data.fragments) {
                            // Update fragments
                            $.each(response.data.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                            $(document.body).trigger('wc_fragments_refreshed');
                        }
                    }
                });
            }

            function removeCartItem(cartKey) {
                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'remove_slide_cart_item',
                        cart_key: cartKey,
                        security: '" . wp_create_nonce('slide-cart-nonce') . "'
                    },
                    success: function(response) {
                        if (response.success && response.data.fragments) {
                            // Update fragments
                            $.each(response.data.fragments, function(key, value) {
                                $(key).replaceWith(value);
                            });
                            $(document.body).trigger('wc_fragments_refreshed');
                        }
                    }
                });
            }

            // Listen for WooCommerce AJAX add to cart event
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
                openSlideInCart();
            });
            
            // Check for WooCommerce message ONLY on page load (for non-AJAX add to cart)
            // This runs once, not repeatedly
            if ($('.woocommerce-message').length > 0) {
                $('.woocommerce-message').hide();
                openSlideInCart();
            }

            // Close slide-in cart
            $(document).on('click', '.slide-in-cart-close, .slide-in-cart-overlay', function(e) {
                e.preventDefault();
                closeSlideInCart();
            });

            // Handle quantity changes
            $(document).on('click', '.qty-minus, .qty-plus', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                var \$btn = $(this);
                
                // Prevent double clicks
                if (\$btn.prop('disabled')) {
                    return;
                }
                
                \$btn.prop('disabled', true);
                
                var cartKey = \$btn.data('cart-key');
                var action = \$btn.hasClass('qty-minus') ? 'minus' : 'plus';
                
                updateCartQuantity(cartKey, action);
                
                // Re-enable after delay
                setTimeout(function() {
                    \$btn.prop('disabled', false);
                }, 1000);
            });

            // Handle item removal
            $(document).on('click', '.cart-item-remove', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                var \$btn = $(this);
                
                // Prevent double clicks
                if (\$btn.prop('disabled')) {
                    return;
                }
                
                \$btn.prop('disabled', true);
                
                var cartKey = \$btn.data('cart-key');
                removeCartItem(cartKey);
            });
        });
    ");
}

/**
 * AJAX: Update cart quantity
 */
add_action('wp_ajax_update_slide_cart_quantity', 'ajax_update_slide_cart_quantity');
add_action('wp_ajax_nopriv_update_slide_cart_quantity', 'ajax_update_slide_cart_quantity');
function ajax_update_slide_cart_quantity()
{
    check_ajax_referer('slide-cart-nonce', 'security');

    if (!isset($_POST['cart_key']) || !isset($_POST['qty_action'])) {
        wp_send_json_error('Missing parameters');
        return;
    }

    $cart_key = sanitize_text_field($_POST['cart_key']);
    $action = sanitize_text_field($_POST['qty_action']);

    $cart = WC()->cart->get_cart();

    if (isset($cart[$cart_key])) {
        $quantity = $cart[$cart_key]['quantity'];

        if ($action === 'plus') {
            $quantity++;
        } elseif ($action === 'minus') {
            $quantity = max(1, $quantity - 1);
        }

        WC()->cart->set_quantity($cart_key, $quantity);
        WC()->cart->calculate_totals();
    }

    // Return updated cart data
    WC()->cart->maybe_set_cart_cookies();

    wp_send_json_success(array(
        'cart_hash' => WC()->cart->get_cart_hash(),
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array())
    ));
}

/**
 * AJAX: Remove cart item
 */
add_action('wp_ajax_remove_slide_cart_item', 'ajax_remove_slide_cart_item');
add_action('wp_ajax_nopriv_remove_slide_cart_item', 'ajax_remove_slide_cart_item');
function ajax_remove_slide_cart_item()
{
    check_ajax_referer('slide-cart-nonce', 'security');

    if (!isset($_POST['cart_key'])) {
        wp_send_json_error('Missing cart_key');
        return;
    }

    $cart_key = sanitize_text_field($_POST['cart_key']);

    WC()->cart->remove_cart_item($cart_key);
    WC()->cart->calculate_totals();

    // Return updated cart data
    WC()->cart->maybe_set_cart_cookies();

    wp_send_json_success(array(
        'cart_hash' => WC()->cart->get_cart_hash(),
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array())
    ));
}

/**
 * Add slide-in cart to WooCommerce fragments for AJAX refresh
 */
add_filter('woocommerce_add_to_cart_fragments', 'slide_in_cart_fragments');
function slide_in_cart_fragments($fragments)
{
    ob_start();

    // Pobierz ustawienia z ACF
    $cart_title = get_field('cart_title', 'option') ?: 'Zawartość koszyka';
    $free_shipping_enabled = get_field('free_shipping_enabled', 'option');
    $free_shipping_threshold = floatval(get_field('free_shipping_threshold', 'option') ?: 200);
    $free_shipping_text = get_field('free_shipping_text', 'option') ?: 'Brakuje Ci tylko {amount} zł do darmowej wysyłki!';
    $free_shipping_achieved = get_field('free_shipping_achieved_text', 'option') ?: '🎉 Gratulacje! Masz darmową wysyłkę!';
    $free_shipping_link_text = get_field('free_shipping_link_text', 'option') ?: 'Zobacz akcesoria';
    $accessories_url = get_field('accessories_category_url', 'option') ?: '/shop/';
    $checkout_button_text = get_field('checkout_button_text', 'option') ?: 'Przejdź do kasy';
    $shipping_info = get_field('shipping_info', 'option') ?: '📦 Wysyłka w 24h';
    $trust_badges = get_field('trust_badges', 'option');

    // Oblicz brakującą kwotę do darmowej wysyłki
    $cart_total = WC()->cart->get_subtotal();
    $amount_left = max(0, $free_shipping_threshold - $cart_total);
    $free_shipping_reached = $cart_total >= $free_shipping_threshold;

?>
    <div class="slide-in-cart-content">
        <?php if (WC()->cart->is_empty()) : ?>
            <div class="slide-in-cart-empty">
                <p>Twój koszyk jest pusty</p>
            </div>
        <?php else : ?>
            <div class="slide-in-cart-items">
                <?php foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) :
                    $product = $cart_item['data'];
                    $product_id = $cart_item['product_id'];
                    $quantity = $cart_item['quantity'];
                    $product_name = $product->get_name();
                    $product_price = $product->get_price();
                    $product_image = $product->get_image('thumbnail');
                    $product_url = get_permalink($product_id);
                    $item_total = $product_price * $quantity;
                ?>
                    <div class="slide-in-cart-item" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                        <div class="cart-item-image">
                            <a href="<?php echo esc_url($product_url); ?>">
                                <?php echo $product_image; ?>
                            </a>
                        </div>
                        <div class="cart-item-details">
                            <a href="<?php echo esc_url($product_url); ?>" class="cart-item-name">
                                <?php echo esc_html($product_name); ?>
                            </a>
                            <div class="cart-item-price">
                                <?php echo wc_price($product_price); ?>
                            </div>
                            <div class="cart-item-quantity">
                                <button class="qty-btn qty-minus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">−</button>
                                <span class="qty-value"><?php echo esc_html($quantity); ?></span>
                                <button class="qty-btn qty-plus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                            </div>
                        </div>
                        <div class="cart-item-total">
                            <?php echo wc_price($item_total); ?>
                        </div>
                        <button class="cart-item-remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="Usuń">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="slide-in-cart-totals">
                <div class="cart-total-row">
                    <span>Razem:</span>
                    <strong><?php echo WC()->cart->get_cart_subtotal(); ?></strong>
                </div>
            </div>

            <?php if ($free_shipping_enabled) : ?>
                <div class="slide-in-cart-free-shipping">
                    <?php if ($free_shipping_reached) : ?>
                        <div class="free-shipping-achieved">
                            <?php echo esc_html($free_shipping_achieved); ?>
                        </div>
                    <?php else : ?>
                        <div class="free-shipping-progress">
                            <p><?php echo str_replace('{amount}', number_format($amount_left, 0, ',', ' '), esc_html($free_shipping_text)); ?></p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min(100, ($cart_total / $free_shipping_threshold) * 100); ?>%"></div>
                            </div>
                            <a href="<?php echo esc_url($accessories_url); ?>" class="free-shipping-link">
                                <?php echo esc_html($free_shipping_link_text); ?> →
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <a href="<?php echo wc_get_checkout_url(); ?>" class="slide-in-cart-checkout-btn">
                <?php echo esc_html($checkout_button_text); ?>
            </a>

            <div class="slide-in-cart-shipping-info">
                <?php echo nl2br(esc_html($shipping_info)); ?>
            </div>

            <?php if ($trust_badges && is_array($trust_badges)) : ?>
                <div class="slide-in-cart-trust-badges">
                    <?php foreach ($trust_badges as $badge) :
                        if (isset($badge['badge_image'])) :
                            $image = $badge['badge_image'];
                            $alt = isset($badge['badge_alt']) ? $badge['badge_alt'] : 'Trust badge';

                            // Sprawdź czy $image to array czy ID
                            if (is_array($image) && isset($image['url'])) {
                                $image_url = $image['url'];
                            } elseif (is_numeric($image)) {
                                $image_url = wp_get_attachment_url($image);
                            } else {
                                $image_url = $image;
                            }
                    ?>
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
                    <?php
                        endif;
                    endforeach;
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php

    $fragments['.slide-in-cart-content'] = ob_get_clean();

    return $fragments;
}
