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

/**
 * Sync product slug from ACF field "product_name" when field is not empty.
 */
function jetlagz_generate_slug_from_product_name($post_id, $post = null, $update = null)
{
    $post_id = absint($post_id);

    if (!$post_id || get_post_type($post_id) !== 'product') {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $product_name = '';

    if (function_exists('get_field')) {
        $product_name = get_field('product_name', $post_id);
    }

    if (!is_string($product_name) || trim($product_name) === '') {
        $product_name = get_post_meta($post_id, 'product_name', true);
    }

    if (!is_string($product_name) || trim($product_name) === '') {
        return;
    }

    $new_slug = sanitize_title(trim($product_name));

    if ($new_slug === '') {
        return;
    }

    $current_slug = (string) get_post_field('post_name', $post_id);

    if ($new_slug === $current_slug) {
        return;
    }

    $new_slug = wp_unique_post_slug($new_slug, $post_id, get_post_status($post_id), 'product', 0);

    // Prevent recursion for both hooks that can trigger wp_update_post.
    remove_action('acf/save_post', 'jetlagz_generate_slug_from_product_name', 20);
    remove_action('save_post_product', 'jetlagz_generate_slug_from_product_name', 20);

    wp_update_post(array(
        'ID' => $post_id,
        'post_name' => $new_slug,
    ));

    add_action('acf/save_post', 'jetlagz_generate_slug_from_product_name', 20);
    add_action('save_post_product', 'jetlagz_generate_slug_from_product_name', 20, 3);
}
add_action('acf/save_post', 'jetlagz_generate_slug_from_product_name', 20);
add_action('save_post_product', 'jetlagz_generate_slug_from_product_name', 20, 3);

/**
 * Zwraca URL obrazka z pola ACF "flat" dla produktu lub wariantu.
 * Dla kompatybilności wstecznej czyta też starsze pole "fotka".
 */
function jetlagz_get_flat_image_url($post_id)
{
    $post_id = absint($post_id);

    if (!$post_id) {
        return '';
    }

    $sources = array($post_id);

    if (get_post_type($post_id) === 'product_variation') {
        $parent_id = wp_get_post_parent_id($post_id);
        if ($parent_id) {
            $sources[] = $parent_id;
        }
    }

    foreach ($sources as $source_id) {
        $raw = get_post_meta($source_id, 'flat', true);

        // Backward compatibility for existing products using old key.
        if (!is_numeric($raw) && (!is_string($raw) || trim($raw) === '')) {
            $raw = get_post_meta($source_id, 'fotka', true);
        }

        if (is_numeric($raw) && (int) $raw > 0) {
            $url = wp_get_attachment_url((int) $raw);
            if ($url) {
                return esc_url_raw($url);
            }
        }

        if (is_string($raw) && trim($raw) !== '') {
            return esc_url_raw(trim($raw));
        }
    }

    return '';
}

/**
 * Przy zapisie produktu lub wariantu zapisuje URL z pola "flat" do meta klucza
 * "_flat_url" – bezpośrednio w bazie danych, bez żadnych ACF-owych filtrów.
 * CTX Feed może ten klucz odczytać jako zwykłe Custom Field.
 */
function jetlagz_sync_flat_url_on_save($post_id)
{
    $post_id = absint($post_id);
    if (!$post_id) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    $post_type = get_post_type($post_id);

    if (!in_array($post_type, array('product', 'product_variation'), true)) {
        return;
    }

    // Pobierz URL dla tego produktu/wariantu (warianty szukają też u rodzica)
    $url = jetlagz_get_flat_image_url($post_id);

    if ($url !== '') {
        update_post_meta($post_id, '_flat_url', $url);
        // Keep legacy key for existing feed configurations.
        update_post_meta($post_id, '_fotka_url', $url);
    } else {
        delete_post_meta($post_id, '_flat_url');
        delete_post_meta($post_id, '_fotka_url');
    }

    // Jeśli to produkt nadrzędny – zsynchronizuj też wszystkie warianty
    if ($post_type === 'product') {
        $variation_ids = get_posts(array(
            'post_type'   => 'product_variation',
            'post_parent' => $post_id,
            'fields'      => 'ids',
            'numberposts' => -1,
            'post_status' => 'any',
        ));

        foreach ((array) $variation_ids as $var_id) {
            $var_url = jetlagz_get_flat_image_url((int) $var_id);
            if ($var_url !== '') {
                update_post_meta($var_id, '_flat_url', $var_url);
                update_post_meta($var_id, '_fotka_url', $var_url);
            } else {
                delete_post_meta($var_id, '_flat_url');
                delete_post_meta($var_id, '_fotka_url');
            }
        }
    }
}
add_action('acf/save_post', 'jetlagz_sync_flat_url_on_save', 30);
add_action('save_post_product', 'jetlagz_sync_flat_url_on_save', 30);
add_action('save_post_product_variation', 'jetlagz_sync_flat_url_on_save', 30);
