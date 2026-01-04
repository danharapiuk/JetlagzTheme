<?php

/**
 * ACF Options Pages dla Template Parts
 * 
 * System do zarządzania treścią template-parts przez ACF Options
 * Pola ACF definiujesz samodzielnie w WP Admin → Custom Fields
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rejestracja ACF Options Page
 * Czysty system - pola dodajesz sam przez interfejs ACF
 */
function jetlagz_register_acf_options_pages()
{
    if (function_exists('acf_add_options_page')) {

        // Główna strona opcji dla Template Parts
        acf_add_options_page(array(
            'page_title'    => 'Template Parts',
            'menu_title'    => 'Template Parts',
            'menu_slug'     => 'template-parts-settings',
            'capability'    => 'edit_posts',
            'icon_url'      => 'dashicons-layout',
            'position'      => 30,
            'redirect'      => false
        ));

        // Strona opcji dla tabeli rozmiarów
        acf_add_options_page(array(
            'page_title'    => 'Tabela rozmiarów',
            'menu_title'    => 'Tabela rozmiarów',
            'menu_slug'     => 'sizes-settings',
            'capability'    => 'edit_posts',
            'icon_url'      => 'dashicons-admin-appearance',
            'position'      => 31,
            'redirect'      => false
        ));
    }
}
add_action('acf/init', 'jetlagz_register_acf_options_pages');
