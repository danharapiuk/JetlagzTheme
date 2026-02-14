<?php

/**
 * Template Part: Features
 * 
 * Wyświetla repeater z ACF Options Page "Features"
 * Konfiguracja: ACF Options > Template Parts > Twoja grupa pól
 */

if (!defined('ABSPATH')) {
    exit;
}

// Pobierz repeater z ACF Options (pole o nazwie 'features')
$features = safe_get_field('features', 'option');

// Jeśli brak danych - nie wyświetlaj
if (!$features || empty($features)) {
    return;
}
?>

<section class="features-section py-16 md:py-20 mb-16 md:mb-20 bg-white">
    <div class="wrapper mx-auto px-4">
        <div class="flex flex-col sm:flex-row sm:flex-wrap mx-4 gap-8 justify-center">
            <?php foreach ($features as $feature): ?>
                <div class="sm:max-w-[185px]">
                    <?php if (!empty($feature['icon'])):
                        // Obsługa różnych Return Format ACF
                        if (is_array($feature['icon'])) {
                            $image_url = $feature['icon']['url'];
                        } elseif (is_numeric($feature['icon'])) {
                            $image_url = wp_get_attachment_image_url($feature['icon'], 'full');
                        } else {
                            $image_url = $feature['icon'];
                        }
                    ?>
                        <div class="feature-image mb-4 w-[35px] h-[35px] mx-auto">
                            <img src="<?php echo esc_url($image_url); ?>"
                                alt="<?php echo esc_attr($feature['title'] ?? ''); ?>"
                                class="w-full h-auto">
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($feature['title'])): ?>
                        <h3 class="text-xs font-semibold uppercase text-center">
                            <?php echo esc_html($feature['title']); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if (!empty($feature['description'])): ?>
                        <p class="font-light text-xs text-center pt-1">
                            <?php echo esc_html($feature['description']); ?>
                        </p>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>