<?php

/**
 * Simple Checkout Layout Fix
 * 
 * Napraw kolejność kolumn checkout - Order Summary po lewej, Contact Info po prawej
 *
 * @package Universal_Theme
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AGRESYWNE FORCING CUSTOM CHECKOUT TEMPLATE
 */
function universal_simple_checkout_layout_fix()
{
    if (!is_checkout()) {
        return;
    }

    // Dodaj debug banner żeby sprawdzić czy CSS się ładuje
?>
    <div style="position:fixed;top:40px;left:0;background:blue;color:white;padding:10px;z-index:99999;font-weight:bold;">
        CSS CHECKOUT HOOKS ACTIVE!
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔥 FORCING CHECKOUT LAYOUT!');

            // Znajdź każdą możliwą strukturę checkout i przestaw
            setTimeout(function() {

                // Sprawdź czy to WooCommerce Blocks
                const blocksCheckout = document.querySelector('.wc-block-checkout');
                if (blocksCheckout) {
                    console.log('📦 WooCommerce Blocks wykryty - przekierowuję...');
                    // Przeładuj stronę z parametrem wymuszającym classic checkout
                    if (!window.location.search.includes('classic=1')) {
                        window.location.href = window.location.href + '?classic=1';
                        return;
                    }
                }

                // Sprawdź czy to shortcode checkout
                const shortcodeForm = document.querySelector('form.checkout.woocommerce-checkout');
                if (shortcodeForm) {
                    console.log('📋 Classic WooCommerce shortcode wykryty');

                    const customerDetails = document.querySelector('#customer_details');
                    const orderReview = document.querySelector('#order_review');

                    if (customerDetails && orderReview) {
                        console.log('🔄 Przestawiam elementy...');

                        // Stwórz nowy kontener
                        const newLayout = document.createElement('div');
                        newLayout.className = 'universal-forced-layout';
                        newLayout.style.cssText = `
                        display: flex !important;
                        gap: 2rem !important;
                        max-width: 1200px !important;
                        margin: 0 auto !important;
                    `;

                        // Przenieś order review do lewej kolumny
                        const leftCol = document.createElement('div');
                        leftCol.style.cssText = `
                        flex: 0 0 400px !important;
                        background: #f8f9fa !important;
                        border-radius: 12px !important;
                        padding: 2rem !important;
                        order: 1 !important;
                    `;
                        leftCol.appendChild(orderReview.cloneNode(true));

                        // Przenieś customer details do prawej kolumny
                        const rightCol = document.createElement('div');
                        rightCol.style.cssText = `
                        flex: 1 !important;
                        background: white !important;
                        border-radius: 12px !important;
                        padding: 2rem !important;
                        order: 2 !important;
                    `;
                        rightCol.appendChild(customerDetails.cloneNode(true));

                        // Dodaj do nowego layoutu
                        newLayout.appendChild(leftCol);
                        newLayout.appendChild(rightCol);

                        // Zastąp oryginalny form
                        const parent = shortcodeForm.parentNode;
                        parent.insertBefore(newLayout, shortcodeForm);
                        shortcodeForm.style.display = 'none';

                        console.log('✅ LAYOUT ZOSTAŁ ZMIENIONY!');
                    }
                }

            }, 100);
        });
    </script>
    <?php
    if (!is_checkout()) {
        return;
    }
    ?>
    <style id="universal-checkout-fix">
        /* === CHECKOUT LAYOUT FIX === */

        /* Ukryj coupon toggle */
        .woocommerce-form-coupon-toggle,
        .checkout_coupon,
        .woocommerce-form-coupon {
            display: none !important;
        }

        /* Podstawowe stylowanie checkout */
        .woocommerce-checkout {
            font-family: inherit;
            line-height: 1.6;
        }

        .woocommerce-checkout h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }

        .woocommerce-checkout .form-row {
            margin-bottom: 1.5rem;
        }

        .woocommerce-checkout label {
            font-weight: 500;
            color: #555;
            margin-bottom: 0.5rem;
            display: block;
        }

        .woocommerce-checkout input[type="text"],
        .woocommerce-checkout input[type="email"],
        .woocommerce-checkout input[type="tel"],
        .woocommerce-checkout select,
        .woocommerce-checkout textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .woocommerce-checkout input:focus,
        .woocommerce-checkout select:focus,
        .woocommerce-checkout textarea:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
        }

        .woocommerce-checkout .required {
            color: #e74c3c;
        }

        /* Order review styling */
        #order_review {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .shop_table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .shop_table th,
        .shop_table td {
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .shop_table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .shop_table .product-name {
            font-weight: 500;
        }

        .shop_table .product-total {
            text-align: right;
            font-weight: 600;
        }

        .order-total {
            font-size: 1.2rem;
            font-weight: 700;
            background: #007cba !important;
            color: white !important;
        }

        .order-total th,
        .order-total td {
            border-bottom: none;
            font-size: 1.2rem;
        }

        /* Place order button */
        #place_order {
            background: #007cba;
            color: white;
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s ease;
        }

        #place_order:hover {
            background: #005a8b;
        }

        #place_order:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Checkbox styling */
        .woocommerce-checkout input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }

        /* Payment methods */
        .wc_payment_methods {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #ddd;
        }

        .wc_payment_method {
            margin-bottom: 1rem;
        }

        .wc_payment_method label {
            font-weight: 500;
            cursor: pointer;
        }

        @media (min-width: 1024px) {

            /* Target ALL possible WooCommerce checkout layouts */
            .wc-block-components-sidebar-layout.wc-block-checkout.is-large,
            .woocommerce-checkout .col2-set,
            .checkout.woocommerce-checkout,
            .wc-block-checkout {
                display: grid !important;
                grid-template-columns: 400px 1fr !important;
                grid-template-areas: "sidebar main" !important;
                gap: 2rem !important;
                max-width: 1200px !important;
                margin: 0 auto !important;
            }

            /* Order Summary (po lewej) - wszystkie możliwe selektory */
            .wc-block-components-sidebar-layout.wc-block-checkout.is-large .wc-block-components-sidebar,
            .woocommerce-checkout #order_review,
            .checkout #order_review,
            .wc-block-checkout .wc-block-components-sidebar,
            #order_review_heading+#order_review {
                grid-area: sidebar !important;
                order: 1 !important;
                background: #f8f9fa !important;
                border-radius: 12px !important;
                padding: 2rem !important;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
            }

            /* Contact Information Form (po prawej) - wszystkie możliwe selektory */
            .wc-block-components-sidebar-layout.wc-block-checkout.is-large .wc-block-components-main,
            .woocommerce-checkout #customer_details,
            .checkout #customer_details,
            .wc-block-checkout .wc-block-components-main,
            .col2-set {
                grid-area: main !important;
                order: 2 !important;
                background: white !important;
                border-radius: 12px !important;
                padding: 2rem !important;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
            }

            /* Customer details columns */
            .col2-set .col-1,
            .col2-set .col-2 {
                width: 100% !important;
                float: none !important;
                margin-bottom: 2rem !important;
            }

            /* Force layout for classic WooCommerce checkout */
            .woocommerce-checkout form.checkout {
                display: grid !important;
                grid-template-columns: 400px 1fr !important;
                grid-template-areas: "sidebar main" !important;
                gap: 2rem !important;
                max-width: 1200px !important;
                margin: 0 auto !important;
            }

            .woocommerce-checkout #customer_details {
                grid-area: main !important;
                order: 2 !important;
            }

            .woocommerce-checkout #order_review,
            .woocommerce-checkout .woocommerce-checkout-review-order {
                grid-area: sidebar !important;
                order: 1 !important;
            }

            /* Ukryj overflow issues */
            .wc-block-components-sidebar-layout.wc-block-checkout.is-large,
            .woocommerce-checkout,
            .checkout {
                overflow: visible !important;
            }

            /* Anuluj wszystkie flex properties */
            .wc-block-components-sidebar-layout.wc-block-checkout.is-large,
            .woocommerce-checkout .col2-set {
                flex-direction: unset !important;
                flex-wrap: unset !important;
                align-items: unset !important;
                justify-content: unset !important;
            }

            .wc-block-components-sidebar,
            .wc-block-components-main,
            #customer_details,
            #order_review {
                flex: unset !important;
                flex-basis: unset !important;
                flex-grow: unset !important;
                flex-shrink: unset !important;
                width: auto !important;
                max-width: unset !important;
                min-width: unset !important;
            }

            /* Visual debug - shows when ANY checkout is detected */
            /* Debug wyłączony - layout działa poprawnie */
            /*
            body.woocommerce-checkout::before,
            .wc-block-checkout::before {
                content: "ORDER LEFT, FORM RIGHT - CSS ACTIVE" !important;
                position: fixed !important;
                top: 10px !important;
                left: 10px !important;
                background: #10b981 !important;
                color: white !important;
                padding: 4px 8px !important;
                font-size: 11px !important;
                border-radius: 4px !important;
                z-index: 99999 !important;
                font-weight: bold !important;
            }
            */
        }

        /* Mobile - zachowaj normalny layout */
        @media (max-width: 1023px) {
            .wc-block-components-sidebar-layout.wc-block-checkout {
                display: block !important;
                grid-template-columns: unset !important;
                grid-template-areas: unset !important;
            }

            .wc-block-components-sidebar,
            .wc-block-components-main {
                grid-area: unset !important;
                order: unset !important;
                background: unset !important;
                border-radius: unset !important;
                padding: unset !important;
                box-shadow: unset !important;
            }
        }
    </style>
