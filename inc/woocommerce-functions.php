<?php

/**
 * Funkcje WooCommerce - tylko niezbędne dostosowania
 */

// Zapobieganie bezpośredniemu dostępowi
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Convert comma-separated GET parameters to arrays before WordPress processes them
 * This prevents the urlencode() error with array values
 */
function jetlagz_convert_filter_params()
{
    // Convert comma-separated strings to arrays for filter parameters
    $filter_params = array('product_cat', 'pa_rozmiar', 'pa_kolor');

    foreach ($filter_params as $param) {
        if (isset($_GET[$param]) && is_string($_GET[$param]) && !empty($_GET[$param])) {
            // Keep as string - we'll handle it in our filter function
            // This prevents WordPress from trying to parse it as a taxonomy term
            continue;
        }
    }
}
add_action('init', 'jetlagz_convert_filter_params', 1);

/**
 * Remove .00 from prices (hide decimals when price is whole number)
 */
function jetlagz_trim_zeros_from_price($price)
{
    // Remove .00 or ,00 from the end of price
    $price = preg_replace('/[.,]00([^\d]|$)/', '$1', $price);
    return $price;
}
add_filter('woocommerce_price_trim_zeros', '__return_true');
add_filter('formatted_woocommerce_price', 'jetlagz_trim_zeros_from_price', 10, 1);
add_filter('woocommerce_format_sale_price', 'jetlagz_trim_zeros_from_price', 10, 1);

/**
 * Dostosowanie liczby produktów na stronę (z możliwością wyboru przez użytkownika)
 */
function universal_theme_products_per_page($per_page)
{
    // Check if user selected custom per_page
    if (isset($_GET['per_page']) && !empty($_GET['per_page'])) {
        $per_page = intval($_GET['per_page']);
        // -1 means all products
        if ($per_page == -1) {
            return 9999;
        }
        return $per_page;
    }

    // Default from theme options
    $products_per_page = get_theme_option('woocommerce.products_per_page');
    return $products_per_page ? $products_per_page : 12;
}
add_filter('loop_shop_per_page', 'universal_theme_products_per_page', 20);

/**
 * Dostosowanie liczby produktów w rzędzie (z konfiguracji motywu)
 */
function universal_theme_products_per_row()
{
    $products_per_row = get_theme_option('woocommerce.products_per_row');
    return $products_per_row ? $products_per_row : 3; // domyślnie jak Storefront
}
add_filter('storefront_loop_columns', 'universal_theme_products_per_row');

/**
 * Customize Add to Cart button classes for shop loop
 */
function jetlagz_custom_add_to_cart_class($classes)
{
    // Remove default WooCommerce classes
    $classes = array_diff($classes, ['button', 'product_type_simple', 'add_to_cart_button', 'ajax_add_to_cart']);

    // Add our custom class
    $classes[] = 'add-to-cart-btn';

    return $classes;
}
add_filter('woocommerce_loop_add_to_cart_args', function ($args) {
    $args['class'] = 'add-to-cart-btn';
    return $args;
}, 10, 1);

/**
 * Remove default WooCommerce shop elements (we have custom versions)
 */
remove_action('woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30);
remove_action('woocommerce_before_shop_loop', 'woocommerce_result_count', 20);

/**
 * Remove Add to Cart buttons from product loop (all products have variations)
 */
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
remove_action('woocommerce_after_shop_loop', 'woocommerce_pagination', 10);

/**
 * Advanced Product Filtering
 */
function jetlagz_filter_products_query($query)
{
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_taxonomy())) {

        $meta_query = $query->get('meta_query') ?: array();
        $tax_query = $query->get('tax_query') ?: array();

        // Price filter
        if (isset($_GET['min_price']) || isset($_GET['max_price'])) {
            $price_meta = array('key' => '_price', 'type' => 'NUMERIC');

            if (isset($_GET['min_price']) && !empty($_GET['min_price'])) {
                $price_meta['value'] = array(floatval($_GET['min_price']));
                $price_meta['compare'] = '>=';
            }

            if (isset($_GET['max_price']) && !empty($_GET['max_price'])) {
                if (isset($price_meta['value'])) {
                    $price_meta['value'][] = floatval($_GET['max_price']);
                    $price_meta['compare'] = 'BETWEEN';
                } else {
                    $price_meta['value'] = floatval($_GET['max_price']);
                    $price_meta['compare'] = '<=';
                }
            }

            $meta_query[] = $price_meta;
        }

        // Stock status filter
        if (isset($_GET['stock_status']) && $_GET['stock_status'] === 'instock') {
            $meta_query[] = array(
                'key' => '_stock_status',
                'value' => 'instock',
                'compare' => '='
            );
        }

        // On sale filter
        if (isset($_GET['on_sale']) && $_GET['on_sale'] === '1') {
            $meta_query[] = array(
                'key' => '_sale_price',
                'value' => '',
                'compare' => '!='
            );
        }

        // Category filter
        if (isset($_GET['product_cat']) && !empty($_GET['product_cat'])) {
            $categories = is_string($_GET['product_cat']) ? explode(',', $_GET['product_cat']) : (array)$_GET['product_cat'];
            $categories = array_filter(array_map('sanitize_text_field', $categories));
            if (!empty($categories)) {
                $tax_query[] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $categories,
                    'operator' => 'IN'
                );
            }
        }

        // Size filter - only show products with selected size IN STOCK
        if (isset($_GET['filter_rozmiar']) && !empty($_GET['filter_rozmiar'])) {
            $sizes = is_string($_GET['filter_rozmiar']) ? explode(',', $_GET['filter_rozmiar']) : (array)$_GET['filter_rozmiar'];
            $sizes = array_filter(array_map('sanitize_text_field', $sizes));
            if (!empty($sizes)) {
                $tax_query[] = array(
                    'taxonomy' => 'pa_rozmiar',
                    'field' => 'slug',
                    'terms' => $sizes,
                    'operator' => 'IN'
                );
            }
        }

        // Color filter
        if (isset($_GET['filter_kolor']) && !empty($_GET['filter_kolor'])) {
            $colors = is_string($_GET['filter_kolor']) ? explode(',', $_GET['filter_kolor']) : (array)$_GET['filter_kolor'];
            $colors = array_filter(array_map('sanitize_text_field', $colors));
            if (!empty($colors)) {
                $tax_query[] = array(
                    'taxonomy' => 'pa_kolor',
                    'field' => 'slug',
                    'terms' => $colors,
                    'operator' => 'IN'
                );
            }
        }
        if (!empty($meta_query)) {
            $meta_query['relation'] = 'AND';
            $query->set('meta_query', $meta_query);
        }

        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }

        // Custom sorting by size attribute
        if (isset($_GET['orderby'])) {
            if ($_GET['orderby'] === 'size-asc') {
                $query->set('orderby', 'meta_value');
                $query->set('meta_key', 'attribute_pa_rozmiar');
                $query->set('order', 'ASC');
            } elseif ($_GET['orderby'] === 'size-desc') {
                $query->set('orderby', 'meta_value');
                $query->set('meta_key', 'attribute_pa_rozmiar');
                $query->set('order', 'DESC');
            }
        }
    }
}
add_action('pre_get_posts', 'jetlagz_filter_products_query');

/**
 * Filter products by size availability - only show if variation with selected size is in stock
 */
function jetlagz_filter_products_by_size_stock($posts, $query)
{
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_taxonomy())) {
        if (isset($_GET['pa_rozmiar']) && !empty($_GET['pa_rozmiar'])) {
            $selected_sizes = is_string($_GET['pa_rozmiar']) ? explode(',', $_GET['pa_rozmiar']) : (array)$_GET['pa_rozmiar'];
            $selected_sizes = array_filter(array_map('sanitize_text_field', $selected_sizes));

            if (empty($selected_sizes)) {
                return $posts;
            }

            $filtered_posts = array();

            foreach ($posts as $post) {
                $product = wc_get_product($post->ID);

                // Skip if not a variable product
                if (!$product || !$product->is_type('variable')) {
                    continue;
                }

                $variations = $product->get_available_variations();
                $has_size_in_stock = false;

                // Check if any variation with selected size is in stock
                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);

                    if (!$variation_obj || !$variation_obj->is_in_stock()) {
                        continue;
                    }

                    // Get size attribute from variation
                    $variation_attributes = $variation_obj->get_attributes();

                    if (isset($variation_attributes['pa_rozmiar'])) {
                        $variation_size = $variation_attributes['pa_rozmiar'];

                        // Check if this variation has one of the selected sizes
                        if (in_array($variation_size, $selected_sizes)) {
                            $has_size_in_stock = true;
                            break;
                        }
                    }
                }

                // Only include product if it has selected size in stock
                if ($has_size_in_stock) {
                    $filtered_posts[] = $post;
                }
            }

            return $filtered_posts;
        }
    }

    return $posts;
}
add_filter('posts_results', 'jetlagz_filter_products_by_size_stock', 5, 2);

/**
 * Custom sorting for products by size attribute
 */
