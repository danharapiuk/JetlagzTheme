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
    error_log('Universal Debug: enable_one_click = ' . ($enable_one_click ? 'true' : 'false'));

    if (!$enable_one_click) {
        error_log('Universal Debug: One-click checkout is disabled');
        return;
    }

    error_log('Universal Debug: One-click checkout enabled, setting up hooks');

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
    error_log('Universal Debug: universal_display_one_click_button called');

    if (!$product) {
        error_log('Universal Debug: Brak obiektu $product');
        return;
    }

    if (!universal_can_show_one_click_button($product)) {
        error_log('Universal Debug: universal_can_show_one_click_button returned false');
        return;
    }

    error_log('Universal Debug: Renderuję przycisk one-click dla produktu: ' . $product->get_id());

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
    error_log('Universal Debug: Sprawdzam czy można pokazać przycisk...');

    if (!$product || !is_a($product, 'WC_Product')) {
        error_log('Universal Debug: Brak produktu lub nieprawidłowy typ');
        return false;
    }

    // Sprawdź czy produkt jest dostępny
    if (!$product->is_purchasable() || !$product->is_in_stock()) {
        error_log('Universal Debug: Produkt niedostępny lub brak w magazynie');
        return false;
    }

    // Sprawdź czy to produkt zmienhy (wymaga wyboru opcji)
    if ($product->is_type('variable') && !$product->get_default_attributes()) {
        error_log('Universal Debug: Produkt zmienhy bez domyślnych atrybutów');
        return false;
    }

    // W trybie modal checkout - pokaż dla wszystkich
    if (get_theme_option('checkout.modal_checkout', false)) {
        error_log('Universal Debug: Tryb modal checkout - pokazuję przycisk');
        return true;
    }

    // Klasyczna logika - sprawdź czy wymagane logowanie
    $require_login = get_theme_option('checkout.require_login', true);
    error_log('Universal Debug: require_login = ' . ($require_login ? 'true' : 'false'));

    if ($require_login && !is_user_logged_in()) {
        error_log('Universal Debug: Wymagane logowanie, a użytkownik nie zalogowany');
        return false;
    }

    error_log('Universal Debug: Wszystkie warunki spełnione - pokazuję przycisk');
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
 * Dodaj custom checkout fields jeśli potrzebne
 */
function universal_add_one_click_checkout_fields($fields)
{
    // Można dodać dodatkowe pola specyficzne dla one-click checkout
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'universal_add_one_click_checkout_fields');

/**
 * Admin: Dodaj informację o one-click checkout do zamówienia
 */
function universal_add_one_click_order_meta($order_id)
{
    if (isset($_POST['universal_one_click']) && $_POST['universal_one_click'] === 'true') {
        update_post_meta($order_id, '_universal_one_click_order', 'yes');
    }
}
add_action('woocommerce_checkout_update_order_meta', 'universal_add_one_click_order_meta');

/**
 * Wyświetl informację o one-click w admin zamówienia
 */
function universal_display_one_click_order_meta($order)
{
    $is_one_click = get_post_meta($order->get_id(), '_universal_one_click_order', true);
    if ($is_one_click === 'yes') {
        echo '<p><strong>' . __('One-Click Checkout:', 'universal-theme') . '</strong> ' . __('Tak', 'universal-theme') . '</p>';
    }
}
add_action('woocommerce_admin_order_data_after_order_details', 'universal_display_one_click_order_meta');

/**
 * Wyświetl modal checkout w footer
 */
