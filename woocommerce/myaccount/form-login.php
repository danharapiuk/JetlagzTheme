<?php

/**
 * Login Form
 * Override WooCommerce My Account login page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Jeśli użytkownik jest zalogowany, nie pokazuj formularza
if (is_user_logged_in()) {
    return;
}

do_action('woocommerce_before_customer_login_form'); ?>

<div class="custom-login-page woocommerce-account">
    <div class="custom-login-container">
        <!-- Left Side - Image (visible only on desktop 1024+) -->
        <div class="custom-login-image">
            <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/login-bg.jpg" alt="<?php bloginfo('name'); ?>">
        </div>

        <!-- Right Side - Login/Register Form -->
        <div class="custom-login-form-wrapper">
            <!-- Toggle Buttons -->
            <div class="login-register-toggle">
                <button type="button" class="toggle-btn active" data-form="login">
                    <?php echo __('Logowanie', 'woocommerce'); ?>
                </button>
                <button type="button" class="toggle-btn" data-form="register">
                    <?php echo __('Rejestracja', 'woocommerce'); ?>
                </button>
            </div>

            <!-- Login Form -->
            <div class="custom-form-container active" id="login-form">
                <h2><?php esc_html_e('Witaj ponownie!', 'woocommerce'); ?></h2>
                <p class="form-description"><?php esc_html_e('Zaloguj się do swojego konta', 'woocommerce'); ?></p>

                <form class="woocommerce-form woocommerce-form-login login custom-login-form" method="post">

                    <?php do_action('woocommerce_login_form_start'); ?>

                    <div class="form-group">
                        <label for="username"><?php esc_html_e('Email lub nazwa użytkownika', 'woocommerce'); ?></label>
                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required />
                    </div>

                    <div class="form-group">
                        <label for="password"><?php esc_html_e('Hasło', 'woocommerce'); ?></label>
                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required />
                    </div>

                    <?php do_action('woocommerce_login_form'); ?>

                    <div class="form-group-inline">
                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme remember-me">
                            <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                            <span><?php esc_html_e('Zapamiętaj mnie', 'woocommerce'); ?></span>
                        </label>
                        <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="forgot-password">
                            <?php esc_html_e('Zapomniałeś hasła?', 'woocommerce'); ?>
                        </a>
                    </div>

                    <?php wp_nonce_field('woocommerce-login', 'woocommerce-login-nonce'); ?>
                    <button type="submit" class="woocommerce-button button woocommerce-form-login__submit submit-btn" name="login" value="<?php esc_attr_e('Zaloguj się', 'woocommerce'); ?>">
                        <?php esc_html_e('Zaloguj się', 'woocommerce'); ?>
                    </button>

                    <?php do_action('woocommerce_login_form_end'); ?>

                </form>
            </div>

            <!-- Register Form -->
            <div class="custom-form-container" id="register-form">
                <h2><?php esc_html_e('Utwórz konto', 'woocommerce'); ?></h2>
                <p class="form-description"><?php esc_html_e('Dołącz do nas i korzystaj z ekskluzywnych ofert', 'woocommerce'); ?></p>

                <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                    <p><?php esc_html_e('Wypełnij poniższe pola, aby założyć nowe konto.', 'woocommerce'); ?></p>
                <?php else : ?>
                    <p><?php esc_html_e('Wypełnij poniższe pola, aby założyć nowe konto. Automatycznie wygenerujemy nazwę użytkownika na podstawie Twojego adresu email.', 'woocommerce'); ?></p>
                <?php endif; ?>

                <form method="post" class="woocommerce-form woocommerce-form-register register custom-register-form" <?php do_action('woocommerce_register_form_tag'); ?>>

                    <?php do_action('woocommerce_register_form_start'); ?>

                    <?php if ('no' === get_option('woocommerce_registration_generate_username')) : ?>
                        <div class="form-group">
                            <label for="reg_username"><?php esc_html_e('Nazwa użytkownika', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo (!empty($_POST['username'])) ? esc_attr(wp_unslash($_POST['username'])) : ''; ?>" required />
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="reg_email"><?php esc_html_e('Adres email', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo (!empty($_POST['email'])) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>" required />
                    </div>

                    <?php if ('no' === get_option('woocommerce_registration_generate_password')) : ?>
                        <div class="form-group">
                            <label for="reg_password"><?php esc_html_e('Hasło', 'woocommerce'); ?>&nbsp;<span class="required">*</span></label>
                            <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required />
                        </div>
                    <?php else : ?>
                        <p><?php esc_html_e('Link do ustawienia hasła zostanie wysłany na Twój adres email.', 'woocommerce'); ?></p>
                    <?php endif; ?>

                    <?php do_action('woocommerce_register_form'); ?>

                    <div class="form-group-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required />
                            <span><?php echo sprintf(__('Akceptuję %sregulamin%s i %spolitykę prywatności%s', 'woocommerce'), '<a href="#">', '</a>', '<a href="#">', '</a>'); ?></span>
                        </label>
                    </div>

                    <?php wp_nonce_field('woocommerce-register', 'woocommerce-register-nonce'); ?>
                    <button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit submit-btn" name="register" value="<?php esc_attr_e('Zarejestruj się', 'woocommerce'); ?>">
                        <?php esc_html_e('Zarejestruj się', 'woocommerce'); ?>
                    </button>

                    <?php do_action('woocommerce_register_form_end'); ?>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    jQuery(document).ready(function($) {
        // Toggle between login and register forms
        $('.toggle-btn').on('click', function() {
            const formType = $(this).data('form');

            $('.toggle-btn').removeClass('active');
            $(this).addClass('active');

            $('.custom-form-container').removeClass('active');
            $('#' + formType + '-form').addClass('active');
        });
    });
</script>

<?php do_action('woocommerce_after_customer_login_form'); ?>