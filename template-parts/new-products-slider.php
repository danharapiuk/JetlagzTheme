<?php

/**
 * Template Part: New Products Slider
 * Displays newest products in a slider
 */

// Get new products - newest first
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post_status'    => 'publish',
);

$products_query = new WP_Query($args);
$slider_class = 'new-products-swiper';

// Load universal products slider
get_template_part('template-parts/products-slider', null, array(
    'products_query' => $products_query,
    'slider_class' => $slider_class
));
