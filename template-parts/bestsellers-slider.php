<?php

/**
 * Template Part: Bestsellers Slider
 * Displays bestselling products in a slider
 */

// Get bestsellers - products ordered by total sales
$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'meta_key'       => 'total_sales',
    'orderby'        => 'meta_value_num',
    'order'          => 'DESC',
    'post_status'    => 'publish',
);

$products_query = new WP_Query($args);
$slider_class = 'bestsellers-swiper';

// Load universal products slider
get_template_part('template-parts/products-slider', null, array(
    'products_query' => $products_query,
    'slider_class' => $slider_class
));
