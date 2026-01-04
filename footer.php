<?php

/**
 * The template for displaying the footer.
 */

?>

</div><!-- .col-full -->
</div><!-- #content -->

<?php do_action('storefront_before_footer'); ?>

<footer id="colophon" class="site-footer bg-[#F9A2BC] w-full !text-white pt-12 md:pt-24" role="contentinfo">
    <section class="wrapper">
        <div class="md:flex md:justify-between">
            <div class="flex flex-wrap justify-between items-center w-full md:w-2/3 uppercase">
                <div class="footer-element">
                    <h4 class="title">sklep</h4>
                    <ul>
                        <li><a href="/o-nas">O nas</a></li>
                        <li><a href="#">Kontakt</a></li>
                        <li><a href="#">link 3</a></li>
                    </ul>
                </div>

                <div class="footer-element">
                    <h4 class="title">Obsługa klienta</h4>
                    <ul>
                        <li><a href="/o-nas">O nas</a></li>
                        <li><a href="#">Kontakt</a></li>
                        <li><a href="#">link 3</a></li>
                    </ul>
                </div>

                <div class="footer-element">
                    <h4 class="title">Regulaminy</h4>
                    <ul>
                        <li><a href="/o-nas">O nas</a></li>
                        <li><a href="#">Kontakt</a></li>
                        <li><a href="#">link 3</a></li>
                    </ul>
                </div>

            </div>
            <div class="w-full md:w-1/3 flex justify-end gap-2 hidden">
                <a href="/">
                    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/instagram.svg'); ?>" alt="Instagram Logo" class="w-[24px] h-[24px]">
                </a>
                <a href="/">
                    <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/facebook.svg'); ?>" alt="Facebook Logo" class="w-[24px] h-[24px]">
                </a>
            </div>

        </div>
    </section>
    <div class="wrapper">
        <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/footer-logo.svg'); ?>" alt="Footer logo" class="w-full h-auto">
    </div>
</footer><!-- #colophon -->
<div class="wrapper !p-0 text-[10px] py-4">
    <?php do_action('storefront_after_footer'); ?>
    © <?php echo date('Y'); ?> ALMOST DREAM
    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" aria-label="Wróć na górę" style="display:none;position:fixed;right:2rem;bottom:2.5rem;z-index:9999;background:#F9A2BC;color:#fff;border:none;border-radius:50%;width:48px;height:48px;box-shadow:0 2px 8px rgba(0,0,0,0.15);cursor:pointer;transition:background 0.2s;">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" style="display:block;margin:auto;">
            <path d="M12 19V5M12 5l-7 7M12 5l7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    </button>
</div>
</div><!-- #page -->

<?php wp_footer(); ?>

<script>
    // Scroll to Top Button: clean version with scroll event fix
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('scrollToTopBtn');
        if (!btn) return;
        window.addEventListener('scroll', function() {
            btn.style.display = (window.scrollY > 300) ? 'block' : 'none';
        });
        btn.addEventListener('click', function() {
            var header = document.getElementById('site-header');

            if (header) {
                header.scrollIntoView({
                    behavior: 'smooth'
                });
            } else {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            // Resetuj hero do pozycji początkowej (jeśli funkcja istnieje)
            setTimeout(function() {
                if (typeof window.resetHeroPosition === 'function') {
                    window.resetHeroPosition();
                }
                window.dispatchEvent(new Event('scroll'));
            }, 500);
        });
    });
</script>
</body>

</html>