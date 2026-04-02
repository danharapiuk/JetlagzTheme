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
 * Pobierz nazwę produktu do UI koszyka: ACF product_name -> fallback WooCommerce name.
 */
function jetlagz_get_cart_display_name($product, $product_id)
{
    if (!$product || !($product instanceof WC_Product)) {
        return '';
    }

    $candidate_ids = array();
    $candidate_ids[] = (int) $product_id;
    $candidate_ids[] = (int) $product->get_id();

    if (method_exists($product, 'get_parent_id')) {
        $parent_id = (int) $product->get_parent_id();
        if ($parent_id > 0) {
            $candidate_ids[] = $parent_id;
        }
    }

    $candidate_ids = array_values(array_unique(array_filter($candidate_ids)));

    foreach ($candidate_ids as $candidate_id) {
        $acf_product_name = '';

        if (function_exists('get_field')) {
            $acf_product_name = get_field('product_name', $candidate_id);
        }

        if (!is_string($acf_product_name) || trim($acf_product_name) === '') {
            $acf_product_name = get_post_meta($candidate_id, 'product_name', true);
        }

        if (is_string($acf_product_name) && trim($acf_product_name) !== '') {
            return trim($acf_product_name);
        }
    }

    return $product->get_name();
}

function jetlagz_get_slide_cart_price_html($cart_item, $context = 'unit')
{
    $context = $context === 'line' ? 'line' : 'unit';
    $is_gift = !empty($cart_item['jetlagz_is_gift']);

    if ($is_gift) {
        $price_data = jetlagz_get_slide_cart_gift_price_data($cart_item);
        $regular_amount = $context === 'line' ? (float) $price_data['base_line_total'] : (float) $price_data['base_unit_price'];
        $discounted_amount = $context === 'line' ? (float) $price_data['discounted_line_total'] : (float) $price_data['discounted_unit_price'];

        if (!empty($price_data['has_discount'])) {
            return '<div class="slide-cart-price-stack slide-cart-price-stack--discounted">'
                . '<span class="slide-cart-price-original">' . wp_kses_post(wc_price($regular_amount)) . '</span>'
                . '<span class="slide-cart-price-current">' . wp_kses_post(wc_price($discounted_amount)) . '</span>'
                . '</div>';
        }

        return '<div class="slide-cart-price-stack">' . wp_kses_post(wc_price($discounted_amount)) . '</div>';
    }

    if (function_exists('jetlagz_get_slide_cart_price_data')) {
        $price_data = jetlagz_get_slide_cart_price_data($cart_item);
        $regular_amount = $context === 'line' ? (float) $price_data['base_line_total'] : (float) $price_data['base_unit_price'];
        $discounted_amount = $context === 'line' ? (float) $price_data['discounted_line_total'] : (float) $price_data['discounted_unit_price'];
        $has_discount = !empty($price_data['has_discount']);

        if ($has_discount) {
            return '<div class="slide-cart-price-stack slide-cart-price-stack--discounted">'
                . '<span class="slide-cart-price-original">' . wp_kses_post(wc_price($regular_amount)) . '</span>'
                . '<span class="slide-cart-price-current">' . wp_kses_post(wc_price($discounted_amount)) . '</span>'
                . '</div>';
        }

        return '<div class="slide-cart-price-stack">' . wp_kses_post(wc_price($discounted_amount)) . '</div>';
    }

    $product = isset($cart_item['data']) ? $cart_item['data'] : null;
    $quantity = max(1, (int) ($cart_item['quantity'] ?? 1));
    $amount = $product instanceof WC_Product ? (float) $product->get_price() : 0;

    if ($context === 'line') {
        $amount *= $quantity;
    }

    return '<div class="slide-cart-price-stack">' . wp_kses_post(wc_price($amount)) . '</div>';
}

function jetlagz_get_slide_cart_gift_price_data($cart_item)
{
    if (function_exists('universal_get_gift_cart_item_price_data')) {
        return universal_get_gift_cart_item_price_data($cart_item);
    }

    $quantity = max(1, (int) ($cart_item['quantity'] ?? 1));
    $product_id = (int) ($cart_item['product_id'] ?? 0);
    $variation_id = (int) ($cart_item['variation_id'] ?? 0);
    $gift_rule = $cart_item['jetlagz_gift_rule'] ?? array();
    $source_product_id = $variation_id > 0 ? $variation_id : $product_id;
    $original_product = $source_product_id ? wc_get_product($source_product_id) : null;
    $regular_price = 0.0;

    if ($source_product_id > 0) {
        $raw_regular_price = get_post_meta($source_product_id, '_regular_price', true);
        if ($raw_regular_price !== '') {
            $regular_price = (float) $raw_regular_price;
        }
    }

    if ($regular_price <= 0 && $original_product) {
        $regular_price = (float) $original_product->get_regular_price();
    }

    if ($regular_price <= 0 && $original_product) {
        $regular_price = (float) $original_product->get_price();
    }

    $gift_price = (float) ($gift_rule['price'] ?? 0.10);

    return array(
        'has_discount' => $regular_price > $gift_price,
        'base_unit_price' => $regular_price > 0 ? $regular_price : $gift_price,
        'discounted_unit_price' => $gift_price,
        'base_line_total' => ($regular_price > 0 ? $regular_price : $gift_price) * $quantity,
        'discounted_line_total' => $gift_price * $quantity,
    );
}

