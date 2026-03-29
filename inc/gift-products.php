<?php

/**
 * Gift Products System
 *
 * Automatyczne dodawanie prezentu do koszyka po przekroczeniu progu cenowego.
 * Panel admina: WooCommerce → Prezenty (progi cenowe)
 */

if (!defined('ABSPATH')) {
    exit;
}

// ============================================================================
// ADMIN PANEL
// ============================================================================

/**
 * Dodaj stronę w menu WooCommerce
 */
function jetlagz_gift_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        'Prezenty (progi cenowe)',
        '🎁 Prezenty',
        'manage_options',
        'jetlagz-gift-products',
        'jetlagz_gift_admin_page'
    );
}
add_action('admin_menu', 'jetlagz_gift_admin_menu');

/**
 * Rejestracja ustawień
 */
function jetlagz_gift_admin_init()
{
    register_setting('jetlagz_gift_settings', 'jetlagz_gift_rules', 'jetlagz_gift_validate_rules');
}
add_action('admin_init', 'jetlagz_gift_admin_init');

/**
 * Enqueue admin scripts dla WC product search
 */
function jetlagz_gift_admin_scripts($hook)
{
    if ($hook !== 'woocommerce_page_jetlagz-gift-products') {
        return;
    }

    wp_enqueue_style('woocommerce_admin_styles');
    wp_enqueue_script('wc-enhanced-select');
    wp_enqueue_style('jetlagz-gift-admin', get_stylesheet_directory_uri() . '/assets/css/components/gift-admin.css', [], '1.0.0');
}
add_action('admin_enqueue_scripts', 'jetlagz_gift_admin_scripts');

/**
 * Walidacja reguł prezentów
 */
function jetlagz_gift_validate_rules($input)
{
    $output = [];

    if (!is_array($input)) {
        return $output;
    }

    foreach ($input as $rule) {
        if (empty($rule['product_id']) || empty($rule['threshold'])) {
            continue;
        }

        $output[] = [
            'enabled'    => !empty($rule['enabled']) ? 1 : 0,
            'threshold'  => floatval($rule['threshold']),
            'product_id' => intval($rule['product_id']),
            'price'      => floatval($rule['price'] ?? 0.10),
            'message'    => sanitize_textarea_field($rule['message'] ?? ''),
        ];
    }

    // Sort by threshold ascending
    usort($output, function ($a, $b) {
        return $a['threshold'] <=> $b['threshold'];
    });

    return $output;
}

/**
 * Strona admina
 */
