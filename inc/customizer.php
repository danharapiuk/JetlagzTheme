<?php

/**
 * Customizer WordPress - łatwa personalizacja motywu
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dodanie opcji do customizera
 */
function universal_theme_customize_register($wp_customize)
{

    // === SEKCJA: Kolory motywu ===
    $wp_customize->add_section('universal_theme_colors', array(
        'title' => __('Kolory motywu', 'universal-theme'),
        'priority' => 30,
        'description' => __('Dostosuj kolory swojego sklepu', 'universal-theme'),
    ));

    // Kolor główny
    $wp_customize->add_setting('universal_primary_color', array(
        'default' => get_theme_option('colors.primary'),
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'universal_primary_color', array(
        'label' => __('Kolor główny', 'universal-theme'),
        'section' => 'universal_theme_colors',
        'settings' => 'universal_primary_color',
    )));

    // Kolor drugorzędny
    $wp_customize->add_setting('universal_secondary_color', array(
        'default' => get_theme_option('colors.secondary'),
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'universal_secondary_color', array(
        'label' => __('Kolor drugorzędny', 'universal-theme'),
        'section' => 'universal_theme_colors',
        'settings' => 'universal_secondary_color',
    )));

    // Kolor akcentu
    $wp_customize->add_setting('universal_accent_color', array(
        'default' => get_theme_option('colors.accent'),
        'sanitize_callback' => 'sanitize_hex_color',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'universal_accent_color', array(
        'label' => __('Kolor akcentu/przycisków', 'universal-theme'),
        'section' => 'universal_theme_colors',
        'settings' => 'universal_accent_color',
    )));

    // === SEKCJA: Typografia ===
    $wp_customize->add_section('universal_theme_typography', array(
        'title' => __('Typografia', 'universal-theme'),
        'priority' => 31,
        'description' => __('Dostosuj czcionki i typografię', 'universal-theme'),
    ));

    // Główna czcionka
    $wp_customize->add_setting('universal_primary_font', array(
        'default' => get_theme_option('typography.primary_font'),
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('universal_primary_font', array(
        'label' => __('Główna czcionka', 'universal-theme'),
        'section' => 'universal_theme_typography',
        'type' => 'select',
        'choices' => array(
            'Inter, sans-serif' => 'Inter',
            'Roboto, sans-serif' => 'Roboto',
            'Open Sans, sans-serif' => 'Open Sans',
            'Lato, sans-serif' => 'Lato',
            'Montserrat, sans-serif' => 'Montserrat',
            'Poppins, sans-serif' => 'Poppins',
        ),
    ));

    // === SEKCJA: Ustawienia sklepu ===
    $wp_customize->add_section('universal_theme_shop', array(
        'title' => __('Ustawienia sklepu', 'universal-theme'),
        'priority' => 32,
        'description' => __('Dostosuj wygląd sklepu', 'universal-theme'),
    ));

    // Produkty na stronę
    $wp_customize->add_setting('universal_products_per_page', array(
        'default' => get_theme_option('woocommerce.products_per_page'),
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('universal_products_per_page', array(
        'label' => __('Produkty na stronę', 'universal-theme'),
        'section' => 'universal_theme_shop',
        'type' => 'number',
        'input_attrs' => array(
            'min' => 4,
            'max' => 48,
            'step' => 4,
        ),
    ));

    // Produkty w rzędzie
    $wp_customize->add_setting('universal_products_per_row', array(
        'default' => get_theme_option('woocommerce.products_per_row'),
        'sanitize_callback' => 'absint',
        'transport' => 'refresh',
    ));

    $wp_customize->add_control('universal_products_per_row', array(
        'label' => __('Produkty w rzędzie', 'universal-theme'),
        'section' => 'universal_theme_shop',
        'type' => 'select',
        'choices' => array(
            '2' => '2',
            '3' => '3',
            '4' => '4',
            '5' => '5',
            '6' => '6',
        ),
    ));
}
add_action('customize_register', 'universal_theme_customize_register');

/**
 * Generowanie CSS na podstawie ustawień customizera
 */
function universal_theme_customizer_css()
{
    $primary_color = get_theme_mod('universal_primary_color', get_theme_option('colors.primary'));
    $secondary_color = get_theme_mod('universal_secondary_color', get_theme_option('colors.secondary'));
    $accent_color = get_theme_mod('universal_accent_color', get_theme_option('colors.accent'));
    $primary_font = get_theme_mod('universal_primary_font', get_theme_option('typography.primary_font'));

?>
    <style type="text/css">
        :root {
            --theme-primary: <?php echo esc_attr($primary_color); ?>;
            --theme-secondary: <?php echo esc_attr($secondary_color); ?>;
            --theme-accent: <?php echo esc_attr($accent_color); ?>;
            --theme-font-primary: <?php echo esc_attr($primary_font); ?>;
        }

        /* Zastosowanie kolorów */
        .site-header,
        .main-navigation ul li a:hover,
        .button.alt,
        .woocommerce .button.alt,
        .woocommerce-cart .wc-proceed-to-checkout a.checkout-button {
            background-color: var(--theme-primary);
        }

        .woocommerce .button,
        .button,
        .wp-block-button__link {
            background-color: var(--theme-accent);
        }

        a:hover,
        .woocommerce-loop-product__title:hover,
        .price .amount {
            color: var(--theme-primary);
        }

        /* Typografia */
        body,
        input,
        textarea,
        select,
        button {
            font-family: var(--theme-font-primary);
        }
    </style>
<?php
}
add_action('wp_head', 'universal_theme_customizer_css');
