<?php

/**
 * Template Name: Home
 * Template Post Type: page
 */

// Enqueue home page specific styles
wp_enqueue_style('home-page-styles', get_stylesheet_directory_uri() . '/assets/css/pages/home.css', array('storefront-style'), filemtime(get_stylesheet_directory() . '/assets/css/pages/home.css'));

get_header(); ?>

<?php
$hero = safe_get_field('hero');
if (!$hero) {
    $hero = array('title' => '', 'images' => array());
}
?>
<section class="mobile-hero lg:flex">
    <div>
        <?php if (!empty($hero['video'])) :
            $video_url = is_array($hero['video']) ? $hero['video']['url'] : $hero['video'];
        ?>
            <video autoplay muted loop playsinline>
                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            </video>
        <?php endif; ?>
    </div>
</section>



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

<?php get_template_part('template-parts/scroll-effect-section', null, [
    'title'  => $hero['title']  ?? '',
    'images' => $hero['images'] ?? [],
]); ?>

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
