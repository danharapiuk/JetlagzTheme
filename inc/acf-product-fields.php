<?php

/**
 * ACF Product Custom Fields
 * 
 * Dodatkowe pola dla produktów WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rejestracja pola "Stara nazwa" dla produktów
 */
function jetlagz_register_product_acf_fields()
{
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_product_old_name',
        'title' => 'Dodatkowe informacje o produkcie',
        'fields' => array(
            array(
                'key' => 'field_product_old_name',
                'label' => 'Stara nazwa',
                'name' => 'stara_nazwa',
                'type' => 'text',
                'instructions' => 'Poprzednia nazwa produktu (do celów archiwizacyjnych, synchronizacji z Baselinker)',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '100',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'placeholder' => 'Wpisz starą nazwę produktu...',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'product',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'side', // Wyświetl w panelu bocznym
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => 'Pola do przechowywania dodatkowych informacji o produkcie',
    ));
}
add_action('acf/init', 'jetlagz_register_product_acf_fields');

/**
 * Opcjonalnie: Wyświetl starą nazwę na stronie produktu (np. jako podtytuł)
 * Odkomentuj poniższy kod jeśli chcesz wyświetlać starą nazwę pod tytułem produktu
 */
/*
function jetlagz_display_old_product_name() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $old_name = get_field('stara_nazwa', $product->get_id());
    
    if ($old_name) {
        echo '<p class="product-old-name" style="font-size: 14px; color: #666; margin-top: -10px; margin-bottom: 15px;">';
        echo '<span style="font-weight: 300;">Poprzednio: </span>' . esc_html($old_name);
        echo '</p>';
    }
}
add_action('woocommerce_single_product_summary', 'jetlagz_display_old_product_name', 6);
*/
