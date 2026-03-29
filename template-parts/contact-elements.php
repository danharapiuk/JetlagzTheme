<?php

/**
 * Reusable contact elements block.
 *
 * Usage:
 * get_template_part('template-parts/contact-elements', null, array(
 *     'source_post_id' => 123,
 * ));
 */

if (!defined('ABSPATH')) {
    exit;
}

$component_args = isset($args) && is_array($args) ? $args : array();
$source_post_id = isset($component_args['source_post_id']) ? $component_args['source_post_id'] : get_the_ID();

$contact = isset($component_args['contact']) && is_array($component_args['contact'])
    ? $component_args['contact']
    : safe_get_field('contact', $source_post_id);










// Fallback for usage on other pages without explicitly passing source_post_id.
if ((!is_array($contact) || empty($contact)) && function_exists('get_posts')) {
    $contact_pages = get_posts(array(
        'post_type' => 'page',
        'meta_key' => '_wp_page_template',
        'meta_value' => 'contact.php',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ));

    if (!empty($contact_pages)) {
        $contact = safe_get_field('contact', (int) $contact_pages[0]);
    }
}

$contact = is_array($contact) ? $contact : array();
$elements = !empty($contact['elements']) && is_array($contact['elements']) ? $contact['elements'] : array();
?>

<div class="contact-elements flex flex-col gap-6 md:gap-10 justify-between">
    <?php foreach ($elements as $element) : ?>
        <?php
        $icon_url = $element['icon']['url'] ?? '';
        $icon_alt = $element['icon']['alt'] ?? '';
        $title = $element['title'] ?? '';
        $description = $element['description'] ?? '';
        $link_value = $element['link_url_1'] ?? '';
        ?>

        <div class="contact-element flex gap-2 md:gap-6">
            <?php if (!empty($icon_url)) : ?>
                <div class="contact-icon border border-[#F3F3F3] rounded-[8px] p-2 h-[45px] w-[45px] flex w-fit">
                    <img class="w-fit h-fit" src="<?php echo esc_url($icon_url); ?>" alt="<?php echo esc_attr($icon_alt); ?>">
                </div>
            <?php endif; ?>

            <div>
                <h3 class="font-bold leading-base"><?php echo esc_html($title); ?></h3>
                <p class="text-sm leading-sm font-light text-[#676767]"><?php echo esc_html($description); ?></p>
                <div class="pt-1 font-medium text-xs text-black contact-link cursor-pointer flex gap-1" data-value="<?php echo esc_attr($link_value); ?>">
                    <?php echo esc_html($link_value); ?>
                    <?php if (!empty($link_value)) : ?>
                        <span class="copy-icon hidden md:inline-flex ml-1"><img src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/images/Stack.svg" alt="Copy" class="w-4 h-4" /></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="flex gap-3 social-links">
        <a class="flex gap-1 items-center" href="https://www.instagram.com/myalmostdream/" target="_blank" rel="noopener noreferrer">
            <img class="w-[20px] h-[20px]" src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/images/instagram_logo.svg" alt="Instagram">Almostdream
        </a>
        <a class="flex gap-1 items-center" href="https://www.facebook.com/myalmostdream/" target="_blank" rel="noopener noreferrer">
            <img class="w-[20px] h-[20px]" src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/images/facebook_logo.svg" alt="Facebook">Almostdream
        </a>
    </div>
</div>