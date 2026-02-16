<?php

/**
 * Universal Theme Admin Panel
 * Panel administracyjny do zarządzania wyglądem motywu
 */

// Zapobiegaj bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dodaj panel administracyjny w menu Wygląd
 */
function universal_theme_admin_menu()
{
    // WAŻNE: Dodaj panel z wysokim priorytetem żeby był pierwszym podmenu
    add_theme_page(
        'Ustawienia Motywu',           // Tytuł strony
        'Ustawienia Motywu',           // Tytuł menu
        'manage_options',              // Uprawnienia
        'universal-theme-settings',    // Slug strony
        'universal_theme_admin_page'   // Funkcja wyświetlająca
    );
}
add_action('admin_menu', 'universal_theme_admin_menu', 5); // WYSOKI PRIORYTET = PIERWSZA POZYCJA

/**
 * Przekieruj główną zakładkę Wygląd do naszego panelu
 */
function universal_theme_redirect_appearance()
{
    global $pagenow;

    // Jeśli ktoś wchodzi na themes.php bez żadnych parametrów, przekieruj do naszego panelu
    if ($pagenow === 'themes.php' && !isset($_GET['page']) && !isset($_GET['action'])) {
        wp_redirect(admin_url('themes.php?page=universal-theme-settings'));
        exit;
    }
}
add_action('admin_init', 'universal_theme_redirect_appearance');

/**
 * Rejestracja ustawień motywu
 */
function universal_theme_admin_init()
{
    // Rejestracja grup ustawień
    register_setting('universal_theme_settings', 'universal_theme_options', 'universal_theme_validate_options');

    // === SEKCJA LOGO I BRANDING ===
    add_settings_section(
        'universal_branding',
        'Logo i Branding',
        'universal_branding_section_text',
        'universal_theme_settings'
    );

    add_settings_field(
        'logo',
        'Logo strony',
        'universal_logo_field',
        'universal_theme_settings',
        'universal_branding'
    );

    add_settings_field(
        'logo_mobile',
        'Logo na urządzeniach mobilnych',
        'universal_logo_mobile_field',
        'universal_theme_settings',
        'universal_branding'
    );

    add_settings_field(
        'favicon',
        'Favicon (ikona strony)',
        'universal_favicon_field',
        'universal_theme_settings',
        'universal_branding'
    );

    add_settings_field(
        'logo_max_height',
        'Maksymalna wysokość logo (px)',
        'universal_logo_max_height_field',
        'universal_theme_settings',
        'universal_branding'
    );
}
add_action('admin_init', 'universal_theme_admin_init');

/**
 * Funkcje wyświetlające sekcje
 */
function universal_branding_section_text()
{
    echo '<p>Ustaw logo, favicon i inne elementy brandingu Twojego sklepu.</p>';
}

/**
 * Funkcje wyświetlające pola
 */
function universal_logo_field()
{
    $options = get_option('universal_theme_options');
    $logo_url = $options['logo'] ?? '';

    echo '<input type="hidden" id="logo_url" name="universal_theme_options[logo]" value="' . esc_attr($logo_url) . '" />';
    echo '<div style="margin-bottom: 10px;">';
    if ($logo_url) {
        echo '<img id="logo_preview" src="' . esc_url($logo_url) . '" style="max-width: 200px; max-height: 100px; display: block; margin-bottom: 10px;" />';
    } else {
        echo '<img id="logo_preview" style="display: none; max-width: 200px; max-height: 100px; margin-bottom: 10px;" />';
    }
    echo '</div>';
    echo '<button type="button" class="button" id="upload_logo_button">Wybierz Logo</button>';
    if ($logo_url) {
        echo ' <button type="button" class="button" id="remove_logo_button">Usuń Logo</button>';
    }
    echo '<p class="description">Rekomendowany rozmiar: 300x100px lub proporcjonalnie.</p>';
}

function universal_logo_mobile_field()
{
    $options = get_option('universal_theme_options');
    $logo_mobile_url = $options['logo_mobile'] ?? '';

    echo '<input type="hidden" id="logo_mobile_url" name="universal_theme_options[logo_mobile]" value="' . esc_attr($logo_mobile_url) . '" />';
    echo '<div style="margin-bottom: 10px;">';
    if ($logo_mobile_url) {
        echo '<img id="logo_mobile_preview" src="' . esc_url($logo_mobile_url) . '" style="max-width: 150px; max-height: 80px; display: block; margin-bottom: 10px;" />';
    } else {
        echo '<img id="logo_mobile_preview" style="display: none; max-width: 150px; max-height: 80px; margin-bottom: 10px;" />';
    }
    echo '</div>';
    echo '<button type="button" class="button" id="upload_logo_mobile_button">Wybierz Logo Mobilne</button>';
    if ($logo_mobile_url) {
        echo ' <button type="button" class="button" id="remove_logo_mobile_button">Usuń Logo</button>';
    }
    echo '<p class="description">Opcjonalne. Mniejsze logo na telefony. Rozmiar: 200x60px.</p>';
}

