<?php

/**
 * Template Name: Home
 * Template Post Type: page
 */

// Enqueue home page specific styles
wp_enqueue_style('home-page-styles', get_stylesheet_directory_uri() . '/assets/css/pages/home.css', array(), filemtime(get_stylesheet_directory() . '/assets/css/pages/home.css'));

get_header(); ?>

<?php
$hero = safe_get_field('hero');
if (!$hero) {
    $hero = array('title' => '', 'images' => array());
}
?>


<section class="h-screen overflow-x-hidden relative flex items-center justify-center font-inter" id="hero-section">
    <div id="hero-title-wrapper" class="absolute z-0 left-0 flex transition-opacity duration-700 ease-in-out">
        <h1 class="hero-title-text uppercase font-black text-[400px] text-[#6A263A] whitespace-nowrap">
            <?php echo !empty($hero['title']) ? esc_html($hero['title']) : ''; ?>
        </h1>
        <h1 class="hero-title-text uppercase font-bold text-[200px] sm:text-[400px] text-[#6A263A] whitespace-nowrap">
            <?php echo !empty($hero['title']) ? esc_html($hero['title']) : ''; ?>
        </h1>
        <h1 class="hero-title-text uppercase font-bold text-[400px] text-[#6A263A] whitespace-nowrap">
            <?php echo !empty($hero['title']) ? esc_html($hero['title']) : ''; ?>
        </h1>
    </div>

    <?php if (isset($hero['images']) && is_array($hero['images']) && count($hero['images']) > 0) : ?>
        <div class="hero-image-container absolute inset-0 flex items-center justify-center z-10">
            <div class="relative w-[50%] h-[50%] sm:w-[400px] sm:h-[600px]">
                <?php
                $img_counter = 0;
                foreach ($hero['images'] as $image) :
                    // ACF Gallery zwraca array URL-i (stringi)
                    if (is_string($image)) {
                        $img_url = $image;
                        $img_alt = '';
                    } else {
                        continue;
                    }

                    if (!$img_url) continue;
                    $img_opacity = ($img_counter === 0) ? 'opacity-100' : 'opacity-0';
                ?>
                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($img_alt); ?>" class="hero-image absolute inset-0 w-full h-full object-cover <?php echo $img_opacity; ?>" data-index="<?php echo $img_counter; ?>">
                <?php
                    $img_counter++;
                endforeach;
                ?>
            </div>
        </div>
    <?php else: ?>
        <!-- No images found -->
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.hero-image');
        const heroTitleWrapper = document.getElementById('hero-title-wrapper');
        const heroTitleTexts = document.querySelectorAll('.hero-title-text');
        const totalImages = images.length;

        if (totalImages === 0) return;

        let currentIndex = 0;
        let scrollAccumulator = 0;
        let titleScrollAccumulator = 0;
        const scrollThreshold = 50;

        // Oblicz szerokość pojedynczego tekstu
        let singleTextWidth = 0;
        if (heroTitleTexts.length > 0) {
            singleTextWidth = heroTitleTexts[0].offsetWidth;
        }

        // Użyj wheel event zamiast scroll
        window.addEventListener('wheel', function(e) {
            scrollAccumulator += e.deltaY;
            scrollAccumulator = Math.max(0, Math.min(scrollAccumulator, (totalImages - 1) * scrollThreshold));

            titleScrollAccumulator += e.deltaY;
            titleScrollAccumulator = Math.max(0, titleScrollAccumulator);

            const newIndex = Math.floor(scrollAccumulator / scrollThreshold);

            // Zapętlenie tekstu - resetuj pozycję co szerokość jednego tekstu
            let titleOffset = -titleScrollAccumulator;
            if (singleTextWidth > 0) {
                titleOffset = titleOffset % singleTextWidth;
            }
            heroTitleWrapper.style.transform = `translateX(${titleOffset}px)`;

            if (newIndex !== currentIndex) {
                images[currentIndex].classList.remove('opacity-100');
                images[currentIndex].classList.add('opacity-0');

                images[newIndex].classList.remove('opacity-0');
                images[newIndex].classList.add('opacity-100');

                currentIndex = newIndex;
            }
        });
    });
</script>

<script>
    // Zmiana tytułu strony gdy użytkownik przełączy kartę
    document.addEventListener('DOMContentLoaded', function() {
        const originalTitle = document.title;
        const newTitle = "Wróć do nas! 👋"; // Dostosuj tekst według potrzeb

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                document.title = newTitle;
            } else {
                document.title = originalTitle;
            }
        });
    });
</script>

<?php get_template_part('template-parts/product-categories'); ?>

<section class="bestsellers py-16 md:py-20 overflow-hidden">
    <div class="wrapper mx-auto">
        <h2 class="title">Bestsellery</h2>
    </div>
    <div class="bestsellers-slider-container wrapper-mobile">
        <?php get_template_part('template-parts/bestsellers-slider'); ?>
    </div>
</section>

<section class="new-products py-16 md:py-20 overflow-hidden">
    <div class="wrapper mx-auto">
        <h2 class="title">Nowości</h2>
    </div>
    <div class="bestsellers-slider-container">
        <?php get_template_part('template-parts/new-products-slider'); ?>
    </div>
</section>

<section class="">
    <?php jetlagz_inject_template_part('features'); ?>
</section>

<section class="wrapper flex flex-col-reverse sm:flex-row gap-12 max-w-4xl mx-auto my-20 px-4 sm:items-end">
    <div class=""><img class="w-full h-1/2" src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/newsletter.png" alt="Newsletter"></div>
    <div class="klaviyo-form-T8xFYE"></div>
</section>

<section class="video-section">

</section>

<?php
get_footer();
