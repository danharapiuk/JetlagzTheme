<?php

/**
 * Checkout Cross-sell Functions
 * Funkcje dla rekomendowanych produktów w checkout
 */

// Zapobiegnij bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inicjalizacja cross-sell w checkout
 */
function universal_init_checkout_crosssell()
{
    // AJAX endpoints
    add_action('wp_ajax_universal_add_crosssell_product', 'universal_handle_add_crosssell_product');
    add_action('wp_ajax_nopriv_universal_add_crosssell_product', 'universal_handle_add_crosssell_product');
    
    // Enqueue scripts tylko na checkout
    add_action('wp_enqueue_scripts', 'universal_enqueue_crosssell_scripts');
}
add_action('init', 'universal_init_checkout_crosssell');

/**
 * Enqueue cross-sell scripts
 */
function universal_enqueue_crosssell_scripts()
{
    if (!is_checkout()) {
        return;
    }

    wp_enqueue_script(
        'universal-checkout-crosssell',
        get_stylesheet_directory_uri() . '/assets/js/checkout-crosssell.js',
        array('jquery', 'wc-checkout'),
        '1.0.0',
        true
    );

    // Przekaż dane do JS
    wp_localize_script('universal-checkout-crosssell', 'universalCrosssell', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('universal_crosssell_nonce'),
        'currency' => get_woocommerce_currency_symbol(),
        'messages' => array(
            'added' => __('Dodano do koszyka!', 'universal-theme'),
            'error' => __('Wystąpił błąd podczas dodawania produktu.', 'universal-theme'),
        ),
        'settings' => array(
            'free_shipping_threshold' => get_theme_option('checkout.free_shipping_threshold', 199),
            'update_checkout_delay' => get_theme_option('checkout.update_delay', 1500),
        )
    ));
}

/**
 * Wyświetl cross-sell box w checkout
 */
function universal_display_checkout_crosssell()
{
    if (!is_checkout() || is_wc_endpoint_url()) {
        return;
    }

    $cart_total = WC()->cart->get_cart_contents_total();
    $free_shipping_threshold = get_theme_option('checkout.free_shipping_threshold', 199);
    $remaining_for_free_shipping = max(0, $free_shipping_threshold - $cart_total);
    
    // Pobierz produkty cross-sell
    $crosssell_products = universal_get_checkout_crosssell_products();
    
    if (empty($crosssell_products) && $remaining_for_free_shipping <= 0) {
        return; // Brak produktów i już mamy darmową wysyłkę
    }
?>
    <div class="checkout-crosssell-section">
        
        <?php if ($remaining_for_free_shipping > 0) : ?>
            <!-- Free Shipping Progress -->
            <div class="free-shipping-progress">
                <div class="shipping-progress-header">
                    <h3>
                        <i class="shipping-icon">🚚</i>
                        Darmowa wysyłka
                    </h3>
                    <span class="remaining-amount">
                        Zostało: <strong><?php echo wc_price($remaining_for_free_shipping); ?></strong>
                    </span>
                </div>
                
                <div class="shipping-progress-bar">
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?php echo min(100, ($cart_total / $free_shipping_threshold) * 100); ?>%"></div>
                    </div>
                    <div class="progress-labels">
                        <span>Twój koszyk: <?php echo wc_price($cart_total); ?></span>
                        <span>Darmowa wysyłka: <?php echo wc_price($free_shipping_threshold); ?></span>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <!-- Free Shipping Achieved -->
            <div class="free-shipping-achieved">
                <h3>
                    <i class="shipping-icon">✅</i>
                    Gratulacje! Masz darmową wysyłkę!
                </h3>
            </div>
        <?php endif; ?>

        <?php if (!empty($crosssell_products)) : ?>
            <!-- Cross-sell Products -->
            <div class="crosssell-products">
                <div class="crosssell-header">
                    <h3>
                        <i class="products-icon">💡</i>
                        Polecane produkty
                    </h3>
                    <p class="crosssell-subtitle">Dodaj do zamówienia i oszczędź na kolejnych zakupach</p>
                </div>

                <div class="crosssell-products-grid">
                    <?php foreach ($crosssell_products as $product) : ?>
                        <div class="crosssell-product-item" data-product-id="<?php echo $product->get_id(); ?>">
                            <div class="crosssell-product-image">
                                <?php echo $product->get_image('thumbnail'); ?>
                                
                                <?php if ($product->is_on_sale()) : ?>
                                    <span class="crosssell-sale-badge">SALE</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="crosssell-product-info">
                                <h4 class="crosssell-product-name"><?php echo $product->get_name(); ?></h4>
                                
                                <div class="crosssell-product-price">
                                    <?php echo $product->get_price_html(); ?>
                                </div>
                                
                                <?php if ($product->get_short_description()) : ?>
                                    <p class="crosssell-product-description">
                                        <?php echo wp_trim_words($product->get_short_description(), 10); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="crosssell-product-actions">
                                <?php if ($product->is_purchasable() && $product->is_in_stock()) : ?>
                                    <button type="button" 
                                            class="crosssell-add-btn button"
                                            data-product-id="<?php echo $product->get_id(); ?>"
                                            data-product-name="<?php echo esc_attr($product->get_name()); ?>"
                                            data-product-price="<?php echo $product->get_price(); ?>">
                                        <span class="btn-icon">+</span>
                                        <span class="btn-text">Dodaj</span>
                                        <span class="btn-price"><?php echo wc_price($product->get_price()); ?></span>
                                    </button>
                                <?php else : ?>
                                    <button class="crosssell-add-btn button disabled" disabled>
                                        Niedostępny
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="crosssell-benefits">
                    <div class="benefit-item">
                        <i class="benefit-icon">⚡</i>
                        <span>Szybkie dodanie</span>
                    </div>
                    <div class="benefit-item">
                        <i class="benefit-icon">🔄</i>
                        <span>Auto-update koszyka</span>
                    </div>
                    <div class="benefit-item">
                        <i class="benefit-icon">💰</i>
                        <span>Oszczędności na wysyłce</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php
}

