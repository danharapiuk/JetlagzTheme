<?php

/**
 * The template for displaying product content within loops
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/content-product.php.
 *
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined('ABSPATH') || exit;

global $product;

// Ensure visibility.
if (empty($product) || !$product->is_visible()) {
    return;
}

$product_id = $product->get_id();
$image_id = $product->get_image_id();
$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'product_thumbnail_hq') : wc_placeholder_img_src();

// Get gallery images for hover effect
$gallery_image_ids = $product->get_gallery_image_ids();
$secondary_image_url = null;
if (!empty($gallery_image_ids)) {
    $secondary_image_url = wp_get_attachment_image_url($gallery_image_ids[0], 'product_thumbnail_hq');
}

// Ratings are only shown on single product page
?>
<li <?php wc_product_class('', $product); ?>>
    <div class="product-card">
        <a href="<?php echo esc_url(get_permalink()); ?>" class="product-link">
            <div class="product-image-wrapper">
                <img
                    class="product-image-primary"
                    src="<?php echo esc_url($image_url); ?>"
                    alt="<?php echo esc_attr(get_the_title()); ?>"
                    loading="lazy">
                <?php if ($secondary_image_url): ?>
                    <img
                        class="product-image-secondary"
                        src="<?php echo esc_url($secondary_image_url); ?>"
                        alt="<?php echo esc_attr(get_the_title()); ?>"
                        loading="lazy">
                <?php endif; ?>
                <?php if ($product->is_on_sale()) : ?>
                    <span class="sale-badge">
                        Sale
                    </span>
                <?php endif; ?>
            </div>

            <div class="product-info-row">
                <h3 class="product-title">
                    <?php
                    // Wyświetl tylko część nazwy do myślnika
                    $full_title = html_entity_decode(get_the_title(), ENT_QUOTES | ENT_HTML5, 'UTF-8');

                    if (strpos($full_title, '–') !== false) {
                        $title_parts = explode('–', $full_title, 2);
                        echo esc_html(trim($title_parts[0]));
                    } elseif (strpos($full_title, '—') !== false) {
                        $title_parts = explode('—', $full_title, 2);
                        echo esc_html(trim($title_parts[0]));
                    } elseif (strpos($full_title, ' - ') !== false) {
                        $title_parts = explode(' - ', $full_title, 2);
                        echo esc_html(trim($title_parts[0]));
                    } elseif (strpos($full_title, '-') !== false) {
                        $title_parts = explode('-', $full_title, 2);
                        echo esc_html(trim($title_parts[0]));
                    } else {
                        echo esc_html($full_title);
                    }
                    ?>
                </h3>

                <div class="product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
            </div>
        </a>
    </div>
</li>