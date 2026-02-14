<?php

/**
 * Thankyou page
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou.php.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.1.0
 *
 * @var WC_Order $order
 */

defined('ABSPATH') || exit;
?>

<div class="woocommerce-order thankyou-page-custom font-inter">

    <?php
    if ($order) :

        do_action('woocommerce_before_thankyou', $order->get_id());

        $customer_first_name = $order->get_billing_first_name();
        $customer_email = $order->get_billing_email();
        $order_number = $order->get_order_number();

        // Pobierz punkty z WPLoyalty
        $loyalty_points = 0;
        $user_id = $order->get_user_id();

        // Sprawdź czy WPLoyalty jest aktywne
        if (class_exists('\Wlr\App\Helpers\EarnCampign')) {
            // Pobierz earn campaign helper
            $earn_campaign_helper = \Wlr\App\Helpers\EarnCampign::getInstance();

            // Pobierz punkty za to zamówienie
            if (method_exists($earn_campaign_helper, 'getPointsFromOrder')) {
                $points_data = $earn_campaign_helper->getPointsFromOrder($order);
                if (isset($points_data['earn_point'])) {
                    $loyalty_points = $points_data['earn_point'];
                }
            }
        }

        // Fallback - jeśli WPLoyalty nie zwróciło punktów, użyj prostego obliczenia
        if ($loyalty_points == 0) {
            $loyalty_points = floor($order->get_subtotal());
        }

        // Funkcja do odmieniania słowa "punkt"
        function decline_points($number)
        {
            if ($number == 1) {
                return 'punkt';
            } elseif ($number % 10 >= 2 && $number % 10 <= 4 && ($number % 100 < 10 || $number % 100 >= 20)) {
                return 'punkty';
            } else {
                return 'punktów';
            }
        }
    ?>

        <?php if ($order->has_status('failed') || $order->needs_payment()) : ?>

            <!-- PŁATNOŚĆ NIEUDANA / OCZEKUJĄCA -->
            <div class="thankyou-payment-failed font-inter">
                <div class="payment-failed-hero">
                    <svg width="60" height="60" viewBox="0 0 100 100" fill="none" xmlns="https://www.w3.org/2000/svg">
                        <circle cx="50" cy="50" r="48" stroke="#f59e0b" stroke-width="4" fill="none" />
                        <path d="M50 30v25M50 65v5" stroke="#f59e0b" stroke-width="4" stroke-linecap="round" />
                    </svg>

                    <h1 class="payment-failed-title py-2">Twoje zamówienie nie zostało opłacone</h1>

                    <p class="payment-failed-subtitle pb-4">
                        Zamówienie nr <strong><?php echo esc_html($order_number); ?></strong> zostało utworzone, ale aby je zrealizować musisz je opłacić.
                    </p>
                </div>

                <div class="payment-failed-details">
                    <h3>Szczegóły zamówienia:</h3>
                    <div class="order-summary">
                        <div class="summary-row">
                            <span>Kwota do zapłaty:</span>
                            <strong><?php echo $order->get_formatted_order_total(); ?></strong>
                        </div>
                        <div class="summary-row">
                            <span>Email:</span>
                            <span><?php echo esc_html($customer_email); ?></span>
                        </div>
                    </div>

                    <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="button-primary">
                        Opłać teraz
                    </a>

                    <p class="payment-help-text pt-6">
                        Masz problem z płatnością? <a href="<?php echo esc_url(home_url('/kontakt')); ?>">Skontaktuj się z nami</a>
                    </p>
                </div>
            </div>

        <?php else : ?>

            <!-- THANK YOU HERO -->
            <div class="thankyou-hero font-inter">
                <h1 class="thankyou-title">THANK YOU!</h1>

                <h2 class="thankyou-subtitle">
                    <?php echo $customer_first_name ? esc_html($customer_first_name) . ', d' : 'D'; ?>ziękujemy za zakupy!
                </h2>

                <p class="thankyou-confirmation">
                    Potwierdzenie zamówienia nr <strong><?php echo esc_html($order_number); ?></strong> wysłaliśmy na <strong><?php echo esc_html($customer_email); ?></strong>
                </p>

                <?php if (isset($_GET['account_created']) && $_GET['account_created'] == '1') : ?>
                    <div class="account-created-notice bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
                        <strong class="font-bold">Konto utworzone!</strong>
                        <span class="block sm:inline"> Zostałeś automatycznie zalogowany. Hasło wysłaliśmy na Twój email.</span>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['account_error'])) : ?>
                    <div class="account-error-notice bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                        <?php if ($_GET['account_error'] == 'exists') : ?>
                            <span class="block sm:inline">Konto z tym adresem email już istnieje. <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>" class="underline">Zaloguj się</a></span>
                        <?php else : ?>
                            <span class="block sm:inline">Wystąpił błąd podczas tworzenia konta. Spróbuj ponownie.</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sm:flex sm:gap-2 justify-between">
                <!-- ZAMÓWIONE PRODUKTY -->
                <div class="thankyou-products-section">
                    <p class="products-section-title mb-1 sm:mb-5">Zamówione produkty:</p>

                    <div class="thankyou-products-grid">
                        <?php
                        foreach ($order->get_items() as $item_id => $item) :
                            $product = $item->get_product();
                            if (!$product) continue;

                            $product_name = $item->get_name();
                            $image_id = $product->get_image_id();
                            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : wc_placeholder_img_src();

                            // Truncate title at dash
                            $display_name = html_entity_decode($product_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            if (strpos($display_name, '–') !== false) {
                                $title_parts = explode('–', $display_name, 2);
                                $display_name = trim($title_parts[0]);
                            }
                        ?>
                            <div class="thankyou-product-card">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($product_name); ?>">
                                <p class="product-card-name"><?php echo esc_html($display_name); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="h-auto w-[1px] bg-black bg-opacity-30"></div>

                <!-- CO DALEJ -->
                <div class="thankyou-steps">
                    <p class="products-section-title mb-1 mt-4 sm:mt-0 sm:mb-5">
                        Dalsze kroki <span class="arrow">→</span>
                    </p>
                    <div class="step-item">
                        <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/fullcheck.svg'); ?>" alt="Step 1 Icon">
                        <p>Właśnie rozpoczęliśmy realizację Twojego zamówienia</p>
                    </div>
                    <div class="step-item">
                        <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/emptycheck.svg'); ?>" alt="Step 1 Icon">
                        <p>Paczka zostanie wysłana w ciągu 24h!</p>
                    </div>
                    <div class="step-item">
                        <img src="<?php echo esc_url(get_stylesheet_directory_uri() . '/assets/images/emptycheck.svg'); ?>" alt="Step 1 Icon">
                        <p>Gdy to nastąpi wyślemy Ci potwierdzenie na maila!</p>
                    </div>
                </div>
            </div>

            <!-- PROGRAM LOJALNOŚCIOWY -->
            <div class="thankyou-loyalty bg-[#CD0568] bg-opacity-[3%] border border-[#CD0568] rounded-lg p-6 mt-10 font-inter">

                <?php
                // Check if we're forcing guest mode for preview
                global $preview_force_guest;
                $is_logged_in = $preview_force_guest ? false : is_user_logged_in();

                if ($is_logged_in) :
                ?>
                    <!-- ZALOGOWANY UŻYTKOWNIK -->
                    <h3 class="loyalty-title">Do tego zakupu otrzymujesz <?php echo esc_html($loyalty_points); ?> <?php echo decline_points($loyalty_points); ?> w naszym programie lojalnościowym!</h3>

                    <a href="<?php echo esc_url(home_url('/my-account/loyalty_reward/')); ?>" class="button loyalty-button-primary mt-4">
                        Sprawdź swoje punkty
                    </a>

                <?php else : ?>
                    <!-- NIEZALOGOWANY UŻYTKOWNIK -->
                    <h3 class="loyalty-title">Do tego zakupu otrzymałabyś <?php echo esc_html($loyalty_points); ?> <?php echo decline_points($loyalty_points); ?> w naszym programie lojalnościowym!</h3>

                    <ul class="loyalty-benefits">
                        <li>Zebrane punkty to oszczędności na kolejne zakupy.</li>
                        <li>Zakładając konto teraz otrzymasz punkty z tej transakcji</li>
                        <li>Założenie konta trwa 15 sekund</li>
                    </ul>

                    <div class="loyalty-actions flex gap-4 items-center justify-center">
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="loyalty-account-form">
                            <input type="hidden" name="action" value="create_account_from_order">
                            <input type="hidden" name="order_id" value="<?php echo esc_attr($order->get_id()); ?>">
                            <?php wp_nonce_field('create_account_from_order', 'account_nonce'); ?>

                            <button type="submit" class="button loyalty-button-primary">
                                Załóż konto
                            </button>
                        </form>

                        <a href="<?php echo esc_url(add_query_arg(array('order_id' => $order->get_id(), 'redirect_to' => $order->get_checkout_order_received_url()), wc_get_page_permalink('myaccount'))); ?>" class="loyalty-link-secondary">
                            lub zaloguj się
                        </a>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

    <?php else : ?>

        <?php wc_get_template('checkout/order-received.php', array('order' => false)); ?>

    <?php endif; ?>

</div>