function universal_favicon_field()
{
    $options = get_option('universal_theme_options');
    $favicon_url = $options['favicon'] ?? '';

    echo '<input type="hidden" id="favicon_url" name="universal_theme_options[favicon]" value="' . esc_attr($favicon_url) . '" />';
    echo '<div style="margin-bottom: 10px;">';
    if ($favicon_url) {
        echo '<img id="favicon_preview" src="' . esc_url($favicon_url) . '" style="width: 32px; height: 32px; display: block; margin-bottom: 10px;" />';
    } else {
        echo '<img id="favicon_preview" style="display: none; width: 32px; height: 32px; margin-bottom: 10px;" />';
    }
    echo '</div>';
    echo '<button type="button" class="button" id="upload_favicon_button">Wybierz Favicon</button>';
    if ($favicon_url) {
        echo ' <button type="button" class="button" id="remove_favicon_button">Usuń Favicon</button>';
    }
    echo '<p class="description">Ikona strony (32x32px). Format: ICO, PNG.</p>';
}

function universal_logo_max_height_field()
{
    $options = get_option('universal_theme_options');
    $max_height = $options['logo_max_height'] ?? 100;

    echo '<input type="number" name="universal_theme_options[logo_max_height]" value="' . esc_attr($max_height) . '" min="30" max="200" step="10" />';
    echo ' px';
    echo '<p class="description">Maksymalna wysokość logo w pikselach (30-200px). Domyślnie: 100px.</p>';
}

/**
 * Walidacja opcji
 */
function universal_theme_validate_options($input)
{
    $output = array();

    // Walidacja URL-i dla logo i favicon
    if (isset($input['logo'])) {
        $output['logo'] = esc_url_raw($input['logo']);
    }

    if (isset($input['logo_mobile'])) {
        $output['logo_mobile'] = esc_url_raw($input['logo_mobile']);
    }

    if (isset($input['favicon'])) {
        $output['favicon'] = esc_url_raw($input['favicon']);
    }

    // Walidacja wysokości logo
    if (isset($input['logo_max_height'])) {
        $height = intval($input['logo_max_height']);
        // Ograniczenie do 30-200px
        $output['logo_max_height'] = max(30, min(200, $height));
    }

    return $output;
}

/**
 * Funkcja helper do pobierania opcji motywu
 */
function get_universal_theme_option($key, $default = '')
{
    $options = get_option('universal_theme_options');
    return $options[$key] ?? $default;
}

/**
 * Strona administracyjna
 */
function universal_theme_admin_page()
{
?>
    <div class="wrap">
        <h1>🎨 Ustawienia Motywu</h1>
        <p>Ustaw logo i branding swojego sklepu.</p>

        <form method="post" action="options.php">
            <?php
            settings_fields('universal_theme_settings');
            do_settings_sections('universal_theme_settings');
            submit_button('Zapisz ustawienia');
            ?>
        </form>
    </div>

    <style>
        .form-table th {
            font-weight: 600;
            color: #333;
        }

        .form-table td {
            padding: 15px 10px;
        }

        h2 {
            color: #0073aa;
            border-bottom: 2px solid #0073aa;
            padding-bottom: 5px;
        }

        .description {
            color: #666 !important;
            font-style: italic;
        }
    </style>
<?php
}

/**
 * Dodaj media uploader scripts
 */
