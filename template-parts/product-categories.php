<?php

/**
 * Template Part: Product Categories
 * Displays all available product categories with images and names
 */

// Get all product categories
$categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => true,
    'parent' => 0 // Only top-level categories
));

if (!$categories || is_wp_error($categories)) {
    return;
}
?>

<div class="product-categories-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:flex lg:flex-wrap lg:justify-center gap-2 mx-auto pb-6 md:pb-20 max-w-screen-2xl px-4">
    <?php
    // Get current category if on category page
    $current_category_id = is_product_category() ? get_queried_object_id() : 0;

    foreach ($categories as $category):
        $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
        $image_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
        $category_link = get_term_link($category);
        $is_active = ($category->term_id === $current_category_id);
    ?>

        <a href="<?php echo esc_url($category_link); ?>" class="category-item group transition-all duration-300 flex justify-end flex-col <?php echo $is_active ? 'lg:w-[200px]' : 'lg:w-[160px]'; ?>">
            <h3 class="category-name text-xs sm:text-sm md:text-base font-light uppercase transition-all duration-300 <?php echo $is_active ? 'font-bold lg:text-lg' : ''; ?>">
                <?php echo esc_html($category->name); ?>
            </h3>
            <div class="category-image-wrapper relative overflow-hidden mt-1 w-full <?php echo $is_active ? 'lg:h-full' : 'lg:h-[230px]'; ?>">
                <img
                    src="<?php echo esc_url($image_url); ?>"
                    alt="<?php echo esc_attr($category->name); ?>"
                    class="w-full <?php echo $is_active ? 'lg:h-full lg:object-cover' : 'h-full'; ?> object-cover transition-transform duration-300 group-hover:scale-105">
            </div>
        </a>
    <?php endforeach; ?>
</div>