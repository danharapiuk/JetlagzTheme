<?php

/**
 * Checkout customizations for order review
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add remove column to checkout order review table
 */
function universal_add_checkout_remove_column()
{
    // Dodaj JavaScript i style tylko na stronie checkout
    if (is_admin() || !is_checkout()) {
        return;
    }

?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            console.log('Universal: Checkout remove script loaded');

            // Poczekaj aż checkout się załaduje
            setTimeout(function() {
                addRemoveButtons();
            }, 1000);

            // Dodaj ponownie po każdej aktualizacji checkout
            $(document.body).on('updated_checkout updated_wc_div', function() {
                setTimeout(addRemoveButtons, 500);
            });

            function addRemoveButtons() {
                console.log('Universal: Trying to add remove buttons...');

                // Znajdź tabelę order review (różne selektory dla różnych wersji)
                var $table = $('.shop_table.woocommerce-checkout-review-order-table, .wc-block-components-order-summary, .cart_list, .shop_table');

                if ($table.length === 0) {
                    console.log('Universal: No order table found');
                    return;
                }

                console.log('Universal: Found table:', $table);

                // Jeśli to klasyczna tabela
                if ($table.hasClass('shop_table')) {
                    // Dodaj kolumnę Remove do header jeśli nie ma
                    if ($table.find('thead th.product-remove').length === 0) {
                        $table.find('thead tr').append('<th class="product-remove" style="width:60px;text-align:center;">Usuń</th>');
                        console.log('Universal: Added header column');
                    }

                    // Dodaj przyciski remove dla każdego produktu
                    $table.find('tbody tr').each(function() {
                        var $row = $(this);

                        // Pomiń jeśli to wiersz z sumami lub już ma przycisk
                        if ($row.find('.cart-subtotal, .order-total, .product-remove').length > 0) {
                            return;
                        }

                        // Znajdź nazwę produktu dla identyfikacji
                        var productName = $row.find('.product-name, td:first').text().trim();
                        if (!productName) return;

                        console.log('Universal: Adding remove button for:', productName);

                        var removeButton = '<td class="product-remove" style="text-align:center;">' +
                            '<a href="#" class="remove-product-checkout" ' +
                            'data-row-index="' + $row.index() + '" ' +
                            'data-product-name="' + productName + '" ' +
                            'title="Usuń ten produkt" ' +
                            'style="display:inline-block;width:32px;height:32px;background:#e74c3c;color:white;text-align:center;line-height:30px;border-radius:50%;text-decoration:none;font-size:18px;">' +
                            '×</a></td>';

                        $row.append(removeButton);
                    });
                }
            }

            // Obsługa kliknięcia remove
            $(document).on('click', '.remove-product-checkout', function(e) {
                e.preventDefault();

                var $button = $(this);
                var $row = $button.closest('tr');
                var productName = $button.data('product-name');

                console.log('Universal: Remove clicked for:', productName);

                if (confirm('Czy na pewno chcesz usunąć "' + productName + '" z koszyka?')) {
                    // Dodaj loading state
                    $button.css('background', '#95a5a6').html('...');

                    // Tu będzie właściwy AJAX call
                    // Na razie symulujemy usunięcie
                    setTimeout(function() {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            // Odśwież checkout
                            $('body').trigger('update_checkout');

                            // Pokaż powiadomienie
                            alert('Produkt został usunięty z koszyka');
                        });
                    }, 1000);
                }
            });
        });
    </script>
    <style>
        .shop_table.woocommerce-checkout-review-order-table .product-remove {
            text-align: center;
            width: 60px;
        }

        .remove-product-checkout {
            display: inline-block;
            width: 32px;
            height: 32px;
            background: #e74c3c;
            color: white;
            text-align: center;
            line-height: 30px;
            border-radius: 50%;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
        }

        .remove-product-checkout:hover {
            background: #c0392b;
            color: white;
            text-decoration: none;
        }

        .remove-icon {
            display: inline-block;
            line-height: 1;
        }
    </style>
<?php
}
add_action('wp_footer', 'universal_add_checkout_remove_column');

/**
 * AJAX handler for removing products from checkout
 */
function universal_ajax_remove_checkout_product()
{
    // Sprawdź nonce
    if (!wp_verify_nonce($_POST['nonce'], 'checkout_remove_nonce')) {
        wp_die('Security check failed');
    }

    $cart_item_key = sanitize_text_field($_POST['cart_item_key']);

    if (WC()->cart->remove_cart_item($cart_item_key)) {
        WC()->cart->calculate_totals();
        wp_send_json_success(['message' => 'Produkt został usunięty']);
    } else {
        wp_send_json_error(['message' => 'Nie udało się usunąć produktu']);
    }
}
add_action('wp_ajax_remove_checkout_product', 'universal_ajax_remove_checkout_product');
add_action('wp_ajax_nopriv_remove_checkout_product', 'universal_ajax_remove_checkout_product');