function universal_render_checkout_modal()
{
    if (!get_theme_option('checkout.modal_checkout', false)) {
        return;
    }

    $modal_title = get_theme_option('checkout.modal_title', 'Finalizuj zamówienie');
    $payment_methods = get_theme_option('checkout.allowed_payment_methods', array('bacs'));
?>
    <div id="universal-checkout-modal" class="universal-modal">
        <div class="universal-modal-content">
            <div class="universal-modal-header">
                <h2 class="universal-modal-title"><?php echo esc_html($modal_title); ?></h2>
                <button class="universal-close" aria-label="Zamknij">&times;</button>
            </div>

            <div class="universal-modal-body">
                <form id="universal-modal-checkout-form" class="universal-checkout-form">

                    <!-- Order Summary -->
                    <div class="checkout-section order-summary">
                        <h3><?php echo __('Podsumowanie zamówienia', 'universal-theme'); ?></h3>
                        <div class="order-items">
                            <!-- Dynamicznie wypełniane przez JS -->
                        </div>
                        <div class="order-totals">
                            <!-- Sumy -->
                        </div>
                    </div>

                    <!-- Customer Details -->
                    <div class="checkout-section customer-details">
                        <h3><?php echo __('Dane do wysyłki', 'universal-theme'); ?></h3>

                        <div class="form-row">
                            <div class="form-group half">
                                <label for="billing_first_name"><?php echo __('Imię', 'universal-theme'); ?> <span class="required">*</span></label>
                                <input type="text" id="billing_first_name" name="billing_first_name" required>
                            </div>
                            <div class="form-group half">
                                <label for="billing_last_name"><?php echo __('Nazwisko', 'universal-theme'); ?> <span class="required">*</span></label>
                                <input type="text" id="billing_last_name" name="billing_last_name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_email"><?php echo __('Email', 'universal-theme'); ?> <span class="required">*</span></label>
                                <input type="email" id="billing_email" name="billing_email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_address_1"><?php echo __('Adres', 'universal-theme'); ?> <span class="required">*</span></label>
                                <input type="text" id="billing_address_1" name="billing_address_1" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group half">
                                <label for="billing_city"><?php echo __('Miasto', 'universal-theme'); ?> <span class="required">*</span></label>
                                <input type="text" id="billing_city" name="billing_city" required>
                            </div>
                            <div class="form-group half">
                                <label for="billing_postcode"><?php echo __('Kod pocztowy', 'universal-theme'); ?> <span class="required">*</span></label>
                                <input type="text" id="billing_postcode" name="billing_postcode" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="billing_phone"><?php echo __('Telefon', 'universal-theme'); ?></label>
                                <input type="tel" id="billing_phone" name="billing_phone">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="checkout-section payment-methods">
                        <h3><?php echo __('Metoda płatności', 'universal-theme'); ?></h3>

                        <?php foreach ($payment_methods as $method_id):
                            $method_title = '';
                            switch ($method_id) {
                                case 'bacs':
                                    $method_title = 'Przelew bankowy';
                                    break;
                                case 'cod':
                                    $method_title = 'Płatność przy odbiorze';
                                    break;
                                case 'paypal':
                                    $method_title = 'PayPal';
                                    break;
                                case 'stripe':
                                    $method_title = 'Karta kredytowa';
                                    break;
                                default:
                                    $method_title = ucfirst($method_id);
                                    break;
                            }
                        ?>
                            <div class="payment-method">
                                <label>
                                    <input type="radio" name="payment_method" value="<?php echo esc_attr($method_id); ?>" <?php echo $method_id === 'bacs' ? 'checked' : ''; ?>>
                                    <span class="payment-label"><?php echo esc_html($method_title); ?></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Submit -->
                    <div class="checkout-section submit-section">
                        <button type="submit" class="universal-submit-order button">
                            <?php echo __('Złóż zamówienie', 'universal-theme'); ?>
                        </button>
                    </div>

                    <!-- Hidden Fields -->
                    <input type="hidden" id="modal-product-id" name="product_id" value="">
                    <input type="hidden" id="modal-quantity" name="quantity" value="1">
                    <input type="hidden" name="action" value="universal_modal_checkout">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('universal_modal_nonce'); ?>">
                </form>
            </div>
        </div>
    </div>
<?php
}
add_action('wp_footer', 'universal_render_checkout_modal');

/**
 * AJAX: Pobierz dane produktu dla modala
 */
function universal_get_product_data()
{
    // Sprawdź nonce
    if (!wp_verify_nonce($_POST['nonce'], 'universal_one_click_nonce')) {
        wp_die(__('Security check failed.', 'universal-theme'));
    }

    $product_id = absint($_POST['product_id'] ?? 0);
    $quantity = absint($_POST['quantity'] ?? 1);

    if (!$product_id) {
        wp_send_json_error(array(
            'message' => __('Nieprawidłowy produkt.', 'universal-theme')
        ));
    }

    $product = wc_get_product($product_id);
    if (!$product) {
        wp_send_json_error(array(
            'message' => __('Produkt nie został znaleziony.', 'universal-theme')
        ));
    }

    // Przygotuj dane produktu
    $subtotal = $product->get_price() * $quantity;
    $tax = 0; // Można dodać kalkulację podatku jeśli potrzebne
    $total = $subtotal + $tax;

    $data = array(
        'name' => $product->get_name(),
        'price_html' => wc_price($product->get_price()),
        'quantity' => $quantity,
        'subtotal' => wc_price($subtotal),
        'tax' => wc_price($tax),
        'total' => wc_price($total),
        'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: ''
    );

    wp_send_json_success($data);
}

