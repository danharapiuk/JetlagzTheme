<?php

/**
 * Template Name: Pusty Koszyk
 * Template Post Type: page
 */

get_header(); ?>

<div id="primary" class="content-area wrapper">
    <main id="main" class="site-main" role="main">

        <div class="empty-cart-container" style="text-align: center; padding: 60px 20px; margin: 0 auto;">

            <!-- Ikona pustego koszyka -->
            <div class="empty-cart-icon" style="font-size: 80px; color: #ddd; margin-bottom: 30px;">
                🛒
            </div>

            <!-- Nagłówek -->
            <h1 style="font-size: 32px; margin-bottom: 20px; color: #333;">
                Twój koszyk jest pusty
            </h1>

            <!-- Opis -->
            <p style="font-size: 18px; color: #666; margin-bottom: 40px; line-height: 1.6;">
                Wygląda na to, że nie dodałeś jeszcze żadnych produktów do koszyka.<br>
                Zacznij zakupy i znajdź coś dla siebie!
            </p>

            <!-- Przyciski -->
            <div class="empty-cart-actions" style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>"
                    class="button-primary">
                    Przejdź do sklepu
                </a>

                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo wc_get_account_endpoint_url('orders'); ?>"
                        class="button button-secondary"
                        class="button-secondary">
                        Moje zamówienia
                    </a>
                <?php endif; ?>
            </div>

            <?php jetlagz_inject_template_part('features'); ?>

        </div>

    </main>
</div>

<?php
get_footer();
