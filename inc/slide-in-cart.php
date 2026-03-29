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
function jetlagz_render_free_shipping_upsell_block($amount_left, $accessories_url, $free_shipping_link_text)
{
    $candidates = jetlagz_get_cart_upsell_candidates();

    if (empty($candidates)) {
        echo '<a href="' . esc_url($accessories_url) . '" class="free-shipping-link">' . esc_html($free_shipping_link_text) . ' →</a>';
        return;
    }

    $amount_left = (float) $amount_left;

    $single_satisfying = array_values(array_filter($candidates, function ($item) use ($amount_left) {
        return $item['price'] >= $amount_left;
    }));

    if ($amount_left < 70) {
        if (empty($single_satisfying)) {
            echo '<a href="' . esc_url($accessories_url) . '" class="free-shipping-link">' . esc_html($free_shipping_link_text) . ' →</a>';
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
        echo '<a href="' . esc_url($accessories_url) . '" class="free-shipping-link">' . esc_html($free_shipping_link_text) . ' →</a>';
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
                        $product_price = $product->get_price();
                        $product_image = $product->get_image('thumbnail');
                        $product_url = get_permalink($product_id);
                        $item_total = $product_price * $quantity;
                        $is_gift = !empty($cart_item['jetlagz_is_gift']);
                        $gift_rule = $is_gift ? ($cart_item['jetlagz_gift_rule'] ?? []) : [];
                    ?>
                        <div class="slide-in-cart-item<?php echo $is_gift ? ' is-gift-item' : ''; ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                            <div class="cart-item-image">
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
                                    <?php if ($is_gift) :
                                        $original_product = wc_get_product($product_id);
                                        $regular_price = $original_product ? $original_product->get_regular_price() : 0;
                                        if (empty($regular_price) && $original_product) {
                                            $regular_price = $original_product->get_price();
                                        }
                                        $gift_price = floatval($gift_rule['price'] ?? 0.10);
                                        if ($regular_price && floatval($regular_price) > $gift_price) : ?>
                                            <del class="gift-original-price"><?php echo wc_price($regular_price); ?></del>
                                            <ins class="gift-price"><?php echo wc_price($gift_price); ?></ins>
                                        <?php else : ?>
                                            <?php echo wc_price($gift_price); ?>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <?php echo wc_price($product_price); ?>
                                    <?php endif; ?>
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
                                <?php if ($is_gift) :
                                    $original_product = isset($original_product) ? $original_product : wc_get_product($product_id);
                                    $regular_price = $original_product ? $original_product->get_regular_price() : 0;
                                    if (empty($regular_price) && $original_product) {
                                        $regular_price = $original_product->get_price();
                                    }
                                    $gift_price = floatval($gift_rule['price'] ?? 0.10);
                                    if ($regular_price && floatval($regular_price) > $gift_price) : ?>
                                        <del class="gift-original-price"><?php echo wc_price($regular_price); ?></del>
                                        <ins class="gift-price"><?php echo wc_price($gift_price); ?></ins>
                                    <?php else : ?>
                                        <?php echo wc_price($gift_price); ?>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <?php echo wc_price($item_total); ?>
                                <?php endif; ?>
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
                                <?php jetlagz_render_free_shipping_upsell_block($amount_left, $accessories_url, $free_shipping_link_text); ?>
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
                    $product_price = $product->get_price();
                    $product_image = $product->get_image('thumbnail');
                    $product_url = get_permalink($product_id);
                    $item_total = $product_price * $quantity;
                    $is_gift = !empty($cart_item['jetlagz_is_gift']);
                    $gift_rule = $is_gift ? ($cart_item['jetlagz_gift_rule'] ?? []) : [];
                ?>
                    <div class="slide-in-cart-item<?php echo $is_gift ? ' is-gift-item' : ''; ?>" data-cart-key="<?php echo esc_attr($cart_item_key); ?>">
                        <div class="cart-item-image">
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
                                <?php if ($is_gift) :
                                    $original_product = wc_get_product($product_id);
                                    $regular_price = $original_product ? $original_product->get_regular_price() : 0;
                                    if (empty($regular_price) && $original_product) {
                                        $regular_price = $original_product->get_price();
                                    }
                                    $gift_price = floatval($gift_rule['price'] ?? 0.10);
                                    if ($regular_price && floatval($regular_price) > $gift_price) : ?>
                                        <del class="gift-original-price"><?php echo wc_price($regular_price); ?></del>
                                        <ins class="gift-price"><?php echo wc_price($gift_price); ?></ins>
                                    <?php else : ?>
                                        <?php echo wc_price($gift_price); ?>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <?php echo wc_price($product_price); ?>
                                <?php endif; ?>
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
                            <?php if ($is_gift) :
                                $original_product = isset($original_product) ? $original_product : wc_get_product($product_id);
                                $regular_price = $original_product ? $original_product->get_regular_price() : 0;
                                if (empty($regular_price) && $original_product) {
                                    $regular_price = $original_product->get_price();
                                }
                                $gift_price = floatval($gift_rule['price'] ?? 0.10);
                                if ($regular_price && floatval($regular_price) > $gift_price) : ?>
                                    <del class="gift-original-price"><?php echo wc_price($regular_price); ?></del>
                                    <ins class="gift-price"><?php echo wc_price($gift_price); ?></ins>
                                <?php else : ?>
                                    <?php echo wc_price($gift_price); ?>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php echo wc_price($item_total); ?>
                            <?php endif; ?>
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
                            <?php jetlagz_render_free_shipping_upsell_block($amount_left, $accessories_url, $free_shipping_link_text); ?>
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

    // Add floating toggle cart count fragment
    ob_start();
    ?>
    <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
<?php
    $fragments['.slide-in-cart-floating-toggle .cart-count'] = ob_get_clean();

    return $fragments;
}