/**
 * Pobierz produkty cross-sell dla checkout
 */
function universal_get_checkout_crosssell_products($limit = 4)
{
    $products = array();
    
    // Strategia 1: Cross-sell products z produktów w koszyku
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        $cross_sells = $product->get_cross_sell_ids();
        
        if (!empty($cross_sells)) {
            foreach ($cross_sells as $cross_sell_id) {
                $cross_sell_product = wc_get_product($cross_sell_id);
                if ($cross_sell_product && $cross_sell_product->is_purchasable()) {
                    $products[$cross_sell_id] = $cross_sell_product;
                }
            }
        }
    }
    
    // Strategia 2: Jeśli brak cross-sell, użyj produktów z tej samej kategorii
    if (empty($products)) {
        $category_ids = array();
        
        // Zbierz kategorie z produktów w koszyku
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $terms = get_the_terms($product->get_id(), 'product_cat');
            
            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $category_ids[] = $term->term_id;
                }
            }
        }
        
        if (!empty($category_ids)) {
            $related_products = wc_get_products(array(
                'limit' => $limit * 2, // Pobierz więcej żeby móc filtrować
                'category' => array_unique($category_ids),
                'exclude' => array_keys(WC()->cart->get_cart_contents()), // Wyklucz produkty już w koszyku
                'orderby' => 'popularity',
                'return' => 'objects',
            ));
            
            foreach ($related_products as $related_product) {
                $products[$related_product->get_id()] = $related_product;
            }
        }
    }
    
    // Strategia 3: Fallback - popularne produkty
    if (empty($products)) {
        $popular_products = wc_get_products(array(
            'limit' => $limit,
            'orderby' => 'popularity',
            'exclude' => array_keys(WC()->cart->get_cart_contents()),
            'return' => 'objects',
        ));
        
        foreach ($popular_products as $popular_product) {
            $products[$popular_product->get_id()] = $popular_product;
        }
    }
    
    // Zwróć tylko określoną liczbę produktów
    return array_slice($products, 0, $limit, true);
}

/**
 * AJAX: Dodaj crosssell product do koszyka
 */
function universal_handle_add_crosssell_product()
{
    try {
        // Sprawdź nonce
        if (!wp_verify_nonce($_POST['nonce'], 'universal_crosssell_nonce')) {
            throw new Exception(__('Security check failed.', 'universal-theme'));
        }

        $product_id = absint($_POST['product_id'] ?? 0);
        $quantity = absint($_POST['quantity'] ?? 1);

        if (!$product_id) {
            throw new Exception(__('Nieprawidłowy produkt.', 'universal-theme'));
        }

        $product = wc_get_product($product_id);
        if (!$product || !$product->is_purchasable()) {
            throw new Exception(__('Produkt nie jest dostępny.', 'universal-theme'));
        }

        // Sprawdź czy produkt nie jest już w koszyku
        $cart_contents = WC()->cart->get_cart_contents();
        foreach ($cart_contents as $cart_item) {
            if ($cart_item['product_id'] == $product_id) {
                throw new Exception(__('Produkt jest już w koszyku.', 'universal-theme'));
            }
        }

        // Dodaj produkt do koszyka
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);

        if (!$cart_item_key) {
            throw new Exception(__('Nie udało się dodać produktu do koszyka.', 'universal-theme'));
        }

        // Pobierz zaktualizowane dane koszyka
        $cart_total = WC()->cart->get_cart_contents_total();
        $free_shipping_threshold = get_theme_option('checkout.free_shipping_threshold', 199);
        $remaining_for_free_shipping = max(0, $free_shipping_threshold - $cart_total);
        
        wp_send_json_success(array(
            'message' => sprintf(__('%s został dodany do koszyka!', 'universal-theme'), $product->get_name()),
            'product_name' => $product->get_name(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'cart_total' => $cart_total,
            'cart_total_formatted' => wc_price($cart_total),
            'remaining_for_free_shipping' => $remaining_for_free_shipping,
            'remaining_formatted' => wc_price($remaining_for_free_shipping),
            'free_shipping_achieved' => $remaining_for_free_shipping <= 0,
            'progress_percentage' => min(100, ($cart_total / $free_shipping_threshold) * 100),
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

/**
 * Hook do wyświetlenia cross-sell w checkout template
 */
add_action('universal_checkout_after_order_review', 'universal_display_checkout_crosssell');
