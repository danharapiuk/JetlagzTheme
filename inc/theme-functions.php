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
 * Layout CSS z konfiguracji motywu - kontrola sidebar
 */
function universal_theme_layout_css() {
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

