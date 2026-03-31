<?php

/**
 * Template Part: Bestsellers Slider
 * Displays bestselling products in a slider
 */

// Build bestseller list: first products tagged "bestseller", then fill with top sales.
$products_limit = 12;
$excluded_ids = function_exists('jetlagz_get_all_gift_product_ids') ? jetlagz_get_all_gift_product_ids() : array();

$tagged_bestseller_ids = get_posts(array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'fields'         => 'ids',
    'posts_per_page' => $products_limit,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'post__not_in'   => $excluded_ids,
    'tax_query'      => array(
        array(
            'taxonomy' => 'product_tag',
            'field'    => 'slug',
            'terms'    => array('bestseller'),
            'operator' => 'IN',
        ),
    ),
));

$final_product_ids = $tagged_bestseller_ids;

if (count($final_product_ids) < $products_limit) {
    $fallback_ids = get_posts(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'posts_per_page' => $products_limit - count($final_product_ids),
        'meta_key'       => 'total_sales',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'post__not_in'   => array_merge($excluded_ids, $final_product_ids),
    ));

    $final_product_ids = array_merge($final_product_ids, $fallback_ids);
}

$final_product_ids = array_values(array_unique(array_map('intval', $final_product_ids)));

if (!empty($final_product_ids)) {
    $products_query = new WP_Query(array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => $products_limit,
        'post__in'       => $final_product_ids,
        'orderby'        => 'post__in',
    ));
} else {
    $products_query = new WP_Query(array(
        'post_type'      => 'product',
        'posts_per_page' => $products_limit,
        'meta_key'       => 'total_sales',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
        'post_status'    => 'publish',
        'post__not_in'   => $excluded_ids,
    ));
}
$slider_class = 'bestsellers-swiper';

// Load universal products slider
get_template_part('template-parts/products-slider', null, array(
    'products_query' => $products_query,
    'slider_class' => $slider_class
));
