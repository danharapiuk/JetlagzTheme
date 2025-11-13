<?php

/**
 * One-Click Checkout Functions
 * Funkcje obsługujące zakupy w jeden klik
 */

// Zapobiegnij bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Inicjalizacja one-click checkout
 */
function universal_theme_init_one_click_checkout()
{
    $enable_one_click = get_theme_option('checkout.enable_one_click', false);
    universal_debug_log('Universal Debug: enable_one_click = ' . ($enable_one_click ? 'true' : 'false'));

    if (!$enable_one_click) {
        universal_debug_log('Universal Debug: One-click checkout is disabled');
        return;
    }

    universal_debug_log('Universal Debug: One-click checkout enabled, setting up hooks');

    // AJAX endpoints
    add_action('wp_ajax_universal_one_click_checkout', 'universal_handle_one_click_checkout');
    add_action('wp_ajax_nopriv_universal_one_click_checkout', 'universal_handle_one_click_checkout_guest');

    // Modal checkout endpoints
    add_action('wp_ajax_universal_modal_checkout', 'universal_handle_modal_checkout');
    add_action('wp_ajax_nopriv_universal_modal_checkout', 'universal_handle_modal_checkout');

    // Product data endpoint
    add_action('wp_ajax_universal_get_product_data', 'universal_get_product_data');
    add_action('wp_ajax_nopriv_universal_get_product_data', 'universal_get_product_data');

    // Add to cart and redirect endpoint
    add_action('wp_ajax_universal_add_to_cart_redirect', 'universal_add_to_cart_and_redirect');
    add_action('wp_ajax_nopriv_universal_add_to_cart_redirect', 'universal_add_to_cart_and_redirect');

    // Dodaj przyciski na stronie produktu
    if (get_theme_option('checkout.show_on_single_product', true)) {
        add_action('woocommerce_single_product_summary', 'universal_display_one_click_button', 31);
    }

    // Dodaj przyciski w pętli produktów
    if (get_theme_option('checkout.show_on_shop_loop', true)) {
        add_action('woocommerce_after_shop_loop_item', 'universal_display_one_click_button_loop', 15);
    }

    // Enqueue scripts
    add_action('wp_enqueue_scripts', 'universal_enqueue_one_click_scripts');
}
add_action('init', 'universal_theme_init_one_click_checkout');

/**
 * Enqueue scripts i styles
 */
function universal_enqueue_one_click_scripts()
{
    if (!get_theme_option('checkout.enable_one_click', false) || !is_woocommerce()) {
        return;
    }

    wp_enqueue_script(
        'universal-one-click-checkout',
        get_stylesheet_directory_uri() . '/assets/js/one-click-checkout.js',
        array('jquery', 'wc-checkout'),
        '1.0.0',
        true
    );

    // Przekaż dane do JS
    wp_localize_script('universal-one-click-checkout', 'universalOneClick', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('universal_one_click_nonce'),
        'messages' => array(
            'processing' => __('Przetwarzanie zamówienia...', 'universal-theme'),
            'success' => __('Zamówienie zostało złożone!', 'universal-theme'),
            'error' => __('Wystąpił błąd. Spróbuj ponownie.', 'universal-theme'),
            'login_required' => __('Musisz być zalogowany, aby korzystać z zakupów w 1 klik.', 'universal-theme'),
        ),
        'settings' => array(
            'require_login' => get_theme_option('checkout.require_login', true),
            'success_redirect' => get_theme_option('checkout.success_redirect', 'order_received'),
        )
    ));
}

/**
 * Wyświetl przycisk one-click na stronie produktu
 */
function universal_display_one_click_button()
{
    global $product;

    // Debug - sprawdź czy funkcja się wykonuje
    universal_debug_log('Universal Debug: universal_display_one_click_button called');

    if (!$product) {
        universal_debug_log('Universal Debug: Brak obiektu $product');
        return;
    }

    if (!universal_can_show_one_click_button($product)) {
        universal_debug_log('Universal Debug: universal_can_show_one_click_button returned false');
        return;
    }

    universal_debug_log('Universal Debug: Renderuję przycisk one-click dla produktu: ' . $product->get_id());

    $button_text = get_theme_option('checkout.button_text', 'Dodaj do koszyka i przejdź do płatności');
    $redirect_mode = get_theme_option('checkout.redirect_to_checkout', true);
?>
    <div class="universal-one-click-wrapper">
        <button
            type="button"
            class="universal-one-click-btn button alt <?php echo $redirect_mode ? 'redirect-checkout' : ''; ?>"
            data-product-id="<?php echo esc_attr($product->get_id()); ?>"
            data-quantity="1"
            data-action="<?php echo $redirect_mode ? 'add-and-redirect' : 'direct-order'; ?>">
            <span class="btn-text"><?php echo esc_html($button_text); ?></span>
            <span class="btn-loading" style="display: none;">
                <svg class="spinner" width="20" height="20" viewBox="0 0 20 20">
                    <circle cx="10" cy="10" r="8" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="50" stroke-dashoffset="0">
                        <animateTransform attributeName="transform" type="rotate" dur="1s" repeatCount="indefinite" values="0 10 10;360 10 10" />
                    </circle>
                </svg>
            </span>
        </button>
    </div>
<?php
}

