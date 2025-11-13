/**
* Dynamic Theme Colors CSS
* Kolory motywu ustawiane z panelu administracyjnego
*/

<?php
// Pobierz opcje motywu
$primary_color = get_universal_theme_option('primary_color', '#e74c3c');
$secondary_color = get_universal_theme_option('secondary_color', '#3498db');
$accent_color = get_universal_theme_option('accent_color', '#f39c12');
$header_overlay_opacity = get_universal_theme_option('header_overlay_opacity', 30);

// Header background
$header_bg = get_universal_theme_option('header_background_image');
$footer_bg = get_universal_theme_option('footer_background_image');

header('Content-Type: text/css');
?>

/* ===== KOLORY GŁÓWNE ===== */
:root {
--primary-color: <?php echo esc_attr($primary_color); ?>;
--secondary-color: <?php echo esc_attr($secondary_color); ?>;
--accent-color: <?php echo esc_attr($accent_color); ?>;
--header-overlay-opacity: <?php echo esc_attr($header_overlay_opacity / 100); ?>;
}

/* Kolory główne */
.button.primary,
.woocommerce .button.primary,
.woocommerce-page .button.primary,
.woocommerce button.button.primary,
.woocommerce input.button.primary,
#submit,
.wc-block-cart__submit-button,
.wc-block-components-checkout-place-order-button {
background-color: var(--primary-color) !important;
border-color: var(--primary-color) !important;
}

.button.primary:hover,
.woocommerce .button.primary:hover,
.woocommerce-page .button.primary:hover,
.woocommerce button.button.primary:hover,
.woocommerce input.button.primary:hover,
#submit:hover,
.wc-block-cart__submit-button:hover,
.wc-block-components-checkout-place-order-button:hover {
background-color: var(--secondary-color) !important;
border-color: var(--secondary-color) !important;
}

/* Linki i akcenty */
a,
.site-title a,
.main-navigation ul.nav-menu ul li a,
.woocommerce .star-rating span:before,
.woocommerce p.stars a:hover,
.price .amount,
.woocommerce .price .amount,
.woocommerce-Price-amount {
color: var(--primary-color) !important;
}

a:hover,
.site-title a:hover,
.main-navigation ul.nav-menu ul li a:hover {
color: var(--secondary-color) !important;
}

/* Przyciski akcentujące */
.woocommerce .button.alt,
.woocommerce button.button.alt,
.woocommerce input.button.alt,
.woocommerce #respond input#submit.alt,
.woocommerce a.button.alt,
.woocommerce-page .button.alt,
.woocommerce-page button.button.alt,
.woocommerce-page input.button.alt,
.woocommerce-page #respond input#submit.alt,
.woocommerce-page a.button.alt,
.cross-sell-item .add-to-cart-btn {
background-color: var(--accent-color) !important;
border-color: var(--accent-color) !important;
color: #ffffff !important;
}

.woocommerce .button.alt:hover,
.woocommerce button.button.alt:hover,
.woocommerce input.button.alt:hover,
.woocommerce #respond input#submit.alt:hover,
.woocommerce a.button.alt:hover,
.woocommerce-page .button.alt:hover,
.woocommerce-page button.button.alt:hover,
.woocommerce-page input.button.alt:hover,
.woocommerce-page #respond input#submit.alt:hover,
.woocommerce-page a.button.alt:hover,
.cross-sell-item .add-to-cart-btn:hover {
background-color: var(--primary-color) !important;
border-color: var(--primary-color) !important;
}

/* Ceny i wyróżnienia */
.woocommerce ins .amount,
.woocommerce .price ins .amount,
.sale-price,
.cross-sell-item .price,
.woocommerce del,
.woocommerce del .amount {
color: var(--accent-color) !important;
font-weight: bold;
}

/* Header styles */
<?php if ($header_bg): ?>
    .site-header {
    background-image: url('<?php echo esc_url($header_bg); ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
    }

    .site-header:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, var(--header-overlay-opacity));
    z-index: 1;
    }

    .site-header * {
    position: relative;
    z-index: 2;
    }
<?php endif; ?>

/* Footer styles */
<?php if ($footer_bg): ?>
    .site-footer {
    background-image: url('<?php echo esc_url($footer_bg); ?>');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    position: relative;
    }

    .site-footer:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1;
    }

    .site-footer * {
    position: relative;
    z-index: 2;
    color: #ffffff !important;
    }
<?php endif; ?>

/* Cross-sell specjalne kolory */
.cross-sell-section {
border-top: 3px solid var(--primary-color);
}

.cross-sell-section h3 {
color: var(--primary-color) !important;
border-bottom: 2px solid var(--accent-color);
padding-bottom: 10px;
}

.cross-sell-item {
border: 1px solid #e0e0e0;
transition: all 0.3s ease;
}

.cross-sell-item:hover {
border-color: var(--primary-color);
box-shadow: 0 4px 12px rgba(0,0,0,0.1);
transform: translateY(-2px);
}

/* Logo customizations */
.custom-logo {
max-height: 60px;
width: auto;
}

@media (max-width: 768px) {
.custom-logo {
max-height: 40px;
}
}

/* Checkout specjalne style */
.woocommerce-checkout .woocommerce-info,
.woocommerce-checkout .woocommerce-message {
background-color: rgba(<?php
                        list($r, $g, $b) = sscanf($primary_color, "#%02x%02x%02x");
                        echo "$r, $g, $b";
                        ?>, 0.1);
border-left-color: var(--primary-color);
}

/* WooCommerce Blocks styling */
.wc-block-components-button:not(.is-link) {
background-color: var(--primary-color) !important;
border-color: var(--primary-color) !important;
}

.wc-block-components-button:not(.is-link):hover {
background-color: var(--secondary-color) !important;
border-color: var(--secondary-color) !important;
}

.wc-block-components-radio-control__input:checked::before {
background-color: var(--primary-color) !important;
}

.wc-block-components-checkbox__input:checked {
background-color: var(--primary-color) !important;
border-color: var(--primary-color) !important;
}

/* Progress bar dla darmowej wysyłki */
.free-shipping-progress {
background-color: #f0f0f0;
border-radius: 10px;
overflow: hidden;
margin: 10px 0;
}

.free-shipping-progress-bar {
background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
height: 8px;
transition: width 0.5s ease;
}

/* Animacje i efekty */
@keyframes pulseAccent {
0% { background-color: var(--accent-color); }
50% { background-color: var(--primary-color); }
100% { background-color: var(--accent-color); }
}

.highlight-success {
animation: pulseAccent 2s ease-in-out;
}

/* Responsive design */
@media (max-width: 768px) {
.cross-sell-item {
margin-bottom: 15px;
}

.cross-sell-item .add-to-cart-btn {
width: 100%;
font-size: 14px;
padding: 8px 12px;
}
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
.cross-sell-item {
background-color: #2a2a2a;
color: #ffffff;
}

.cross-sell-item .price {
color: var(--accent-color) !important;
}
}