function jetlagz_gift_admin_page()
{
    $rules = get_option('jetlagz_gift_rules', []);
    if (!is_array($rules)) {
        $rules = [];
    }
?>
    <div class="wrap jetlagz-gift-admin">
        <h1>🎁 Prezenty – progi cenowe</h1>
        <p>Konfiguruj automatyczne prezenty dodawane do koszyka po przekroczeniu określonego progu cenowego.</p>

        <form method="post" action="options.php">
            <?php settings_fields('jetlagz_gift_settings'); ?>

            <table class="widefat gift-rules-table" id="gift-rules-table">
                <thead>
                    <tr>
                        <th class="col-enabled">Aktywna</th>
                        <th class="col-threshold">Próg cenowy (zł)</th>
                        <th class="col-product">Produkt-prezent</th>
                        <th class="col-price">Cena prezentu (zł)</th>
                        <th class="col-message">Wiadomość (opcjonalna)</th>
                        <th class="col-actions">Akcje</th>
                    </tr>
                </thead>
                <tbody id="gift-rules-body">
                    <?php if (!empty($rules)): ?>
                        <?php foreach ($rules as $i => $rule): ?>
                            <tr class="gift-rule-row" data-index="<?php echo $i; ?>">
                                <td class="col-enabled">
                                    <input type="checkbox"
                                        name="jetlagz_gift_rules[<?php echo $i; ?>][enabled]"
                                        value="1"
                                        <?php checked($rule['enabled'] ?? 0, 1); ?> />
                                </td>
                                <td class="col-threshold">
                                    <input type="number"
                                        name="jetlagz_gift_rules[<?php echo $i; ?>][threshold]"
                                        value="<?php echo esc_attr($rule['threshold']); ?>"
                                        min="0" step="0.01" class="small-text" required />
                                </td>
                                <td class="col-product">
                                    <select name="jetlagz_gift_rules[<?php echo $i; ?>][product_id]"
                                        class="wc-product-search"
                                        data-placeholder="Szukaj produktu..."
                                        data-action="woocommerce_json_search_products"
                                        data-allow_clear="true"
                                        style="width: 100%;">
                                        <?php if (!empty($rule['product_id'])): ?>
                                            <?php $product = wc_get_product($rule['product_id']); ?>
                                            <?php if ($product): ?>
                                                <option value="<?php echo esc_attr($rule['product_id']); ?>" selected>
                                                    <?php echo esc_html($product->get_name()); ?> (#<?php echo $rule['product_id']; ?>)
                                                </option>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </select>
                                </td>
                                <td class="col-price">
                                    <input type="number"
                                        name="jetlagz_gift_rules[<?php echo $i; ?>][price]"
                                        value="<?php echo esc_attr($rule['price'] ?? 0.10); ?>"
                                        min="0" step="0.01" class="small-text" />
                                </td>
                                <td class="col-message">
                                    <textarea name="jetlagz_gift_rules[<?php echo $i; ?>][message]"
                                        rows="2"
                                        placeholder="Np: Przekroczyłaś próg darmowego prezentu!"><?php echo esc_textarea($rule['message'] ?? ''); ?></textarea>
                                </td>
                                <td class="col-actions">
                                    <button type="button" class="button remove-gift-rule">✕ Usuń</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <p>
                <button type="button" class="button button-secondary" id="add-gift-rule">+ Dodaj regułę</button>
            </p>

            <?php submit_button('Zapisz reguły prezentów'); ?>
        </form>

        <div class="gift-info-box">
            <h3>ℹ️ Jak to działa?</h3>
            <ul>
                <li><strong>Próg cenowy</strong> – wartość koszyka (bez prezentu), po przekroczeniu której prezent jest dodawany automatycznie.</li>
                <li><strong>Produkt-prezent</strong> – dowolny produkt z WooCommerce. Zostanie dodany do koszyka w ilości 1 szt.</li>
                <li><strong>Cena prezentu</strong> – cena wyświetlana w koszyku (np. 0,10 zł). Oryginalna cena produktu zostanie pokazana jako przekreślona.</li>
                <li><strong>Wiadomość</strong> – opcjonalny tekst powiadomienia. Jeśli pusty, zostanie użyta domyślna wiadomość.</li>
                <li>Jeśli klient usunie produkty z koszyka i spadnie poniżej progu, prezent zostanie automatycznie usunięty.</li>
                <li>Klient może też ręcznie usunąć prezent z koszyka.</li>
                <li>Można zdefiniować wiele reguł z różnymi progami (np. 150 zł → drobny gadżet, 300 zł → większy prezent).</li>
            </ul>
        </div>
    </div>

    <script>
        jQuery(function($) {
            var ruleIndex = <?php echo count($rules); ?>;

            // Dodaj nową regułę
            $('#add-gift-rule').on('click', function() {
                var row = `
                <tr class="gift-rule-row" data-index="${ruleIndex}">
                    <td class="col-enabled">
                        <input type="checkbox" name="jetlagz_gift_rules[${ruleIndex}][enabled]" value="1" checked />
                    </td>
                    <td class="col-threshold">
                        <input type="number" name="jetlagz_gift_rules[${ruleIndex}][threshold]" value="" min="0" step="0.01" class="small-text" required placeholder="np. 150" />
                    </td>
                    <td class="col-product">
                        <select name="jetlagz_gift_rules[${ruleIndex}][product_id]"
                            class="wc-product-search"
                            data-placeholder="Szukaj produktu..."
                            data-action="woocommerce_json_search_products"
                            data-allow_clear="true"
                            style="width: 100%;">
                        </select>
                    </td>
                    <td class="col-price">
                        <input type="number" name="jetlagz_gift_rules[${ruleIndex}][price]" value="0.10" min="0" step="0.01" class="small-text" />
                    </td>
                    <td class="col-message">
                        <textarea name="jetlagz_gift_rules[${ruleIndex}][message]" rows="2" placeholder="Opcjonalny tekst powiadomienia..."></textarea>
                    </td>
                    <td class="col-actions">
                        <button type="button" class="button remove-gift-rule">✕ Usuń</button>
                    </td>
                </tr>`;

                $('#gift-rules-body').append(row);

                // Re-init WC product search on new selects
                $('#gift-rules-body tr:last .wc-product-search').selectWoo({
                    minimumInputLength: 3,
                    ajax: {
                        url: ajaxurl,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term,
                                action: 'woocommerce_json_search_products',
                                security: '<?php echo wp_create_nonce('search-products'); ?>'
                            };
                        },
                        processResults: function(data) {
                            var results = [];
                            $.each(data, function(id, text) {
                                results.push({
                                    id: id,
                                    text: text
                                });
                            });
                            return {
                                results: results
                            };
                        }
                    }
                });

                ruleIndex++;
            });

            // Usuń regułę
            $(document).on('click', '.remove-gift-rule', function() {
                $(this).closest('tr').remove();
            });
        });
    </script>
