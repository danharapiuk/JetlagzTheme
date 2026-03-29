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

<div class="product-categories-grid grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 lg:flex lg:flex-nowrap lg:justify-stretch gap-0 mx-auto max-w-screen-2xl px-4 w-full border-[1px]">
    <?php
    // Get current category if on category page
    $current_category_id = is_product_category() ? get_queried_object_id() : 0;

    foreach ($categories as $category):
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
        $category_link = get_term_link($category);
        $is_active = ($category->term_id === $current_category_id);
    ?>

        <a href="<?php echo esc_url($category_link); ?>" class="py-2 sm:py-4 category-item group transition-all duration-300 <?php echo $is_active ? 'font-bold' : ''; ?>">
            <div class="flex justify-center items-center md:py-4">
                <h3 class="category-name text-xs md:text-base font-light uppercase transition-all duration-300 <?php echo $is_active ? 'font-bold lg:text-lg' : ''; ?>">
                    <?php echo esc_html($category->name); ?>
                </h3>
                <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/chevron-right.svg" class="sm:hidden w-4 h-4">
            </div>
        </a>
    <?php endforeach; ?>
</div>