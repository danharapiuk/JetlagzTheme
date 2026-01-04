<?php

/**
 * Template Name: 404
 * Template Post Type: page
 */

get_header(); ?>

<style>
    #content {
        display: flex;
        align-items: center;
    }
</style>


<main id="main" class="site-main" role="main">
    <div class="">
        <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/404.png'); ?>" alt="404 Image" class="mx-auto mb-8" />
        <h1 class="text-lg text-center">404 - Strona nie znaleziona</h1>
        <div class="flex flex-col sm:flex-row gap-3 sm:gap-6 justify-center mt-6">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <button class="button-primary w-full">Powrót do strony głównej</button>
            </a>
            <a href="">
                <button class="button-secondary w-full">Przejdź do sklepu</button>
        </div>
    </div>
</main>

<?php
get_footer();
