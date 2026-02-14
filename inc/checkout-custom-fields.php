<?php

/**
 * Custom Checkout Fields - Faktura VAT
 * Dodatkowe pola dla faktury VAT na stronie checkout
 */

// Zapobiegnij bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dodaj pole NIP do checkout
 */
function jetlagz_add_invoice_fields($fields)
{
    // Dodaj pole NIP - opcjonalne
    $fields['billing']['billing_nip'] = array(
        'type'          => 'text',
        'label'         => __('NIP (podaj jedynie jeśli chcesz otrzymać FV)', 'jetlagz-theme'),
        'placeholder'   => __('123-456-78-90', 'jetlagz-theme'),
        'required'      => false,
        'class'         => array('form-row-wide', 'invoice-nip-field'),
        'priority'      => 120, // Po checkboxie "Wysłać na inny adres"
        'clear'         => true,
    );

    return $fields;
}
add_filter('woocommerce_checkout_fields', 'jetlagz_add_invoice_fields');

/**
 * Walidacja pola NIP - opcjonalna walidacja formatu jeśli zostało wypełnione
 */
function jetlagz_validate_invoice_fields()
{
    // Jeśli NIP został wypełniony, sprawdź jego format
    if (!empty($_POST['billing_nip'])) {
        // Walidacja formatu NIP (usuń spacje i myślniki)
        $nip = preg_replace('/[\s\-]/', '', $_POST['billing_nip']);

        // NIP powinien mieć 10 cyfr
        if (!preg_match('/^[0-9]{10}$/', $nip)) {
            wc_add_notice(__('Numer NIP powinien zawierać 10 cyfr.', 'jetlagz-theme'), 'error');
        }
    }
}
add_action('woocommerce_checkout_process', 'jetlagz_validate_invoice_fields');

/**
 * Zapisz dane faktury VAT do zamówienia
 */
function jetlagz_save_invoice_fields($order_id)
{
    if (!empty($_POST['billing_nip'])) {
        update_post_meta($order_id, '_billing_nip', sanitize_text_field($_POST['billing_nip']));
    }
}
add_action('woocommerce_checkout_update_order_meta', 'jetlagz_save_invoice_fields');

/**
 * Wyświetl dane faktury w panelu administracyjnym
 */
function jetlagz_display_invoice_fields_admin($order)
{
    $nip = get_post_meta($order->get_id(), '_billing_nip', true);

    if ($nip) {
        echo '<div class="order_data_column">';
        echo '<h3>' . __('Faktura VAT', 'jetlagz-theme') . '</h3>';
        echo '<p><strong>' . __('NIP:', 'jetlagz-theme') . '</strong> ' . esc_html($nip) . '</p>';
        
        $company = $order->get_billing_company();
        if ($company) {
            echo '<p><strong>' . __('Nazwa firmy:', 'jetlagz-theme') . '</strong> ' . esc_html($company) . '</p>';
        }
        
        echo '</div>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'jetlagz_display_invoice_fields_admin');

/**
 * Dodaj dane faktury do emaili zamówienia
 */
function jetlagz_add_invoice_to_emails($order, $sent_to_admin, $plain_text, $email)
{
    $nip = get_post_meta($order->get_id(), '_billing_nip', true);

    if ($nip) {
        if ($plain_text) {
            echo "\n" . __('FAKTURA VAT', 'jetlagz-theme') . "\n";
            echo "------------------------\n";
            echo __('NIP:', 'jetlagz-theme') . ' ' . $nip . "\n";
            $company = $order->get_billing_company();
            if ($company) {
                echo __('Firma:', 'jetlagz-theme') . ' ' . $company . "\n";
            }
            echo "\n";
        } else {
            echo '<h2>' . __('Faktura VAT', 'jetlagz-theme') . '</h2>';
            echo '<p>';
            echo '<strong>' . __('NIP:', 'jetlagz-theme') . '</strong> ' . esc_html($nip) . '<br>';
            $company = $order->get_billing_company();
            if ($company) {
                echo '<strong>' . __('Firma:', 'jetlagz-theme') . '</strong> ' . esc_html($company);
            }
            echo '</p>';
        }
    }
}
add_action('woocommerce_email_after_order_table', 'jetlagz_add_invoice_to_emails', 20, 4);

/**
 * Wyświetl dane faktury na stronie "Dziękujemy za zamówienie"
 */
function jetlagz_display_invoice_on_thankyou($order_id)
{
    $nip = get_post_meta($order_id, '_billing_nip', true);

    if ($nip) {
        echo '<section class="woocommerce-invoice-data">';
        echo '<h2 class="woocommerce-column__title">' . __('Faktura VAT', 'jetlagz-theme') . '</h2>';
        echo '<address>';
        echo '<p><strong>' . __('NIP:', 'jetlagz-theme') . '</strong> ' . esc_html($nip) . '</p>';
        
        $order = wc_get_order($order_id);
        $company = $order->get_billing_company();
        if ($company) {
            echo '<p><strong>' . __('Firma:', 'jetlagz-theme') . '</strong> ' . esc_html($company) . '</p>';
        }
        
        echo '</address>';
        echo '</section>';
    }
}
add_action('woocommerce_thankyou', 'jetlagz_display_invoice_on_thankyou', 20);
