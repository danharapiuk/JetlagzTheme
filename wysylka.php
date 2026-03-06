<?php get_header();

/**
 * Template Name: Wysylka
 */


$content = safe_get_field('content'); 
$image = safe_get_field('image'); ?>


<section class="wrapper md:flex gap-5">
    <div class="flex flex-col gap-5">
        <?php foreach ($content as $item) : ?>

            <h2 class="text-xl sm:text-4xl"><?php echo $item['title']; ?></h2>
            <p class="font-light text-sm pb-4"><?php echo $item['description']; ?></p>
            <div class="flex flex-col gap-4">
                <?php foreach ($item['elements'] as $element) : ?>
                    <div class="wysylka-element flex gap-2 md:gap-6">
                        <div class="wysylka-icon border border-[#F3F3F3] rounded-[8px] p-2 h-[45px] w-[45px] flex w-fit">
                            <img class="w-fit h-fit" src="<?php echo esc_url($element['icon']['url']); ?>" alt="<?php echo esc_attr($element['icon']['alt']); ?>">
                        </div>
                        <div>
                            <h3 class="font-bold leading-base"><?php echo $element['title']; ?></h3>
                            <p class="text-sm leading-sm font-light text-[#676767]"><?php echo $element['description']; ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="w-full h-full flex items-center justify-center p-4">
        <img src="<?php echo esc_url($item['image']['url']); ?>" alt="<?php echo esc_attr($item['image']['alt']); ?>">
    </div>
</section>


<section class="wrapper">
    <?php get_template_part('template-parts/product-faq'); ?>
</section>

<style>


</style>


<?php
get_footer(); ?>