<?php
}

// ============================================================================
// CART LOGIC — Auto-add/remove gift products
// ============================================================================

/**
 * Get applicable gift rules based on cart total
 */
function jetlagz_get_applicable_gift_rules($cart_total)
{
    $rules = get_option('jetlagz_gift_rules', []);
    if (!is_array($rules)) {
        return [];
    }

    $applicable = [];
    foreach ($rules as $rule) {
        if (empty($rule['enabled'])) {
            continue;
        }
        if ($cart_total >= $rule['threshold']) {
            $applicable[] = $rule;
        }
    }

    return $applicable;
}

/**
 * Get all enabled gift product IDs
 */
function jetlagz_get_all_gift_product_ids()
{
    $rules = get_option('jetlagz_gift_rules', []);
    if (!is_array($rules)) {
        return [];
    }

    $ids = [];
    foreach ($rules as $rule) {
        if (!empty($rule['product_id'])) {
            $ids[] = intval($rule['product_id']);
        }
    }

    return array_unique($ids);
}

/**
 * Check if product ID is configured as gift source product.
 */
function jetlagz_is_gift_source_product($product_id)
{
    $product_id = intval($product_id);
    if ($product_id <= 0) {
        return false;
    }

    return in_array($product_id, jetlagz_get_all_gift_product_ids(), true);
}

/**
 * Block direct access to single pages of gift source products.
 */
function jetlagz_block_gift_product_page_access()
{
    if (is_admin() || wp_doing_ajax() || !is_product()) {
        return;
    }

    global $post;
    if (!$post) {
        return;
    }

    $product_id = intval($post->ID);
    if (!jetlagz_is_gift_source_product($product_id)) {
        return;
    }

    wc_add_notice(__('Ten produkt jest dodawany automatycznie jako prezent i nie jest dostępny do samodzielnego zakupu.', 'universal-theme'), 'notice');
    wp_safe_redirect(wc_get_cart_url());
    exit;
}
add_action('template_redirect', 'jetlagz_block_gift_product_page_access', 1);

/**
 * Prevent manual add-to-cart for gift source products.
 */
function jetlagz_block_manual_gift_add_to_cart($passed, $product_id, $quantity, $variation_id = 0, $variations = [], $cart_item_data = [])
{
    // Allow internal auto-add from gift engine.
    if (!empty($cart_item_data['jetlagz_is_gift'])) {
        return $passed;
    }

    $check_id = intval($variation_id ?: $product_id);
    $check_product = wc_get_product($check_id);
    if ($check_product && $check_product->is_type('variation')) {
        $parent_id = intval($check_product->get_parent_id());
        if ($parent_id > 0) {
            $check_id = $parent_id;
        }
    }

    if (jetlagz_is_gift_source_product($check_id)) {
        wc_add_notice(__('Tego produktu nie można dodać ręcznie do koszyka. Jest dodawany automatycznie jako prezent.', 'universal-theme'), 'error');
        return false;
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'jetlagz_block_manual_gift_add_to_cart', 10, 6);

/**
 * Hide gift products from related products
 */
function jetlagz_exclude_gifts_from_related($related_posts, $product_id, $args)
{
    $gift_ids = jetlagz_get_all_gift_product_ids();
    if (empty($gift_ids)) {
        return $related_posts;
    }

    return array_diff($related_posts, $gift_ids);
}
add_filter('woocommerce_related_products', 'jetlagz_exclude_gifts_from_related', 10, 3);

/**
 * Hide gift products from upsells
 */
function jetlagz_exclude_gifts_from_upsells($upsells, $product_id)
{
    $gift_ids = jetlagz_get_all_gift_product_ids();
    if (empty($gift_ids)) {
        return $upsells;
    }

    return array_diff($upsells, $gift_ids);
}
add_filter('woocommerce_product_get_upsell_ids', 'jetlagz_exclude_gifts_from_upsells', 10, 2);
add_filter('woocommerce_product_variation_get_upsell_ids', 'jetlagz_exclude_gifts_from_upsells', 10, 2);

/**
 * Hide gift products from cross-sells
 */
function jetlagz_exclude_gifts_from_crosssells($crosssells, $product_id)
{
    $gift_ids = jetlagz_get_all_gift_product_ids();
    if (empty($gift_ids)) {
        return $crosssells;
    }

    return array_diff($crosssells, $gift_ids);
}
add_filter('woocommerce_product_get_cross_sell_ids', 'jetlagz_exclude_gifts_from_crosssells', 10, 2);
add_filter('woocommerce_product_variation_get_cross_sell_ids', 'jetlagz_exclude_gifts_from_crosssells', 10, 2);

/**
 * Hide gift products from archive/shop/search pages
 */
function jetlagz_exclude_gifts_from_queries($q)
{
    // Only run on product archives, shop, search - not admin
    if (is_admin() || !$q->is_main_query()) {
        return;
    }

    // Only for product queries
    if (!is_post_type_archive('product') && !is_search() && !is_tax(get_object_taxonomies('product'))) {
        return;
    }

    $gift_ids = jetlagz_get_all_gift_product_ids();
    if (empty($gift_ids)) {
        return;
    }

    $existing_not_in = $q->get('post__not_in');
    if (empty($existing_not_in)) {
        $existing_not_in = [];
    }

    $q->set('post__not_in', array_merge($existing_not_in, $gift_ids));
}
add_action('pre_get_posts', 'jetlagz_exclude_gifts_from_queries', 20);

/**
 * Calculate cart total EXCLUDING gift products
 */
function jetlagz_get_cart_total_without_gifts()
{
    if (!WC()->cart) {
        return 0;
    }

    $gift_product_ids = jetlagz_get_all_gift_product_ids();
    $total = 0;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        if (in_array($product_id, $gift_product_ids) && !empty($cart_item['jetlagz_is_gift'])) {
            continue; // Skip gift products
        }
        $total += floatval($cart_item['line_subtotal']);
    }

    return $total;
}

