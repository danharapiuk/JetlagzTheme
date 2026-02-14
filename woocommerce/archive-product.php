<?php

/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined('ABSPATH') || exit;

get_header('shop');

/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 */
do_action('woocommerce_before_main_content');

?>
<header class="woocommerce-products-header">
    <?php if (apply_filters('woocommerce_show_page_title', true)) : ?>
        <h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
    <?php endif; ?>

    <?php
    /**
     * Hook: woocommerce_archive_description.
     *
     * @hooked woocommerce_taxonomy_archive_description - 10
     * @hooked woocommerce_product_archive_description - 10
     */
    do_action('woocommerce_archive_description');
    ?>
</header>

<!-- Product Categories -->
<?php
get_template_part('template-parts/product-categories');
?>
<div class="wrapper">
<!-- Filters Overlay -->
<div class="shop-filters-overlay"></div>

<!-- Advanced Product Filters Panel (shared) -->
<div class="shop-filters" id="shop-filters">
    <div class="filters-header">
        <h3>Filtruj produkty</h3>
        <button class="filters-close" id="filters-close">×</button>
    </div>

    <div class="filters-content">
        <!-- Kategorie -->
        <?php
        $product_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'parent' => 0
        ));
        if (!empty($product_categories) && !is_wp_error($product_categories)) :
        ?>
            <div class="filter-group">
                <h4 class="filter-title">Kategorie</h4>
                <div class="filter-options">
                    <?php
                    $selected_cats = isset($_GET['product_cat']) ? (is_string($_GET['product_cat']) ? explode(',', $_GET['product_cat']) : (array)$_GET['product_cat']) : array();
                    foreach ($product_categories as $category) :
                    ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="product_cat[]" value="<?php echo esc_attr($category->slug); ?>"
                                <?php echo in_array($category->slug, $selected_cats) ? 'checked' : ''; ?>>
                            <span><?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)</span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Cena -->
        <div class="filter-group">
            <h4 class="filter-title">Cena</h4>
            <div class="filter-options">
                <div class="price-range-inputs">
                    <input type="number" name="min_price" id="min_price" placeholder="Od"
                        value="<?php echo isset($_GET['min_price']) ? esc_attr($_GET['min_price']) : ''; ?>">
                    <span>-</span>
                    <input type="number" name="max_price" id="max_price" placeholder="Do"
                        value="<?php echo isset($_GET['max_price']) ? esc_attr($_GET['max_price']) : ''; ?>">
                </div>
            </div>
        </div>

        <!-- Rozmiary -->
        <?php
        $sizes = get_terms(array(
            'taxonomy' => 'pa_rozmiar',
            'hide_empty' => true
        ));
        if (!empty($sizes) && !is_wp_error($sizes)) :
        ?>
            <div class="filter-group">
                <h4 class="filter-title">Rozmiar</h4>
                <div class="filter-options filter-sizes">
                    <?php
                    $selected_sizes = isset($_GET['filter_rozmiar']) ? (is_string($_GET['filter_rozmiar']) ? explode(',', $_GET['filter_rozmiar']) : (array)$_GET['filter_rozmiar']) : array();
                    foreach ($sizes as $size) :
                    ?>
                        <label class="filter-size-button">
                            <input type="checkbox" name="pa_rozmiar[]" value="<?php echo esc_attr($size->slug); ?>"
                                <?php echo in_array($size->slug, $selected_sizes) ? 'checked' : ''; ?>>
                            <span><?php echo esc_html($size->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Kolory -->
        <?php
        $colors = get_terms(array(
            'taxonomy' => 'pa_kolor',
            'hide_empty' => true
        ));
        if (!empty($colors) && !is_wp_error($colors)) :
        ?>
            <div class="filter-group">
                <h4 class="filter-title">Kolor</h4>
                <div class="filter-options filter-colors">
                    <?php
                    $selected_colors = isset($_GET['filter_kolor']) ? (is_string($_GET['filter_kolor']) ? explode(',', $_GET['filter_kolor']) : (array)$_GET['filter_kolor']) : array();
                    foreach ($colors as $color) :
                        $color_value = get_term_meta($color->term_id, 'attribute_color', true);
                    ?>
                        <label class="filter-color-button" title="<?php echo esc_attr($color->name); ?>">
                            <input type="checkbox" name="pa_kolor[]" value="<?php echo esc_attr($color->slug); ?>"
                                <?php echo in_array($color->slug, $selected_colors) ? 'checked' : ''; ?>>
                            <span class="color-swatch" style="background-color: <?php echo esc_attr($color_value ? $color_value : '#ddd'); ?>"></span>
                            <span class="color-name"><?php echo esc_html($color->name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Na stanie -->
        <div class="filter-group">
            <h4 class="filter-title">Dostępność</h4>
            <div class="filter-options">
                <label class="filter-checkbox">
                    <input type="checkbox" name="stock_status" value="instock"
                        <?php echo (isset($_GET['stock_status']) && $_GET['stock_status'] === 'instock') ? 'checked' : ''; ?>>
                    <span>Tylko w magazynie</span>
                </label>
                <label class="filter-checkbox">
                    <input type="checkbox" name="on_sale" value="1"
                        <?php echo (isset($_GET['on_sale']) && $_GET['on_sale'] === '1') ? 'checked' : ''; ?>>
                    <span>Tylko wyprzedaż</span>
                </label>
            </div>
        </div>
    </div>

    <div class="filters-actions">
        <button type="button" class="filter-apply-btn" id="apply-filters">Zastosuj filtry</button>
        <button type="button" class="filter-reset-btn" id="reset-filters">Wyczyść wszystko</button>
    </div>
</div>

<?php
if (woocommerce_product_loop()) {

    /**
     * Hook: woocommerce_before_shop_loop.
     *
     * @hooked woocommerce_output_all_notices - 10
     * @hooked woocommerce_result_count - 20
     * @hooked woocommerce_catalog_ordering - 30
     */
    do_action('woocommerce_before_shop_loop');
?>

    <!-- Products Per Page & Sorting Controls -->
    <div class="shop-toolbar">
        <div class="toolbar-left">
            <button class="filters-toggle-inline" id="filters-toggle-inline">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M2.5 5.83333H6.66667M6.66667 5.83333C6.66667 7.67428 8.15905 9.16667 10 9.16667C11.8409 9.16667 13.3333 7.67428 13.3333 5.83333M6.66667 5.83333C6.66667 3.99238 8.15905 2.5 10 2.5C11.8409 2.5 13.3333 3.99238 13.3333 5.83333M13.3333 5.83333H17.5M2.5 14.1667H6.66667M6.66667 14.1667C6.66667 16.0076 8.15905 17.5 10 17.5C11.8409 17.5 13.3333 16.0076 13.3333 14.1667M6.66667 14.1667C6.66667 12.3257 8.15905 10.8333 10 10.8333C11.8409 10.8333 13.3333 12.3257 13.3333 14.1667M13.3333 14.1667H17.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                Filtry
                <span class="filters-count-inline" style="display: none;"></span>
            </button>

            <div class="products-per-page">
                <label for="products-per-page">Pokaż:</label>
                <select id="products-per-page" name="products_per_page">
                    <option value="12" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '12') ? 'selected' : ''; ?>>12</option>
                    <option value="24" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '24') ? 'selected' : ''; ?>>24</option>
                    <option value="36" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '36') ? 'selected' : ''; ?>>36</option>
                    <option value="48" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '48') ? 'selected' : ''; ?>>48</option>
                    <option value="-1" <?php echo (isset($_GET['per_page']) && $_GET['per_page'] == '-1') ? 'selected' : ''; ?>>Wszystkie</option>
                </select>
            </div>
        </div>

        <div class="woocommerce-result-count">
            <?php
            $total = wc_get_loop_prop('total');
            $per_page = wc_get_loop_prop('per_page');
            $current = wc_get_loop_prop('current_page');
            $from = ($per_page * ($current - 1)) + 1;
            $to = min($total, $per_page * $current);

            if ($per_page == -1 || $total <= $per_page) {
                printf('Pokazuje wszystkie %d produktów', $total);
            } else {
                printf('Pokazuje %d–%d z %d produktów', $from, $to, $total);
            }
            ?>
        </div>

        <div class="woocommerce-ordering">
            <label for="orderby">Sortuj:</label>
            <select name="orderby" id="orderby" class="orderby">
                <option value="menu_order" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'menu_order') ? 'selected' : ''; ?>>Domyślne</option>
                <option value="popularity" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'popularity') ? 'selected' : ''; ?>>Popularność</option>
                <option value="rating" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'rating') ? 'selected' : ''; ?>>Ocena</option>
                <option value="date" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'date') ? 'selected' : ''; ?>>Najnowsze</option>
                <option value="price" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'price') ? 'selected' : ''; ?>>Cena: rosnąco</option>
                <option value="price-desc" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'price-desc') ? 'selected' : ''; ?>>Cena: malejąco</option>
                <option value="size-asc" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'size-asc') ? 'selected' : ''; ?>>Rozmiar: od najmniejszego</option>
                <option value="size-desc" <?php echo (isset($_GET['orderby']) && $_GET['orderby'] == 'size-desc') ? 'selected' : ''; ?>>Rozmiar: od największego</option>
            </select>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {

            // Products per page
            $('#products-per-page').on('change', function() {
                var url = new URL(window.location.href);
                url.searchParams.set('per_page', $(this).val());
                window.location.href = url.toString();
            });

            // Sorting
            $('#orderby').on('change', function() {
                var url = new URL(window.location.href);
                var orderby = $(this).val();
                if (orderby && orderby !== 'menu_order') {
                    url.searchParams.set('orderby', orderby);
                } else {
                    url.searchParams.delete('orderby');
                }
                window.location.href = url.toString();
            });
        });
    </script>

    <?php

    woocommerce_product_loop_start();

    if (wc_get_loop_prop('total')) {
        while (have_posts()) {
            the_post();

            /**
             * Hook: woocommerce_shop_loop.
             */
            do_action('woocommerce_shop_loop');

            wc_get_template_part('content', 'product');
        }
    }

    woocommerce_product_loop_end();

    // Custom Pagination
    $total_pages = wc_get_loop_prop('total_pages');
    $current_page = wc_get_loop_prop('current_page');

    if ($total_pages > 1) :
    ?>
        <nav class="woocommerce-pagination">
            <?php
            $base_url = strtok($_SERVER["REQUEST_URI"], '?');
            $query_params = $_GET;

            // Previous button
            if ($current_page > 1) {
                $query_params['paged'] = $current_page - 1;
                $prev_url = $base_url . '?' . http_build_query($query_params);
                echo '<a href="' . esc_url($prev_url) . '" class="pagination-btn prev">← Poprzednia</a>';
            } else {
                echo '<span class="pagination-btn prev disabled">← Poprzednia</span>';
            }
            ?>

            <div class="pagination-numbers">
                <?php
                // Calculate page range
                $range = 2; // Show 2 pages before and after current
                $start = max(1, $current_page - $range);
                $end = min($total_pages, $current_page + $range);

                // First page + ellipsis
                if ($start > 1) {
                    $query_params['paged'] = 1;
                    $first_url = $base_url . '?' . http_build_query($query_params);
                    echo '<a href="' . esc_url($first_url) . '" class="page-number">1</a>';
                    if ($start > 2) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                }

                // Page numbers
                for ($i = $start; $i <= $end; $i++) {
                    if ($i == $current_page) {
                        echo '<span class="page-number current">' . $i . '</span>';
                    } else {
                        $query_params['paged'] = $i;
                        $page_url = $base_url . '?' . http_build_query($query_params);
                        echo '<a href="' . esc_url($page_url) . '" class="page-number">' . $i . '</a>';
                    }
                }

                // Ellipsis + last page
                if ($end < $total_pages) {
                    if ($end < $total_pages - 1) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    $query_params['paged'] = $total_pages;
                    $last_url = $base_url . '?' . http_build_query($query_params);
                    echo '<a href="' . esc_url($last_url) . '" class="page-number">' . $total_pages . '</a>';
                }
                ?>
            </div>

            <?php
            // Next button
            if ($current_page < $total_pages) {
                $query_params['paged'] = $current_page + 1;
                $next_url = $base_url . '?' . http_build_query($query_params);
                echo '<a href="' . esc_url($next_url) . '" class="pagination-btn next">Następna →</a>';
            } else {
                echo '<span class="pagination-btn next disabled">Następna →</span>';
            }
            ?>
        </nav>
    <?php
    endif;
    // End pagination
    ?>

<?php
} else {
    /**
     * Hook: woocommerce_no_products_found.
     *
     * @hooked wc_no_products_found - 10
     */
    do_action('woocommerce_no_products_found');
}

/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action('woocommerce_after_main_content');

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
do_action('woocommerce_sidebar');?>
</div>
<?php

get_footer('shop');
