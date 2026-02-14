<?php

/**
 * Template Part: Payment icons
 * 
 * Wyświetla repeater z ACF Options Page "Features"
 * Konfiguracja: ACF Options > Template Parts > Twoja grupa pól
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pobierz repeater z ACF Options (pole o nazwie 'ugc')
$ugc = safe_get_field('ugc', 'option');

// Jeśli brak danych - nie wyświetlaj
if (!$ugc || empty($ugc)) {
    return;
}
?>

<div class="">

UGC SECTIONS

</div>