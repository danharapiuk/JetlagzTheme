<?php

/**
 * Template Name: About
 * Template Post Type: page
 */

// Enqueue about page specific styles
wp_enqueue_style('about-page-styles', get_stylesheet_directory_uri() . '/assets/css/pages/about.css', array(), filemtime(get_stylesheet_directory() . '/assets/css/pages/about.css'));
get_header();

// Pobierz pola ACF z grupy 'about'
$about = get_field('about');
$about_info = $about['info'] ?? null;
$about_image = $about['image'] ?? null;
$about_bottom_image = $about['bottom_image'] ?? null;

// Upewnij się, że obrazki są w pełnym rozmiarze
if ($about_image && is_array($about_image)) {
    $about_image_url = $about_image['sizes']['full'] ?? $about_image['url'];
    $about_image_alt = $about_image['alt'] ?? '';
}

if ($about_bottom_image && is_array($about_bottom_image)) {
    $about_bottom_image_url = $about_bottom_image['sizes']['full'] ?? $about_bottom_image['url'];
    $about_bottom_image_alt = $about_bottom_image['alt'] ?? '';
}
?>


<section class="about-info-section font-inter text-black text-base">
    <div class="wrapper mx-auto">
        <div class="md:flex md:gap-16 justify-between">
            <?php if ($about_info) : ?>
                <div class="about-info md:w-2/3 max-w-[560px] pb-12">
                    <?php echo $about_info; ?>
                </div>
            <?php endif; ?>

            <?php if ($about_image) : ?>
                <div class="about-image md:w-1/3">
                    <img src="<?php echo esc_url($about_image_url); ?>" alt="<?php echo esc_attr($about_image_alt); ?>" class="w-full h-auto">
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($about_bottom_image) : ?>
    <section class="about-bottom-image-section">
        <div class="wrapper mx-auto flex">
            <div class="w-full mb-[-48px] md:mb-[-68px] lg:mb-[-48px]"><img src="<?php echo esc_url($about_bottom_image_url); ?>" alt="<?php echo esc_attr($about_bottom_image_alt); ?>" class="w-full h-auto"></div>
        </div>
    </section>
<?php endif; ?>


<?php
get_footer();
