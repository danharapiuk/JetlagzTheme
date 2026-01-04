<?php

/**
 * Checkout Layout Hooks - Contact Info LEFT, Order Summary RIGHT (ORYGINALNY UKŁAD)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dodaj CSS dla checkout layout z grid - ORYGINALNY UKŁAD
 */
function universal_checkout_layout_css()
{
    if (!is_checkout()) {
        return;
    }
?>
    <style id="universal-checkout-layout">
        /* CSS Grid dla checkout - Contact Info po lewej, Order Summary po prawej */
        @media (min-width: 1024px) {

            /* Główny kontener WC Blocks */
            .wc-block-checkout {
                display: flex !important;
                flex-direction: column !important;
                max-width: 1200px !important;
                margin: 0 auto !important;
            }

            /* Cross-sell nad głównym checkout */
            .wc-block-checkout .custom-crosssell-added {
                order: -10 !important;
                align-self: stretch !important;
                width: 100% !important;
                margin: 0 0 30px 0 !important;
                background: white !important;
                border-radius: 12px !important;
                padding: 0 !important;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
                position: relative !important;
                z-index: 10 !important;
                overflow: hidden !important;
            }

            /* Pasek postępu darmowej wysyłki - USUNIĘTY, używamy oryginalnego */

            /* Treść cross-sell */
            .wc-block-checkout .custom-crosssell-added .wc-block-components-totals-wrapper {
                padding: 20px !important;
                background: transparent !important;
            }

            /* Cross-sell produkty w flex-wrap */
            .wc-block-checkout .custom-crosssell-added .crosssell-products {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 20px !important;
                justify-content: flex-start !important;
                padding: 0 !important;
            }

            .wc-block-checkout .custom-crosssell-added .wc-block-components-totals-wrapper>div {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 20px !important;
                justify-content: flex-start !important;
                padding: 0 !important;
            }

            /* Każdy produkt */
            .wc-block-checkout .custom-crosssell-added .crosssell-product,
            .wc-block-checkout .custom-crosssell-added .wc-block-components-totals-wrapper>div>div {
                flex: 0 0 calc(50% - 10px) !important;
                min-width: 200px !important;
                max-width: 300px !important;
                background: #f8f9fa !important;
                border-radius: 8px !important;
                padding: 15px !important;
                border: 1px solid #e9ecef !important;
                transition: transform 0.2s ease, box-shadow 0.2s ease !important;
            }

            /* LAYOUT GRID - Contact Info po lewej, Order Summary po prawej (ORYGINALNY) */
            .wc-block-checkout .wc-block-components-sidebar-layout.is-large {
                order: 1 !important;
                display: grid !important;
                grid-template-columns: 1fr 400px !important;
                grid-template-areas: "main sidebar" !important;
                gap: 40px !important;
            }

            /* Order Summary po prawej (400px szerokości) */
            .wc-block-components-sidebar-layout.is-large .wc-block-components-sidebar {
                grid-area: sidebar !important;
                position: sticky !important;
                top: 20px !important;
                height: fit-content !important;
                width: 100% !important;
            }

            /* Contact Information po lewej (elastyczna szerokość) */
            .wc-block-components-sidebar-layout.is-large .wc-block-components-main {
                grid-area: main !important;
                width: 100% !important;
            }

            /* Fallback dla classic checkout - Contact Info po lewej, Order Summary po prawej */
            .woocommerce-checkout form.checkout {
                display: grid !important;
                grid-template-columns: 1fr 400px !important;
                grid-template-areas: "main sidebar" !important;
                gap: 40px !important;
                max-width: 1200px !important;
                margin: 0 auto !important;
            }

            .woocommerce-checkout #order_review {
                grid-area: sidebar !important;
                position: sticky !important;
                top: 20px !important;
                height: fit-content !important;
            }

            .woocommerce-checkout #customer_details {
                grid-area: main !important;
            }

            .woocommerce-checkout .woocommerce-checkout-review-order {
                background: #f8f9fa !important;
                border: 1px solid #e9ecef !important;
                border-radius: 8px !important;
                padding: 20px !important;
            }
        }

        /* Mobile responsywność */
        @media (max-width: 1023px) {
            .woocommerce-checkout form.checkout {
                display: block !important;
            }

            .woocommerce-checkout #order_review {
                order: 2 !important;
                position: relative !important;
            }

            .woocommerce-checkout #customer_details {
                order: 1 !important;
                margin-bottom: 20px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            setTimeout(function() {
                const crossSell = document.querySelector('.custom-crosssell-added');
                const wcBlockCheckout = document.querySelector('.wc-block-checkout');
                const sidebarLayout = document.querySelector('.wc-block-components-sidebar-layout.is-large');

                if (crossSell && wcBlockCheckout && sidebarLayout && window.innerWidth >= 1024) {
                    if (sidebarLayout.contains(crossSell)) {
                        crossSell.remove();
                        wcBlockCheckout.insertBefore(crossSell, sidebarLayout);
                    }
                }

                // Clean up text
                const crossSellElement = document.querySelector('.custom-crosssell-added');
                if (crossSellElement) {
                    const unwantedElements = crossSellElement.querySelectorAll('h1, h2, h3, h4, h5, h6');
                    unwantedElements.forEach(el => {
                        if (el.textContent.includes('Dodaj te produkty') || el.textContent.includes('Polecane')) {
                            el.remove();
                        }
                    });
                }
            }, 1500);

        });
    </script>
<?php
}
add_action('wp_head', 'universal_checkout_layout_css', 999);
