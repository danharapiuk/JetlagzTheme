<?php

/**
 * Template Part: Products Slider
 * Universal slider for any product collection
 * 
 * @param WP_Query $products_query - Products query to display
 * @param string $slider_class - Optional CSS class for slider wrapper
 */

// Get parameters from $args array passed by get_template_part
$products_query = isset($args['products_query']) ? $args['products_query'] : null;
$slider_class = isset($args['slider_class']) ? $args['slider_class'] : 'products-swiper';

if (!$products_query || !$products_query->have_posts()) {
    return;
}
?>

<div class="products-slider-wrapper">
    <div class="swiper <?php echo esc_attr($slider_class); ?>">
        <div class="swiper-wrapper">
            <?php while ($products_query->have_posts()) : $products_query->the_post(); ?>
                <?php
                global $product;
                $product_id = get_the_ID();
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

                <div class="swiper-slide">
                    <div class="product-card">
                        <a href="<?php echo esc_url(get_permalink()); ?>" class="product-link">
                            <div class="product-image-wrapper">
                                <img
                                    class="product-image-primary"
                                    src="<?php echo esc_url($image_url); ?>"
                                    alt="<?php echo esc_attr(get_the_title()); ?>">
                                <?php if ($secondary_image_url): ?>
                                    <img
                                        class="product-image-secondary"
                                        src="<?php echo esc_url($secondary_image_url); ?>"
                                        alt="<?php echo esc_attr(get_the_title()); ?>">
                                <?php endif; ?>
                                <?php if ($product->is_on_sale()) : ?>
                                    <span class="sale-badge">
                                        Sale
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div>
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

                                <div class="product-info-row">
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
                            </div>
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Navigation arrows (not for recently-viewed which has custom navigation) -->
        <?php if ($slider_class !== 'recently-viewed-swiper'): ?>
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        <?php endif; ?>
    </div>
</div>

<?php wp_reset_postdata(); ?>

<?php if (!wp_script_is('swiper-js', 'enqueued')): ?>
    <script src="<?php echo get_stylesheet_directory_uri(); ?>/assets/js/swiper-bundle.min.js" id="swiper-js"></script>
    <link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/assets/css/swiper-bundle.min.css" id="swiper-css">
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize all product swipers
        const swiperClass = '<?php echo isset($slider_class) ? esc_js($slider_class) : "products-swiper"; ?>';
        const swiperElement = document.querySelector('.' + swiperClass);

        if (swiperElement && !swiperElement.swiper) {
            const swiperConfig = {
                slidesPerView: 2.15,
                spaceBetween: 6,
                breakpoints: {
                    640: {
                        slidesPerView: 2,
                        spaceBetween: 20,
                    },
                    768: {
                        slidesPerView: 3,
                        spaceBetween: 30,
                    },
                    1024: {
                        slidesPerView: 4,
                        spaceBetween: 40,
                    },
                },
            };

            // Add navigation only for non-recently-viewed sliders
            if (swiperClass !== 'recently-viewed-swiper') {
                swiperConfig.navigation = {
                    nextEl: '.' + swiperClass + ' .swiper-button-next',
                    prevEl: '.' + swiperClass + ' .swiper-button-prev',
                    disabledClass: 'swiper-button-disabled',
                };
            }

            const swiper = new Swiper('.' + swiperClass, swiperConfig);

            // Handle button visibility only for sliders with navigation
            if (swiperClass !== 'recently-viewed-swiper') {
                swiper.on('init', function() {
                    const prevBtn = this.el.querySelector('.swiper-button-prev');
                    if (this.isBeginning && prevBtn) {
                        prevBtn.style.display = 'none';
                    }
                });

                swiper.on('slideChange', function() {
                    const prevBtn = this.el.querySelector('.swiper-button-prev');
                    const nextBtn = this.el.querySelector('.swiper-button-next');

                    if (prevBtn) {
                        prevBtn.style.display = this.isBeginning ? 'none' : 'flex';
                    }

                    if (nextBtn) {
                        nextBtn.style.display = this.isEnd ? 'none' : 'flex';
                    }
                });
            }
        }
    });
</script>