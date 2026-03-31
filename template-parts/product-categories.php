<?php

/**
 * Template Part: Product Categories
 * Displays all available product categories with images and names
 */

// Get all product categories (exclude "uncategorized")
$uncategorized = get_term_by('slug', 'uncategorized', 'product_cat');
$exclude_ids = $uncategorized ? array($uncategorized->term_id) : array();

$categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
    'parent' => 0, // Only top-level categories
    'exclude' => $exclude_ids
));

if (!$categories || is_wp_error($categories)) {
    return;
}
?>

<div class="product-categories-grid grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 lg:flex lg:flex-nowrap lg:justify-stretch gap-0 mx-auto w-full md:border-black md:border-[1px]">
    <?php
    // Get current category if on category page
    $current_category_id = is_product_category() ? get_queried_object_id() : 0;
    $sale_link = add_query_arg('on_sale', '1', wc_get_page_permalink('shop'));
    $is_sale_filter_active = isset($_GET['on_sale']) && $_GET['on_sale'] === '1';

    foreach ($categories as $category):
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
        $category_link = get_term_link($category);
        $is_active = ($category->term_id === $current_category_id);
    ?>

        <a href="<?php echo esc_url($category_link); ?>" class="py-2 border-b-[1px] md:border-b-0 category-item group transition-all duration-300 md:hover:bg-[#9F7B8A] md:hover:text-white <?php echo $is_active ? 'font-bold bg-[#51172F] !text-white' : ''; ?>">
            <div class="flex justify-between md:justify-center items-center ">
                <h3 class="category-name text-xs lg:text-base font-light uppercase transition-all duration-300 <?php echo $is_active ? 'font-medium lg:text-lg !text-white pl-2 md:pl-0' : ''; ?>">
                    <?php echo esc_html($category->name); ?>
                </h3>
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/chevron-right.svg" class="sm:hidden w-4 h-4">
            </div>
        </a>
    <?php endforeach; ?>

    <a href="<?php echo esc_url($sale_link); ?>" class="py-2 border-b-[1px] md:border-b-0 category-item group transition-all duration-300 md:hover:bg-[#9F7B8A] md:hover:text-white <?php echo $is_sale_filter_active ? 'font-bold bg-[#51172F] !text-white' : ''; ?>">
        <div class="flex justify-between md:justify-center items-center ">
            <h3 class="category-name text-xs lg:text-base font-light uppercase transition-all duration-300 <?php echo $is_sale_filter_active ? 'font-medium lg:text-lg !text-white pl-2 md:pl-0' : ''; ?>">
                Promo do -60%
            </h3>
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/chevron-right.svg" class="sm:hidden w-4 h-4">
        </div>
    </a>
</div>