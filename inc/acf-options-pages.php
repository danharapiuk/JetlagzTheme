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

        // Strona opcji dla ustawień dostawy
        acf_add_options_page(array(
            'page_title'    => 'Ustawienia dostawy',
            'menu_title'    => 'Dostawa',
            'menu_slug'     => 'delivery-settings',
            'capability'    => 'edit_posts',
            'icon_url'      => 'dashicons-car',
            'position'      => 32,
            'redirect'      => false
        ));
    }
}
add_action('acf/init', 'jetlagz_register_acf_options_pages');

/**
 * Rejestracja pól ACF dla ustawień dostawy
 */
function jetlagz_register_delivery_acf_fields()
{
    if (function_exists('acf_add_local_field_group')) {
        acf_add_local_field_group(array(
            'key' => 'group_delivery_settings',
            'title' => 'Ustawienia dostawy',
            'fields' => array(
                array(
                    'key' => 'field_delivery_enabled',
                    'label' => 'Włącz informację o dostawie',
                    'name' => 'delivery_enabled',
                    'type' => 'true_false',
                    'default_value' => 1,
                    'ui' => 1,
                ),
                array(
                    'key' => 'field_delivery_cutoff_time',
                    'label' => 'Godzina graniczna zamówienia',
                    'name' => 'delivery_cutoff_time',
                    'type' => 'time_picker',
                    'display_format' => 'H:i',
                    'return_format' => 'H:i',
                    'default_value' => '14:00',
                    'instructions' => 'Zamówienia złożone przed tą godziną zostaną wysłane tego samego dnia roboczego.',
                ),
                array(
                    'key' => 'field_delivery_days',
                    'label' => 'Dni dostawy od wysyłki',
                    'name' => 'delivery_days',
                    'type' => 'number',
                    'default_value' => 1,
                    'min' => 1,
                    'max' => 7,
                    'instructions' => 'Ile dni roboczych zajmuje dostawa po wysyłce paczki.',
                ),
                array(
                    'key' => 'field_delivery_exclude_weekends',
                    'label' => 'Wyklucz weekendy',
                    'name' => 'delivery_exclude_weekends',
                    'type' => 'true_false',
                    'default_value' => 1,
                    'ui' => 1,
                    'instructions' => 'Czy pomijać soboty i niedziele przy obliczaniu daty dostawy.',
                ),
                array(
                    'key' => 'field_delivery_holidays',
                    'label' => 'Dni wolne od pracy',
                    'name' => 'delivery_holidays',
                    'type' => 'repeater',
                    'layout' => 'table',
                    'button_label' => 'Dodaj dzień wolny',
                    'sub_fields' => array(
                        array(
                            'key' => 'field_holiday_date',
                            'label' => 'Data',
                            'name' => 'date',
                            'type' => 'date_picker',
                            'display_format' => 'd.m.Y',
                            'return_format' => 'Y-m-d',
                        ),
                        array(
                            'key' => 'field_holiday_name',
                            'label' => 'Nazwa (opcjonalnie)',
                            'name' => 'name',
                            'type' => 'text',
                        ),
                    ),
                    'instructions' => 'Dodaj święta i inne dni wolne, które należy pominąć.',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'delivery-settings',
                    ),
                ),
            ),
        ));
    }
}
add_action('acf/init', 'jetlagz_register_delivery_acf_fields');

/**
 * Oblicz przewidywaną datę dostawy
 */