/**
 * Wyświetl przycisk one-click w pętli produktów
 */
function universal_display_one_click_button_loop()
{
    global $product;

    if (!universal_can_show_one_click_button($product)) {
        return;
    }

    $button_text = get_theme_option('checkout.button_text', 'Kup w 1 klik');
    $redirect_mode = get_theme_option('checkout.redirect_to_checkout', true);
?>
<button
    type="button"
    class="universal-one-click-btn-loop button"
    data-product-id="<?php echo esc_attr($product->get_id()); ?>"
    data-quantity="1"
    data-action="<?php echo $redirect_mode ? 'add-and-redirect' : 'direct-order'; ?>">
    <?php echo esc_html($button_text); ?>
</button>
<?php
}

/**
 * Sprawdź czy można wyświetlić przycisk one-click
 */
function universal_can_show_one_click_button($product)
{
    universal_debug_log('Universal Debug: Sprawdzam czy można pokazać przycisk...');

    if (!$product || !is_a($product, 'WC_Product')) {
        universal_debug_log('Universal Debug: Brak produktu lub nieprawidłowy typ');
        return false;
    }

    // Sprawdź czy produkt jest dostępny
    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        universal_debug_log('Universal Debug: Produkt niedostępny lub brak w magazynie');
        return false;
    }

    // Sprawdź czy to produkt zmienny (wymaga wyboru opcji)
    if ($product->is_type('variable') && !$product->get_default_attributes()) {
        universal_debug_log('Universal Debug: Produkt zmienny bez domyślnych atrybutów');
        return false;
    }

    // W trybie modal checkout - pokaż dla wszystkich
    if (get_theme_option('checkout.modal_checkout', false)) {
        universal_debug_log('Universal Debug: Tryb modal checkout - pokazuję przycisk');
        return true;
    }

    // Klasyczna logika - sprawdź czy wymagane logowanie
    $require_login = get_theme_option('checkout.require_login', true);
    universal_debug_log('Universal Debug: require_login = ' . ($require_login ? 'true' : 'false'));

    if ($require_login && !is_user_logged_in()) {
        universal_debug_log('Universal Debug: Wymagane logowanie, a użytkownik nie zalogowany');
        return false;
    }

    universal_debug_log('Universal Debug: Wszystkie warunki spełnione - pokazuję przycisk');
    return true;
}

/**
 * Obsłuż AJAX request dla zalogowanych użytkowników
 */
function universal_handle_one_click_checkout()
{
    // Sprawdź nonce
    if (!wp_verify_nonce($_POST['nonce'], 'universal_one_click_nonce')) {
        wp_die(__('Security check failed.', 'universal-theme'));
    }

    // Sprawdź czy użytkownik jest zalogowany
    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('Musisz być zalogowany.', 'universal-theme')
        ));
    }

    universal_process_one_click_order();
}

/**
 * Obsłuż AJAX request dla gości (jeśli włączone)
 */
function universal_handle_one_click_checkout_guest()
{
    if (!get_theme_option('checkout.enable_guest_checkout', false)) {
        wp_send_json_error(array(
            'message' => __('Musisz być zalogowany.', 'universal-theme')
        ));
    }

    // Sprawdź nonce
    if (!wp_verify_nonce($_POST['nonce'], 'universal_one_click_nonce')) {
        wp_die(__('Security check failed.', 'universal-theme'));
    }

    universal_process_one_click_order();
}

/**
 * Przetwórz zamówienie one-click
 */