function universal_theme_admin_scripts($hook)
{
    if ($hook !== 'appearance_page_universal-theme-settings') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script('universal-admin', get_stylesheet_directory_uri() . '/assets/js/admin.js', array('jquery'), '1.0.0', true);

    // Dodaj debug info
    wp_add_inline_script('universal-admin', '
        jQuery(document).ready(function($) {
            if (typeof wp === "undefined" || typeof wp.media === "undefined") {
                console.error("WordPress Media API not loaded! Check wp_enqueue_media()");
            }
        });
    ');
}
add_action('admin_enqueue_scripts', 'universal_theme_admin_scripts');

/**
 * Zastosuj favicon jeśli jest ustawiony
 */
function universal_theme_favicon()
{
    $favicon = get_universal_theme_option('favicon');
    if ($favicon) {
        echo '<link rel="icon" type="image/png" href="' . esc_url($favicon) . '">';
    }
}
add_action('wp_head', 'universal_theme_favicon');

/**
 * Wyświetlanie custom logo w headerze
 */
function universal_theme_custom_logo()
{
    $logo_url = get_universal_theme_option('logo');

    if ($logo_url) {
        // Logo handling jest w header-functions.php, ale potrzebujemy backup filter
        // remove_action('storefront_header', 'storefront_site_branding', 20);
        // add_action('storefront_header', 'universal_theme_display_custom_logo', 20);

        // Backup: zastąp get_custom_logo dla całego WordPress
        add_filter('get_custom_logo', 'universal_theme_custom_logo_html');

        // CSS dla stylowania logo
        add_action('wp_head', 'universal_theme_hide_site_title_css');
    }
}
add_action('init', 'universal_theme_custom_logo');

/**
 * Wyświetl nasze custom logo w miejsce Storefront site branding
 */
function universal_theme_display_custom_logo()
{
    $logo_url = get_universal_theme_option('logo');
    $logo_mobile_url = get_universal_theme_option('logo_mobile');

    if (!$logo_url) {
        // Fallback - pokaż default site branding
        storefront_site_branding();
        return;
    }

?>
    <div class="site-branding">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="custom-logo-link" rel="home" itemprop="url">
            <?php if ($logo_mobile_url): ?>
                <!-- Mamy osobne logo desktop i mobile -->
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="custom-logo custom-logo-desktop" />
                <img src="<?php echo esc_url($logo_mobile_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="custom-logo custom-logo-mobile" />
            <?php else: ?>
                <!-- Używamy tego samego logo dla desktop i mobile -->
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="custom-logo custom-logo-desktop" />
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="custom-logo custom-logo-mobile" />
            <?php endif; ?>
        </a>
    </div>
<?php
}

/**
 * Zastąp domyślne logo WordPress
 */
function universal_theme_custom_logo_html($html)
{
    $logo_url = get_universal_theme_option('logo');
    $logo_mobile_url = get_universal_theme_option('logo_mobile');

    if (!$logo_url) {
        return $html;
    }

    $site_name = get_bloginfo('name');
    $home_url = home_url('/');

    $custom_html = '<a href="' . esc_url($home_url) . '" class="custom-logo-link" rel="home" itemprop="url">';

    // Logo desktop
    $custom_html .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" class="custom-logo custom-logo-desktop" />';

    // Logo mobile - jeśli nie ma mobilnego, użyj desktop
    if ($logo_mobile_url) {
        $custom_html .= '<img src="' . esc_url($logo_mobile_url) . '" alt="' . esc_attr($site_name) . '" class="custom-logo custom-logo-mobile" />';
    } else {
        // Fallback: użyj desktop logo również na mobile
        $custom_html .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" class="custom-logo custom-logo-mobile" />';
    }

    $custom_html .= '</a>';

    return $custom_html;
}

/**
 * Ukryj site title gdy logo jest dostępne
 */
function universal_theme_hide_site_title($title)
{
    $logo_url = get_universal_theme_option('logo');
    return $logo_url ? '' : $title;
}

/**
 * Dynamiczne CSS dla logo (wartości z panelu admina)
 * Style statyczne są w assets/css/pages/header.css
 */
function universal_theme_hide_site_title_css()
{
    $logo_url = get_universal_theme_option('logo');
    $logo_mobile_url = get_universal_theme_option('logo_mobile');
    $logo_max_height = get_universal_theme_option('logo_max_height', 100);

    if (!$logo_url) {
        return;
    }

?>
    <style type="text/css">
        /* Dynamiczna wysokość logo z ustawień admina */
        .custom-logo {
            max-height: <?php echo intval($logo_max_height); ?>px !important;
        }

        .custom-logo-mobile {
            max-height: <?php echo intval($logo_max_height * 0.75); ?>px;
        }

        @media (max-width: 768px) {
            <?php if (!$logo_mobile_url): ?>.custom-logo-desktop {
                max-height: <?php echo intval($logo_max_height * 0.75); ?>px;
            }

            <?php endif; ?>
        }
    </style>
<?php
}
function universal_theme_display_logo()
{
    $logo_url = get_universal_theme_option('logo');
    $logo_mobile_url = get_universal_theme_option('logo_mobile');

    if (!$logo_url) {
        return;
    }

    echo '<div class="custom-logo-container">';

    // Logo na desktop
    echo '<a href="' . esc_url(home_url('/')) . '" class="custom-logo-link desktop-logo" rel="home">';
    echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="custom-logo" />';
    echo '</a>';

    // Logo mobilne jeśli dostępne
    if ($logo_mobile_url) {
        echo '<a href="' . esc_url(home_url('/')) . '" class="custom-logo-link mobile-logo" rel="home">';
        echo '<img src="' . esc_url($logo_mobile_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="custom-logo-mobile" />';
        echo '</a>';
    }

    echo '</div>';
}

/**
 * Zastąp domyślny site branding
 */
function universal_theme_replace_site_branding()
{
    $logo_url = get_universal_theme_option('logo');

    if (!$logo_url) {
        return;
    }

    ob_start();
?>
    <div class="site-branding">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="custom-logo-link" rel="home">
            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="custom-logo" />
        </a>
    </div>
<?php
    return ob_get_clean();
}

/* Funkcja universal_theme_logo_styles() usunięta - style przeniesione do assets/css/pages/header.css */
