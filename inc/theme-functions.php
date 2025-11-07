<?php

/**
 * Ogólne funkcje motywu
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', function () {
    // Usuwa dostęp do motywów, personalizacji motywu i edytora motywu
    remove_menu_page('themes.php');
    // Usuwa określone podmenu, np.
    // remove_submenu_page('themes.php', 'themes.php'); // Lista motywów
    // remove_submenu_page('themes.php', 'customize.php'); // Personalizacja
    // remove_submenu_page('themes.php', 'theme-editor.php'); // Edytor plików
});

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
 * Blokowanie dostępu do customizer przez URL
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

    // Blokuj dostęp przez admin-ajax.php
    if (defined('DOING_AJAX') && DOING_AJAX) {
        if (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'customize') !== false) {
            wp_die(__('Dostęp do personalizacji motywu został zablokowany.', 'universal-theme'));
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
        
        /* Ukryj w admin bar */
        #wpadminbar #wp-admin-bar-customize {
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
    $container_width = $layout_config['container_width'] ?? '1200px';
    echo '.container, .site-content { max-width: ' . $container_width . '; margin: 0 auto; }';

    echo '</style>';
}
add_action('wp_head', 'universal_theme_layout_css');

/**
 * Kolory motywu z konfiguracji
 */
function universal_theme_colors_css()
{
    $colors = get_theme_option('colors');

    echo '<style type="text/css">';
    echo ':root {';
    echo '--color-primary: ' . $colors['primary'] . ';';
    echo '--color-secondary: ' . $colors['secondary'] . ';';
    echo '--color-accent: ' . $colors['accent'] . ';';
    echo '--color-text-dark: ' . $colors['text_dark'] . ';';
    echo '--color-text-light: ' . $colors['text_light'] . ';';
    echo '--color-background: ' . $colors['background'] . ';';
    echo '--color-background-alt: ' . $colors['background_alt'] . ';';
    echo '}';

    // Header background (używa primary color)
    echo '.site-header { background-color: ' . $colors['primary'] . '; }';
    echo '.main-navigation ul li a { color: white; }'; // Białe linki w menu na kolorowym tle

    echo '</style>';
}
add_action('wp_head', 'universal_theme_colors_css');