/**
 * Track when customer manually removes a gift — store in session so it's not re-added
 */
function jetlagz_track_gift_removal($cart_item_key, $cart)
{
    $cart_item = $cart->get_cart_item($cart_item_key);
    if (!empty($cart_item['jetlagz_is_gift']) && WC()->session) {
        $dismissed = WC()->session->get('jetlagz_dismissed_gifts', []);
        $product_id = intval($cart_item['jetlagz_gift_source_product_id'] ?? $cart_item['product_id']);
        if (!in_array($product_id, $dismissed)) {
            $dismissed[] = $product_id;
        }
        WC()->session->set('jetlagz_dismissed_gifts', $dismissed);
    }
}
add_action('woocommerce_remove_cart_item', 'jetlagz_track_gift_removal', 10, 2);

/**
 * Resolve add_to_cart params for gift product.
 * Supports simple and variable products (default variation preferred).
 */
function jetlagz_resolve_gift_add_to_cart_params($product)
{
    if (!$product || !($product instanceof WC_Product)) {
        return false;
    }

    // Simple (or already concrete) product.
    if (!$product->is_type('variable')) {
        if (!$product->is_purchasable() || !$product->is_in_stock()) {
            return false;
        }

        return [
            'product_id'   => (int) $product->get_id(),
            'variation_id' => 0,
            'variation'    => [],
            'name'         => $product->get_name(),
        ];
    }

    // Variable product: try default attributes first.
    $default_attributes = array_filter((array) $product->get_default_attributes(), function ($value) {
        return $value !== '';
    });

    if (!empty($default_attributes)) {
        $variation_id = (int) WC_Data_Store::load('product')->find_matching_product_variation($product, $default_attributes);

        if ($variation_id > 0) {
            $variation_product = wc_get_product($variation_id);
            if ($variation_product && $variation_product->is_purchasable() && $variation_product->is_in_stock()) {
                $variation_attributes = [];
                foreach ((array) $variation_product->get_attributes() as $attr_name => $attr_value) {
                    if ($attr_value === '') {
                        continue;
                    }
                    $variation_attributes['attribute_' . $attr_name] = $attr_value;
                }

                return [
                    'product_id'   => (int) $product->get_id(),
                    'variation_id' => $variation_id,
                    'variation'    => $variation_attributes,
                    'name'         => $variation_product->get_name(),
                ];
            }
        }
    }

    // Fallback: pick first in-stock purchasable variation.
    foreach ($product->get_children() as $variation_id) {
        $variation_product = wc_get_product($variation_id);
        if (!$variation_product || !$variation_product->is_purchasable() || !$variation_product->is_in_stock()) {
            continue;
        }

        $variation_attributes = [];
        foreach ((array) $variation_product->get_attributes() as $attr_name => $attr_value) {
            if ($attr_value === '') {
                continue;
            }
            $variation_attributes['attribute_' . $attr_name] = $attr_value;
        }

        return [
            'product_id'   => (int) $product->get_id(),
            'variation_id' => (int) $variation_id,
            'variation'    => $variation_attributes,
            'name'         => $variation_product->get_name(),
        ];
    }

    return false;
}

/**
 * When cart contents change (add product), reset dismissed gifts
 * so the gift can be re-offered if threshold is still met
 */
