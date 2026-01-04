<?php

/**
 * Ogólne funkcje motywu
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    // DEBUG: Sprawdź czy funkcja się wykonuje
    error_log('Universal Theme: admin_menu hook executed');

    // KLUCZOWE: Problem był w tym, że zakładka "Wygląd" i "Motywy" mają tę samą ścieżkę themes.php
    // Gdy klikniesz "Wygląd", WordPress przekierowuje do pierwszego podmenu (themes.php)
    // Ale jak ukryjemy themes.php, nie ma gdzie przekierować i cała zakładka wydaje się nieaktywna
    //
    // ROZWIĄZANIE: 
    // 1. Nasz panel "Ustawienia Motywu" ma priorytet 5 = będzie PIERWSZY
    // 2. Ta funkcja ma priorytet 99 = będzie PÓŹNIEJ usuwać niepotrzebne opcje
    // 3. Dodano redirect z themes.php do naszego panelu

    // ZAKŁADKA "WYGLĄD" BĘDZIE PRZEKIEROWYWAĆ DO NASZEGO PANELU

    // BLOKADY TYLKO KONKRETNYCH PODMENU (bez themes.php):
    // remove_submenu_page('themes.php', 'themes.php'); // WYŁĄCZONE - ukrywało całą zakładkę!
    remove_submenu_page('themes.php', 'customize.php'); // Personalizacja - zablokowana  
    remove_submenu_page('themes.php', 'theme-editor.php'); // Edytor plików motywu - zablokowany
    remove_submenu_page('themes.php', 'widgets.php'); // Widżety - zablokowane

    // BLOKADY PROJEKT (Site Editor) - różne możliwe nazwy:
    remove_submenu_page('themes.php', 'site-editor.php'); // Site Editor (WordPress 6.0+)
    remove_submenu_page('themes.php', 'gutenberg-edit-site'); // Gutenberg Site Editor
    remove_submenu_page('themes.php', 'edit-site.php'); // Alternatywna nazwa
    remove_submenu_page('themes.php', 'edit.php?post_type=wp_template'); // Templates editor

    // BLOKADY STOREFRONT - wszystkie możliwe strony:
    remove_submenu_page('themes.php', 'storefront-welcome'); // Wygląd → Storefront
    remove_submenu_page('themes.php', 'storefront-setup'); // Storefront Setup  
    remove_submenu_page('themes.php', 'storefront-changelog'); // Storefront Changelog
    remove_submenu_page('themes.php', 'storefront-pro'); // Storefront Pro

    // POZOSTAWIONE DOSTĘPNE:
    // ✅ themes.php (główna strona Wygląd) - MUSI POZOSTAĆ dla widoczności zakładki
    // ✅ nav-menus.php (Menu) - DOSTĘPNE  
    // ✅ universal-theme-settings (nasz panel) - DOSTĘPNE
    // 
    // ZABLOKOWANE PODMENU:
    // ❌ Personalizacja, Edytor plików, Widżety, Projekt, Storefront
    // 
    // UWAGA: Opcje zmiany motywu ukrywane przez CSS, nie przez blokowanie menu!

    // UPEWNIJ SIĘ ŻE ZAKŁADKA "WYGLĄD" JEST WIDOCZNA
    // Dodaj zmienną globalna by WordPress wiedział że ma pokazać menu
    global $menu, $submenu;

    // Jeśli zakładka Wygląd nie istnieje, dodaj ją
    if (!array_key_exists('themes.php', $submenu) || empty($submenu['themes.php'])) {
        // Może być potrzebne dodanie podstawowej strony
        add_submenu_page(
            'themes.php',
            'Motyw',
            'Aktualny motyw',
            'manage_options',
            'themes.php'
        );
    }
}, 99); // NIŻSZY PRIORYTET = po dodaniu naszego panelu (który ma priorytet 5)

/**
 * Usunięcie przycisku "Dostosuj" z paska narzędzi WordPress
 */
function universal_theme_remove_customize_toolbar($wp_admin_bar)
{
    // Usuń link "Dostosuj" z toolbar
    $wp_admin_bar->remove_node('customize');
}
add_action('admin_bar_menu', 'universal_theme_remove_customize_toolbar', 999);