function jetlagz_sort_products_by_size($posts, $query)
{
    if (!is_admin() && $query->is_main_query() && (is_shop() || is_product_taxonomy())) {
        if (isset($_GET['orderby']) && in_array($_GET['orderby'], ['size-asc', 'size-desc'])) {

            // Define size order (smallest to largest)
            $size_order = array(
                'xxxs' => 1,
                '2xs' => 2,
                'xxs' => 3,
                'xs' => 4,
                's' => 5,
                'm' => 6,
                'l' => 7,
                'xl' => 8,
                'xxl' => 9,
                '2xl' => 10,
                'xxxl' => 11,
                '3xl' => 12,
                '4xl' => 13,
                '5xl' => 14,
                // Numeric sizes
                '28' => 15,
                '30' => 16,
                '32' => 17,
                '34' => 18,
                '36' => 19,
                '38' => 20,
                '40' => 21,
                '42' => 22,
                '44' => 23,
                '46' => 24
            );

            usort($posts, function ($a, $b) use ($size_order) {
                $product_a = wc_get_product($a->ID);
                $product_b = wc_get_product($b->ID);

                // Get size attributes
                $size_a = $product_a->get_attribute('pa_rozmiar');
                $size_b = $product_b->get_attribute('pa_rozmiar');

                // Handle multiple sizes (take first one)
                if (strpos($size_a, ',') !== false) {
                    $size_a = trim(explode(',', $size_a)[0]);
                }
                if (strpos($size_b, ',') !== false) {
                    $size_b = trim(explode(',', $size_b)[0]);
                }

                $size_a = strtolower(trim($size_a));
                $size_b = strtolower(trim($size_b));

                $order_a = isset($size_order[$size_a]) ? $size_order[$size_a] : 999;
                $order_b = isset($size_order[$size_b]) ? $size_order[$size_b] : 999;

                return $order_a - $order_b;
            });

            // Reverse if descending
            if ($_GET['orderby'] === 'size-desc') {
                $posts = array_reverse($posts);
            }
        }
    }

    return $posts;
}
add_filter('posts_results', 'jetlagz_sort_products_by_size', 10, 2);

/**
 * Remove default rating from single product
 */
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10);

/**
 * Add custom rating display before title
 */
function jetlagz_custom_product_rating()
{
    global $product;

    if (!$product) {
        return;
    }

    $rating_count = $product->get_rating_count();
    $average_rating = $product->get_average_rating();

    if ($rating_count === 0) {
        return;
    }

    // Odmiana słowa "opinia"
    if ($rating_count === 1) {
        $opinion_word = 'opinia';
    } elseif ($rating_count % 10 >= 2 && $rating_count % 10 <= 4 && ($rating_count % 100 < 10 || $rating_count % 100 >= 20)) {
        $opinion_word = 'opinie';
    } else {
        $opinion_word = 'opinii';
    }

    // Format ratingu: 5/5 (bez przecinka) lub 4.7/5 (z przecinkiem)
    $rating_display = ($average_rating == 5) ? '5/5' : number_format($average_rating, 1, ',', '') . '/5';
?>
    <div class="custom-product-rating">
        <div class="rating-stars">
            <?php
            for ($i = 1; $i <= 5; $i++) {
                if ($i <= floor($average_rating)) {
                    echo '<span class="star filled">★</span>';
                } elseif ($i == ceil($average_rating) && $average_rating - floor($average_rating) >= 0.5) {
                    echo '<span class="star filled">★</span>';
                } else {
                    echo '<span class="star empty">★</span>';
                }
            }
            ?>
        </div>
        <span class="average-rating"><?php echo $rating_display; ?></span>
        <a href="#product-reviews" class="reviews-link">
            (<?php echo $rating_count; ?> <?php echo $opinion_word; ?>)
        </a>
    </div>
<?php
}
add_action('woocommerce_single_product_summary', 'jetlagz_custom_product_rating', 4);

/**
 * Display benefits from product ACF field after product title
 */
function jetlagz_display_benefits_after_title()
{
    if (!is_product()) {
        return;
    }

    global $product;
    $product_id = $product->get_id();

    // Get benefits repeater from current product ACF
    $benefits = get_field('benefits', $product_id);

    if (!$benefits || !is_array($benefits)) {
        return;
    }
?>
    <div class="product-benefits">
        <?php foreach ($benefits as $benefit):
            $icon = $benefit['icon'] ?? '';
            $title = $benefit['title'] ?? '';

            // Skip if both icon and title are empty
            if (empty($icon) && empty($title)) {
                continue;
            }
        ?>
            <div class="benefit-item">
                <?php if (!empty($icon)): ?>
                    <div class="benefit-icon">
                        <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($title); ?>" />
                    </div>
                <?php endif; ?>
                <?php if (!empty($title)): ?>
                    <div class="benefit-title">
                        <?php echo esc_html($title); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}
add_action('woocommerce_single_product_summary', 'jetlagz_display_benefits_after_title', 6);

/**
 * Add first word from product title as data attribute for ::before
 */
function jetlagz_add_first_word_to_title()
{
    if (!is_product()) {
        return;
    }

    global $product;
    $title = $product->get_name();
    $first_word = strtok($title, ' ');

    if (empty($first_word)) {
        return;
    }
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var productTitle = document.querySelector('.product_title.entry-title');
            if (productTitle) {
                productTitle.setAttribute('data-first-word', '<?php echo esc_js($first_word); ?>');
            }
        });
    </script>
<?php
}
add_action('wp_footer', 'jetlagz_add_first_word_to_title');

/**
 * Display product features after summary (from Template Parts - repeater)
 */
function jetlagz_display_product_features()
{
    if (!is_product()) {
        return;
    }

    // Pobierz repeater z Template Parts
    $product_features = get_field('product_features', 'option');

    if (!$product_features || !is_array($product_features)) {
        return;
    }
?>
    <div class="product-features-section">
        <div class="flex flex-col border border-gray-200 divide-y divide-gray-200">
            <?php foreach ($product_features as $feature): ?>
                <div class="product-feature-item p-2 flex gap-6 items-start">
                    <?php if (!empty($feature['icon'])): ?>
                        <div class="feature-icon">
                            <img src="<?php echo esc_url($feature['icon']['url']); ?>" alt="<?php echo esc_attr($feature['title']); ?>">
                        </div>
                    <?php endif; ?>
                    <div class="feature-content">
                        <?php if (!empty($feature['title'])): ?>
                            <h4 class="feature-title"><?php echo esc_html($feature['title']); ?></h4>
                        <?php endif; ?>
                        <?php if (!empty($feature['description'])): ?>
                            <p class="feature-description"><?php echo esc_html($feature['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php
}
add_action('woocommerce_after_add_to_cart_form', 'jetlagz_display_product_features', 15);

/**
 * Display newsletter discount section
 */
function jetlagz_display_newsletter_discount()
{
    if (!is_product()) {
        return;
    }

    global $product;
    $product_price = $product->get_price();

    if (!$product_price) {
        return;
    }

    // Calculate 11% discount
    $discount_amount = $product_price * 0.11;
    $discount_formatted = number_format($discount_amount, 2, ',', ' ');
?>
    <div class="newsletter-discount-section">
        <div class="newsletter-discount-wrapper">
            <!-- Button with dropdown -->
            <div class="newsletter-button-container">
                <button type="button" class="newsletter-toggle-btn" id="newsletter-toggle">
                    KLIKNIJ I ODBIERZ RABAT
                </button>

                <!-- Modal Backdrop -->
                <div class="newsletter-modal-backdrop" id="newsletter-modal-backdrop" style="display: none;"></div>

                <!-- Modal Newsletter Form -->
                <div class="newsletter-modal" id="newsletter-dropdown" style="display: none;">
                    <button type="button" class="newsletter-close-btn" id="newsletter-close-btn" aria-label="Zamknij">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12" />
                        </svg>
                    </button>
                    <h3 class="newsletter-dropdown-title">Odbierz kod rabatowy -11%</h3>
                    <p class="newsletter-dropdown-description">Zapisz się do newslettera i otrzymaj rabat na pierwsze zakupy!</p>

                    <form class="newsletter-form" id="product-newsletter-form">
                        <input
                            type="email"
                            name="newsletter_email"
                            placeholder="Twój adres e-mail"
                            required
                            class="newsletter-input" />
                        <button type="submit" class="newsletter-submit-btn">
                            Zapisz się
                        </button>
                    </form>

                    <div class="newsletter-message" style="display: none;"></div>
                </div>
            </div>

            <!-- Savings Info -->
            <div class="newsletter-savings-info">
                <span class="savings-text">Oszczędzasz <?php echo esc_html($discount_formatted); ?> zł!</span>
                <span class="savings-percentage">(-11%)</span>
            </div>
        </div>
    </div>

    <style>
        .newsletter-discount-wrapper {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin: 1.5rem 0;
        }

        .newsletter-button-container {
            position: relative;
            display: inline-flex;
            align-items: center;
            cursor: pointer;
        }

        .newsletter-button-container::after {
            content: '';
            display: inline-block;
            width: 50px;
            height: 28px;
            background-image: url('<?php echo get_stylesheet_directory_uri(); ?>/assets/images/code.svg');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            margin-left: -1px;
        }

        .newsletter-toggle-btn {
            padding: 6px 1px 6px 12px;
            background: #000;
            color: #fff;
            border: none;
            font-weight: 300;
            font-size: 10px;
            cursor: pointer;
            text-transform: uppercase;
        }

        .newsletter-toggle-btn:hover {
            background-color: black;
            border-color: black;
            color: white;
        }

        /* Modal Backdrop - full screen overlay with blur */
        .newsletter-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 9998;
        }

        /* Modal - centered on screen */
        .newsletter-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            min-width: 400px;
            max-width: 90vw;
        }

        /* Close button */
        .newsletter-close-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .newsletter-close-btn:hover {
            background: #f3f4f6;
        }

        .newsletter-close-btn svg {
            width: 24px;
            height: 24px;
            stroke: #666;
        }

        .newsletter-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 100;
            min-width: 450px;
        }

        .newsletter-dropdown-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
            color: #111;
        }

        .newsletter-dropdown-description {
            font-size: 14px;
            color: #666;
            margin: 0 0 1rem 0;
        }

        .newsletter-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .newsletter-input {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }

        .newsletter-input:focus {
            outline: none;
            border-color: #000;
        }

        .newsletter-submit-btn {
            padding: 10px 20px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .newsletter-submit-btn:hover {
            background: #333;
        }

        .newsletter-message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 4px;
            font-size: 14px;
        }

        .newsletter-message.success {
            background: #d1fae5;
            color: #065f46;
        }

        .newsletter-message.error {
            background: #fee2e2;
            color: #991b1b;
        }

        .newsletter-savings-info {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0 10px;
            background: rgba(48, 211, 102, 0.2);
            height: 20px;
        }

        .savings-text {
            font-size: 12px;
            font-weight: 300;
            color: #008C23;
            padding-right: 3px;
        }

        .savings-percentage {
            font-size: 12px;
            color: #008C23;
            font-weight: 500;
        }

        @media (max-width: 640px) {
            .newsletter-discount-wrapper {
                flex-direction: column;
            }

            .newsletter-button-container,
            .newsletter-savings-info {
                width: 100%;
            }

            .newsletter-modal {
                min-width: 320px;
                padding: 1.5rem;
            }
        }

        /* Prevent body scroll when modal is open */
        body.newsletter-modal-open {
            overflow: hidden;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            var $toggleBtn = $('#newsletter-toggle');
            var $backdrop = $('#newsletter-modal-backdrop');
            var $modal = $('#newsletter-dropdown');
            var $closeBtn = $('#newsletter-close-btn');
            var $form = $('#product-newsletter-form');
            var $message = $('.newsletter-message');

            // Open modal
            $toggleBtn.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $backdrop.fadeIn(300);
                $modal.fadeIn(300);
                $('body').addClass('newsletter-modal-open');
            });

            // Close modal function
            function closeModal() {
                $backdrop.fadeOut(300);
                $modal.fadeOut(300);
                $('body').removeClass('newsletter-modal-open');
            }

            // Close button
            $closeBtn.on('click', closeModal);

            // Close on backdrop click
            $backdrop.on('click', closeModal);

            // Close on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $modal.is(':visible')) {
                    closeModal();
                }
            });

            // Handle form submission
            $form.on('submit', function(e) {
                e.preventDefault();

                var email = $form.find('input[name="newsletter_email"]').val();

                // Basic email validation
                if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    $message.removeClass('success').addClass('error')
                        .text('Proszę podać prawidłowy adres e-mail.')
                        .show();
                    return;
                }

                // Show loading state
                var $submitBtn = $form.find('.newsletter-submit-btn');
                var originalText = $submitBtn.text();
                $submitBtn.text('Wysyłanie...').prop('disabled', true);

                // TODO: Integrate with your newsletter service
                // For now, just show success message
                setTimeout(function() {
                    $message.removeClass('error').addClass('success')
                        .html('✓ Dziękujemy! Kod rabatowy został wysłany na adres: <strong>' + email + '</strong>')
                        .show();

                    $form[0].reset();
                    $submitBtn.text(originalText).prop('disabled', false);

                    // Close modal after 3 seconds
                    setTimeout(function() {
                        closeModal();
                        $message.hide();
                    }, 3000);
                }, 1000);
            });
        });
    </script>
