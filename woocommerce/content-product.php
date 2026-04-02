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
                <?php jetlagz_render_product_card_badges($product); ?>
                <?php
                $rating_count = $product->get_rating_count();
                $average_rating = $product->get_average_rating();
                if ($rating_count > 0) :
                    $rating_display = ($average_rating == 5) ? '5/5' : number_format($average_rating, 1, '.', '') . '/5';
                endif;
                ?>
            </div>
        </a>
        <?php
        // Wishlist button on image
        if (function_exists('YITH_WCWL')) {
            $wishlist = YITH_WCWL();
            $is_in_wishlist = $wishlist->is_product_in_wishlist($product_id);
        ?>
            <button type="button" class="wishlist-heart-btn<?php echo $is_in_wishlist ? ' in-wishlist' : ''; ?>"
                data-product-id="<?php echo esc_attr($product_id); ?>"
                title="<?php echo $is_in_wishlist ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'; ?>">
                <svg class="heart-icon" width="20" height="20" viewBox="0 0 24 24" fill="<?php echo $is_in_wishlist ? 'currentColor' : 'none'; ?>" stroke="currentColor" stroke-width="2">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        <?php } ?>
        <a href="<?php echo esc_url(get_permalink()); ?>" class="product-link product-info-link">
            <?php
            // Check for ACF custom name/description
            $acf_name = function_exists('get_field') ? get_field('product_name', $product_id) : '';
            $acf_description = function_exists('get_field') ? get_field('product_description', $product_id) : '';

            if (!empty($acf_name)) :
            ?>
                <div class="product-title-wrapper">
                    <h3 class="slider-product-title" itemprop="name"><?php echo esc_html($acf_name); ?></h3>
                    <?php if (!empty($acf_description)) : ?>
                        <p class="slider-product-subtitle" itemprop="description"><?php echo esc_html($acf_description); ?></p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <h3 class="product-title">
                    <?php echo esc_html(get_the_title()); ?>
                </h3>
            <?php endif; ?>

            <?php if ($rating_count > 0) : ?>
                <div class="stars">
                    <span class="rating-stars">
                        <?php
                        for ($i = 1; $i <= 5; $i++) {
                            if ($i <= round($average_rating)) {
                                echo '<span class="star filled">★</span>';
                            } else {
                                echo '<span class="star empty">★</span>';
                            }
                        }
                        ?>
                    </span>
                    <span class="rating-number"><?php echo $rating_display; ?></span>
                    <span class="rating-count">(<?php echo $rating_count; ?>)</span>
                </div>
            <?php endif; ?>

            <div class="product-info-row">
                <div class="product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
            </div>

            <?php
            // Wyświetl kolory produktu pod ceną
            jetlagz_display_product_colors($product);
            ?>
        </a>
    </div>
</li>