/**
 * AJAX: Obsługa modal checkout
 */
function universal_handle_modal_checkout()
{
    try {
        // Sprawdź nonce
        if (!wp_verify_nonce($_POST['nonce'], 'universal_modal_nonce')) {
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

        // Utwórz zamówienie
        $order = wc_create_order();

        // Dodaj produkt
        $order->add_product($product, $quantity);

        // Ustaw dane klienta z formularza
        $billing_data = array(
            'first_name' => sanitize_text_field($_POST['billing_first_name'] ?? ''),
            'last_name'  => sanitize_text_field($_POST['billing_last_name'] ?? ''),
            'email'      => sanitize_email($_POST['billing_email'] ?? ''),
            'phone'      => sanitize_text_field($_POST['billing_phone'] ?? ''),
            'address_1'  => sanitize_text_field($_POST['billing_address_1'] ?? ''),
            'city'       => sanitize_text_field($_POST['billing_city'] ?? ''),
            'postcode'   => sanitize_text_field($_POST['billing_postcode'] ?? ''),
            'country'    => sanitize_text_field($_POST['billing_country'] ?? 'PL'),
        );

        // Walidacja wymaganych pól
        $required_fields = get_theme_option('checkout.guest_required_fields', array());
        foreach ($required_fields as $field => $required) {
            if ($required && empty($billing_data[str_replace('billing_', '', $field)])) {
                throw new Exception(sprintf(__('Pole %s jest wymagane.', 'universal-theme'), $field));
            }
        }

        $order->set_address($billing_data, 'billing');
        $order->set_address($billing_data, 'shipping');

        // Ustaw klienta jeśli zalogowany
        if (is_user_logged_in()) {
            $order->set_customer_id(get_current_user_id());
        } else {
            // Ustaw email dla gościa
            $order->set_billing_email($billing_data['email']);
        }

        // Ustaw metodę płatności
        $payment_method = sanitize_text_field($_POST['payment_method'] ?? get_theme_option('checkout.default_payment_method', 'bacs'));
        $allowed_methods = get_theme_option('checkout.allowed_payment_methods', array('bacs'));

        if (in_array($payment_method, $allowed_methods)) {
            $order->set_payment_method($payment_method);
        } else {
            throw new Exception(__('Nieprawidłowa metoda płatności.', 'universal-theme'));
        }

        // Oblicz kwoty
        $order->calculate_totals();

        // Zapisz zamówienie
        $order->save();

        // Ustaw status
        if ($payment_method === 'cod') {
            $order->update_status('processing', __('Zamówienie złożone przez modal checkout.', 'universal-theme'));
        } else {
            $order->update_status('pending-payment', __('Zamówienie złożone przez modal checkout - oczekuje na płatność.', 'universal-theme'));
        }

        // Dodaj meta info
        update_post_meta($order->get_id(), '_universal_modal_checkout', 'yes');

        // Wyślij emaile
        WC()->mailer()->get_emails()['WC_Email_New_Order']->trigger($order->get_id(), $order);

        // Zmniejsz stan magazynowy
        wc_reduce_stock_levels($order->get_id());

        // Przygotuj odpowiedź
        $redirect_url = '';
        if ($payment_method === 'paypal') {
            // Dla PayPal można dodać link do płatności
            $redirect_url = $order->get_checkout_payment_url();
        } else {
            // Dla innych metod - strona potwierdzenia
            $redirect_url = $order->get_checkout_order_received_url();
        }

        wp_send_json_success(array(
            'message' => __('Zamówienie zostało złożone pomyślnie!', 'universal-theme'),
            'order_id' => $order->get_id(),
            'order_key' => $order->get_order_key(),
            'redirect_url' => $redirect_url,
            'total' => $order->get_formatted_order_total()
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
            error_log('Universal Debug: Czyszczę koszyk przed dodaniem produktu');
            WC()->cart->empty_cart();
        } else {
            error_log('Universal Debug: Dodaję produkt do istniejącego koszyka (sumowanie)');
        }

        // Dodaj produkt do koszyka
        $cart_item_key = WC()->cart->add_to_cart($product_id, $quantity);

        if (!$cart_item_key) {
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