<?php
}
add_action('woocommerce_after_add_to_cart_form', 'jetlagz_display_newsletter_discount', 16);

/**
 * Display product videos slider
 */
function jetlagz_display_product_videos()
{
    if (!is_product()) {
        return;
    }

    global $product;
    $product_id = $product->get_id();

    // Pobierz repeater video/obrazków z ACF
    $media = get_field('videos', $product_id);

    if (!$media || !is_array($media)) {
        return;
    }

    // Get first word from product title
    $product_title = get_the_title($product_id);
    $first_word = strtok($product_title, ' ');
?>
    <div class="product-videos-section">
        <h2 class="text-xl pb-1 tracking-tight">Dziewczyny w <?php echo esc_html($first_word); ?></h2>
        <div class="swiper product-videos-swiper">
            <div class="swiper-wrapper">
                <?php foreach ($media as $item): ?>
                    <div class="swiper-slide">
                        <div class="media-wrapper">
                            <?php
                            // Pobierz plik media i rozmiar
                            $media_file = $item['media_file'];
                            $size_label = $item['size_label'];

                            if (!$media_file) continue;

                            // Sprawdź czy to video czy obrazek
                            $mime_type = $media_file['mime_type'];
                            $is_video = strpos($mime_type, 'video') !== false;

                            if ($is_video): ?>
                                <div class="custom-video-container">
                                    <video
                                        class="custom-video"
                                        preload="metadata"
                                        muted>
                                        <source src="<?php echo esc_url($media_file['url']); ?>#t=0.1" type="<?php echo esc_attr($mime_type); ?>">
                                        Twoja przeglądarka nie obsługuje odtwarzania wideo.
                                    </video>
                                    <button class="video-play-button" aria-label="Play/Pause">
                                        <svg class="play-icon" width="48" height="48" viewBox="0 0 48 48" fill="none">
                                            <circle cx="24" cy="24" r="24" fill="rgba(255,255,255,0.5)" />
                                            <path d="M18 14v20l16-10z" fill="#fff" />
                                        </svg>
                                        <svg class="pause-icon" width="48" height="48" viewBox="0 0 48 48" fill="none" style="display: none;">
                                            <circle cx="24" cy="24" r="24" fill="rgba(255,255,255,0.5)" />
                                            <rect x="16" y="14" width="5" height="20" fill="#fff" />
                                            <rect x="27" y="14" width="5" height="20" fill="#fff" />
                                        </svg>
                                    </button>
                                    <?php if ($size_label): ?>
                                        <div class="media-size-badge"><?php echo esc_html($size_label); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo esc_url($media_file['url']); ?>" alt="<?php echo esc_attr($media_file['alt']); ?>">
                                <?php if ($size_label): ?>
                                    <div class="media-size-badge"><?php echo esc_html($size_label); ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Navigation -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            console.log('Product videos script loaded');
            console.log('Swiper available:', typeof Swiper !== 'undefined');
            console.log('Swiper element:', $('.product-videos-swiper').length);

            if (typeof Swiper !== 'undefined') {
                var swiper = new Swiper('.product-videos-swiper', {
                    slidesPerView: 1,
                    slidesPerGroup: 1,
                    spaceBetween: 20,
                    watchSlidesProgress: true,
                    navigation: {
                        nextEl: '.product-videos-swiper .swiper-button-next',
                        prevEl: '.product-videos-swiper .swiper-button-prev',
                    },
                    breakpoints: {
                        640: {
                            slidesPerView: 2,
                            slidesPerGroup: 2,
                        },
                        1024: {
                            slidesPerView: 3,
                            slidesPerGroup: 3,
                        }
                    }
                });
                console.log('Swiper initialized:', swiper);
            } else {
                console.error('Swiper not loaded!');
            }

            // Custom video play/pause controls
            $('.video-play-button').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $container = $button.closest('.custom-video-container');
                var video = $container.find('video')[0];
                var $playIcon = $button.find('.play-icon');
                var $pauseIcon = $button.find('.pause-icon');

                if (video.paused) {
                    video.play();
                    $playIcon.hide();
                    $pauseIcon.show();
                    $button.addClass('playing');
                } else {
                    video.pause();
                    $playIcon.show();
                    $pauseIcon.hide();
                    $button.removeClass('playing');
                }
            });

            // Hide button when video ends
            $('.custom-video').on('ended', function() {
                var $container = $(this).closest('.custom-video-container');
                var $button = $container.find('.video-play-button');
                var $playIcon = $button.find('.play-icon');
                var $pauseIcon = $button.find('.pause-icon');

                $playIcon.show();
                $pauseIcon.hide();
                $button.removeClass('playing');
            });
        });
    </script>
<?php
}
add_action('woocommerce_after_add_to_cart_form', 'jetlagz_display_product_videos', 20);


/**
 * Replace Additional Information tab with custom shipping content
 */
function jetlagz_custom_product_tabs($tabs)
{
    // Usuń zakładkę "Informacje dodatkowe"
    unset($tabs['additional_information']);

    // Pobierz treść z ACF
    $shipping_content = get_field('product_shipping', 'option');

    if ($shipping_content) {
        // Dodaj nową zakładkę "Wysyłka"
        $tabs['shipping'] = array(
            'title'    => __('Wysyłka i zwroty', 'jetlagz-theme'),
            'priority' => 20,
            'callback' => 'jetlagz_shipping_tab_content'
        );
    }

    return $tabs;
}
add_filter('woocommerce_product_tabs', 'jetlagz_custom_product_tabs', 98);

/**
 * Display shipping tab content
 */
function jetlagz_shipping_tab_content()
{
    $shipping_content = get_field('product_shipping', 'option');
    if ($shipping_content) {
        echo '<div class="shipping-content">';
        echo wp_kses_post($shipping_content);
        echo '</div>';
    }
}

/**
 * Remove default related products output
 */
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

/**
 * Add crosssell products after videos section (in summary)
 */
