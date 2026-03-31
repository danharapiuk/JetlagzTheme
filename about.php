<?php

/**
 * Template Name: About
 * Template Post Type: page
 */

// Enqueue about page specific styles
wp_enqueue_style('about-page-styles', get_stylesheet_directory_uri() . '/assets/css/pages/about.css', array('storefront-style'), filemtime(get_stylesheet_directory() . '/assets/css/pages/about.css'));
get_header();

// Pobierz pola ACF z grupy 'about'
$about = safe_get_field('about');
$about = is_array($about) ? $about : array();
?>
<section class="wrapper">
    <div class="lg:min-h-screen lg:mt-[-120px] flex flex-col justify-between">
        <div></div>
        <h1 class="font-thin text-4xl text-center max-w-[800px] mx-auto py-20 lg:py-0"><?php echo esc_html(wp_strip_all_tags($about['hero'] ?? '')); ?></h1>
        <div class="max-w-[760px] font-light pb-2 text-black mx-auto text-center pb-[60px]"><?php echo esc_html($about['description'] ?? ''); ?></div>
    </div>

    <div class="">
        <div class="grid grid-cols-3 gap-1 sm:gap-3">
            <?php
            $about_gallery = !empty($about['gallery']) && is_array($about['gallery'])
                ? $about['gallery']
                : array();

            foreach ($about_gallery as $image) :
                $image_url = '';
                $image_alt = '';

                if (is_array($image)) {
                    $image_url = $image['url'] ?? '';
                    $image_alt = $image['alt'] ?? '';
                } elseif (is_numeric($image)) {
                    $image_url = wp_get_attachment_image_url((int) $image, 'full');
                    $image_alt = get_post_meta((int) $image, '_wp_attachment_image_alt', true);
                }

                if (!$image_url) {
                    continue;
                }
            ?>
                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_alt); ?>" class="block w-full max-w-full h-full object-cover object-center rounded-[2px] w-full">
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($about['gallery'])) : ?>
    <?php
    global $scroll_effect_data;
    $scroll_effect_data = ['title' => 'ALMOSTDREAM', 'images' => $about['gallery']];
    get_template_part('template-parts/scroll-effect-section');
    ?>
<?php endif; ?>

<section class="wrapper py-20">
    <h2 class="text-2xl text-center mb-10 tracking-tight">Dlaczego my?</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-10 mx-auto max-w-[600px] lg:max-w-none">
        <?php foreach (($about['features'] ?? array()) as $feature) : ?>
            <div class="flex flex-col items-center text-center">
                <?php
                $feature_image = '';

                if (!empty($feature['image']) && is_array($feature['image'])) {
                    $feature_image = $feature['image']['url'] ?? '';
                } elseif (!empty($feature['image']) && is_string($feature['image'])) {
                    $feature_image = $feature['image'];
                }
                ?>
                <?php if ($feature_image) : ?>
                    <img src="<?php echo esc_url($feature_image); ?>" alt="<?php echo esc_attr($feature['title'] ?? ''); ?>">
                <?php endif; ?>
                <h3 class="mb-2 font-medium text-center tracking-tight"><?php echo esc_html($feature['title'] ?? ''); ?></h3>
                <p class="mb-4 font-light text-xs text-[#939393] text-center max-w-[210px]"><?php echo esc_html($feature['description'] ?? ''); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="wrapper">
    <div class="flex flex-col sm:flex-row gap-3 justify-center lg:max-w-[760px] mx-auto sm:gap-8 md:gap-12 py-12 md:py-32">
        <div><img src="<?php echo esc_url($about['image']['url'] ?? ''); ?>" alt="<?php echo esc_attr($about['image']['alt'] ?? ''); ?>" class="w-full h-full object-cover object-center"></div>
        <div class="tracking-tight text-black">
            <h2 class="text-2xl mb-3"><?php echo esc_html($about['second_title'] ?? ''); ?></h2>
            <div class="font-light text-sm"><?php echo wp_kses_post($about['second_description'] ?? ''); ?></div>
        </div>
    </div>
</section>

<section class="wrapper py-12 md:py-20 mx-auto">
    <?php get_template_part('template-parts/contact-elements'); ?>
</section>

<section class="pb-12">
    <?php echo do_shortcode('[instagram-feed feed=1]'); ?>
</section>

<?php
get_footer();
?>