function jetlagz_reset_dismissed_on_add($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
{
    // Only reset if the added item is NOT a gift itself
    if (empty($cart_item_data['jetlagz_is_gift']) && WC()->session) {
        WC()->session->set('jetlagz_dismissed_gifts', []);
    }
}
add_action('woocommerce_add_to_cart', 'jetlagz_reset_dismissed_on_add', 10, 6);

/**
 * Main hook: Check and manage gift products on cart update
 */
function jetlagz_manage_gift_products()
{
    if (!WC()->cart || WC()->cart->is_empty()) {
        return;
    }

    // Prevent infinite loops
    static $running = false;
    if ($running) {
        return;
    }
    $running = true;

    $rules = get_option('jetlagz_gift_rules', []);
    if (!is_array($rules) || empty($rules)) {
        $running = false;
        return;
    }

    // Get dismissed gifts from session
    $dismissed_gifts = WC()->session ? WC()->session->get('jetlagz_dismissed_gifts', []) : [];

    $cart_total = jetlagz_get_cart_total_without_gifts();
    $applicable_rules = jetlagz_get_applicable_gift_rules($cart_total);
    $applicable_product_ids = array_map('intval', array_column($applicable_rules, 'product_id'));
    $all_gift_product_ids = jetlagz_get_all_gift_product_ids();

    // Track which gifts were added this round (for notification)
    $gifts_added = [];
    $gifts_removed = [];

    // STEP 1: Remove gifts that no longer qualify
    foreach (WC()->cart->get_cart() as $cart_key => $cart_item) {
        if (empty($cart_item['jetlagz_is_gift'])) {
            continue;
        }

        $product_id = intval($cart_item['jetlagz_gift_source_product_id'] ?? $cart_item['product_id']);

        if (!in_array($product_id, $applicable_product_ids, true)) {
            WC()->cart->remove_cart_item($cart_key);
            $gifts_removed[] = $product_id;

            // Also clear from dismissed if threshold no longer met
            if (WC()->session) {
                $dismissed = WC()->session->get('jetlagz_dismissed_gifts', []);
                $dismissed = array_diff($dismissed, [$product_id]);
                WC()->session->set('jetlagz_dismissed_gifts', array_values($dismissed));
            }
        }
    }

    // STEP 2: Add gifts that qualify but aren't in cart yet (and not dismissed by customer)
    foreach ($applicable_rules as $rule) {
        $product_id = intval($rule['product_id']);

        // Skip if customer manually dismissed this gift
        if (in_array($product_id, $dismissed_gifts, true)) {
            continue;
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            continue;
        }

        $add_params = jetlagz_resolve_gift_add_to_cart_params($product);
        if (!$add_params) {
            continue;
        }

        // Check if this gift is already in cart
        $already_in_cart = false;
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (
                !empty($cart_item['jetlagz_is_gift'])
                && intval($cart_item['jetlagz_gift_source_product_id'] ?? $cart_item['product_id']) === $product_id
            ) {
                $already_in_cart = true;
                break;
            }
        }

        if (!$already_in_cart) {
            $cart_item_data = [
                'jetlagz_is_gift'               => true,
                'jetlagz_gift_rule'             => $rule,
                'jetlagz_gift_source_product_id' => $product_id,
            ];

            $added_key = WC()->cart->add_to_cart(
                $add_params['product_id'],
                1,
                $add_params['variation_id'],
                $add_params['variation'],
                $cart_item_data
            );

            if (!$added_key) {
                continue;
            }

            $gifts_added[] = [
                'product_id'   => $product_id,
                'product_name' => $add_params['name'],
                'price'        => $rule['price'],
                'message'      => $rule['message'] ?? '',
            ];

            // Also mark this gift as "just added" for page reload detection
            $just_added = WC()->session->get('jetlagz_gifts_just_added', []);
            $just_added[$product_id] = [
                'product_name' => $add_params['name'],
                'message'      => $rule['message'] ?? '',
            ];
            WC()->session->set('jetlagz_gifts_just_added', $just_added);
        }
    }

    // Store notifications for frontend (for AJAX scenarios)
    if (!empty($gifts_added)) {
        WC()->session->set('jetlagz_gift_notifications', $gifts_added);
    }

    $running = false;
}
add_action('woocommerce_before_calculate_totals', 'jetlagz_manage_gift_products', 10);

/**
 * Override gift product price in cart
 */