function jetlagz_crosssell_in_summary()
{
    global $product;

    if (!$product) {
        return;
    }

    $crosssell_ids = $product->get_cross_sell_ids();

    if (empty($crosssell_ids)) {
        return;
    }

    // Limit to 4 products
    $crosssell_ids = array_slice($crosssell_ids, 0, 4);
?>
    <div class="crosssell-products-section">
        <h3 class="crosssell-title">Dobierz do kompletu</h3>
        <div class="crosssell-products-list">
            <?php foreach ($crosssell_ids as $crosssell_id):
                $crosssell_product = wc_get_product($crosssell_id);
                if (!$crosssell_product || !$crosssell_product->is_visible()) {
                    continue;
                }

                $image = wp_get_attachment_image_src(get_post_thumbnail_id($crosssell_id), 'thumbnail');
                $image_url = $image ? $image[0] : wc_placeholder_img_src();
            ?>
                <div class="crosssell-product-item" data-product-id="<?php echo esc_attr($crosssell_id); ?>">
                    <a href="<?php echo esc_url(get_permalink($crosssell_id)); ?>" class="crosssell-product-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($crosssell_product->get_name()); ?>">
                    </a>
                    <div class="crosssell-product-info">
                        <a href="<?php echo esc_url(get_permalink($crosssell_id)); ?>" class="crosssell-product-title">
                            <?php echo esc_html($crosssell_product->get_name()); ?>
                        </a>
                        <div class="crosssell-product-price">
                            <?php echo $crosssell_product->get_price_html(); ?>
                        </div>
                    </div>
                    <button type="button"
                        class="crosssell-add-to-cart"
                        data-product-id="<?php echo esc_attr($crosssell_id); ?>"
                        data-product-name="<?php echo esc_attr($crosssell_product->get_name()); ?>">
                        Wybierz
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            // Check which products are already in cart on page load
            function updateButtonStates() {
                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'jetlagz_get_cart_product_ids'
                    },
                    success: function(response) {
                        if (response.success && response.data.product_ids) {
                            var cartProductIds = response.data.product_ids;

                            $('.crosssell-add-to-cart').each(function() {
                                var $btn = $(this);
                                var productId = $btn.data('product-id').toString();

                                if (cartProductIds.indexOf(productId) !== -1) {
                                    $btn.addClass('added').text('✓ Dodano');
                                }
                            });
                        }
                    }
                });
            }

            updateButtonStates();

            $('.crosssell-add-to-cart').on('click', function(e) {
                e.preventDefault();
                var $button = $(this);
                var productId = $button.data('product-id');

                if ($button.hasClass('loading')) {
                    return;
                }

                // If already added, remove from cart
                if ($button.hasClass('added')) {
                    $button.addClass('loading').text('...');

                    $.ajax({
                        url: wc_add_to_cart_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'jetlagz_remove_crosssell_from_cart',
                            product_id: productId,
                            nonce: '<?php echo wp_create_nonce('jetlagz_remove_crosssell'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $button.removeClass('loading added').text('Wybierz');

                                // Update cart fragments
                                if (response.data.fragments) {
                                    $.each(response.data.fragments, function(key, value) {
                                        $(key).replaceWith(value);
                                    });
                                }
                            } else {
                                alert('Nie udało się usunąć produktu.');
                                $button.removeClass('loading');
                            }
                        },
                        error: function() {
                            alert('Wystąpił błąd. Spróbuj ponownie.');
                            $button.removeClass('loading');
                        }
                    });
                    return;
                }

                // Add to cart
                $button.addClass('loading').text('...');

                $.post(wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), {
                    product_id: productId,
                    quantity: 1
                }, function(response) {
                    if (response.error && response.product_url) {
                        window.location = response.product_url;
                        return;
                    }

                    $button.removeClass('loading').addClass('added').text('✓ Dodano');

                    // Update cart fragments silently (no side cart, no "View cart" button)
                    if (response.fragments) {
                        $.each(response.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                }).fail(function() {
                    alert('Wystąpił błąd. Spróbuj ponownie.');
                    $button.removeClass('loading').text('Wybierz');
                });
            });
        });
    </script>
<?php
}
// Add after videos section (priority 21, videos are at 20)
add_action('woocommerce_after_add_to_cart_form', 'jetlagz_crosssell_in_summary', 21);

/**
 * Display product description after crosssell
 */
function jetlagz_display_product_description()
{
    global $product;

    if (!$product) {
        return;
    }

    $description = $product->get_description();

    // Get ACF repeater from options page
    $has_repeater = have_rows('product_description', 'option');

    if (empty($description) && !$has_repeater) {
        return;
    }
?>
    <div class="product-description-section">
        <h2 class="product-description-title">Opis produktu</h2>

        <div class="product-description-accordion">
            <?php if (!empty($description)): ?>
                <div class="accordion-item">
                    <button class="accordion-header" type="button">
                        <span class="accordion-title">Opis</span>
                        <svg class="accordion-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div class="accordion-content">
                        <div class="accordion-content-inner">
                            <?php echo apply_filters('the_content', $description); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Display ACF repeater items from options page
            if (have_rows('product_description', 'option')):
                while (have_rows('product_description', 'option')): the_row();
                    $title = get_sub_field('title');
                    $description_part = get_sub_field('description');

                    if (!empty($title) && !empty($description_part)):
            ?>
                        <div class="accordion-item">
                            <button class="accordion-header" type="button">
                                <span class="accordion-title"><?php echo esc_html($title); ?></span>
                                <svg class="accordion-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5 7.5L10 12.5L15 7.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>
                            <div class="accordion-content">
                                <div class="accordion-content-inner">
                                    <?php echo wp_kses_post($description_part); ?>
                                </div>
                            </div>
                        </div>
            <?php
                    endif;
                endwhile;
            endif;
            ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.product-description-accordion .accordion-header').on('click', function() {
                var $item = $(this).closest('.accordion-item');
                var $content = $item.find('.accordion-content');

                // Toggle current item
                $item.toggleClass('active');

                if ($item.hasClass('active')) {
                    $content.slideDown(300);
                } else {
                    $content.slideUp(300);
                }
            });
        });
    </script>
<?php
}
add_action('woocommerce_after_add_to_cart_form', 'jetlagz_display_product_description', 22);

/**
 * Display product reviews as standalone section outside summary
 * Show reviews even if comment form is disabled
 */
function jetlagz_display_product_reviews()
{
    global $product;

    if (!$product) {
        return;
    }

    // Get review data
    $review_count = $product->get_review_count();
    $average_rating = $product->get_average_rating();

    // Get rating breakdown
    $rating_counts = array(5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0);

    // Get comments - WooCommerce reviews are type "comment", not "review"
    // Only filter by approved status and check if they have rating meta
    $comments = get_comments(array(
        'post_id' => $product->get_id(),
        'status' => 'approve',
        'parent' => 0, // Only top-level comments (not replies)
    ));

    // Filter out comments without rating (keep only actual reviews)
    $comments = array_filter($comments, function ($comment) {
        $rating = intval(get_comment_meta($comment->comment_ID, 'rating', true));
        return $rating > 0;
    });

    foreach ($comments as $comment) {
        $rating = intval(get_comment_meta($comment->comment_ID, 'rating', true));
        if ($rating >= 1 && $rating <= 5) {
            $rating_counts[$rating]++;
        }
    }

    // Use actual count of comments with ratings for percentage calculation
    $total_reviews_with_ratings = count($comments);

?>
    <div class="ratings-reviews-section" id="product-reviews" role="region" aria-label="Product Reviews">
        <div class="reviews-container">
            <!-- Left Column - Rating Summary -->
            <div class="reviews-summary md:flex gap-3 md:w-1/2" aria-label="Rating Summary">
                <div class="overall-rating">
                    <div class="rating-score flex gap-1">
                        <span class="score-number" aria-label="Average rating"><?php echo number_format($average_rating, 1); ?></span>
                        <span class="score-max">/ 5</span>
                    </div>
                    <div class="rating-subtitle">
                        <?php
                        // Poprawna odmiana słowa "opinia"
                        if ($review_count === 1) {
                            $opinion_word = 'Opinia';
                        } elseif ($review_count % 10 >= 2 && $review_count % 10 <= 4 && ($review_count % 100 < 10 || $review_count % 100 >= 20)) {
                            $opinion_word = 'Opinie';
                        } else {
                            $opinion_word = 'Opinii';
                        }
                        ?>
                        (<?php echo $review_count; ?> <?php echo $opinion_word; ?>)
                    </div>
                </div>

                <div class="rating-breakdown" role="group" aria-label="Rating breakdown">
                    <?php for ($i = 5; $i >= 1; $i--) :
                        $count = $rating_counts[$i];
                        $percentage = $total_reviews_with_ratings > 0 ? ($count / $total_reviews_with_ratings) * 100 : 0;
                    ?>
                        <div class="rating-bar-item"
                            data-rating="<?php echo $i; ?>"
                            role="button"
                            tabindex="0"
                            aria-label="Filter by <?php echo $i; ?> star reviews. <?php echo $count; ?> reviews."
                            aria-pressed="false">
                            <div class="rating-stars" aria-hidden="true">
                                <span class="star filled">★</span>
                                <span class="rating-number"><?php echo $i; ?></span>
                            </div>
                            <div class="rating-bar" role="progressbar" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="rating-bar-fill" style="width: <?php echo $percentage . '%'; ?>"></div>
                            </div>
                            <div class="rating-count"><?php echo $count; ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Right Column - Reviews List -->
            <div class="reviews-list-container sm:w-1/2 overflow-hidden" aria-label="Customer Reviews">
                <?php if ($review_count > 0) : ?>
                    <div class="reviews-controls">
                        <div class="reviews-filter">
                            <label for="reviews-sort">Sortuj według:</label>
                            <select id="reviews-sort" class="reviews-sort-select" aria-label="Sort reviews">
                                <option value="newest">Najnowsze</option>
                                <option value="oldest">Najstarsze</option>
                                <option value="highest">Najwyższa ocena</option>
                                <option value="lowest">Najniższa ocena</option>
                            </select>
                        </div>
                    </div>

                    <!-- Swiper Reviews Slider -->
                    <div class="swiper reviews-swiper" data-product-id="<?php echo $product->get_id(); ?>">
                        <div class="swiper-wrapper">
                            <?php
                            foreach ($comments as $comment) :
                            ?>
                                <div class="swiper-slide">
                                    <?php jetlagz_display_single_review($comment); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Navigation Arrows -->
                        <div class="swiper-button-prev reviews-slider-prev"></div>
                        <div class="swiper-button-next reviews-slider-next"></div>

                        <!-- Pagination -->
                        <div class="swiper-pagination reviews-slider-pagination"></div>
                    </div>

                <?php else : ?>
                    <div class="no-reviews">
                        <p>Brak opinii. Bądź pierwszy! Kup i oceń ten produkt!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
}

/**
 * Display single review card
 */
