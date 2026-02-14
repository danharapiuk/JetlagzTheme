<?php

/**
 * Template Part: Recently Viewed Products Slider
 * Displays recently viewed products in a slider
 */

// Get recently viewed products from cookies/session
$recently_viewed = array();

// Debug: Check if cookie exists
// echo '<!-- Recently viewed cookie: ' . (isset($_COOKIE['woocommerce_recently_viewed']) ? $_COOKIE['woocommerce_recently_viewed'] : 'NOT SET') . ' -->';

// Check if WooCommerce has recently viewed products feature
if (function_exists('wc_get_product') && isset($_COOKIE['woocommerce_recently_viewed'])) {
    $recently_viewed_ids = array_reverse(array_map('absint', explode('|', $_COOKIE['woocommerce_recently_viewed'])));

    // Remove current product from the list if we're on single product page
    if (is_product()) {
        global $product;
        $current_product_id = $product ? $product->get_id() : 0;
        $recently_viewed_ids = array_filter($recently_viewed_ids, function ($id) use ($current_product_id) {
            return $id !== $current_product_id;
        });
    }

    // Limit to 8 products
    $recently_viewed_ids = array_slice($recently_viewed_ids, 0, 8);
} else {
    // Fallback: Show some recent products if no cookie is set (for testing)
    $recent_products_args = array(
        'post_type'      => 'product',
        'posts_per_page' => 8,
        'orderby'        => 'date',
        'order'          => 'DESC',
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            )
        )
    );

    if (is_product()) {
        global $product;
        $current_product_id = $product ? $product->get_id() : 0;
        $recent_products_args['post__not_in'] = array($current_product_id);
    }

    $recent_query = new WP_Query($recent_products_args);
    $recently_viewed_ids = array();
    if ($recent_query->have_posts()) {
        while ($recent_query->have_posts()) {
            $recent_query->the_post();
            $recently_viewed_ids[] = get_the_ID();
        }
        wp_reset_postdata();
    }
}

if (!empty($recently_viewed_ids)) {
    // Create WP_Query for recently viewed products
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 8,
        'post__in'       => $recently_viewed_ids,
        'orderby'        => isset($_COOKIE['woocommerce_recently_viewed']) ? 'post__in' : 'date',
        'post_status'    => 'publish',
        'meta_query'     => array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '='
            )
        )
    );

    $products_query = new WP_Query($args);
}

// If no recently viewed products or query failed, don't display anything
if (empty($recently_viewed_ids) || !isset($products_query) || !$products_query->have_posts()) {
    // echo '<!-- Recently viewed: No products to show -->';
    return;
}

$slider_class = 'recently-viewed-swiper';
?>

<!-- DEBUG: File loaded at <?php echo date('H:i:s'); ?> -->
<section class="recently-viewed-section relative py-16">
    <div class="">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800">
                <?php echo isset($_COOKIE['woocommerce_recently_viewed']) ? 'Ostatnio oglądane' : 'Polecane produkty'; ?>
            </h2>
            <div class="slider-navigation flex gap-2">
                <button class="<?php echo esc_attr($slider_class); ?>-prev slider-nav-btn prev-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <button class="<?php echo esc_attr($slider_class); ?>-next slider-nav-btn next-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>

        <?php
        // Load universal products slider
        get_template_part('template-parts/products-slider', null, array(
            'products_query' => $products_query,
            'slider_class' => $slider_class
        ));
        ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Recently Viewed Swiper
        const recentlyViewedSwiper = new Swiper('.<?php echo esc_js($slider_class); ?>', {
            slidesPerView: 2.15,
            spaceBetween: 6,
            navigation: {
                nextEl: '.<?php echo esc_js($slider_class); ?>-next',
                prevEl: '.<?php echo esc_js($slider_class); ?>-prev',
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                    spaceBetween: 20
                },
                768: {
                    slidesPerView: 3,
                    spaceBetween: 30
                },
                1024: {
                    slidesPerView: 4,
                    spaceBetween: 30
                }
            },
            loop: false,
            grabCursor: true,
        });
    });
</script>