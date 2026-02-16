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
            <div class="product-info-row">
                <h3 class="product-title">
                    <?php echo esc_html(get_the_title()); ?>
                </h3>
                <div class="stars">
                    <?php
                    $rating_count = $product->get_rating_count();
                    $average_rating = $product->get_average_rating();

                    if ($rating_count > 0) :
                        // Format rating: 5/5 (bez przecinka) lub 4.7/5 (z przecinkiem)
                        $rating_display = ($average_rating == 5) ? '5/5' : number_format($average_rating, 1, '.', '') . '/5';
                    ?>
                        <span class="rating-number"><?php echo $rating_display; ?></span>
                        <span class="rating-count">(<?php echo $rating_count; ?>)</span>
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
                    <?php endif; ?>
                </div>
                <div class="product-price">
                    <?php echo $product->get_price_html(); ?>
                </div>
            </div>
        </a>
    </div>
</li>