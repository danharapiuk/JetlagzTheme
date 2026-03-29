<?php get_header();

/**
 * Template Name: Contact
 */


$contact = safe_get_field('contact'); ?>
<section class="wrapper md:flex gap-4 items-stretch py-12 md:py-20">
    <div class="md:w-1/3">
        <div class="md:hidden tracking-tight pb-12 pt-3">
            <h2 class="text-xl pb-2"> <?php echo $contact['title']; ?></h2>
            <div class="font-light text-sm text-black ">
                <?php echo $contact['description']; ?>
            </div>
        </div>
        <div class="h-full flex flex-col justify-between tracking-tight">
            <div></div>
            <?php
            get_template_part('template-parts/contact-elements', null, array(
                'contact' => $contact,
                'source_post_id' => get_the_ID(),
            ));
            ?>
            <div class="pt-12 md:pt-0"><?php echo $contact['adress']; ?></div>
        </div>
    </div>

    <div class="md:w-2/3 bg-[#51172F] rounded-[8px] md:rounded-[20px] p-4 pt-8 md:p-[50px] text-white mt-12 md:mt-0 relative">
        <div class="absolute right-[20px] md:right-[50px] top-[20px]">
            <img src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/images/chat-icon.svg" class="w-[35px] h-[35px] md:w-[78px] md:h-[78px]">
        </div>
        <h3 class="md:hidden text-white font-medium text-xl">Formularz kontaktowy</h3>
        <div class="hidden md:block tracking-tight pb-3">
            <h2 class="text-3xl pb-2 text-white"><?php echo $contact['title']; ?></h2>
            <div class="font-light text-base">
                <?php echo $contact['description']; ?>
            </div>
        </div>
        <div class="contact-form-contact"><?php echo do_shortcode('[contact-form-7 id="61d7383" title="Kontakt"]'); ?></div>
        <div class="font-light text-base">
            .. a zanim napiszesz, sprawdź nasze FAQ – być może odpowiedź już tam na Ciebie czeka! A jeśli szukasz informacji na temat Wysyłki lub zwrotu to zajrzyj <a href="/wysylka-i-zwroty">tutaj</a>.
        </div>
    </div>
</section>

<section class="wrapper">
    <?php get_template_part('template-parts/product-faq'); ?>
</section>

<section class="pt-12 md:pt-20">
    <?php echo do_shortcode('[instagram-feed feed=1]'); ?>
</section>


<style>
    .contact-form-contact .wpcf7 textarea {
        height: 50px;
        border: none;
        border-bottom: 1px solid white;
        background-color: transparent;
        color: white;
    }

    .contact-form-contact .wpcf7 input {
        border: none;
        border-bottom: 1px solid white;
        background-color: transparent;
        width: 100%;
        color: white;
    }

    .contact-form-contact .wpcf7 textarea::placeholder {
        color: white;
    }

    .contact-form-contact .wpcf7 .wpcf7-submit {
        margin-top: 55px;
        background-color: white;
        color: black;
        border-radius: 2px;
        padding: 10px 24px;
        width: 100%;
    }

    .contact-form-contact .wpcf7 input::placeholder {
        color: white;
    }
</style>


<?php
get_footer(); ?>