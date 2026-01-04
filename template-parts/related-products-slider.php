<?php

/**
 * Template Part: Related Products Slider
 * Displays related products in a slider (replaces default WooCommerce related products)
 */

global $product;

if (!$product) {
    return;
}

// Get related product IDs
$related_ids = wc_get_related_products($product->get_id(), 12);

if (empty($related_ids)) {
    return;
}

// Create WP_Query for related products
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'post__in'       => $related_ids,
    'orderby'        => 'rand',
    'post_status'    => 'publish',
);

$products_query = new WP_Query($args);
$slider_class = 'related-products-swiper';

// Load universal products slider
get_template_part('template-parts/products-slider', null, array(
    'products_query' => $products_query,
    'slider_class' => $slider_class
));
