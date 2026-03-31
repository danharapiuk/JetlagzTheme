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

$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/sklep/');
$sale_link = add_query_arg('on_sale', '1', $shop_url);
?>
<section class="wrapper">
    <div class="mobile-hero mb-3 md:mb-0 relative md:border-black md:border-[1px]">
        <?php if (!empty($hero['video'])) :
            $video_url = is_array($hero['video']) ? $hero['video']['url'] : $hero['video'];
        ?>
            <video autoplay muted loop playsinline webkit-playsinline x5-playsinline>
                <source src="<?php echo esc_url($video_url); ?>" type="video/mp4">
            </video>
            <script>
                (function() {
                    var videos = document.querySelectorAll('.mobile-hero video');
                    videos.forEach(function(v) {
                        v.muted = true;
                        var p = v.play();
                        if (p !== undefined) {
                            p.catch(function() {
                                v.muted = true;
                                v.play();
                            });
                        }
                    });
                })();
            </script>
        <?php endif; ?>
        <div class="absolute bottom-12 sm:bottom-[250px] left-1/2 transform -translate-x-1/2 w-full px-4 sm:px-0 font-inter">
            <h1 class="text-left sm:text-center font-thin text-white text-2xl sm:text-5xl leading-[1.1]">Zmysłowość zaczyna się od detali.</h1>
            <p class="text-left sm:text-center text-white pt-2 font-thin">Od subtelnej koronki po odważne fasony — wybierz styl, który mówi więcej niż słowa.</p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-12">
                <a href="#bestsellers"><button class="bg-white w-full sm:w-fit text-black uppercase px-4 py-2">Bestsellery</button></a>
                <a href="<?php echo esc_url($sale_link); ?>"><button class="bg-[#51172F] w-full sm:w-fit text-white uppercase px-4 py-2">Promo do -60%</button></a>
            </div>
        </div>
    </div>
    <?php get_template_part('template-parts/product-categories'); ?>
</section>



<script>
    // Zmiana tytułu strony gdy użytkownik przełączy kartę
    document.addEventListener('DOMContentLoaded', function() {
        const originalTitle = document.title;
        const newTitle = " Wróć do nas! 👋"; // Dostosuj tekst według potrzeb

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                document.title = newTitle;
            } else {
                document.title = originalTitle;
            }
        });
    });
</script>



<section id="bestsellers" class="bestsellers py-16 md:py-20 overflow-hidden">
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