/**
 * Blokowanie dostępu do customizer i innych zabronionych stron przez URL
 */
function universal_theme_block_customizer_access()
{
    global $pagenow;

    // Blokuj customize.php
    if ($pagenow === 'customize.php') {
        wp_die(
            __('Dostęp do personalizacji motywu został zablokowany.', 'universal-theme'),
            __('Dostęp zabroniony', 'universal-theme'),
            array('response' => 403)
        );
    }

    // CZASOWO WYŁĄCZONE: Blokowanie dostępu do głównej strony motywów
    // Może to powodować ukrycie całej zakładki "Wygląd"
    /*
    if ($pagenow === 'themes.php' && !isset($_GET['page'])) {
        wp_die(
            __('Dostęp do zmiany motywu został zablokowany.', 'universal-theme'),
            __('Dostęp zabroniony', 'universal-theme'),
            array('response' => 403)
        );
    }
    */

    // NOWE: Blokuj dostęp do Site Editor (Projekt)
    if ($pagenow === 'site-editor.php' || $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'wp_template') {
        wp_die(
            __('Dostęp do edytora witryny został zablokowany.', 'universal-theme'),
            __('Dostęp zabroniony', 'universal-theme'),
            array('response' => 403)
        );
    }

    // NOWE: Blokuj strony Storefront
    if (isset($_GET['page']) && (
        $_GET['page'] === 'storefront-welcome' ||
        $_GET['page'] === 'storefront-setup' ||
        strpos($_GET['page'], 'storefront') !== false
    )) {
        wp_die(
            __('Dostęp do ustawień motywu Storefront został zablokowany.', 'universal-theme'),
            __('Dostęp zabroniony', 'universal-theme'),
            array('response' => 403)
        );
    }

    // Blokuj dostęp przez admin-ajax.php
    if (defined('DOING_AJAX') && DOING_AJAX) {
        if (isset($_REQUEST['action']) && (
            strpos($_REQUEST['action'], 'customize') !== false ||
            strpos($_REQUEST['action'], 'storefront') !== false ||
            strpos($_REQUEST['action'], 'edit-site') !== false
        )) {
            wp_die(__('Dostęp do tej funkcjonalności został zablokowany.', 'universal-theme'));
        }
    }
}
add_action('admin_init', 'universal_theme_block_customizer_access');

/**
 * Usunięcie metabox "Dostosuj" ze stron i postów
 */
function universal_theme_remove_customize_meta_boxes()
{
    // Usuń wszystkie metaboxy związane z customizer
    remove_meta_box('customize-homepage', 'page', 'normal');
    remove_meta_box('customize-homepage', 'post', 'normal');
}
add_action('add_meta_boxes', 'universal_theme_remove_customize_meta_boxes', 999);

/**
 * Ukrycie linków do customizer w CSS (admin)
 */