function jetlagz_get_estimated_delivery_date()
{
    if (!function_exists('get_field')) {
        return null;
    }

    // Pobierz ustawienia
    $enabled = get_field('delivery_enabled', 'option');
    if (!$enabled) {
        return null;
    }

    $cutoff_time = get_field('delivery_cutoff_time', 'option') ?: '14:00';
    $delivery_days = get_field('delivery_days', 'option') ?: 1;
    $exclude_weekends = get_field('delivery_exclude_weekends', 'option');
    $holidays = get_field('delivery_holidays', 'option') ?: array();

    // Przygotuj listę świąt
    $holiday_dates = array();
    if (!empty($holidays)) {
        foreach ($holidays as $holiday) {
            if (!empty($holiday['date'])) {
                $holiday_dates[] = $holiday['date'];
            }
        }
    }

    // Ustaw strefę czasową
    $timezone = new DateTimeZone(wp_timezone_string());
    $now = new DateTime('now', $timezone);
    $cutoff = DateTime::createFromFormat('H:i', $cutoff_time, $timezone);
    $cutoff->setDate($now->format('Y'), $now->format('m'), $now->format('d'));

    // Oblicz datę wysyłki
    $ship_date = clone $now;

    // Jeśli po godzinie granicznej, wysyłka następnego dnia roboczego
    if ($now > $cutoff) {
        $ship_date->modify('+1 day');
    }

    // Pomiń weekendy i święta dla daty wysyłki
    while (jetlagz_is_non_working_day($ship_date, $exclude_weekends, $holiday_dates)) {
        $ship_date->modify('+1 day');
    }

    // Oblicz datę dostawy (dodaj dni robocze)
    $delivery_date = clone $ship_date;
    $days_added = 0;

    while ($days_added < $delivery_days) {
        $delivery_date->modify('+1 day');
        if (!jetlagz_is_non_working_day($delivery_date, $exclude_weekends, $holiday_dates)) {
            $days_added++;
        }
    }

    return $delivery_date;
}

/**
 * Sprawdź czy data to dzień wolny (weekend lub święto)
 */
function jetlagz_is_non_working_day($date, $exclude_weekends, $holiday_dates)
{
    $day_of_week = $date->format('N'); // 1 (pon) - 7 (niedz)
    $date_string = $date->format('Y-m-d');

    // Sprawdź weekend
    if ($exclude_weekends && ($day_of_week == 6 || $day_of_week == 7)) {
        return true;
    }

    // Sprawdź święta
    if (in_array($date_string, $holiday_dates)) {
        return true;
    }

    return false;
}

/**
 * Renderuj informację o dostawie (do użycia w template)
 */
function jetlagz_render_delivery_info()
{
    // Debug - sprawdź czy funkcja jest wywoływana
    if (current_user_can('administrator') && isset($_GET['debug_delivery'])) {
        echo '<pre style="background:#fff;padding:10px;border:2px solid red;">';
        echo 'ACF exists: ' . (function_exists('get_field') ? 'YES' : 'NO') . "\n";
        echo 'delivery_enabled: ' . var_export(get_field('delivery_enabled', 'option'), true) . "\n";
        echo 'delivery_cutoff_time: ' . var_export(get_field('delivery_cutoff_time', 'option'), true) . "\n";
        echo 'delivery_days: ' . var_export(get_field('delivery_days', 'option'), true) . "\n";
        echo '</pre>';
    }

    $delivery_date = jetlagz_get_estimated_delivery_date();

    if (!$delivery_date) {
        return;
    }

    // Polskie nazwy dni tygodnia
    $days_pl = array(
        1 => 'poniedziałek',
        2 => 'wtorek',
        3 => 'środa',
        4 => 'czwartek',
        5 => 'piątek',
        6 => 'sobota',
        7 => 'niedziela'
    );

    $day_name = $days_pl[(int)$delivery_date->format('N')];
    $formatted_date = $delivery_date->format('d.m');
?>
    <div class="border-[1px] border-gray-300 border-b-[0] p-2">
        <div class="delivery-info">
            <svg class="delivery-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                <path d="M1 3h15v13H1zM16 8h4l3 3v5h-7V8z" />
                <circle cx="5.5" cy="18.5" r="2.5" />
                <circle cx="18.5" cy="18.5" r="2.5" />
            </svg>
            <span class="delivery-text"><span class="font-bold">Zamów teraz</span> a paczka dotrze do Ciebie <span class="font-bold"><?php echo esc_html($formatted_date); ?></span> (<?php echo esc_html($day_name); ?>)</span>
        </div>
    </div>
    <style>
        .delivery-info {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #333;
            font-weight: 300;
        }

        .delivery-icon {
            flex-shrink: 0;
        }
    </style>
<?php
}
