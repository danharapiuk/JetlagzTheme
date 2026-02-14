<?php

/**
 * Template Name: Content
 * Template Post Type: page
 */

wp_enqueue_style('about-page-styles', get_stylesheet_directory_uri() . '/assets/css/pages/content.css', array(), filemtime(get_stylesheet_directory() . '/assets/css/pages/content.css'));
get_header();



$content = safe_get_field('content');
$image = safe_get_field('image');
$image_url = '';
$image_alt = '';

if ($image) {
    // Pobierz pełny rozmiar obrazu
    if (is_array($image)) {
        $image_url = $image['url'];
        $image_alt = $image['alt'];
    } elseif (is_numeric($image)) {
        // Jeśli zwrócone jest ID, pobierz pełny rozmiar
        $image_url = wp_get_attachment_image_url($image, 'full');
        $image_alt = get_post_meta($image, '_wp_attachment_image_alt', true);
    }
}
?>

<section class="content-section">
    <div class="wrapper mx-auto px-4 pb-12 md:pb-24">
        <?php echo $content; ?>
        <?php if ($image_url) : ?>
            <div class="content-image-section py-20">
                <img src="<?php echo esc_url($image_url); ?>"
                    alt="<?php echo esc_attr($image_alt ?: 'Content image'); ?>"
                    class="content-image">
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
get_footer();
?>