function jetlagz_display_single_review($comment)
{
    $rating = intval(get_comment_meta($comment->comment_ID, 'rating', true));
    $author = get_comment_author($comment);
    $date = get_comment_date('j M Y', $comment);
    $content = get_comment_text($comment);
    $avatar_url = get_avatar_url($comment, array('size' => 48));

    // Get review images if any
    $review_images = get_comment_meta($comment->comment_ID, 'review_images', true);
    if (!is_array($review_images)) {
        $review_images = array();
    }
?>
    <article class="review-card"
        data-rating="<?php echo $rating; ?>"
        data-date="<?php echo get_comment_date('U', $comment); ?>"
        role="listitem">
        <div class="review-header">
            <div class="review-author-info">
                <img src="<?php echo esc_url($avatar_url); ?>"
                    alt="<?php echo esc_attr($author); ?>"
                    class="review-avatar"
                    loading="lazy">
                <div class="review-author-details">
                    <h4 class="review-author-name"><?php echo esc_html($author); ?></h4>
                    <div class="review-rating"
                        role="img"
                        aria-label="Rated <?php echo $rating; ?> out of 5 stars">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <span class="star <?php echo $i <= $rating ? 'filled' : 'empty'; ?>" aria-hidden="true">★</span>
                        <?php endfor; ?>
                        <span class="sr-only"><?php echo $rating; ?> out of 5 stars</span>
                    </div>
                </div>
            </div>
            <time class="review-date" datetime="<?php echo get_comment_date('c', $comment); ?>">
                <?php echo esc_html($date); ?>
            </time>
        </div>
        <div class="review-content <?php echo strlen($content) > 250 ? 'has-read-more' : ''; ?>">
            <p><?php echo esc_html($content); ?></p>
            <?php if (strlen($content) > 250) : ?>
                <a href="#" class="read-more-toggle" aria-expanded="false">Read more</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($review_images)) : ?>
            <div class="review-images">
                <?php foreach ($review_images as $image_id) :
                    $image_url = wp_get_attachment_image_url($image_id, 'medium');
                    $image_full_url = wp_get_attachment_image_url($image_id, 'full');
                    if ($image_url) :
                ?>
                        <a href="<?php echo esc_url($image_full_url); ?>"
                            class="review-image-link"
                            data-lightbox="review-<?php echo $comment->comment_ID; ?>"
                            target="_blank">
                            <img src="<?php echo esc_url($image_url); ?>"
                                alt="Review image"
                                class="review-image"
                                loading="lazy">
                        </a>
                <?php
                    endif;
                endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
<?php
}

add_action('woocommerce_after_single_product_summary', 'jetlagz_display_product_reviews', 15);

/**
 * Remove default WooCommerce tabs (description, reviews, additional info)
 * We have custom sections for these
 */
remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);

/**
 * Related products removed - using only cross-sells in summary
 */

/**
 * Display Product FAQ section
 */
function jetlagz_display_product_faq()
{
    get_template_part('template-parts/product-faq');
}
add_action('woocommerce_after_single_product_summary', 'jetlagz_display_product_faq', 25);

/**
 * Disable product gallery slider on desktop (1024px+)
 */
function jetlagz_disable_product_gallery_slider()
{
    if (!is_product()) {
        return;
    }
?>
    <script>
        // Block FlexSlider on desktop - completely disable it
        (function() {
            if (window.innerWidth >= 1024) {
                // Override jQuery.fn.flexslider to make it do nothing
                if (typeof jQuery !== 'undefined') {
                    jQuery.fn.flexslider = function() {
                        console.log('FlexSlider blocked on desktop');
                        return this; // Return jQuery object for chaining
                    };
                }

                // Also block WooCommerce params just in case
                window.wc_single_product_params = window.wc_single_product_params || {};
                window.wc_single_product_params.flexslider_enabled = false;
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {

            // Mobile: Let WooCommerce handle gallery normally
            if (window.innerWidth < 640) {}

            if (false && window.innerWidth < 640) {
                setTimeout(function() {

                    var wrapper = document.querySelector('.woocommerce-product-gallery__wrapper');

                    var images = document.querySelectorAll('.woocommerce-product-gallery__image');

                    if (!wrapper) {
                        console.error('❌ Wrapper not found!');
                        return;
                    }

                    if (images.length === 0) {
                        console.error('❌ No images found!');
                        return;
                    }

                    if (images.length === 1) {
                        console.warn('⚠️ Only 1 image, slider not needed');
                        return;
                    }


                    if (wrapper && images.length > 1) {
                        var currentIndex = 0;

                        // Hide all images except first and set proper z-index
                        images.forEach(function(img, index) {
                            img.style.display = index === 0 ? 'block' : 'none';
                            img.style.position = 'relative';
                            img.style.zIndex = '1';
                        });


                        // Create navigation buttons
                        var prevBtn = document.createElement('button');
                        prevBtn.className = 'mobile-slider-prev';
                        prevBtn.innerHTML = '‹';
                        prevBtn.style.cssText = 'position:absolute;left:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);color:white;border:none;width:50px;height:50px;border-radius:50%;font-size:32px;line-height:1;cursor:pointer;z-index:10000 !important;pointer-events:auto !important;';

                        var nextBtn = document.createElement('button');
                        nextBtn.className = 'mobile-slider-next';
                        nextBtn.innerHTML = '›';
                        nextBtn.style.cssText = 'position:absolute;right:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,0.7);color:white;border:none;width:50px;height:50px;border-radius:50%;font-size:32px;line-height:1;cursor:pointer;z-index:10000 !important;pointer-events:auto !important;';
                        var dotsContainer = document.createElement('div');
                        dotsContainer.className = 'mobile-slider-dots';
                        dotsContainer.style.cssText = 'position:absolute;bottom:15px;left:0;right:0;text-align:center;z-index:100;';

                        images.forEach(function(img, index) {
                            var dot = document.createElement('span');
                            dot.style.cssText = 'display:inline-block;width:10px;height:10px;border-radius:50%;background:rgba(255,255,255,0.6);border:1px solid rgba(0,0,0,0.3);margin:0 4px;cursor:pointer;';
                            if (index === 0) {
                                dot.style.background = 'rgba(255,255,255,1)';
                                dot.style.borderColor = 'rgba(0,0,0,0.5)';
                            }
                            dot.addEventListener('click', function() {
                                goToSlide(index);
                            });
                            dotsContainer.appendChild(dot);
                        });

                        function goToSlide(index) {
                            images[currentIndex].style.display = 'none';
                            currentIndex = index;
                            images[currentIndex].style.display = 'block';

                            // Update dots
                            var dots = dotsContainer.querySelectorAll('span');
                            dots.forEach(function(dot, i) {
                                if (i === currentIndex) {
                                    dot.style.background = 'rgba(255,255,255,1)';
                                    dot.style.borderColor = 'rgba(0,0,0,0.5)';
                                } else {
                                    dot.style.background = 'rgba(255,255,255,0.6)';
                                    dot.style.borderColor = 'rgba(0,0,0,0.3)';
                                }
                            });

                        }

                        prevBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            var newIndex = currentIndex === 0 ? images.length - 1 : currentIndex - 1;
                            goToSlide(newIndex);
                        });

                        prevBtn.addEventListener('touchstart', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            var newIndex = currentIndex === 0 ? images.length - 1 : currentIndex - 1;
                            goToSlide(newIndex);
                        });

                        nextBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            var newIndex = currentIndex === images.length - 1 ? 0 : currentIndex + 1;
                            goToSlide(newIndex);
                        });

                        nextBtn.addEventListener('touchstart', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            var newIndex = currentIndex === images.length - 1 ? 0 : currentIndex + 1;
                            goToSlide(newIndex);
                        });
                        wrapper.style.position = 'relative';
                        wrapper.style.zIndex = '1';

                        // Force button z-index to be very high
                        prevBtn.style.zIndex = '9999';
                        nextBtn.style.zIndex = '9999';
                        dotsContainer.style.zIndex = '9999';

                        wrapper.appendChild(prevBtn);
                        wrapper.appendChild(nextBtn);

                        wrapper.appendChild(dotsContainer);

                    } else {
                        console.error('❌ Condition failed: wrapper or images check');
                    }
                }, 500);
            }

            if (window.innerWidth >= 1024) {

                // Disable FlexSlider completely
                jQuery(document).on('wc-product-gallery-before-init', function(e, gallery) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                });

                // Destroy FlexSlider if already initialized
                if (jQuery('.woocommerce-product-gallery').data('flexslider')) {
                    jQuery('.woocommerce-product-gallery').flexslider('destroy');
                }

                // Wait for WooCommerce gallery to initialize
                setTimeout(function() {
                    // Fix flex-viewport height immediately
                    var flexViewport = document.querySelector('.flex-viewport');
                    if (flexViewport) {
                        flexViewport.style.cssText = 'overflow: visible !important; height: auto !important; max-height: none !important; position: relative !important;';
                    }

                    var images = document.querySelectorAll('.woocommerce-product-gallery__image');

                    // Make all images visible in grid
                    images.forEach(function(img) {
                        img.style.display = 'block';
                        img.style.opacity = '1';
                        img.style.position = 'relative';
                    });

                    var trigger = document.querySelector('.woocommerce-product-gallery__trigger');

                    // Enable click on all images for lightbox
                    var imageLinks = document.querySelectorAll('.woocommerce-product-gallery__image > a');

                    imageLinks.forEach(function(link, index) {
                        link.style.cursor = 'zoom-in';

                        // Add click handler to the image itself (not the link)
                        var img = link.querySelector('img');

                        if (img) {
                            img.style.cursor = 'zoom-in';

                            img.addEventListener('click', function(e) {
                                e.preventDefault();
                                e.stopPropagation();

                                var allLinks = Array.from(document.querySelectorAll('.woocommerce-product-gallery__image > a'));
                                var parentLink = this.closest('a');
                                var clickedIndex = allLinks.indexOf(parentLink);

                                // Open PhotoSwipe directly on the clicked image
                                openPhotoSwipeGallery(clickedIndex);
                            }, true); // Use capture phase

                        }
                    });

                    // Intercept PhotoSwipe initialization to store instance (desktop only)
                    if (window.innerWidth >= 1024) {
                        var originalPhotoSwipe = window.PhotoSwipe;
                        if (originalPhotoSwipe) {
                            window.PhotoSwipe = function(pswpElement, PhotoSwipeUI_Class, items, options) {
                                var instance = new originalPhotoSwipe(pswpElement, PhotoSwipeUI_Class, items, options);
                                window.pswpInstance = instance;
                                return instance;
                            };
                            // Copy over static properties
                            for (var prop in originalPhotoSwipe) {
                                if (originalPhotoSwipe.hasOwnProperty(prop)) {
                                    window.PhotoSwipe[prop] = originalPhotoSwipe[prop];
                                }
                            }
                        }
                    }

                    // Function to open PhotoSwipe gallery at specific index
                    function openPhotoSwipeGallery(startIndex) {

                        // Build items array from gallery images
                        var items = [];
                        var galleryImages = document.querySelectorAll('.woocommerce-product-gallery__image > a');

                        galleryImages.forEach(function(link) {
                            var img = link.querySelector('img');
                            items.push({
                                src: link.getAttribute('href'),
                                w: img.getAttribute('data-large_image_width') || 1600,
                                h: img.getAttribute('data-large_image_height') || 2000,
                                title: img.getAttribute('alt') || ''
                            });
                        });


                        // Get PhotoSwipe element
                        var pswpElement = document.querySelector('.pswp');

                        if (pswpElement && typeof PhotoSwipe !== 'undefined' && typeof PhotoSwipeUI_Default !== 'undefined') {
                            var options = {
                                index: startIndex,
                                shareEl: false,
                                closeOnScroll: false,
                                history: false
                            };

                            var gallery = new PhotoSwipe(pswpElement, PhotoSwipeUI_Default, items, options);
                            gallery.init();
                        } else {
                            console.warn('❌ PhotoSwipe not available');
                        }
                    }

                    // Make function globally available
                    window.openPhotoSwipeGallery = openPhotoSwipeGallery;

                    // Alternative: Add overlay div on first image to catch clicks
                    var firstImage = document.querySelector('.woocommerce-product-gallery__image:first-child');
                    if (firstImage) {
                        var overlay = document.createElement('div');
                        overlay.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;cursor:zoom-in;z-index:5;';
                        overlay.addEventListener('click', function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            openPhotoSwipeGallery(0);
                        });
                        firstImage.style.position = 'relative';
                        firstImage.appendChild(overlay);
                    }

                    // Handle variation changes - fix FlexSlider interference
                    jQuery('form.variations_form').on('found_variation', function(event, variation) {
                        // Fix immediately (no delay)
                        function forceGalleryFix() {
                            var flexViewport = document.querySelector('.flex-viewport');
                            if (flexViewport) {
                                flexViewport.removeAttribute('style');
                                flexViewport.style.cssText = 'overflow: visible !important; height: auto !important; max-height: none !important; position: relative !important; transition: none !important;';
                            }

                            var wrapper = document.querySelector('.woocommerce-product-gallery__wrapper');
                            if (wrapper) {
                                wrapper.removeAttribute('style');
                            }
                        }

                        // Fix immediately
                        forceGalleryFix();

                        // Keep fixing for 500ms to override FlexSlider's async changes
                        var fixCount = 0;
                        var fixInterval = setInterval(function() {
                            forceGalleryFix();
                            fixCount++;
                            if (fixCount >= 10) { // 10 * 50ms = 500ms
                                clearInterval(fixInterval);
                            }
                        }, 50);
                    });

                    // Handle when variation is reset
                    jQuery('form.variations_form').on('reset_data', function() {
                        setTimeout(function() {
                            var images = document.querySelectorAll('.woocommerce-product-gallery__image');
                            images.forEach(function(img) {
                                img.style.display = 'block';
                                img.style.opacity = '1';
                                img.style.visibility = 'visible';
                            });
                        }, 100);
                    });
                }, 500);
            }
        });
    </script>