function universal_theme_hide_customize_links()
{
    echo '<style>
        .customize-support .hide-if-no-customize,
        .customize-support .wp-core-ui .button-link-delete,
        a[href*="customize.php"],
        #customize-theme,
        .customize-control,
        .theme-options .theme-overlay .theme-actions .button[href*="customize"] {
            display: none !important;
        }
        
        /* TYLKO bardzo konkretne ukrycie opcji motywów - nie całej zakładki! */
        body.themes-php .theme-browser .theme .theme-actions .activate,
        body.themes-php .theme-overlay .theme-actions .activate,
        body.themes-php .available-theme .activate,
        body.themes-php .theme-screenshot .more-details {
            display: none !important;
        }
        
        /* Ukryj konkretne elementy interfejsu motywów ale ZACHOWAJ zakładkę */
        /* .theme-browser, */
        /* .theme-overlay, */
        /* .theme-actions, */
        /* .theme-screenshot, */
        /* .available-theme, */
        /* .theme-browser .theme, */
        /* .wrap .theme-browser, */
        /* .themes-php .theme-browser, */
        /* .theme-overlay .theme-actions .activate, */
        /* .theme-screenshot .more-details, */
        /* .theme-browser .theme:not(.active) { */
        /*     display: none !important; */
        /* } */
        
        /* Ukryj całą zawartość strony motywów gdy ktoś spróbuje wejść */
        body.themes-php .wrap > h1 + .theme-browser,
        body.themes-php .wrap .theme-browser {
            display: none !important;
        }
        
        /* Pokaż tylko komunikat o przekierowaniu */
        body.themes-php .wrap:after {
            content: "🎨 Opcje motywu zostały przeniesione do: Wygląd → Ustawienia Motywu";
            display: block;
            padding: 20px;
            background: #f0f8ff;
            border: 2px solid #0073aa;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 16px;
            text-align: center;
        }
        
        /* USUŃ ukrywanie linków do podmenu - to ukrywało całą zakładkę! */
        /* #menu-appearance a[href="themes.php"]:not([href*="page="]), */
        /* .wp-submenu a[href="themes.php"]:not([href*="page="]) { */
        /*     display: none !important; */
        /* } */
        
        /* ZACHOWAJ widoczność zakładki, ale ukryj funkcje zmiany */
        .theme-browser .theme.active .theme-actions .activate,
        .theme-overlay .theme-actions .button[href*="customize"] {
            display: none !important;
        }
        
        /* WYMUŚ widoczność zakładki Wygląd */
        #adminmenu #menu-appearance,
        #adminmenu li#menu-appearance,
        #menu-appearance,
        .wp-menu-name:contains("Wygląd") {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        /* Ukryj w admin bar */
        #wpadminbar #wp-admin-bar-customize {
            display: none !important;
        }
        
        /* NOWE: Ukryj pierwszy element podmenu (Motywy) */
        #menu-appearance .wp-submenu li:first-child,
        #adminmenu #menu-appearance .wp-submenu a[href="themes.php"]:not([href*="page="]),
        .wp-submenu a[href="themes.php"]:not([href*="page="]),
        #adminmenu #menu-appearance .wp-submenu li a[href="themes.php"]:not([href*="page="]),
        #menu-appearance .wp-submenu li a[href="themes.php"]:not([href*="page="]) {
            display: none !important;
        }
        
        /* Dodatkowe ukrycie dla linku "Motywy" w różnych kontekstach */
        #adminmenu .wp-submenu a[href*="/wp-admin/themes.php"]:not([href*="page="]),
        #menu-appearance ul.wp-submenu li:first-child a {
            display: none !important;
        }
        
        /* NOWE: Ukryj Edytor plików motywu (theme-editor.php) */
        #menu-appearance .wp-submenu a[href*="theme-editor.php"],
        #adminmenu #menu-appearance .wp-submenu a[href*="theme-editor.php"],
        .wp-submenu a[href*="theme-editor.php"],
        #adminmenu .wp-submenu a[href*="/wp-admin/theme-editor.php"] {
            display: none !important;
        }
        
        /* NOWE: Ukryj Wygląd → Projekt i Wygląd → Storefront */
        a[href*="edit-site"], 
        a[href*="gutenberg-edit-site"], 
        a[href*="wp_template"],
        a[href*="storefront-welcome"], 
        a[href*="storefront-setup"],
        li[class*="storefront"],
        #menu-appearance a[href*="site-editor.php"] {
            display: none !important;
        }
    </style>';
}
add_action('admin_head', 'universal_theme_hide_customize_links');
add_action('wp_head', 'universal_theme_hide_customize_links');

/**
 * Layout CSS z konfiguracji motywu - kontrola sidebar
 */
function universal_theme_layout_css()
{
    $layout_config = get_theme_option('layout');

    echo '<style type="text/css">';

    // Sidebar control
    $enable_sidebar = $layout_config['enable_sidebar'] ?? true;
    if (!$enable_sidebar) {
        echo '.widget-area, .sidebar, #secondary { display: none !important; }';
        echo '.content-area { width: 100% !important; max-width: 100% !important; }';
        echo '#primary { width: 100% !important; }';
        echo '.no-wc-breadcrumb:not(.page-template-template-fullwidth) #primary { width: 100% !important; }';
    }

    // Container główny

    echo '</style>';
}
add_action('wp_head', 'universal_theme_layout_css');