function jetlagz_slide_cart_coupon_applies_to_product($coupon, $product, $product_id)
{
    if (!($coupon instanceof WC_Coupon) || !($product instanceof WC_Product)) {
        return false;
    }

    $product_id = (int) $product_id;
    $parent_product_id = method_exists($product, 'get_parent_id') ? (int) $product->get_parent_id() : 0;
    $matched_product_ids = array_values(array_unique(array_filter(array($product_id, $parent_product_id, (int) $product->get_id()))));
    $product_category_ids = wp_get_post_terms($parent_product_id > 0 ? $parent_product_id : $product_id, 'product_cat', array('fields' => 'ids'));

    if (is_wp_error($product_category_ids)) {
        $product_category_ids = array();
    }

    $product_category_ids = array_filter(array_map('absint', (array) $product_category_ids));
    $restricted_category_ids = array_filter(array_map('absint', (array) $coupon->get_product_categories()));
    $excluded_category_ids = array_filter(array_map('absint', (array) $coupon->get_excluded_product_categories()));
    $included_product_ids = array_filter(array_map('absint', (array) $coupon->get_product_ids()));
    $excluded_product_ids = array_filter(array_map('absint', (array) $coupon->get_excluded_product_ids()));

    if (!empty($included_product_ids) && !array_intersect($matched_product_ids, $included_product_ids)) {
        return false;
    }

    if (!empty($excluded_product_ids) && array_intersect($matched_product_ids, $excluded_product_ids)) {
        return false;
    }

    if (!empty($restricted_category_ids) && !array_intersect($product_category_ids, $restricted_category_ids)) {
        return false;
    }

    if (!empty($excluded_category_ids) && array_intersect($product_category_ids, $excluded_category_ids)) {
        return false;
    }

    if ($coupon->get_exclude_sale_items() && $product->is_on_sale()) {
        return false;
    }

    return true;
}

function jetlagz_get_slide_cart_price_data($cart_item)
{
    $quantity = max(1, (int) ($cart_item['quantity'] ?? 1));
    $product = isset($cart_item['data']) ? $cart_item['data'] : null;
    $product_id = (int) ($cart_item['product_id'] ?? 0);

    if (function_exists('universal_get_checkout_cart_item_price_data')) {
        $price_data = universal_get_checkout_cart_item_price_data($cart_item);

        if (!empty($price_data['has_discount'])) {
            return $price_data;
        }
    } else {
        $base_unit_price = $product instanceof WC_Product ? (float) $product->get_price() : 0.0;
        $price_data = array(
            'has_discount' => false,
            'base_unit_price' => $base_unit_price,
            'discounted_unit_price' => $base_unit_price,
            'base_line_total' => $base_unit_price * $quantity,
            'discounted_line_total' => $base_unit_price * $quantity,
        );
    }

    if (!($product instanceof WC_Product) || !function_exists('WC') || !WC()->cart) {
        return $price_data;
    }

    $base_unit_price = (float) $price_data['base_unit_price'];

    if ($base_unit_price <= 0) {
        return $price_data;
    }

    $applied_coupons = array_values(array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons()));

    if (function_exists('jetlagz_get_selected_coupon_code')) {
        $selected_coupon_code = wc_format_coupon_code((string) jetlagz_get_selected_coupon_code());

        if ($selected_coupon_code !== '' && !in_array($selected_coupon_code, $applied_coupons, true)) {
            $applied_coupons[] = $selected_coupon_code;
        }
    }

    if (empty($applied_coupons)) {
        return $price_data;
    }

    $unit_discount_total = 0.0;

    foreach ($applied_coupons as $coupon_code) {
        $coupon = new WC_Coupon($coupon_code);

        if (!$coupon || !$coupon->get_id()) {
            continue;
        }

        if (!jetlagz_slide_cart_coupon_applies_to_product($coupon, $product, $product_id)) {
            continue;
        }

        if (function_exists('jetlagz_get_coupon_discount_amount_for_product')) {
            $unit_discount_total += (float) jetlagz_get_coupon_discount_amount_for_product($coupon, $product, $base_unit_price);
        }
    }

    $unit_discount_total = min($base_unit_price, $unit_discount_total);

    if ($unit_discount_total <= 0) {
        return $price_data;
    }

    $discounted_unit_price = max(0, $base_unit_price - $unit_discount_total);

    return array(
        'has_discount' => true,
        'base_unit_price' => $base_unit_price,
        'discounted_unit_price' => $discounted_unit_price,
        'base_line_total' => $base_unit_price * $quantity,
        'discounted_line_total' => $discounted_unit_price * $quantity,
    );
}

function jetlagz_render_slide_cart_applied_coupons()
{
    if (!function_exists('WC') || !WC()->cart) {
        return;
    }

    $applied_coupons = array_values(array_map('wc_format_coupon_code', (array) WC()->cart->get_applied_coupons()));

    if (empty($applied_coupons)) {
        return;
    }
?>
    <div class="slide-in-cart-applied-coupons">
        <span class="slide-in-cart-applied-coupons-label"><?php echo esc_html__('Zastosowane kupony:', 'jetlagz-theme'); ?></span>
        <div class="slide-in-cart-applied-coupons-list">
            <?php foreach ($applied_coupons as $coupon_code) : ?>
                <div class="slide-in-cart-coupon-pill" data-coupon-code="<?php echo esc_attr($coupon_code); ?>">
                    <span class="slide-in-cart-coupon-code"><?php echo esc_html($coupon_code); ?></span>
                    <button type="button" class="slide-in-cart-coupon-remove" data-coupon-code="<?php echo esc_attr($coupon_code); ?>" aria-label="<?php echo esc_attr(sprintf(__('Usuń kupon %s', 'jetlagz-theme'), $coupon_code)); ?>">×</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
}