function jetlagz_gift_product_price($cart)
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    foreach ($cart->get_cart() as $cart_item) {
        if (!empty($cart_item['jetlagz_is_gift']) && !empty($cart_item['jetlagz_gift_rule'])) {
            $gift_price = floatval($cart_item['jetlagz_gift_rule']['price'] ?? 0.10);
            $cart_item['data']->set_price($gift_price);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'jetlagz_gift_product_price', 20);

/**
 * Show original price as strikethrough + gift price in cart (price column)
 */
function jetlagz_gift_cart_item_price($price, $cart_item, $cart_item_key)
{
    if (!empty($cart_item['jetlagz_is_gift']) && !empty($cart_item['jetlagz_gift_rule'])) {
        $original_product = wc_get_product($cart_item['product_id']);
        $regular_price = $original_product ? $original_product->get_regular_price() : 0;

        // Fallback: try sale price parent or just get_price from fresh product
        if (empty($regular_price) && $original_product) {
            $regular_price = $original_product->get_price();
        }

        $gift_price = floatval($cart_item['jetlagz_gift_rule']['price'] ?? 0.10);

        if ($regular_price && floatval($regular_price) > $gift_price) {
            $price = '<del class="gift-original-price">' . wc_price($regular_price) . '</del> '
                . '<ins class="gift-price">' . wc_price($gift_price) . '</ins>';
        }
    }
    return $price;
}
add_filter('woocommerce_cart_item_price', 'jetlagz_gift_cart_item_price', 10, 3);

/**
 * Show original price as strikethrough + gift price in cart (subtotal column)
 */
function jetlagz_gift_cart_item_subtotal($subtotal, $cart_item, $cart_item_key)
{
    if (!empty($cart_item['jetlagz_is_gift']) && !empty($cart_item['jetlagz_gift_rule'])) {
        $original_product = wc_get_product($cart_item['product_id']);
        $regular_price = $original_product ? $original_product->get_regular_price() : 0;

        if (empty($regular_price) && $original_product) {
            $regular_price = $original_product->get_price();
        }

        $gift_price = floatval($cart_item['jetlagz_gift_rule']['price'] ?? 0.10);

        if ($regular_price && floatval($regular_price) > $gift_price) {
            $subtotal = '<del class="gift-original-price">' . wc_price($regular_price) . '</del> '
                . '<ins class="gift-price">' . wc_price($gift_price) . '</ins>';
        }
    }
    return $subtotal;
}
add_filter('woocommerce_cart_item_subtotal', 'jetlagz_gift_cart_item_subtotal', 10, 3);

/**
 * Prevent customers from changing gift product quantity (but allow removal)
 */
function jetlagz_gift_quantity_input($product_quantity, $cart_item_key, $cart_item)
{
    if (!empty($cart_item['jetlagz_is_gift'])) {
        return '';
    }
    return $product_quantity;
}
add_filter('woocommerce_cart_item_quantity', 'jetlagz_gift_quantity_input', 10, 3);

/**
 * Add "🎁 PREZENT" label to gift items in cart
 */
function jetlagz_gift_cart_item_name($name, $cart_item, $cart_item_key)
{
    if (!empty($cart_item['jetlagz_is_gift'])) {
        $name = '<span class="gift-badge">🎁 PREZENT</span> ' . $name;
    }
    return $name;
}
add_filter('woocommerce_cart_item_name', 'jetlagz_gift_cart_item_name', 10, 3);

/**
 * Preserve gift data in cart session
 */
function jetlagz_gift_get_item_data($item_data, $cart_item)
{
    if (!empty($cart_item['jetlagz_is_gift'])) {
        $item_data[] = [
            'key'   => 'Typ',
            'value' => '🎁 Prezent od AlmostDream',
        ];
    }
    return $item_data;
}
add_filter('woocommerce_get_item_data', 'jetlagz_gift_get_item_data', 10, 2);

/**
 * Save gift meta to order
 */
function jetlagz_gift_order_item_meta($item, $cart_item_key, $values, $order)
{
    if (!empty($values['jetlagz_is_gift'])) {
        $item->add_meta_data('_jetlagz_is_gift', 'yes', true);
        $item->add_meta_data('_jetlagz_gift_threshold', $values['jetlagz_gift_rule']['threshold'] ?? '', true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'jetlagz_gift_order_item_meta', 10, 4);

// ============================================================================
// FRONTEND — Toast notification
// ============================================================================

/**
 * Output toast notification HTML and JS
 */
function jetlagz_gift_notification_frontend()
{
    // Only on frontend, not admin
    if (is_admin()) {
        return;
    }

    $rules = get_option('jetlagz_gift_rules', []);
    if (empty($rules)) {
        return;
    }

    // Force cart calculation to trigger gift logic BEFORE we read notifications
    if (WC()->cart && !WC()->cart->is_empty()) {
        WC()->cart->calculate_totals();
    }

    // Build rules data for JS (only enabled rules)
    $js_rules = [];
    foreach ($rules as $rule) {
        if (!empty($rule['enabled'])) {
            $product = wc_get_product($rule['product_id']);
            $js_rules[] = [
                'threshold'    => $rule['threshold'],
                'product_id'   => $rule['product_id'],
                'product_name' => $product ? $product->get_name() : 'Prezent',
                'price'        => $rule['price'] ?? 0.10,
                'message'      => $rule['message'] ?? '',
            ];
        }
    }

    // Check if there are pending notifications from PHP (page reload after add-to-cart)
    $pending_notifications = [];
    if (WC()->session) {
        // First check "just added" gifts from previous request (page reload scenario)
        $just_added = WC()->session->get('jetlagz_gifts_just_added', []);
        if (!empty($just_added)) {
            foreach ($just_added as $product_id => $gift_data) {
                $pending_notifications[] = [
                    'product_id'   => $product_id,
                    'product_name' => $gift_data['product_name'],
                    'message'      => $gift_data['message'] ?? '',
                ];
            }
            WC()->session->set('jetlagz_gifts_just_added', null);
        }

        // Then check regular notifications (AJAX scenario)
        $pending = WC()->session->get('jetlagz_gift_notifications');
        if (!empty($pending)) {
            // Merge but avoid duplicates
            foreach ($pending as $notif) {
                $exists = false;
                foreach ($pending_notifications as $existing) {
                    if ($existing['product_id'] == $notif['product_id']) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $pending_notifications[] = $notif;
                }
            }
            WC()->session->set('jetlagz_gift_notifications', null);
        }
    }
?>
    <!-- Gift Toast Notification Container -->
    <div id="jetlagz-gift-toast-container"></div>

    <style>
        #jetlagz-gift-toast-container {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 9999999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .jetlagz-gift-toast {
            background: #fff;
            border-left: 4px solid #c9a96e;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            padding: 16px 20px;
            max-width: 380px;
            pointer-events: auto;
            transform: translateX(-120%);
            opacity: 0;
            transition: transform 0.4s cubic-bezier(0.21, 1.02, 0.73, 1), opacity 0.4s ease;
            font-family: inherit;
        }

        .jetlagz-gift-toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .jetlagz-gift-toast.hide {
            transform: translateX(-120%);
            opacity: 0;
        }

        .jetlagz-gift-toast .toast-title {
            font-weight: 700;
            font-size: 14px;
            color: #1a1a1a;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .jetlagz-gift-toast .toast-title .gift-icon {
            font-size: 18px;
        }

        .jetlagz-gift-toast .toast-body {
            font-size: 13px;
            color: #444;
            line-height: 1.5;
        }

        .jetlagz-gift-toast .toast-body .product-name {
            font-weight: 600;
            color: #c9a96e;
        }

        .jetlagz-gift-toast .toast-body .toast-tagline {
            margin-top: 4px;
            font-style: italic;
            color: #888;
            font-size: 12px;
        }

        .jetlagz-gift-toast .toast-close {
            position: absolute;
            top: 8px;
            right: 10px;
            background: none;
            border: none;
            font-size: 16px;
            color: #999;
            cursor: pointer;
            padding: 2px 6px;
            line-height: 1;
        }

        .jetlagz-gift-toast .toast-close:hover {
            color: #333;
        }

        .jetlagz-gift-toast .toast-progress {
            position: absolute;
            bottom: 0;
            left: 4px;
            right: 0;
            height: 3px;
            background: #c9a96e;
            border-radius: 0 0 8px 0;
            transform-origin: left;
            animation: giftToastProgress 10s linear forwards;
        }

        @keyframes giftToastProgress {
            from {
                transform: scaleX(1);
            }

            to {
                transform: scaleX(0);
            }
        }

        @media (max-width: 768px) {
            #jetlagz-gift-toast-container {
                top: auto;
                bottom: 20px;
                left: 10px;
                right: 10px;
            }

            .jetlagz-gift-toast {
                max-width: 100%;
                transform: translateY(120%);
            }

            .jetlagz-gift-toast.show {
                transform: translateY(0);
            }

            .jetlagz-gift-toast.hide {
                transform: translateY(120%);
            }
        }

        /* Gift price styling in cart */
        .gift-original-price {
            color: #999;
            font-size: 0.9em;
            text-decoration: line-through;
            margin-right: 4px;
        }

        .gift-price {
            color: #c9a96e;
            font-weight: 700;
            text-decoration: none;
        }

        .gift-badge {
            display: block;
            background: linear-gradient(135deg, #c9a96e, #b8944f);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
            margin-right: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            vertical-align: middle;
            width: fit-content;
            margin-bottom: 2px;
        }

        .gift-quantity {
            display: inline-block;
            padding: 4px 10px;
            background: #f5f5f5;
            border-radius: 4px;
            font-weight: 600;
            color: #666;
        }
    </style>

    <script>
        (function() {
            var giftRules = <?php echo json_encode($js_rules); ?>;
            var pendingNotifications = <?php echo json_encode($pending_notifications); ?>;

            function showGiftToast(productName, message) {
                var container = document.getElementById('jetlagz-gift-toast-container');
                if (!container) return;

                var defaultMessage = 'Przekroczyłaś próg darmowego prezentu. Dołączamy do Twojej paczki <span class="product-name">' + productName + '</span> za 0,10\u00a0zł.';
                var bodyText = message ? message.replace('{product_name}', '<span class="product-name">' + productName + '</span>') : defaultMessage;

                var toast = document.createElement('div');
                toast.className = 'jetlagz-gift-toast';
                toast.style.position = 'relative';
                toast.innerHTML =
                    '<button class="toast-close" aria-label="Zamknij">&times;</button>' +
                    '<div class="toast-title"><span class="gift-icon">🎁</span> Prezent od AlmostDream dodany do koszyka!</div>' +
                    '<div class="toast-body">' +
                    bodyText +
                    '<div class="toast-tagline">Ciesz się dodatkową dawką pewności siebie!</div>' +
                    '</div>' +
                    '<div class="toast-progress"></div>';

                container.appendChild(toast);

                // Show animation
                requestAnimationFrame(function() {
                    requestAnimationFrame(function() {
                        toast.classList.add('show');
                    });
                });

                // Close button
                toast.querySelector('.toast-close').addEventListener('click', function() {
                    dismissToast(toast);
                });

                // Auto dismiss after 10s
                setTimeout(function() {
                    dismissToast(toast);
                }, 10000);
            }

            function dismissToast(toast) {
                if (toast.classList.contains('hide')) return;
                toast.classList.remove('show');
                toast.classList.add('hide');
                setTimeout(function() {
                    if (toast.parentNode) toast.parentNode.removeChild(toast);
                }, 400);
            }

            // Show pending notifications (from PHP session, after page reload)
            if (pendingNotifications && pendingNotifications.length > 0) {
                document.addEventListener('DOMContentLoaded', function() {
                    pendingNotifications.forEach(function(notif, i) {
                        setTimeout(function() {
                            showGiftToast(notif.product_name, notif.message || '');
                        }, 300 + (i * 200));
                    });
                });
            }

            // Listen for AJAX add-to-cart events (for live updates without reload)
            // Use debounce to prevent multiple calls when event fires rapidly
            var giftCheckPending = false;
            var giftCheckTimeout = null;

            function checkGiftStatus() {
                if (giftCheckPending) {
                    return;
                }
                giftCheckPending = true;

                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=jetlagz_check_gift_status&_wpnonce=<?php echo wp_create_nonce('jetlagz_gift_check'); ?>', {
                        credentials: 'same-origin'
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success && data.data && data.data.gifts_added && data.data.gifts_added.length > 0) {
                            data.data.gifts_added.forEach(function(gift, i) {
                                setTimeout(function() {
                                    showGiftToast(gift.product_name, gift.message || '');
                                }, 200 + (i * 200));
                            });
                        }
                    })
                    .catch(function(err) {})
                    .finally(function() {
                        // Reset after short delay to allow for legitimate subsequent adds
                        setTimeout(function() {
                            giftCheckPending = false;
                        }, 1000);
                    });
            }

            if (typeof jQuery !== 'undefined') {
                jQuery(document.body).on('added_to_cart', function(event, fragments, cart_hash, $button) {

                    // Debounce - wait a bit for multiple rapid events to settle
                    if (giftCheckTimeout) {
                        clearTimeout(giftCheckTimeout);
                    }
                    giftCheckTimeout = setTimeout(function() {
                        checkGiftStatus();
                    }, 300);
                });
            }

            // Expose for manual use
            window.jetlagzShowGiftToast = showGiftToast;
        })();
    </script>
<?php
}
add_action('wp_footer', 'jetlagz_gift_notification_frontend', 50);

/**
 * AJAX endpoint to check gift status (for AJAX add-to-cart)
 */
function jetlagz_ajax_check_gift_status()
{
    check_ajax_referer('jetlagz_gift_check', '_wpnonce');

    // Ensure cart is calculated so gift logic runs
    if (WC()->cart) {
        WC()->cart->calculate_totals();
    }

    $notifications = [];
    if (WC()->session) {
        $pending = WC()->session->get('jetlagz_gift_notifications');
        if (!empty($pending)) {
            $notifications = $pending;
            WC()->session->set('jetlagz_gift_notifications', null);
        }
    }

    wp_send_json_success([
        'gifts_added' => $notifications,
    ]);
}
add_action('wp_ajax_jetlagz_check_gift_status', 'jetlagz_ajax_check_gift_status');
add_action('wp_ajax_nopriv_jetlagz_check_gift_status', 'jetlagz_ajax_check_gift_status');
