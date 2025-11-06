<?php

/**
 * Konfiguracja motywu - łatwe dostosowanie do różnych sklepów
 */

// Główne ustawienia motywu
function get_theme_config()
{
    return array(
        // Podstawowe informacje
        'theme_name' => 'Universal Store Theme',
        'theme_version' => '1.0.0',

        // Kolory (można łatwo zmieniać dla różnych sklepów)
        'colors' => array(
            'primary' => '#e74c3c',           // Główny kolor marki
            'secondary' => '#3498db',         // Kolor drugorzędny
            'accent' => '#f39c12',            // Kolor akcji/przycisków
            'text_dark' => '#2c3e50',         // Ciemny tekst
            'text_light' => '#7f8c8d',        // Jasny tekst
            'background' => '#ffffff',        // Tło
            'background_alt' => '#f8f9fa',    // Alternatywne tło
        ),

        // Typografia
        'typography' => array(
            'primary_font' => 'Inter, sans-serif',
            'secondary_font' => 'Inter, sans-serif',
            'heading_font_weight' => '600',
            'body_font_weight' => '400',
        ),

        // Layout
        'layout' => array(
            'container_width' => '1200px',
            'border_radius' => '8px',
            'box_shadow' => '0 2px 10px rgba(0,0,0,0.1)',
        ),

        // WooCommerce
        'woocommerce' => array(
            'products_per_page' => 12,
            'products_per_row' => 4,
            'product_gallery_zoom' => true,
            'product_gallery_lightbox' => true,
        ),

        // Funkcje
        'features' => array(
            'custom_header' => true,
            'custom_footer' => true,
            'mega_menu' => false,
            'quick_view' => true,
            'wishlist' => false,
        )
    );
}

// Funkcja pomocnicza do pobierania wartości konfiguracji
function get_theme_option($key, $default = '')
{
    $config = get_theme_config();
    $keys = explode('.', $key);
    $value = $config;

    foreach ($keys as $k) {
        if (isset($value[$k])) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }

    return $value;
}
