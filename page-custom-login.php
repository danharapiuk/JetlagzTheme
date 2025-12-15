<?php

/**
 * Template Name: Custom Login Page
 * Description: Custom login and registration page with image on left
 */

// Jeśli użytkownik jest już zalogowany, przekieruj do konta
if (is_user_logged_in()) {
    wp_redirect(wc_get_page_permalink('myaccount'));
    exit;
}

get_header();

// Enqueue login styles
wp_enqueue_style(
    'universal-login-page-styles',
    get_stylesheet_directory_uri() . '/assets/css/pages/login.css',
    array(),
    wp_get_theme()->get('Version')
);
?>

<div class="custom-login-page">
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
                    <?php echo __('Logowanie', 'universal-theme'); ?>
                </button>
                <button type="button" class="toggle-btn" data-form="register">
                    <?php echo __('Rejestracja', 'universal-theme'); ?>
                </button>
            </div>

            <!-- Logo -->
            <div class="custom-login-logo">
                <?php
                $custom_logo_id = get_theme_mod('custom_logo');
                if ($custom_logo_id) {
                    $logo = wp_get_attachment_image_src($custom_logo_id, 'full');
                    echo '<img src="' . esc_url($logo[0]) . '" alt="' . get_bloginfo('name') . '">';
                } else {
                    echo '<h1>' . get_bloginfo('name') . '</h1>';
                }
                ?>
            </div>

            <!-- Login Form -->
            <div class="custom-form-container active" id="login-form">
                <h2><?php echo __('Witaj ponownie!', 'universal-theme'); ?></h2>
                <p class="form-description"><?php echo __('Zaloguj się do swojego konta', 'universal-theme'); ?></p>

                <form method="post" class="custom-login-form" id="loginform">
                    <?php wp_nonce_field('custom_login_action', 'custom_login_nonce'); ?>

                    <div class="form-group">
                        <label for="login_username"><?php echo __('Email lub nazwa użytkownika', 'universal-theme'); ?></label>
                        <input type="text" name="username" id="login_username" required>
                    </div>

                    <div class="form-group">
                        <label for="login_password"><?php echo __('Hasło', 'universal-theme'); ?></label>
                        <input type="password" name="password" id="login_password" required>
                    </div>

                    <div class="form-group-inline">
                        <label class="remember-me">
                            <input type="checkbox" name="rememberme" value="forever">
                            <span><?php echo __('Zapamiętaj mnie', 'universal-theme'); ?></span>
                        </label>
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="forgot-password">
                            <?php echo __('Zapomniałeś hasła?', 'universal-theme'); ?>
                        </a>
                    </div>

                    <button type="submit" name="login" class="submit-btn">
                        <?php echo __('Zaloguj się', 'universal-theme'); ?>
                    </button>

                    <div class="form-messages"></div>
                </form>
            </div>

            <!-- Register Form -->
            <div class="custom-form-container" id="register-form">
                <h2><?php echo __('Utwórz konto', 'universal-theme'); ?></h2>
                <p class="form-description"><?php echo __('Dołącz do nas i korzystaj z ekskluzywnych ofert', 'universal-theme'); ?></p>

                <form method="post" class="custom-register-form" id="registerform">
                    <?php wp_nonce_field('custom_register_action', 'custom_register_nonce'); ?>

                    <div class="form-group">
                        <label for="reg_email"><?php echo __('Adres email', 'universal-theme'); ?></label>
                        <input type="email" name="email" id="reg_email" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_username"><?php echo __('Nazwa użytkownika', 'universal-theme'); ?></label>
                        <input type="text" name="username" id="reg_username" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_password"><?php echo __('Hasło', 'universal-theme'); ?></label>
                        <input type="password" name="password" id="reg_password" required>
                    </div>

                    <div class="form-group">
                        <label for="reg_password2"><?php echo __('Powtórz hasło', 'universal-theme'); ?></label>
                        <input type="password" name="password2" id="reg_password2" required>
                    </div>

                    <div class="form-group-checkbox">
                        <label class="checkbox-label">
                            <input type="checkbox" name="terms" required>
                            <span><?php echo __('Akceptuję', 'universal-theme'); ?> <a href="#"><?php echo __('regulamin', 'universal-theme'); ?></a> <?php echo __('i', 'universal-theme'); ?> <a href="#"><?php echo __('politykę prywatności', 'universal-theme'); ?></a></span>
                        </label>
                    </div>

                    <button type="submit" name="register" class="submit-btn">
                        <?php echo __('Zarejestruj się', 'universal-theme'); ?>
                    </button>

                    <div class="form-messages"></div>
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

            // Clear messages
            $('.form-messages').hide().removeClass('success error');
        });

        // Handle Login Form Submission
        $('#loginform').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $messages = $form.find('.form-messages');
            const $submitBtn = $form.find('.submit-btn');

            $submitBtn.prop('disabled', true).text('<?php echo __('Logowanie...', 'universal-theme'); ?>');
            $messages.hide();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: $form.serialize() + '&action=custom_login',
                success: function(response) {
                    if (response.success) {
                        $messages.removeClass('error').addClass('success').text(response.data.message).show();
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 1000);
                    } else {
                        $messages.removeClass('success').addClass('error').text(response.data.message).show();
                        $submitBtn.prop('disabled', false).text('<?php echo __('Zaloguj się', 'universal-theme'); ?>');
                    }
                }
            });
        });

        // Handle Register Form Submission
        $('#registerform').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $messages = $form.find('.form-messages');
            const $submitBtn = $form.find('.submit-btn');

            // Check if passwords match
            const password = $('#reg_password').val();
            const password2 = $('#reg_password2').val();

            if (password !== password2) {
                $messages.removeClass('success').addClass('error').text('<?php echo __('Hasła nie są zgodne', 'universal-theme'); ?>').show();
                return;
            }

            $submitBtn.prop('disabled', true).text('<?php echo __('Rejestracja...', 'universal-theme'); ?>');
            $messages.hide();

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: $form.serialize() + '&action=custom_register',
                success: function(response) {
                    if (response.success) {
                        $messages.removeClass('error').addClass('success').text(response.data.message).show();
                        setTimeout(function() {
                            window.location.href = response.data.redirect;
                        }, 2000);
                    } else {
                        $messages.removeClass('success').addClass('error').text(response.data.message).show();
                        $submitBtn.prop('disabled', false).text('<?php echo __('Zarejestruj się', 'universal-theme'); ?>');
                    }
                }
            });
        });
    });
</script>

<?php
get_footer();
?>