<?php
}
add_action('wp_head', 'jetlagz_disable_product_gallery_slider', 1);

/**
 * Prevent layout shift - reserve space for gallery in head
 */
function jetlagz_product_gallery_reserve_space()
{
    if (!is_product()) {
        return;
    }
?>
    <style>
        @media (min-width: 1024px) {
            .woocommerce-product-gallery {
                min-height: 1200px !important;
            }

            .woocommerce-product-gallery__wrapper {
                min-height: 1200px !important;
            }

            .woocommerce-product-gallery__image {
                min-height: 200px !important;
                background: #f9f9f9;
            }
        }
    </style>
<?php
}
add_action('wp_head', 'jetlagz_product_gallery_reserve_space', 1);

/**
 * Sticky sidebar effect for product page (desktop 1024px+)
 * Shorter div sticks to top, unsticks when bottom edges align
 */
function jetlagz_sticky_product_sidebar()
{
    if (!is_product()) {
        return;
    }
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.innerWidth < 1024) {
                return;
            }

            // Declare variables in outer scope for resize handler
            var gallery = null;
            var summary = null;

            // Use requestAnimationFrame to avoid blocking scroll
            requestAnimationFrame(function() {
                gallery = document.querySelector('.woocommerce-product-gallery');
                summary = document.querySelector('.summary.entry-summary');

                if (!gallery || !summary) {
                    return;
                }

                // Create wrapper div for gallery + summary to enable flexbox
                var container = gallery.parentElement;
                var wrapper = document.createElement('div');
                wrapper.className = 'product-main-wrapper';
                wrapper.style.cssText = 'display:flex;align-items:flex-start;gap:2%;flex-wrap:nowrap;';

                // Batch DOM operations
                container.insertBefore(wrapper, gallery);
                wrapper.appendChild(gallery);
                wrapper.appendChild(summary);

                // Batch style changes
                gallery.style.cssText += 'width:46%;flex-shrink:0;';
                summary.style.cssText += 'width:42%;flex-shrink:0;position:sticky;top:20px;align-self:flex-start;';

                // Fix overflow on specific ancestors only (not all)
                requestAnimationFrame(function() {
                    var parent = wrapper.parentElement;
                    var maxDepth = 5; // Limit iterations
                    var depth = 0;

                    while (parent && parent !== document.body && depth < maxDepth) {
                        parent.style.overflow = 'visible';
                        parent = parent.parentElement;
                        depth++;
                    }
                });

                // After images load, verify sticky position
                if (document.readyState === 'complete') {
                    adjustSticky();
                } else {
                    window.addEventListener('load', adjustSticky);
                }

                function adjustSticky() {
                    requestAnimationFrame(function() {
                        var galleryHeight = gallery.offsetHeight;
                        var summaryHeight = summary.offsetHeight;

                        if (summaryHeight > galleryHeight) {
                            summary.style.position = '';
                            gallery.style.cssText += 'position:sticky;top:20px;align-self:flex-start;';
                        }
                    });
                }
            });

            // Recalculate on window resize
            var resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (!gallery || !summary) {
                        return;
                    }

                    if (window.innerWidth < 1024) {
                        // Reset on mobile
                        gallery.style.position = '';
                        gallery.style.top = '';
                        gallery.style.alignSelf = '';
                        summary.style.position = '';
                        summary.style.top = '';
                        summary.style.alignSelf = '';
                    } else {
                        // Reapply on desktop
                        var galleryHeight = gallery.offsetHeight;
                        var summaryHeight = summary.offsetHeight;

                        // Reset both first
                        gallery.style.position = '';
                        gallery.style.top = '';
                        gallery.style.alignSelf = '';
                        summary.style.position = '';
                        summary.style.top = '';
                        summary.style.alignSelf = '';

                        // Apply sticky to shorter
                        if (galleryHeight > summaryHeight) {
                            summary.style.position = 'sticky';
                            summary.style.top = '20px';
                            summary.style.alignSelf = 'flex-start';
                        } else if (summaryHeight > galleryHeight) {
                            gallery.style.position = 'sticky';
                            gallery.style.top = '20px';
                            gallery.style.alignSelf = 'flex-start';
                        }
                    }
                }, 250);
            });
        });
    </script>
<?php
}
add_action('wp_footer', 'jetlagz_sticky_product_sidebar');

/**
 * Convert variation dropdowns to button swatches
 */
