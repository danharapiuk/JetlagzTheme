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
            <div class="flex flex-col gap-6 md:gap-10 flex flex-col justify-between">
                <?php foreach ($contact['elements'] as $element) : ?>
                    <div class="contact-element flex gap-2 md:gap-6">
                        <div class="contact-icon border border-[#F3F3F3] rounded-[8px] p-2 h-[45px] w-[45px] flex w-fit">
                            <img class="w-fit h-fit" src="<?php echo esc_url($element['icon']['url']); ?>" alt="<?php echo esc_attr($element['icon']['alt']); ?>">
                        </div>
                        <div>
                            <h3 class="font-bold leading-base"><?php echo $element['title']; ?></h3>
                            <p class="text-sm leading-sm font-light text-[#676767]"><?php echo $element['description']; ?></p>
                            <div class="">
                                <div class="pt-1 font-medium text-xs text-black contact-link cursor-pointer flex gap-1" data-value="<?php echo esc_attr($element['link_url_1']); ?>">
                                    <?php echo esc_html($element['link_url_1']); ?>
                                    <?php if (! empty($element['link_url_1'])) : ?>
                                        <span class="copy-icon hidden md:inline-flex ml-1"><img src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/images/Stack.svg" alt="Copy" class="w-4 h-4" /></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="flex gap-3">
                    <a class="flex gap-1 items-center" href="https://www.instagram.com/almostdream/" target="_blank">
                        <img class="w-[20px] h-[20px]" src=" <?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/images/instagram_logo.svg" alt="Instagram" class="w-6 h-6">Almostdream
                    </a>
                    <a class="flex gap-1 items-center" href="https://www.facebook.com/almostdream/" target="_blank">
                        <img class="w-[20px] h-[20px]" src="<?php echo esc_url(get_stylesheet_directory_uri()); ?>/assets/images/facebook_logo.svg" alt="Facebook" class="w-6 h-6">Almostdream
                    </a>
                </div>
            </div>
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
            .. a zanim napiszesz, sprawdź nasze FAQ – być może odpowiedź już tam na Ciebie czeka!".
        </div>
    </div>
</section>

<section class="wrapper">
    <?php get_template_part('template-parts/product-faq'); ?>
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


<script>
    (function() {
        function isNumeric(str) {
            return /\d/.test(str);
        }

        function showTooltip(el) {
            var tooltip = document.createElement('div');
            tooltip.textContent = 'Skopiowano';
            tooltip.style.position = 'absolute';
            tooltip.style.background = 'black';
            tooltip.style.color = 'white';
            tooltip.style.padding = '4px 8px';
            tooltip.style.fontSize = '12px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.whiteSpace = 'nowrap';
            tooltip.style.zIndex = 1000;

            document.body.appendChild(tooltip);
            var rect = el.getBoundingClientRect();
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 4 + window.scrollY) + 'px';
            tooltip.style.left = (rect.left + (rect.width - tooltip.offsetWidth) / 2 + window.scrollX) + 'px';

            setTimeout(function() {
                tooltip.remove();
            }, 1500);
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.contact-link').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    var value = el.getAttribute('data-value');
                    var isEmail = value.indexOf('@') !== -1;
                    var isPhone = isNumeric(value);
                    var mobile = window.matchMedia('(max-width: 767px)').matches;

                    if (mobile && isEmail) {
                        window.location.href = 'mailto:' + value;
                        return;
                    }
                    if (mobile && isPhone) {
                        window.location.href = 'tel:' + value.replace(/[^0-9+]/g, '');
                        return;
                    }

                    // desktop or fallback: copy to clipboard
                    navigator.clipboard.writeText(value).then(function() {
                        showTooltip(el);
                    });
                });
            });
        });
    })();
</script>

<?php
get_footer(); ?>