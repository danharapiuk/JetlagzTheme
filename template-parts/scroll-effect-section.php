<?php

/**
 * Template Part: Scroll Effect Section
 *
 * Reusable component with scrolling title text and image switcher.
 *
 * Usage:
 *   get_template_part('template-parts/scroll-effect-section', null, [
 *       'title'  => 'TWÓJ TEKST',
 *       'images' => ['https://...jpg', 'https://...jpg'],
 *   ]);
 *
 * @param string $args['title']  Text displayed as large scrolling background title.
 * @param array  $args['images'] Array of image URLs (strings) or ACF image arrays with 'url'/'alt' keys.
 */

global $scroll_effect_data;
$section_title  = $scroll_effect_data['title']  ?? '';
$section_images = $scroll_effect_data['images'] ?? [];
$scroll_effect_data = null;

// Unique ID per instance so multiple components on one page don't conflict.
$instance_id = 'scroll-effect-' . uniqid();
?>

<section class="hidden lg:flex h-screen overflow-x-hidden relative items-center justify-center font-inter" id="<?php echo esc_attr($instance_id); ?>">
    <div class="effect-title-wrapper absolute z-0 left-0 flex transition-opacity duration-700 ease-in-out" style="top: 50%; transform: translateY(-50%);">
        <h2 class="effect-title-text uppercase font-black text-[400px] text-[#6A263A] whitespace-nowrap">
            <?php echo esc_html($section_title); ?>
        </h2>
        <h2 class="effect-title-text uppercase font-bold text-[400px] text-[#6A263A] whitespace-nowrap">
            <?php echo esc_html($section_title); ?>
        </h2>
        <h2 class="effect-title-text uppercase font-bold text-[400px] text-[#6A263A] whitespace-nowrap">
            <?php echo esc_html($section_title); ?>
        </h2>
    </div>

    <?php if (!empty($section_images)) : ?>
        <div class="effect-image-container absolute inset-0 flex items-center justify-center z-10">
            <div class="relative w-[50%] h-[50%] sm:w-[400px] sm:h-[600px]">
                <?php
                $img_counter = 0;
                foreach ($section_images as $image) :
                    if (is_string($image) && $image) {
                        $img_url = $image;
                        $img_alt = '';
                    } elseif (is_array($image) && !empty($image['url'])) {
                        $img_url = $image['url'];
                        $img_alt = $image['alt'] ?? '';
                    } elseif (is_numeric($image)) {
                        $img_url = wp_get_attachment_image_url((int) $image, 'full');
                        $img_alt = get_post_meta((int) $image, '_wp_attachment_image_alt', true);
                    } else {
                        continue;
                    }

                    if (!$img_url) continue;
                    $img_opacity = ($img_counter === 0) ? 'opacity-100' : 'opacity-0';
                ?>
                    <img
                        src="<?php echo esc_url($img_url); ?>"
                        alt="<?php echo esc_attr($img_alt); ?>"
                        class="effect-image absolute inset-0 w-full h-full object-cover transition-opacity duration-700 <?php echo $img_opacity; ?>"
                        data-index="<?php echo $img_counter; ?>">
                <?php
                    $img_counter++;
                endforeach;
                ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<script>
    (function() {
        var instanceId = <?php echo json_encode($instance_id); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            var section = document.getElementById(instanceId);
            if (!section) return;

            var images = section.querySelectorAll('.effect-image');
            var titleWrapper = section.querySelector('.effect-title-wrapper');
            var titleTexts = section.querySelectorAll('.effect-title-text');
            var totalImages = images.length;

            if (totalImages === 0) return;

            var currentIndex = 0;
            var scrollAccumulator = 0;
            var titleScrollAccumulator = 0;
            var scrollThreshold = 50;

            var singleTextWidth = titleTexts.length > 0 ? titleTexts[0].offsetWidth : 0;

            window.addEventListener('wheel', function(e) {
                scrollAccumulator += e.deltaY;
                scrollAccumulator = Math.max(0, Math.min(scrollAccumulator, (totalImages - 1) * scrollThreshold));

                titleScrollAccumulator += e.deltaY;
                titleScrollAccumulator = Math.max(0, titleScrollAccumulator);

                var newIndex = Math.floor(scrollAccumulator / scrollThreshold);
                var titleOffset = -titleScrollAccumulator;

                if (singleTextWidth > 0) {
                    titleOffset = titleOffset % singleTextWidth;
                }

                titleWrapper.style.transform = 'translateY(-50%) translateX(' + titleOffset + 'px)';

                if (newIndex !== currentIndex) {
                    images[currentIndex].classList.remove('opacity-100');
                    images[currentIndex].classList.add('opacity-0');

                    images[newIndex].classList.remove('opacity-0');
                    images[newIndex].classList.add('opacity-100');

                    currentIndex = newIndex;
                }
            });
        });
    }());
</script>