function jetlagz_get_slide_cart_totals_html()
{
    if (!function_exists('WC') || !WC()->cart) {
        return '';
    }

    $subtotal_amount = (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax();
    $discounted_amount = (float) WC()->cart->get_cart_contents_total() + (float) WC()->cart->get_cart_contents_tax();
    $has_discount = $discounted_amount + 0.0001 < $subtotal_amount;

    if ($has_discount) {
        return '<div class="slide-cart-price-stack slide-cart-price-stack--discounted slide-cart-total-stack">'
            . '<span class="slide-cart-price-original">' . wp_kses_post(wc_price($subtotal_amount)) . '</span>'
            . '<span class="slide-cart-price-current">' . wp_kses_post(wc_price($discounted_amount)) . '</span>'
            . '</div>';
    }

    return '<div class="slide-cart-price-stack slide-cart-total-stack">' . wp_kses_post(wc_price($discounted_amount)) . '</div>';
}

function jetlagz_get_slide_cart_coupon_total_html()
{
    if (!function_exists('WC') || !WC()->cart) {
        return '';
    }

    $coupon_discount_total = 0.0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        if (!empty($cart_item['jetlagz_is_gift'])) {
            continue;
        }

        $price_data = function_exists('jetlagz_get_slide_cart_price_data')
            ? jetlagz_get_slide_cart_price_data($cart_item)
            : array();

        if (empty($price_data)) {
            continue;
        }

        $line_discount = max(0, (float) ($price_data['base_line_total'] ?? 0) - (float) ($price_data['discounted_line_total'] ?? 0));
        $coupon_discount_total += $line_discount;
    }

    if ($coupon_discount_total <= 0) {
        $coupon_discount_total = (float) WC()->cart->get_discount_total() + (float) WC()->cart->get_discount_tax();
    }

    if ($coupon_discount_total <= 0) {
        return '';
    }

    return '<span class="slide-cart-coupon-total-amount">-' . wp_strip_all_tags(wc_price($coupon_discount_total)) . '</span>';
}