function jetlagz_variation_swatches_script()
{
    if (!is_product()) {
        return;
    }
?>
    <script>
        jQuery(document).ready(function($) {
            // Reorder variation rows - size first, then color
            var $variationsTable = $('.variations_form .variations');
            var priorityOrder = ['pa_rozmiar', 'pa_size', 'pa_rozmiar', 'pa_kolor', 'pa_color'];

            var $rows = $variationsTable.find('tr');
            var orderedRows = [];
            var remainingRows = [];

            // Separate priority rows from others
            $rows.each(function() {
                var $row = $(this);
                var $select = $row.find('select');
                if ($select.length) {
                    var attrName = $select.attr('name');
                    var normalizedName = attrName ? attrName.replace('attribute_', '') : '';
                    var priorityIndex = priorityOrder.indexOf(normalizedName);

                    if (priorityIndex !== -1) {
                        orderedRows[priorityIndex] = $row;
                    } else {
                        remainingRows.push($row);
                    }
                }
            });

            // Remove empty slots and append rows in order
            orderedRows = orderedRows.filter(function(row) {
                return row !== undefined;
            });
            var allOrderedRows = orderedRows.concat(remainingRows);

            // Detach and reappend in correct order
            $variationsTable.empty();
            allOrderedRows.forEach(function($row) {
                $variationsTable.append($row);
            });

            // Convert each variation select to buttons
            $('.variations_form .variations select').each(function() {
                var $select = $(this);
                var $row = $select.closest('tr');
                var attributeName = $select.attr('name');

                // Normalize attribute name (remove 'attribute_' prefix if exists)
                var normalizedAttrName = attributeName.replace('attribute_', '');

                // Create buttons container
                var $buttonsContainer = $('<div class="variation-buttons"></div>');

                // Get all options
                $select.find('option').each(function() {
                    var $option = $(this);
                    var value = $option.val();
                    var text = $option.text();

                    // Skip empty/placeholder option
                    if (!value || value === '') {
                        return;
                    }

                    // Check if this attribute has color data
                    var hasColor = false;
                    var color = null;

                    if (typeof attributeColors !== 'undefined' && attributeColors[normalizedAttrName]) {
                        if (attributeColors[normalizedAttrName][value]) {
                            color = attributeColors[normalizedAttrName][value];
                            // Validate that it's a hex color (starts with # and has valid format) and not default black
                            if (color && /^#[0-9A-F]{6}$/i.test(color) && color.toUpperCase() !== '#000000') {
                                hasColor = true;
                            }
                        }
                    }

                    // Create button
                    var $button;
                    if (hasColor && color) {
                        // Color swatch button
                        $button = $('<button type="button" class="variation-button variation-color-button" data-value="' + value + '" title="' + text + '">' +
                            '<span class="color-swatch" style="background-color: ' + color + ';"></span>' +
                            '<span class="color-name">' + text + '</span>' +
                            '</button>');
                    } else {
                        // Regular text button
                        $button = $('<button type="button" class="variation-button" data-value="' + value + '">' + text + '</button>');
                    }

                    // Check if option is selected
                    if ($option.is(':selected')) {
                        $button.addClass('selected');
                    }

                    // Check if option is disabled
                    if ($option.is(':disabled')) {
                        $button.prop('disabled', true).addClass('disabled');
                    }

                    $buttonsContainer.append($button);
                });

                // Hide original select
                $select.hide();

                // Add buttons after select
                $select.after($buttonsContainer);

                // Handle button clicks
                $buttonsContainer.on('click', '.variation-button:not(.disabled)', function(e) {
                    e.preventDefault();
                    var $button = $(this);
                    var value = $button.data('value');

                    // Remove selected class from siblings
                    $button.siblings('.variation-button').removeClass('selected');

                    // Add selected class to clicked button
                    $button.addClass('selected');

                    // Update hidden select
                    $select.val(value).trigger('change');
                });
            });

            // Update buttons when variation form changes (e.g., when options become available/unavailable)
            $('.variations_form').on('woocommerce_update_variation_values', function() {
                $('.variations_form .variations select').each(function() {
                    var $select = $(this);
                    var $buttonsContainer = $select.next('.variation-buttons');

                    if ($buttonsContainer.length) {
                        // Update button states based on select options
                        $buttonsContainer.find('.variation-button').each(function() {
                            var $button = $(this);
                            var value = $button.data('value');
                            var $option = $select.find('option[value="' + value + '"]');

                            if ($option.is(':disabled')) {
                                $button.prop('disabled', true).addClass('disabled');
                            } else {
                                $button.prop('disabled', false).removeClass('disabled');
                            }
                        });
                    }
                });
            });
        });
    </script>
<?php
}
add_action('wp_footer', 'jetlagz_variation_swatches_script');

/**
 * Add color field to product attribute terms - EDIT form
 */
function jetlagz_add_attribute_color_field_edit($term)
{
    $color = get_term_meta($term->term_id, 'attribute_color', true);
?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="attribute_color">Kolor</label>
        </th>
        <td>
            <input type="color" name="attribute_color" id="attribute_color" value="<?php echo esc_attr($color ? $color : '#000000'); ?>" />
            <p class="description">Wybierz kolor dla tego atrybutu (zostanie wyświetlony jako kolorowy przycisk na stronie produktu)</p>
        </td>
    </tr>
<?php
}

/**
 * Add color field to product attribute terms - ADD form
 */
function jetlagz_add_attribute_color_field_add()
{
?>
    <div class="form-field">
        <label for="attribute_color">Kolor</label>
        <input type="color" name="attribute_color" id="attribute_color" value="#000000" />
        <p class="description">Wybierz kolor dla tego atrybutu (zostanie wyświetlony jako kolorowy przycisk na stronie produktu)</p>
    </div>
    <?php
}

// Add color field to all attribute taxonomies
$attribute_taxonomies = wc_get_attribute_taxonomies();
if ($attribute_taxonomies) {
    foreach ($attribute_taxonomies as $tax) {
        add_action('pa_' . $tax->attribute_name . '_edit_form_fields', 'jetlagz_add_attribute_color_field_edit', 10, 1);
        add_action('pa_' . $tax->attribute_name . '_add_form_fields', 'jetlagz_add_attribute_color_field_add', 10);
    }
}
/**
 * Save color field for product attribute terms
 */
function jetlagz_save_attribute_color_field($term_id)
{
    if (isset($_POST['attribute_color'])) {
        update_term_meta($term_id, 'attribute_color', sanitize_hex_color($_POST['attribute_color']));
    }
}

// Save color field for all attribute taxonomies
if ($attribute_taxonomies) {
    foreach ($attribute_taxonomies as $tax) {
        add_action('edited_pa_' . $tax->attribute_name, 'jetlagz_save_attribute_color_field', 10, 1);
        add_action('create_pa_' . $tax->attribute_name, 'jetlagz_save_attribute_color_field', 10, 1);
    }
}

/**
 * Add color data to variation buttons via data attribute
 */
function jetlagz_add_color_data_to_variations()
{
    if (!is_product()) {
        return;
    }

    global $product;

    if (!$product || !$product->is_type('variable')) {
        return;
    }

    $attributes = $product->get_variation_attributes();
    $color_data = array();

    foreach ($attributes as $attribute_name => $options) {
        // Check if this is a taxonomy attribute
        if (taxonomy_exists($attribute_name)) {
            foreach ($options as $option) {
                $term = get_term_by('slug', $option, $attribute_name);
                if ($term) {
                    $color = get_term_meta($term->term_id, 'attribute_color', true);
                    // Only add if it's a valid hex color AND not the default black color
                    // Ignore #000000 and empty values
                    if ($color && preg_match('/^#[0-9A-F]{6}$/i', $color) && strtoupper($color) !== '#000000') {
                        $color_data[$attribute_name][$option] = $color;
                    }
                }
            }
        }
    }

    if (!empty($color_data)) {
    ?>
        <script>
            var attributeColors = <?php echo json_encode($color_data); ?>;
        </script>
    <?php
    }
}
add_action('woocommerce_before_single_product', 'jetlagz_add_color_data_to_variations');

/**
 * Add size guide link below variations table
 */
function jetlagz_add_size_guide_link()
{
    global $product;

    if (!$product || !$product->is_type('variable')) {
        return;
    }

    // Get size guide content from ACF options
    $size_guide_content = get_field('size_guide_content', 'option');

    if (!$size_guide_content) {
        return;
    }
    ?>
    <div class="size-guide-wrapper">
        <a href="#" class="size-guide-toggle">Jak dobrać rozmiar?</a>
        <div class="size-guide-content" style="display: none;">
            <?php echo wp_kses_post($size_guide_content); ?>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('.size-guide-toggle').on('click', function(e) {
                e.preventDefault();
                var $content = $(this).next('.size-guide-content');
                $content.slideToggle(300);
                $(this).toggleClass('active');
            });
        });
    </script>
<?php
}
add_action('woocommerce_before_add_to_cart_button', 'jetlagz_add_size_guide_link', 30);

/**
 * Add shipping countdown timer (shows until 15:00, resets at midnight)
 */
function jetlagz_shipping_countdown_timer()
{
    if (!is_product()) {
        return;
    }
?>
    <div class="shipping-countdown" id="shipping-countdown" style="display: none;">
        <p class="countdown-text">
            Kup do 15:00 a paczkę nadamy jeszcze dziś. Pozostało: <span id="countdown-time" class="countdown-time"></span>
        </p>
    </div>

    <script>
        jQuery(document).ready(function($) {
            function updateCountdown() {
                var now = new Date();
                var currentHour = now.getHours();
                var currentMinute = now.getMinutes();
                var currentSecond = now.getSeconds();

                // Jeśli jest po 15:00, ukryj countdown
                if (currentHour >= 15) {
                    $('#shipping-countdown').hide();
                    return;
                }

                // Oblicz czas do 15:00
                var deadline = new Date();
                deadline.setHours(15, 0, 0, 0);

                var timeLeft = deadline - now;

                if (timeLeft <= 0) {
                    $('#shipping-countdown').hide();
                    return;
                }

                // Przelicz na godziny, minuty i sekundy
                var hours = Math.floor(timeLeft / (1000 * 60 * 60));
                var minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                var seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                // Wyświetl countdown z sekundami
                $('#countdown-time').text(hours + 'h ' + minutes + 'min ' + seconds + 's');
                $('#shipping-countdown').show();
            }

            // Aktualizuj co sekundę
            updateCountdown();
            setInterval(updateCountdown, 1000);

            // Sprawdź o północy i zresetuj
            var now = new Date();
            var midnight = new Date();
            midnight.setHours(24, 0, 0, 0);
            var timeUntilMidnight = midnight - now;

            setTimeout(function() {
                updateCountdown();
                // Potem sprawdzaj co 24h
                setInterval(updateCountdown, 24 * 60 * 60 * 1000);
            }, timeUntilMidnight);
        });
    </script>
<?php
}
add_action('woocommerce_after_add_to_cart_form', 'jetlagz_shipping_countdown_timer', 10);

/**
 * Add gift wrapping checkbox in cart for each item
 */
/**
 * REMOVED: Old per-product gift wrapping checkbox
 * Now using single global checkbox at cart/checkout bottom
 */

/**
 * Add CSS and JavaScript for GLOBAL gift wrapping checkbox
 */