function universal_process_one_click_order()
{
    try {
        $product_id = absint($_POST['product_id'] ?? 0);
        $quantity = absint($_POST['quantity'] ?? 1);

        if (!$product_id) {
            throw new Exception(__('Nieprawidłowy produkt.', 'universal-theme'));
        }

        $product = wc_get_product($product_id);
        if (!$product || !$product->is_purchasable()) {
            throw new Exception(__('Produkt nie jest dostępny.', 'universal-theme'));
        }

        // Sprawdź dostępność
        if (!$product->has_enough_stock($quantity)) {
            throw new Exception(__('Niewystarczająca ilość w magazynie.', 'universal-theme'));
        }

        // Utwórz zamówienie
        $order = wc_create_order();

        // Dodaj produkt do zamówienia
        $order->add_product($product, $quantity);

        // Ustaw dane klienta
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $order->set_customer_id($user_id);

            // Pobierz dane z profilu użytkownika
            $user_data = get_userdata($user_id);
            $billing_address = array(
                'first_name' => $user_data->first_name ?: get_user_meta($user_id, 'billing_first_name', true),
                'last_name'  => $user_data->last_name ?: get_user_meta($user_id, 'billing_last_name', true),
                'email'      => $user_data->user_email,
                'phone'      => get_user_meta($user_id, 'billing_phone', true),
                'address_1'  => get_user_meta($user_id, 'billing_address_1', true),
                'city'       => get_user_meta($user_id, 'billing_city', true),
                'postcode'   => get_user_meta($user_id, 'billing_postcode', true),
                'country'    => get_user_meta($user_id, 'billing_country', true) ?: 'PL',
                'state'      => get_user_meta($user_id, 'billing_state', true),
            );

            $order->set_address($billing_address, 'billing');
            $order->set_address($billing_address, 'shipping');
        }

        // Ustaw metodę płatności
        $payment_method = get_theme_option('checkout.default_payment_method', 'bacs');
        $allowed_methods = get_theme_option('checkout.allowed_payment_methods', array('bacs'));

        if (in_array($payment_method, $allowed_methods)) {
            $order->set_payment_method($payment_method);
        }

        // Oblicz kwoty
        $order->calculate_totals();

        // Zapisz zamówienie
        $order->save();

        // Zmień status na processing lub pending
        if ($payment_method === 'bacs') {
            $order->update_status('pending-payment', __('Zamówienie złożone przez one-click checkout.', 'universal-theme'));
        } else {
            $order->update_status('processing', __('Zamówienie złożone przez one-click checkout.', 'universal-theme'));
        }

        // Wyślij email z potwierdzeniem
        WC()->mailer()->get_emails()['WC_Email_New_Order']->trigger($order->get_id(), $order);

        // Zmniejsz stan magazynowy
        wc_reduce_stock_levels($order->get_id());

        // Zwróć sukces
        $redirect_url = '';
        $success_redirect = get_theme_option('checkout.success_redirect', 'order_received');

        if ($success_redirect === 'order_received') {
            $redirect_url = $order->get_checkout_order_received_url();
        } elseif (filter_var($success_redirect, FILTER_VALIDATE_URL)) {
            $redirect_url = $success_redirect;
        }

        wp_send_json_success(array(
            'message' => __('Zamówienie zostało złożone pomyślnie!', 'universal-theme'),
            'order_id' => $order->get_id(),
            'redirect_url' => $redirect_url,
            'order_key' => $order->get_order_key()
        ));
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

/**
 * AJAX: Dodaj do koszyka i przekieruj na checkout
 */
function universal_add_to_cart_and_redirect()
{
    try {
        // Sprawdź nonce
        if (!wp_verify_nonce($_POST['nonce'], 'universal_one_click_nonce')) {
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

        // Sprawdź dostępność
        if (!$product->has_enough_stock($quantity)) {
            throw new Exception(__('Niewystarczająca ilość w magazynie.', 'universal-theme'));
        }

        // Sprawdź czy czyścić koszyk przed dodaniem produktu
        if (get_theme_option('checkout.clear_cart_before_add', false)) {
            universal_debug_log('Universal Debug: Czyszczę koszyk przed dodaniem produktu');
            WC()->cart->empty_cart();
        } else {
            universal_debug_log('Universal Debug: Dodaję produkt do istniejącego koszyka (sumowanie)');
        }

        // Dodaj produkt do koszyka
        // Ensure cart is available in AJAX context
        if (! function_exists('WC') || ! WC()->cart) {
            universal_debug_log("Universal Debug: WC()->cart not available when adding product {$product_id}");
            throw new Exception(__('Koszyk chwilowo niedostępny. Spróbuj ponownie.', 'universal-theme'));
        }

        // Handle variable products gracefully: require variant selection if no defaults
        if ($product->is_type('variable')) {
            $default_attrs = $product->get_default_attributes();
            if (empty($default_attrs)) {
                // Variable product without default variant - cannot add from list via one-click
                universal_debug_log("Universal Debug: Variable product {$product_id} requires variant selection");
                throw new Exception(__('Produkt wymaga wyboru wariantu. Otwórz stronę produktu i wybierz wariant przed dodaniem do koszyka.', 'universal-theme'));
            }
            // If default attributes exist, attempt to add the parent product (WC will try to match), but capture failure
        }

        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);

        if (! $cart_item_key) {
            universal_debug_log("Universal Debug: add_to_cart failed for product {$product_id} (type={$product->get_type()}) qty={$quantity}");
            // Provide a clearer message for variable / complex products
            if ($product->is_type('variable')) {
                throw new Exception(__('Nie udało się dodać produktu - wybierz wariant na stronie produktu.', 'universal-theme'));
            }
            throw new Exception(__('Nie udało się dodać produktu do koszyka.', 'universal-theme'));
        }

        // Zwróć URL checkout
        $checkout_url = wc_get_checkout_url();

        wp_send_json_success(array(
            'message' => __('Produkt został dodany do koszyka!', 'universal-theme'),
            'redirect_url' => $checkout_url,
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'product_name' => $product->get_name()
        ));
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}