function jetlagz_render_slide_in_cart_content($free_shipping_enabled, $free_shipping_reached, $free_shipping_achieved, $free_shipping_text, $amount_left, $cart_total, $free_shipping_threshold, $promotions_url, $free_shipping_link_text, $checkout_button_text, $shipping_info, $trust_badges)
{
?>
    <div class="slide-in-cart-content h-full flex flex-col">
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
                    $product_name = jetlagz_get_cart_display_name($product, $product_id);
                    $product_image = $product->get_image('thumbnail');
                    $product_url = get_permalink($product_id);
                    $is_gift = !empty($cart_item['jetlagz_is_gift']);
                    $price_data = $is_gift ? jetlagz_get_slide_cart_gift_price_data($cart_item) : jetlagz_get_slide_cart_price_data($cart_item);
                    $discount_percentage = function_exists('universal_get_discount_percentage')
                        ? universal_get_discount_percentage($price_data['base_unit_price'] ?? 0, $price_data['discounted_unit_price'] ?? 0)
                        : 0;
                ?>
                    <div class="slide-in-cart-item<?php echo $is_gift ? ' is-gift-item' : ''; ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
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
                        <div class="cart-item-details">
                            <?php if ($is_gift) : ?>
                                <span class="cart-item-name">
                                    <span class="gift-badge">🎁 PREZENT</span>
                                    <?php echo esc_html($product_name); ?>
                                </span>
                            <?php else : ?>
                                <a href="<?php echo esc_url($product_url); ?>" class="cart-item-name">
                                    <?php echo esc_html($product_name); ?>
                                </a>
                            <?php endif; ?>
                            <div class="cart-item-price">
                                <?php echo wp_kses_post(jetlagz_get_slide_cart_price_html($cart_item, 'unit')); ?>
                            </div>
                            <?php if (!$is_gift) : ?>
                                <div class="cart-item-quantity">
                                    <button class="qty-btn qty-minus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">−</button>
                                    <span class="qty-value"><?php echo esc_html($quantity); ?></span>
                                    <button class="qty-btn qty-plus" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">+</button>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-total">
                            <?php echo wp_kses_post(jetlagz_get_slide_cart_price_html($cart_item, 'line')); ?>
                        </div>
                        <button class="cart-item-remove" data-cart-key="<?php echo esc_attr($cart_item_key); ?>" title="Usuń">×</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="slide-in-cart-totals">
                <?php $coupon_total_html = jetlagz_get_slide_cart_coupon_total_html(); ?>
                <?php if ($coupon_total_html !== '') : ?>
                    <div class="cart-total-row cart-total-row--discounts">
                        <span>Wartość kuponów:</span>
                        <strong><?php echo wp_kses_post($coupon_total_html); ?></strong>
                    </div>
                <?php endif; ?>
                <div class="cart-total-row">
                    <span>Razem:</span>
                    <strong><?php echo wp_kses_post(jetlagz_get_slide_cart_totals_html()); ?></strong>
                </div>
            </div>

            <?php jetlagz_render_slide_cart_applied_coupons(); ?>

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
                            <?php jetlagz_render_free_shipping_upsell_block($amount_left, $promotions_url, $free_shipping_link_text); ?>
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
                <div class="slide-in-cart-trust-badges mt-auto">
                    <?php foreach ($trust_badges as $badge) :
                        if (isset($badge['badge_image'])) :
                            $image = $badge['badge_image'];
                            $alt = isset($badge['badge_alt']) ? $badge['badge_alt'] : 'Trust badge';

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
                    'label' => 'Tekst linku do promocji',
                    'name' => 'free_shipping_link_text',
                    'type' => 'text',
                    'default_value' => 'Zobacz promocje',
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
                    'label' => 'Link do strony promocji',
                    'name' => 'accessories_category_url',
                    'type' => 'url',
                    'placeholder' => '/sklep/?on_sale=1',
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
 * Pobierz URL promocji dla linku upsell w koszyku.
 * Ignoruje stare zapisane linki do akcesoriów.
 */
function jetlagz_get_cart_promotions_url()
{
    $default_promotions_url = add_query_arg('on_sale', '1', wc_get_page_permalink('shop'));

    if (!function_exists('get_field')) {
        return $default_promotions_url;
    }

    $configured_url = trim((string) get_field('accessories_category_url', 'option'));

    if ($configured_url === '') {
        return $default_promotions_url;
    }

    $configured_path = wp_parse_url($configured_url, PHP_URL_PATH);
    $configured_path = is_string($configured_path) ? strtolower($configured_path) : '';

    if ($configured_path !== '' && strpos($configured_path, 'akcesoria') !== false) {
        return $default_promotions_url;
    }

    return $configured_url;
}

/**
 * Pobierz kandydatów do upsellu z tagu cart-upsell (posortowane po cenie ASC)
 */
function jetlagz_get_cart_upsell_candidates()
{
    $posts = get_posts(array(
        'post_type' => 'product',
        'posts_per_page' => 60,
        'post_status' => 'publish',
        'tax_query' => array(
            array(
                'taxonomy' => 'product_tag',
                'field'    => 'slug',
                'terms'    => 'cart-upsell',
            ),
        ),
        'meta_key' => '_price',
        'orderby' => 'meta_value_num',
        'order' => 'ASC',
    ));

    $candidates = array();

    foreach ($posts as $post) {
        $wc_product = wc_get_product($post->ID);

        if (!$wc_product || !$wc_product->is_visible()) {
            continue;
        }

        $price = (float) $wc_product->get_price();
        if ($price <= 0) {
            continue;
        }

        $candidates[] = array(
            'id' => $post->ID,
            'product' => $wc_product,
            'price' => $price,
        );
    }

    usort($candidates, function ($a, $b) {
        return $a['price'] <=> $b['price'];
    });

    return $candidates;
}

/**
 * Znajdź najtańszą parę produktów, której suma >= wymaganej kwoty
 */
function jetlagz_find_upsell_pair_for_amount($candidates, $amount_left, $exclude_id = 0)
{
    $best_pair_meeting = null;
    $best_pair_fallback = null;
    $best_overage = null;
    $best_half_distance = null;
    $count = count($candidates);
    $target_half = $amount_left / 2;

    for ($i = 0; $i < $count; $i++) {
        if ($exclude_id && (int) $candidates[$i]['id'] === (int) $exclude_id) {
            continue;
        }

        for ($j = $i + 1; $j < $count; $j++) {
            if ($exclude_id && (int) $candidates[$j]['id'] === (int) $exclude_id) {
                continue;
            }

            $sum = $candidates[$i]['price'] + $candidates[$j]['price'];
            $half_distance = abs($candidates[$i]['price'] - $target_half) + abs($candidates[$j]['price'] - $target_half);

            // Preferuj parę domykającą próg z najmniejszą nadwyżką, a potem najbliższą połowie.
            if ($sum >= $amount_left) {
                $overage = $sum - $amount_left;
                if (
                    $best_pair_meeting === null ||
                    $overage < $best_overage ||
                    ($overage === $best_overage && $half_distance < $best_half_distance)
                ) {
                    $best_overage = $overage;
                    $best_half_distance = $half_distance;
                    $best_pair_meeting = array($candidates[$i], $candidates[$j]);
                }
            }

            // Fallback: jeśli żadna para nie domyka progu, wybierz najbliższą połowie.
            if ($best_pair_fallback === null || $half_distance < $best_pair_fallback['half_distance']) {
                $best_pair_fallback = array(
                    'items' => array($candidates[$i], $candidates[$j]),
                    'half_distance' => $half_distance,
                    'sum' => $sum,
                );
            }
        }
    }

    if ($best_pair_meeting !== null) {
        return array(
            'items' => $best_pair_meeting,
            'meets_threshold' => true,
        );
    }

    if ($best_pair_fallback !== null) {
        return array(
            'items' => $best_pair_fallback['items'],
            'meets_threshold' => false,
        );
    }

    return null;
}

/**
 * Render pojedynczej karty upsell
 */
function jetlagz_render_cart_upsell_item($item)
{
    if (empty($item['product']) || !($item['product'] instanceof WC_Product)) {
        return;
    }

    $product = $item['product'];
    $product_id = (int) $item['id'];
    $product_url = get_permalink($product_id);
    $product_image = $product->get_image('thumbnail');
    $product_name = jetlagz_get_cart_display_name($product, $product_id);
    $product_price = $product->get_price_html();

    $can_add_direct = $product->is_type('simple') && $product->is_purchasable() && $product->is_in_stock();
?>
    <div class="upsell-product-item">
        <div class="upsell-product-main">
            <a href="<?php echo esc_url($product_url); ?>" class="upsell-product-link">
                <div class="upsell-product-image">
                    <?php echo $product_image; ?>
                </div>
                <div class="upsell-product-info">
                    <div class="upsell-product-name"><?php echo esc_html($product_name); ?></div>
                    <div class="upsell-product-price"><?php echo wp_kses_post($product_price); ?></div>
                </div>
            </a>

            <div class="upsell-product-actions<?php echo $can_add_direct ? '' : ' only-arrow'; ?>">
                <?php if ($can_add_direct) : ?>
                    <button type="button" class="upsell-add-btn" data-product-id="<?php echo esc_attr($product_id); ?>" aria-label="Dodaj do koszyka">+</button>
                <?php endif; ?>
                <a href="<?php echo esc_url($product_url); ?>" class="upsell-view-btn" aria-label="Zobacz produkt">→</a>
            </div>
        </div>
    </div>
<?php
}

/**
 * Render bloku upsell dla darmowej wysyłki
 */
function jetlagz_render_free_shipping_upsell_block($amount_left, $promotions_url, $free_shipping_link_text)
{
    $candidates = jetlagz_get_cart_upsell_candidates();

    if (empty($candidates)) {
        echo '<a href="' . esc_url($promotions_url) . '" class="free-shipping-link">' . esc_html($free_shipping_link_text) . ' →</a>';
        return;
    }

    $amount_left = (float) $amount_left;

    $single_satisfying = array_values(array_filter($candidates, function ($item) use ($amount_left) {
        return $item['price'] >= $amount_left;
    }));

    if ($amount_left < 70) {
        if (empty($single_satisfying)) {
            echo '<a href="' . esc_url($promotions_url) . '" class="free-shipping-link">' . esc_html($free_shipping_link_text) . ' →</a>';
            return;
        }

        $selected = array_slice($single_satisfying, 0, 3);

        echo '<div class="cart-upsell-products">';
        echo '<p class="upsell-title">Produkty, które same domykają darmową wysyłkę:</p>';
        echo '<div class="upsell-products-list">';
        foreach ($selected as $item) {
            jetlagz_render_cart_upsell_item($item);
        }
        echo '</div>';
        echo '</div>';
        return;
    }

    if (empty($single_satisfying)) {
        echo '<a href="' . esc_url($promotions_url) . '" class="free-shipping-link">' . esc_html($free_shipping_link_text) . ' →</a>';
        return;
    }

    $first_item = $single_satisfying[0];
    $pair_result = jetlagz_find_upsell_pair_for_amount($candidates, $amount_left, $first_item['id']);

    echo '<div class="cart-upsell-products">';

    echo '<div class="upsell-block upsell-block-single">';
    echo '<p class="upsell-title">1 produkt wystarczy do darmowej wysyłki:</p>';
    echo '<div class="upsell-products-list">';
    jetlagz_render_cart_upsell_item($first_item);
    echo '</div>';
    echo '</div>';

    if (!empty($pair_result['items'])) {
        echo '<div class="upsell-block upsell-block-pair">';
        if (!empty($pair_result['meets_threshold'])) {
            echo '<p class="upsell-title">Albo 2 produkty razem domkną próg:</p>';
        } else {
            echo '<p class="upsell-title">Najbliższy duet do domknięcia progu:</p>';
        }
        echo '<div class="upsell-products-list">';
        foreach ($pair_result['items'] as $item) {
            jetlagz_render_cart_upsell_item($item);
        }
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
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

    // Sprawdź czy ACF jest aktywne
    if (!function_exists('get_field')) {
        return;
    }

    // Pobierz ustawienia z ACF
    $cart_title = get_field('cart_title', 'option') ?: 'Zawartość koszyka';
    $free_shipping_enabled = get_field('free_shipping_enabled', 'option');
    $free_shipping_threshold = floatval(get_field('free_shipping_threshold', 'option') ?: 200);
    $free_shipping_text = get_field('free_shipping_text', 'option') ?: 'Brakuje Ci tylko {amount} zł do darmowej wysyłki!';
    $free_shipping_achieved = get_field('free_shipping_achieved_text', 'option') ?: '🎉 Gratulacje! Masz darmową wysyłkę!';
    $free_shipping_link_text = get_field('free_shipping_link_text', 'option') ?: 'Zobacz promocje';
    $promotions_url = jetlagz_get_cart_promotions_url();
    $checkout_button_text = get_field('checkout_button_text', 'option') ?: 'Przejdź do kasy';
    $shipping_info = get_field('shipping_info', 'option') ?: '📦 Wysyłka w 24h';
    $trust_badges = get_field('trust_badges', 'option');

    // Oblicz brakującą kwotę do darmowej wysyłki
    $cart_total = WC()->cart->get_subtotal();
    $amount_left = max(0, $free_shipping_threshold - $cart_total);
    $free_shipping_reached = $cart_total >= $free_shipping_threshold;

?>
    <div id="slide-in-cart-overlay" class="slide-in-cart-overlay"></div>
    <button type="button" id="slide-in-cart-floating-toggle" class="slide-in-cart-floating-toggle" aria-label="Rozwiń koszyk" aria-expanded="false">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M3 3H5L5.4 5M7 13H17L21 5H5.4M7 13L5.4 5M7 13L4.7 15.3C4.3 15.7 4.6 16.5 5.1 16.5H17M17 13V17C17 18.1 16.1 19 15 19H9C7.9 19 7 18.1 7 17V13M17 13H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
    </button>
    <div id="slide-in-cart" class="slide-in-cart">
        <div class="slide-in-cart-header">
            <h3 class="slide-in-cart-title"><?php echo esc_html($cart_title); ?></h3>
            <button type="button" class="slide-in-cart-toggle" aria-label="Rozwiń koszyk" aria-expanded="false">
                <span class="slide-in-cart-toggle-icon" aria-hidden="true">&gt;</span>
            </button>
        </div>

        <?php jetlagz_render_slide_in_cart_content($free_shipping_enabled, $free_shipping_reached, $free_shipping_achieved, $free_shipping_text, $amount_left, $cart_total, $free_shipping_threshold, $promotions_url, $free_shipping_link_text, $checkout_button_text, $shipping_info, $trust_badges); ?>
    </div>
<?php
}

/**
 * Enqueue CSS i JavaScript dla slide-in cart
 */
add_action('wp_enqueue_scripts', 'enqueue_slide_in_cart_assets', 20); // Lower priority to load after other scripts
function enqueue_slide_in_cart_assets()
{
    $slide_cart_close_icon_url = esc_url(get_stylesheet_directory_uri() . '/assets/images/close.svg');

    // Load CSS file - lightweight, can load early
    wp_enqueue_style(
        'slide-in-cart-styles',
        get_stylesheet_directory_uri() . '/assets/css/slide-in-cart.css',
        array('storefront-style'),
        '1.0.4' // Cache bust - toggle icon/position update
    );

    // Defer JavaScript execution to improve page load
    wp_add_inline_script('jquery', "
        jQuery(document).ready(function($) {
            var \$slideInCart = $('#slide-in-cart');
            var \$slideInCartOverlay = $('#slide-in-cart-overlay');
            var \$slideInCartToggle = \$slideInCart.find('.slide-in-cart-toggle');
            var \$slideInCartToggleIcon = \$slideInCartToggle.find('.slide-in-cart-toggle-icon');
            var \$slideInCartFloatingToggle = $('#slide-in-cart-floating-toggle');
            var \$body = $('body');
            var slideCartCollapsedKey = 'jetlagzSlideCartCollapsed';
            var slideCartCloseIconMarkup = '<img src=\'{$slide_cart_close_icon_url}\' alt=\'\' class=\'slide-in-cart-close-icon\' />';

            function readCollapsedPreference() {
                try {
                    return window.sessionStorage.getItem(slideCartCollapsedKey) === '1';
                } catch (error) {
                    return false;
                }
            }

            function writeCollapsedPreference(isCollapsed) {
                try {
                    if (isCollapsed) {
                        window.sessionStorage.setItem(slideCartCollapsedKey, '1');
                    } else {
                        window.sessionStorage.removeItem(slideCartCollapsedKey);
                    }
                } catch (error) {
                    // Ignore storage access errors (private mode, blocked storage).
                }
            }

            function syncCollapsedStateClass() {
                var isCollapsed = \$slideInCart.hasClass('active') && \$slideInCart.hasClass('collapsed');
                var isOpen = \$slideInCart.hasClass('active') && !\$slideInCart.hasClass('collapsed');
                var hasItems = \$slideInCart.find('.slide-in-cart-item').length > 0;

                \$body.toggleClass('slide-in-cart-is-collapsed', isCollapsed);
                \$body.toggleClass('slide-in-cart-is-open', isOpen);
                \$body.toggleClass('slide-in-cart-has-items', hasItems);
                \$slideInCartFloatingToggle.attr('aria-expanded', isCollapsed ? 'false' : 'true');
                writeCollapsedPreference(isCollapsed);
            }

            function updateSlideInCartToggle() {
                var isExpanded = \$slideInCart.hasClass('active') && !\$slideInCart.hasClass('collapsed');

                \$slideInCartToggle.attr('aria-label', isExpanded ? 'Zwiń koszyk' : 'Rozwiń koszyk');
                \$slideInCartToggle.attr('aria-expanded', isExpanded ? 'true' : 'false');
                if (isExpanded) {
                    \$slideInCartToggleIcon.html(slideCartCloseIconMarkup);
                } else {
                    \$slideInCartToggleIcon.text('<');
                }
                syncCollapsedStateClass();
            }

            // Define functions in scope accessible to events
            function openSlideInCart() {
                \$slideInCartOverlay.addClass('active');
                \$slideInCart.addClass('active').removeClass('collapsed');
                \$body.css('overflow', 'hidden');
                updateSlideInCartToggle();
            }

            function collapseSlideInCart() {
                \$slideInCartOverlay.removeClass('active');
                if (\$slideInCart.hasClass('active')) {
                    \$slideInCart.addClass('collapsed');
                }
                \$body.css('overflow', '');
                updateSlideInCartToggle();
            }

            function toggleSlideInCart() {
                if (!\$slideInCart.hasClass('active') || \$slideInCart.hasClass('collapsed')) {
                    openSlideInCart();
                    return;
                }

                collapseSlideInCart();
            }

            function debugUpsellState(context) {
                var \$content = $('.slide-in-cart-content');
                var \$progress = \$content.find('.free-shipping-progress');
                var \$upsells = \$content.find('.cart-upsell-products .upsell-product-item');
                var \$freeShippingWrapper = \$content.find('.slide-in-cart-free-shipping');

                console.group('[Slide Cart Debug] ' + context);
                console.log('content exists:', \$content.length > 0);
                console.log('free-shipping wrapper exists:', \$freeShippingWrapper.length > 0);
                console.log('free-shipping-progress exists:', \$progress.length > 0);
                console.log('upsell items count:', \$upsells.length);
                if (\$upsells.length > 0) {
                    var names = [];
                    \$upsells.each(function() {
                        var name = $(this).find('.upsell-product-name').text().trim();
                        if (name) {
                            names.push(name);
                        }
                    });
                    console.log('upsell names:', names);
                }
                console.groupEnd();
            }

            function debugFragmentPayload(fragments, source) {
                if (!fragments) {
                    console.warn('[Slide Cart Debug] ' + source + ': missing fragments payload');
                    return;
                }

                var keys = Object.keys(fragments);
                var contentHtml = fragments['.slide-in-cart-content'] || '';

                console.group('[Slide Cart Debug] ' + source + ' fragments payload');
                console.log('fragment keys:', keys);
                console.log('has .slide-in-cart-content fragment:', !!fragments['.slide-in-cart-content']);
                console.log('fragment contains free-shipping-progress:', contentHtml.indexOf('free-shipping-progress') !== -1);
                console.log('fragment contains cart-upsell-products:', contentHtml.indexOf('cart-upsell-products') !== -1);
                console.log('fragment contains free-shipping-link fallback:', contentHtml.indexOf('free-shipping-link') !== -1);
                console.groupEnd();
            }

            function applyFragmentsWithDebug(fragments, source) {
                debugFragmentPayload(fragments, source);
                debugUpsellState(source + ' | before replaceWith');

                $.each(fragments, function(key, value) {
                    $(key).replaceWith(value);
                });

                debugUpsellState(source + ' | after replaceWith');
                $(document.body).trigger('wc_fragments_refreshed');

                // Deferred check - catches delayed fragment updates from other scripts.
                setTimeout(function() {
                    debugUpsellState(source + ' | delayed +100ms');
                }, 100);
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
                            applyFragmentsWithDebug(response.data.fragments, 'updateCartQuantity');
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
                            applyFragmentsWithDebug(response.data.fragments, 'removeCartItem');
                        }
                    }
                });
            }

            function removeSlideCartCoupon(couponCode) {
                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'remove_slide_cart_coupon',
                        coupon_code: couponCode,
                        security: '" . wp_create_nonce('slide-cart-nonce') . "'
                    },
                    success: function(response) {
                        if (response.success && response.data.fragments) {
                            applyFragmentsWithDebug(response.data.fragments, 'removeSlideCartCoupon');
                        }
                    }
                });
            }

            // Listen for WooCommerce AJAX add to cart event
            $(document.body).on('added_to_cart', function(event, fragments, cart_hash, button) {
                debugFragmentPayload(fragments, 'added_to_cart event');

                // Nie otwieraj slide-in cart dla przycisków InPost Pay
                // Sprawdź najpierw globalną flagę ustawianą przez theme.js
                if (window.inpostClickActive || window.inpostBlockNavigation) {
                    console.log('[Slide Cart] Skipping - InPost click active');
                    return;
                }
                
                var isInpostButton = false;
                
                if (button) {
                    // Sprawdź czy button to jQuery object
                    var \$btn = button.jquery ? button : $(button);
                    var btnEl = \$btn.length ? \$btn[0] : button;
                    
                    // Sprawdź różne warianty przycisku InPost
                    isInpostButton = (
                        \$btn.hasClass('inpost-pay-button') ||
                        \$btn.hasClass('inpost-izi-button') ||
                        \$btn.closest('[class*=\"inpost\"]').length > 0 ||
                        \$btn.is('inpost-izi-button') ||
                        (btnEl && btnEl.tagName && btnEl.tagName.toLowerCase() === 'inpost-izi-button') ||
                        (btnEl && btnEl.closest && btnEl.closest('[class*=\"inpost\"]'))
                    );
                }
                
                // Sprawdź też czy widget InPost jest aktualnie otwarty
                var inpostWidgetOpen = $('inpost-izi-widget:visible, .inpost-pay-widget:visible, [class*=\"inpost-izi\"]:visible').length > 0 ||
                                       document.querySelector('inpost-izi-widget[style*=\"display: block\"], inpost-izi-widget[style*=\"visibility: visible\"]');
                
                if (isInpostButton || inpostWidgetOpen) {
                    console.log('[Slide Cart] Skipping - InPost button/widget detected');
                    return;
                }
                openSlideInCart();
                debugUpsellState('added_to_cart | after openSlideInCart');
            });
            
            // Check for WooCommerce message ONLY on page load (for non-AJAX add to cart)
            // This runs once, not repeatedly
            if ($('.woocommerce-message').length > 0) {
                $('.woocommerce-message').hide();
                openSlideInCart();
                debugUpsellState('page load woocommerce-message | after openSlideInCart');
            }

            $(document.body).on('wc_fragments_refreshed', function() {
                debugUpsellState('wc_fragments_refreshed event');
            });

            var contentNode = document.querySelector('.slide-in-cart-content');
            if (contentNode && typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    var changed = mutations.some(function(mutation) {
                        return mutation.type === 'childList' && (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0);
                    });

                    if (changed) {
                        debugUpsellState('MutationObserver childList change');
                    }
                });

                observer.observe(contentNode, { childList: true, subtree: true });
                console.log('[Slide Cart Debug] MutationObserver attached to .slide-in-cart-content');
            }

            debugUpsellState('initial state after document ready');
            updateSlideInCartToggle();

            if (readCollapsedPreference()) {
                \$slideInCart.addClass('active collapsed');
                \$slideInCartOverlay.removeClass('active');
                \$body.css('overflow', '');
                updateSlideInCartToggle();
            }

            // Toggle / collapse slide-in cart
            $(document).on('click', '.slide-in-cart-toggle', function(e) {
                e.preventDefault();
                toggleSlideInCart();
            });

            $(document).on('click', '#slide-in-cart-floating-toggle', function(e) {
                e.preventDefault();
                openSlideInCart();
            });

            // Collapse by clicking overlay
            $(document).on('click', '.slide-in-cart-overlay', function(e) {
                e.preventDefault();
                collapseSlideInCart();
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

            $(document).on('click', '.slide-in-cart-coupon-remove', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var \$btn = $(this);

                if (\$btn.prop('disabled')) {
                    return;
                }

                \$btn.prop('disabled', true);

                var couponCode = ((\$btn.data('coupon-code') || '') + '').trim();

                if (!couponCode) {
                    \$btn.prop('disabled', false);
                    return;
                }

                removeSlideCartCoupon(couponCode);

                setTimeout(function() {
                    \$btn.prop('disabled', false);
                }, 1000);
            });

            // Add upsell product directly from slide-in cart
            $(document).on('click', '.upsell-add-btn', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                var \$btn = $(this);

                if (\$btn.prop('disabled')) {
                    return;
                }

                var productId = \$btn.data('product-id');
                if (!productId) {
                    return;
                }

                \$btn.prop('disabled', true).text('...');

                $.post(
                    wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'),
                    {
                        product_id: productId,
                        quantity: 1
                    },
                    function(response) {
                        if (response && !response.error) {
                            if (response.fragments) {
                                applyFragmentsWithDebug(response.fragments, 'upsellAddToCart');
                            }

                            if (response.fragments && response.cart_hash) {
                                $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, \$btn]);
                            }

                            openSlideInCart();
                        } else if (response && response.product_url) {
                            window.location.href = response.product_url;
                            return;
                        }

                        \$btn.prop('disabled', false).text('+');
                    }
                ).fail(function() {
                    \$btn.prop('disabled', false).text('+');
                });
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

