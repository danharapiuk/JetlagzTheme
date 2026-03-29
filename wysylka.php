<?php get_header();

/**
 * Template Name: Wysylka
 */


$content = safe_get_field('content');
$image = safe_get_field('image');
$higiena = safe_get_field('higiena');
?>

<section class="wrapper md:flex justify-between pt-12 md:pt-20 tracking-tight">
    <div class="lg:flex gap-5 font-inter">
        <div class="flex flex-col gap-12 md:justify-between">
            <?php foreach ($content as $item) : ?>
                <div>
                    <h2 class="text-2xl sm:text-4xl text-center sm:text-left font-medium"><?php echo $item['title']; ?></h2>
                    <div class="font-light pb-4 pt-2 text-center sm:text-left"><?php echo $item['description']; ?></div>
                    <div class="flex flex-col gap-6">
                        <?php foreach ($item['elements'] as $element) : ?>
                            <div class="wysylka-element sm:flex gap-4 md:gap-6">
                                <div class="wysylka-icon flex justify-center">
                                    <img class="h-[45px] min-w-[45px]" src="<?php echo esc_url($element['icon']['url']); ?>" alt="<?php echo esc_attr($element['icon']['alt']); ?>">
                                </div>
                                <div>
                                    <h3 class="font-bold leading-base text-center sm:text-left pb-1"><?php echo $element['title']; ?></h3>
                                    <div class="text-sm leading-sm font-light text-[#676767] text-center sm:text-left"><?php echo $element['description']; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="w-full h-full flex items-center justify-center pt-12 md:pt-0">
            <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>">
        </div>
    </div>
</section>


<section class="wrapper py-12 md:pt-48 font-inter tracking-tight max-w-[700px] mx-auto">
    <div class="text-center max-w-[540px] mx-auto">
        <h2 class="text-2xl text-center font-medium"><?php echo $higiena['title']; ?></h2>
        <div class="font-light text-base pt-2 text-center"><?php echo $higiena['subtitle']; ?></div>
        <div class="font-light text-sm pt-2 text-center"><?php echo $higiena['next_subtitle']; ?></div>
    </div>
    <div class="grid gap-1 grid-cols-1 md:grid-cols-2 pt-12 md:pt-20">
        <?php foreach ($higiena['elements'] as $element) : ?>
            <div class="flex flex-col gap-2 p-4 md:p-10 justify-center items-center border rounded-[10px]">
                <div class="wysylka-icon flex justify-center">
                    <img class="h-[45px] min-w-[45px]" src="<?php echo esc_url($element['icon']['url']); ?>" alt="<?php echo esc_attr($element['icon']['alt']); ?>">
                </div>

                <h3 class="font-bold leading-base text-base"><?php echo $element['title']; ?></h3>
                <div class="text-xs leading-sm font-light text-[#676767] text-center"><?php echo $element['description']; ?></div>
            </div>

        <?php endforeach; ?>
    </div>

</section>


<section class="wrapper py-12 md:py-20">
    <?php
    get_template_part('template-parts/product-faq', null, array(
        'faq_items' => safe_get_field('faq'),
        'faq_title' => safe_get_field('faq_title') ?: 'FAQ',
        'faq_instance' => 'wysylka-faq',
    ));
    ?>
</section>




<?php
get_footer(); ?>