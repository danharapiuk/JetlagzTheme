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

        // Kolory (domyślne Storefront - można łatwo zmieniać dla różnych sklepów)
        'colors' => array(
            'primary' => '#7f54b3',           // Domyślny kolor Storefront (fioletowy)
            'secondary' => '#3498db',         // Kolor drugorzędny
            'accent' => '#7f54b3',            // Kolor akcji/przycisków (taki sam jak primary)
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
            'content_width' => '800px',         // Szerokość treści
            'sidebar_width' => '300px',         // Szerokość sidebar
            'spacing_small' => '0.5rem',        // Małe odstępy
            'spacing_medium' => '1rem',         // Średnie odstępy  
            'spacing_large' => '2rem',          // Duże odstępy
            'spacing_xlarge' => '3rem',         // Bardzo duże odstępy
            'grid_gap' => '1.5rem',             // Odstęp w grid
            'enable_sidebar' => false,          // Czy włączyć sidebar
            'content_alignment' => 'left',      // Wyrównanie treści: left, center, right
            'max_width_full' => '100%',         // Maksymalna szerokość pełnej zawartości
            'enable_wide_alignment' => false,   // Czy włączyć szerokie wyrównanie
        ),

        // Header
        'header' => array(
            'background_image' => '', // Ścieżka do obrazu tła header (np. 'assets/images/header-bg.jpg')
            'background_color' => '', // Kolor tła header (jeśli nie ma obrazu)
            'height' => 'auto',       // Wysokość header
            'overlay_opacity' => 0.3, // Przezroczystość nakładki na obraz (0-1)
        ),

        // Footer
        'footer' => array(
            'background_image' => '',     // Ścieżka do obrazu tła footer
            'background_color' => '#2c3e50', // Kolor tła footer
            'text_color' => '#ffffff',    // Kolor tekstu w footer
            'overlay_opacity' => 0.2,     // Przezroczystość nakładki na obraz
        ),

        // Buttons & Forms
        'buttons' => array(
            'primary_bg' => '#e74c3c',    // Kolor tła głównych przycisków
            'primary_text' => '#ffffff',  // Kolor tekstu głównych przycisków
            'primary_hover' => '#c0392b', // Kolor hover głównych przycisków
            'secondary_bg' => '#3498db',  // Kolor tła drugorzędnych przycisków
            'secondary_text' => '#ffffff', // Kolor tekstu drugorzędnych przycisków
            'secondary_hover' => '#2980b9', // Kolor hover drugorzędnych przycisków
            'border_radius' => '8px',     // Zaokrąglenie rogów przycisków
            'padding' => '12px 24px',     // Padding przycisków
        ),

        // Background (strona)
        'background' => array(
            'body_bg' => '#ffffff',       // Kolor tła strony
            'body_image' => '',           // Obraz tła strony (jeśli potrzebny)
            'body_repeat' => 'no-repeat', // Powtarzanie obrazu tła
            'body_position' => 'center',  // Pozycja obrazu tła
            'body_size' => 'cover',       // Rozmiar obrazu tła
        ),

        // Layout
        'layout' => array(
            'container_width' => '1200px',   // Szerokość głównego kontenera
            'content_width' => '800px',      // Szerokość obszaru treści
            'sidebar_width' => '300px',      // Szerokość sidebar
            'enable_sidebar' => false,       // ← SIDEBAR WYŁĄCZONY
            'spacing_small' => '0.5rem',     // Małe odstępy
            'spacing_medium' => '1rem',      // Średnie odstępy
            'spacing_large' => '2rem',       // Duże odstępy
            'spacing_xlarge' => '3rem',      // Bardzo duże odstępy
            'grid_gap' => '1.5rem',          // Odstępy między elementami grid
            'content_alignment' => 'left',   // Wyrównanie treści (left, center, right)
            'enable_wide_alignment' => false, // Czy włączyć szerokie wyrównanie
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