add_action('wp_ajax_remove_slide_cart_coupon', 'ajax_remove_slide_cart_coupon');
add_action('wp_ajax_nopriv_remove_slide_cart_coupon', 'ajax_remove_slide_cart_coupon');
function ajax_remove_slide_cart_coupon()
{
    check_ajax_referer('slide-cart-nonce', 'security');

    $coupon_code = isset($_POST['coupon_code']) ? wc_format_coupon_code(wc_clean(wp_unslash($_POST['coupon_code']))) : '';

    if ($coupon_code === '') {
        wp_send_json_error('Missing coupon_code');
        return;
    }

    WC()->cart->remove_coupon($coupon_code);
    WC()->cart->calculate_totals();
    WC()->cart->maybe_set_cart_cookies();
    wc_clear_notices();

    if (function_exists('jetlagz_get_selected_coupon_code') && function_exists('jetlagz_clear_selected_coupon_code')) {
        $selected_coupon_code = jetlagz_get_selected_coupon_code();

        if ($selected_coupon_code !== '' && wc_format_coupon_code($selected_coupon_code) === $coupon_code) {
            jetlagz_clear_selected_coupon_code();
        }
    }

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
    // Sprawdź czy ACF jest aktywne
    if (!function_exists('get_field')) {
        return $fragments;
    }

    ob_start();

    // Pobierz ustawienia z ACF
    $cart_title = get_field('cart_title', 'option') ?: 'Zawartość koszyka';
    $free_shipping_enabled = get_field('free_shipping_enabled', 'option');
    $free_shipping_threshold = floatval(get_field('free_shipping_threshold', 'option') ?: 200);
    $free_shipping_text = get_field('free_shipping_text', 'option') ?: 'Brakuje Ci tylko {amount} zł do darmowej wysyłki!';
    $free_shipping_achieved = get_field('free_shipping_achieved_text', 'option') ?: '🎉 Gratulacje! Masz darmową wysyłkę!';
    $free_shipping_link_text = get_field('free_shipping_link_text', 'option') ?: 'Zobacz promocje';
    $promotions_url = jetlagz_get_cart_promotions_url();
    $checkout_button_text = get_field('checkout_button_text', 'option') ?: 'Przejdź do kasy';
    $shipping_info = get_field('shipping_info', 'option') ?: '📦 Wysyłka w 24h';
    $trust_badges = get_field('trust_badges', 'option');

    // Oblicz brakującą kwotę do darmowej wysyłki
    $cart_total = WC()->cart->get_subtotal();
    $amount_left = max(0, $free_shipping_threshold - $cart_total);
    $free_shipping_reached = $cart_total >= $free_shipping_threshold;

?>
    <?php jetlagz_render_slide_in_cart_content($free_shipping_enabled, $free_shipping_reached, $free_shipping_achieved, $free_shipping_text, $amount_left, $cart_total, $free_shipping_threshold, $promotions_url, $free_shipping_link_text, $checkout_button_text, $shipping_info, $trust_badges); ?>
    <?php

    $fragments['.slide-in-cart-content'] = ob_get_clean();

    // Add floating toggle cart count fragment
    ob_start();
    ?>
    <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
<?php
    $fragments['.slide-in-cart-floating-toggle .cart-count'] = ob_get_clean();

    return $fragments;
}
