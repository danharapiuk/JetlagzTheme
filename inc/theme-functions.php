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

