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

// Pobierz repeater z ACF Options (pole o nazwie 'Icons')
$icons = safe_get_field('icons', 'option');
$delivery_icons = safe_get_field('delivery_icons', 'option');

// Jeśli brak danych - nie wyświetlaj
if ((!$icons || empty($icons)) && (!$delivery_icons || empty($delivery_icons))) {
    return;
}
?>
<div class="md:flex gap-8 pt-3 sm:pt-0">
    <div class="sm:flex gap-2 items-center">
        <div class="info-payments text-white opacity-[0.3] text-xs font-light uppercase tracking-wider hidden md:block">Płatności:</div>
        <div class="flex gap-1 sm:gap-3 flex-wrap justify-center md:justify-start py-1 sm:py-3 payment-icons">
            <?php foreach ($icons as $icon): ?>
                <?php if (!empty($icon['icon'])):
                    // Obsługa różnych Return Format ACF
                    if (is_array($icon['icon'])) {
                        $image_url = $icon['icon']['url'];
                    } elseif (is_numeric($icon['icon'])) {
                        $image_url = wp_get_attachment_image_url($icon['icon'], 'full');
                    } else {
                        $image_url = $icon['icon'];
                    }
                ?>
                    <img class="feature-image w-[35px] h-auto md:w-[40px] object-contain" src="<?php echo esc_url($image_url); ?>" alt="">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="gap-2 items-center hidden md:flex">
        <div class="info-delivery text-white opacity-[0.3] text-xs font-light uppercase tracking-wider">Dostawa:</div>
        <div class="flex gap-1 sm:gap-3 flex-wrap justify-center sm:py-3 payment-icons">
            <?php foreach ($delivery_icons as $icon): ?>
                <?php if (!empty($icon['icon'])):
                    // Obsługa różnych Return Format ACF
                    if (is_array($icon['icon'])) {
                        $image_url = $icon['icon']['url'];
                    } elseif (is_numeric($icon['icon'])) {
                        $image_url = wp_get_attachment_image_url($icon['icon'], 'full');
                    } else {
                        $image_url = $icon['icon'];
                    }
                ?>
                    <img class="feature-image w-[35px] h-auto md:w-[40px] object-contain" src="<?php echo esc_url($image_url); ?>" alt="">
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>