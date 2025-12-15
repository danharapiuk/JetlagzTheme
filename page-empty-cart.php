<?php

/**
 * Template Name: Pusty Koszyk
 * Template Post Type: page
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <div class="empty-cart-container" style="text-align: center; padding: 60px 20px; max-width: 600px; margin: 0 auto;">

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
                    class="button"
                    style="background: var(--primary-color, #e74c3c); color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block;">
                    Przejdź do sklepu
                </a>

                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo wc_get_account_endpoint_url('orders'); ?>"
                        class="button button-secondary"
                        style="background: #3498db; color: white; padding: 15px 30px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block;">
                        Moje zamówienia
                    </a>
                <?php endif; ?>
            </div>

            <!-- Dodatkowe informacje -->
            <div class="empty-cart-info" style="margin-top: 60px; padding-top: 40px; border-top: 1px solid #eee;">
                <h3 style="font-size: 20px; margin-bottom: 20px; color: #333;">
                    Dlaczego warto kupować u nas?
                </h3>

                <div class="benefits" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 30px;">
                    <div class="benefit-item">
                        <div style="font-size: 40px; margin-bottom: 10px;">🚚</div>
                        <p style="font-size: 14px; color: #666; margin: 0;">Darmowa dostawa<br>od 200 zł</p>
                    </div>

                    <div class="benefit-item">
                        <div style="font-size: 40px; margin-bottom: 10px;">↩️</div>
                        <p style="font-size: 14px; color: #666; margin: 0;">30 dni<br>na zwrot</p>
                    </div>

                    <div class="benefit-item">
                        <div style="font-size: 40px; margin-bottom: 10px;">✓</div>
                        <p style="font-size: 14px; color: #666; margin: 0;">Gwarancja<br>jakości</p>
                    </div>
                </div>
            </div>

        </div>

    </main>
</div>

<?php
get_footer();