<?php
}
add_action('wp_head', 'universal_simple_checkout_layout_fix', 999); // NOWY SYSTEM AKTYWNY

/**
 * JavaScript backup fix - WYŁĄCZONE
 */
function universal_checkout_js_fix()
{
    // Nowy system obsługuje layout - ten JavaScript nie jest potrzebny
    return;
?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (window.innerWidth >= 1024) {
                    console.log('🔍 Sprawdzam strukturę checkout...');

                    // WooCommerce Blocks
                    const blocksCheckout = document.querySelector('.wc-block-components-sidebar-layout.wc-block-checkout.is-large');
                    if (blocksCheckout) {
                        console.log('📦 Znaleziono WooCommerce Blocks checkout');
                        blocksCheckout.style.setProperty('display', 'grid', 'important');
                        blocksCheckout.style.setProperty('grid-template-columns', '400px 1fr', 'important');
                        blocksCheckout.style.setProperty('grid-template-areas', '"sidebar main"', 'important');
                    }

                    // Classic WooCommerce checkout  
                    const classicCheckout = document.querySelector('form.checkout');
                    if (classicCheckout) {
                        console.log('📦 Znaleziono Classic WooCommerce checkout');
                        console.log('📋 Struktura checkout:', classicCheckout);

                        // Najpierw sprawdź co mamy w środku
                        const customerDetails = document.querySelector('#customer_details');
                        const orderReview = document.querySelector('#order_review, .woocommerce-checkout-review-order');

                        console.log('🧐 Customer details:', customerDetails);
                        console.log('🧐 Order review:', orderReview);

                        if (customerDetails && orderReview) {
                            // Sprawdź obecną pozycję
                            const customerRect = customerDetails.getBoundingClientRect();
                            const orderRect = orderReview.getBoundingClientRect();

                            console.log('📍 Customer details pozycja:', customerRect.left, customerRect.top);
                            console.log('📍 Order review pozycja:', orderRect.left, orderRect.top);

                            // Zastosuj CSS Grid z bardzo agresywnymi ustawieniami
                            classicCheckout.style.setProperty('display', 'grid', 'important');
                            classicCheckout.style.setProperty('grid-template-columns', '400px 1fr', 'important');
                            classicCheckout.style.setProperty('grid-template-areas', '"sidebar main"', 'important');
                            classicCheckout.style.setProperty('gap', '2rem', 'important');
                            classicCheckout.style.setProperty('width', '100%', 'important');
                            classicCheckout.style.setProperty('max-width', '1200px', 'important');
                            classicCheckout.style.setProperty('margin', '0 auto', 'important');

                            // Przypisz elementy do obszarów
                            customerDetails.style.setProperty('grid-area', 'main', 'important');
                            orderReview.style.setProperty('grid-area', 'sidebar', 'important');

                            console.log('✅ Customer details -> main area (formularz po PRAWEJ)');
                            console.log('✅ Order review -> sidebar area (order po LEWEJ)'); // Sprawdź czy się zmieniło po 500ms
                            setTimeout(function() {
                                const newCustomerRect = customerDetails.getBoundingClientRect();
                                const newOrderRect = orderReview.getBoundingClientRect();

                                console.log('📍 NOWA Customer details pozycja:', newCustomerRect.left, newCustomerRect.top);
                                console.log('📍 NOWA Order review pozycja:', newOrderRect.left, newOrderRect.top);

                                if (newOrderRect.left < newCustomerRect.left) {
                                    console.log('✅ SUCCESS: Order po lewej, Customer details po prawej!');
                                } else {
                                    console.log('❌ PROBLEM: Kolejność nadal zła');
                                }
                            }, 500);
                        }
                    }

                    // Sprawdź czy któryś layout zadziałał
                    setTimeout(function() {
                        const anyCheckout = document.querySelector('body.woocommerce-checkout, .wc-block-checkout');
                        if (anyCheckout) {
                            console.log('✅ Checkout layout aktywny!');
                        }
                    }, 200);
                }
            }, 100);
        });
    </script>
<?php
}
// STARY SYSTEM WYŁĄCZONY
// add_action('wp_footer', 'universal_checkout_js_fix');