function jetlagz_gift_wrapping_cart_assets()
{
    if (!is_cart() && !is_checkout()) {
        return;
    }
?>
    <style>
        /* Ukryj domyślny wiersz fee z tabeli WooCommerce (mamy własny custom totals) */
        .woocommerce-checkout-review-order-table tr.fee,
        .shop_table.woocommerce-checkout-review-order-table tr.fee,
        table.shop_table tr.fee {
            display: none !important;
        }

        .universal-gift-wrapping-section {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .gift-wrapping-global-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            user-select: none;
        }

        .gift-wrapping-global-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #000;
        }

        .gift-wrapping-global-text {
            color: #111827;
            font-weight: 600;
            font-size: 14px;
        }

        .gift-wrapping-global-price {
            color: #059669;
            font-weight: 700;
            margin-left: 0.5rem;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            // Handle global gift wrapping checkbox
            $(document.body).on('change', '#global-gift-wrapping-checkbox', function() {
                var $checkbox = $(this);
                var isChecked = $checkbox.prop('checked');


                // Disable checkbox podczas aktualizacji
                $checkbox.prop('disabled', true);

                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'toggle_global_gift_wrapping',
                        enabled: isChecked,
                        security: '<?php echo wp_create_nonce("woocommerce-cart"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {

                            // CHECKOUT: Force full checkout update
                            if (typeof wc_checkout_params !== 'undefined') {
                                $(document.body).trigger('update_checkout');
                            }

                            // CART: Force cart update
                            if ($('[name="update_cart"]').length) {
                                $('[name="update_cart"]').prop('disabled', false).trigger('click');
                            } else {
                                // Fallback: reload całej strony jeśli nie ma przycisku update
                                location.reload();
                            }
                        } else {
                            console.error('❌ Failed to update gift wrapping:', response.data);
                            $checkbox.prop('checked', !isChecked);
                            $checkbox.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('❌ AJAX error:', error);
                        $checkbox.prop('checked', !isChecked);
                        $checkbox.prop('disabled', false);
                    },
                    complete: function() {
                        // Re-enable checkbox po zakończeniu
                        setTimeout(function() {
                            $checkbox.prop('disabled', false);
                        }, 1000);
                    }
                });
            });

            // Ensure checkbox state is preserved after checkout update
            $(document.body).on('updated_checkout', function() {});
        });
    </script>
<?php
}
add_action('wp_footer', 'jetlagz_gift_wrapping_cart_assets');

/**
 * Handle AJAX request to toggle GLOBAL gift wrapping
 */
function jetlagz_toggle_global_gift_wrapping_ajax()
{
    check_ajax_referer('woocommerce-cart', 'security');

    $enabled = isset($_POST['enabled']) && filter_var($_POST['enabled'], FILTER_VALIDATE_BOOLEAN);

    // Save global gift wrapping state in session
    WC()->session->set('global_gift_wrapping_enabled', $enabled);

    // Recalculate cart totals
    WC()->cart->calculate_totals();

    wp_send_json_success(array(
        'message' => 'Global gift wrapping updated',
        'enabled' => $enabled
    ));
}
add_action('wp_ajax_toggle_global_gift_wrapping', 'jetlagz_toggle_global_gift_wrapping_ajax');
add_action('wp_ajax_nopriv_toggle_global_gift_wrapping', 'jetlagz_toggle_global_gift_wrapping_ajax');

/**
 * Add GLOBAL gift wrapping fee to cart totals
 */
function jetlagz_add_gift_wrapping_fee($cart)
{
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Clear any existing gift wrapping fees first
    foreach ($cart->get_fees() as $fee_key => $fee) {
        if (strpos($fee->name, '🎁') !== false) {
            unset($cart->fees[$fee_key]);
        }
    }

    // Check if global gift wrapping is enabled
    $gift_wrapping_enabled = WC()->session->get('global_gift_wrapping_enabled', false);

    if (!$gift_wrapping_enabled) {
        return;
    }

    // Get gift wrapping settings from ACF
    $gift_wrapping_group = get_field('gift_wrapping_field', 'option');

    if (!$gift_wrapping_group || !isset($gift_wrapping_group['gift_wrapping_enabled']) || !$gift_wrapping_group['gift_wrapping_enabled']) {
        return;
    }

    $gift_wrapping_price = floatval($gift_wrapping_group['gift_wrapping_price'] ?? 12);
    $gift_wrapping_label = $gift_wrapping_group['gift_wrapping_label'] ?? 'Zapakować na prezent?';

    // Add single fee for entire order
    $fee_label = '🎁 ' . $gift_wrapping_label;
    $cart->add_fee($fee_label, $gift_wrapping_price, true);
}
add_action('woocommerce_cart_calculate_fees', 'jetlagz_add_gift_wrapping_fee', 20);

/**
 * Display gift wrapping in cart item details
 */
function jetlagz_display_gift_wrapping_in_cart($item_data, $cart_item)
{
    if (isset($cart_item['gift_wrapping']['enabled']) && $cart_item['gift_wrapping']['enabled']) {
        $item_data[] = array(
            'key'   => 'Pakowanie',
            'value' => '🎁 ' . esc_html($cart_item['gift_wrapping']['label'])
        );
    }

    return $item_data;
}
add_filter('woocommerce_get_item_data', 'jetlagz_display_gift_wrapping_in_cart', 10, 2);

/**
 * Save gift wrapping to order meta
 */
function jetlagz_save_gift_wrapping_to_order($item, $cart_item_key, $values, $order)
{
    if (isset($values['gift_wrapping']['enabled']) && $values['gift_wrapping']['enabled']) {
        $item->add_meta_data('_gift_wrapping', 'yes', true);
        $item->add_meta_data('_gift_wrapping_label', $values['gift_wrapping']['label'], true);
        $item->add_meta_data('_gift_wrapping_price', $values['gift_wrapping']['price'], true);
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'jetlagz_save_gift_wrapping_to_order', 10, 4);

/**
 * Display gift wrapping in order items (admin and emails)
 */
function jetlagz_display_gift_wrapping_in_order($item_id, $item, $product)
{
    $gift_wrapping = $item->get_meta('_gift_wrapping');

    if ($gift_wrapping === 'yes') {
        $label = $item->get_meta('_gift_wrapping_label') ?: 'Zapakować na prezent?';
        echo '<div class="gift-wrapping-notice" style="margin-top: 0.5rem; font-size: 13px; color: #059669;">🎁 ' . esc_html($label) . '</div>';
    }
}
add_action('woocommerce_order_item_meta_end', 'jetlagz_display_gift_wrapping_in_order', 10, 3);

// AJAX handler to get cart product IDs
function jetlagz_get_cart_product_ids()
{
    $product_ids = array();

    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $cart_item) {
            // Add both product_id and variation_id (if exists)
            $product_ids[] = (string) $cart_item['product_id'];
            if (!empty($cart_item['variation_id'])) {
                $product_ids[] = (string) $cart_item['variation_id'];
            }
        }
    }

    wp_send_json_success(array('product_ids' => $product_ids));
}
add_action('wp_ajax_jetlagz_get_cart_product_ids', 'jetlagz_get_cart_product_ids');
add_action('wp_ajax_nopriv_jetlagz_get_cart_product_ids', 'jetlagz_get_cart_product_ids');

// AJAX handler to remove crosssell product from cart
function jetlagz_remove_crosssell_from_cart()
{
    // Clean any output buffers to prevent HTML in JSON response
    if (ob_get_length()) {
        ob_end_clean();
    }
    ob_start();

    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jetlagz_remove_crosssell')) {
        ob_end_clean();
        wp_send_json_error(array('message' => 'Nonce verification failed'));
        return;
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

    if (!$product_id) {
        ob_end_clean();
        wp_send_json_error(array('message' => 'Invalid product ID'));
        return;
    }

    $cart = WC()->cart->get_cart();
    $removed = false;

    foreach ($cart as $cart_item_key => $cart_item) {
        // Check both product_id and variation_id
        if ($cart_item['product_id'] == $product_id || $cart_item['variation_id'] == $product_id) {
            WC()->cart->remove_cart_item($cart_item_key);
            $removed = true;
            break;
        }
    }

    if ($removed) {
        WC()->cart->calculate_totals();

        // Get fragments without outputting HTML
        ob_start();
        woocommerce_mini_cart();
        $mini_cart = ob_get_clean();

        ob_end_clean();

        wp_send_json_success(array(
            'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array(
                'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>'
            ))
        ));
    } else {
        ob_end_clean();
        wp_send_json_error(array('message' => 'Product not found in cart'));
    }
}
add_action('wp_ajax_jetlagz_remove_crosssell_from_cart', 'jetlagz_remove_crosssell_from_cart');
add_action('wp_ajax_nopriv_jetlagz_remove_crosssell_from_cart', 'jetlagz_remove_crosssell_from_cart');

/**
 * AJAX handler for loading more reviews
 */
function jetlagz_load_more_reviews()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = 5;

    if (!$product_id) {
        wp_send_json_error(array('message' => 'Invalid product ID'));
        return;
    }

    $offset = ($page - 1) * $per_page;

    $comments = get_comments(array(
        'post_id' => $product_id,
        'status' => 'approve',
        'type' => 'review',
        'number' => $per_page,
        'offset' => $offset
    ));

    if (empty($comments)) {
        wp_send_json_error(array('message' => 'No more reviews'));
        return;
    }

    ob_start();
    foreach ($comments as $comment) {
        jetlagz_display_single_review($comment);
    }
    $html = ob_get_clean();

    // Check if there are more reviews
    $total_comments = get_comments(array(
        'post_id' => $product_id,
        'status' => 'approve',
        'type' => 'review',
        'count' => true
    ));

    $has_more = ($offset + $per_page) < $total_comments;

    wp_send_json_success(array(
        'html' => $html,
        'has_more' => $has_more
    ));
}
add_action('wp_ajax_load_more_reviews', 'jetlagz_load_more_reviews');
add_action('wp_ajax_nopriv_load_more_reviews', 'jetlagz_load_more_reviews');
