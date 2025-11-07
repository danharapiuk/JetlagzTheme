/**
 * Checkout Cross-sell JavaScript
 * Obsługuje dodawanie produktów polecanych w checkout
 */

(function($) {
    'use strict';

    var UniversalCrosssell = {
        
        init: function() {
            this.bindEvents();
            this.updateProgressBar();
        },

        bindEvents: function() {
            // Obsługa przycisków dodawania cross-sell produktów
            $(document).on('click', '.crosssell-add-btn:not(.disabled)', this.handleAddProduct);
            
            // Update progress bar po zmianie koszyka
            $(document.body).on('updated_checkout', this.updateProgressBar);
            $(document.body).on('wc_fragments_refreshed', this.updateProgressBar);
        },

        handleAddProduct: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var productId = $button.data('product-id');
            var productName = $button.data('product-name');
            var productPrice = $button.data('product-price');

            // Sprawdź czy przycisk nie jest w trakcie przetwarzania
            if ($button.hasClass('adding')) {
                return false;
            }

            // Walidacja
            if (!productId || productId <= 0) {
                UniversalCrosssell.showNotification('Nieprawidłowy produkt', 'error');
                return false;
            }

            // Ustaw loading state
            UniversalCrosssell.setButtonState($button, 'adding');

            // AJAX request
            $.ajax({
                url: universalCrosssell.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'universal_add_crosssell_product',
                    nonce: universalCrosssell.nonce,
                    product_id: productId,
                    quantity: 1
                },
                success: function(response) {
                    if (response.success) {
                        // Sukces - produkt dodany
                        UniversalCrosssell.handleAddSuccess($button, response.data);
                    } else {
                        // Błąd
                        UniversalCrosssell.handleAddError($button, response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    UniversalCrosssell.handleAddError($button, 'Wystąpił błąd połączenia.');
                },
                timeout: 10000
            });
        },

        handleAddSuccess: function($button, data) {
            // Ustaw stan sukcesu
            UniversalCrosssell.setButtonState($button, 'added');
            
            // Pokaż powiadomienie
            UniversalCrosssell.showNotification(data.message, 'success');
            
            // Ukryj produkt po dodaniu (żeby nie dodawać ponownie)
            $button.closest('.crosssell-product-item').addClass('product-added');
            
            // Update progress bar
            UniversalCrosssell.updateProgressBarData(data);
            
            // Trigger WooCommerce update checkout
            $('body').trigger('update_checkout');
            
            // Auto-reset button po czasie
            setTimeout(function() {
                $button.prop('disabled', true).text('W koszyku');
            }, 2000);
            
            // Trigger custom event
            $(document).trigger('universalCrosssellAdded', [data, $button]);
        },

        handleAddError: function($button, message) {
            // Reset stan przycisku
            UniversalCrosssell.setButtonState($button, 'default');
            
            // Pokaż błąd
            UniversalCrosssell.showNotification(message, 'error');
            
            // Trigger custom event
            $(document).trigger('universalCrosssellError', [message, $button]);
        },

        setButtonState: function($button, state) {
            $button.removeClass('adding added');
            
            var $icon = $button.find('.btn-icon');
            var $text = $button.find('.btn-text');
            
            switch(state) {
                case 'adding':
                    $button.addClass('adding').prop('disabled', true);
                    $icon.html('⏳');
                    $text.text('Dodawanie...');
                    break;
                    
                case 'added':
                    $button.addClass('added').prop('disabled', false);
                    $icon.html('✓');
                    $text.text('Dodano!');
                    break;
                    
                case 'default':
                default:
                    $button.prop('disabled', false);
                    $icon.html('+');
                    $text.text('Dodaj');
                    break;
            }
        },

        updateProgressBar: function() {
            var $progressSection = $('.free-shipping-progress');
            if (!$progressSection.length) {
                return;
            }

            // Pobierz aktualne dane z checkout
            UniversalCrosssell.refreshProgressFromCheckout();
        },

        updateProgressBarData: function(data) {
            var $progressSection = $('.free-shipping-progress');
            var $achievedSection = $('.free-shipping-achieved');
            
            if (data.free_shipping_achieved) {
                // Ukryj progress bar, pokaż achieved
                $progressSection.slideUp(300, function() {
                    if (!$achievedSection.length) {
                        $progressSection.after(`
                            <div class="free-shipping-achieved" style="display: none;">
                                <h3>
                                    <i class="shipping-icon">✅</i>
                                    Gratulacje! Masz darmową wysyłkę!
                                </h3>
                            </div>
                        `);
                        $('.free-shipping-achieved').slideDown(300);
                    } else {
                        $achievedSection.slideDown(300);
                    }
                });
            } else {
                // Update progress bar
                $progressSection.find('.remaining-amount strong').text(data.remaining_formatted);
                $progressSection.find('.progress-fill').css('width', data.progress_percentage + '%');
                $progressSection.find('.progress-labels span:first').text('Twój koszyk: ' + data.cart_total_formatted);
                
                // Animacja progress bara
                var $progressFill = $progressSection.find('.progress-fill');
                $progressFill.addClass('updating');
                setTimeout(function() {
                    $progressFill.removeClass('updating');
                }, 800);
            }
        },

        refreshProgressFromCheckout: function() {
            // Ta funkcja może być rozszerzona aby pobierać dane z checkoutu w czasie rzeczywistym
            // Na razie polegamy na update_checkout event i AJAX responses
        },

        showNotification: function(message, type) {
            type = type || 'info';
            
            // Usuń poprzednie notyfikacje z tego obszaru
            $('.crosssell-notification').remove();
            
            var $notification = $(`
                <div class="crosssell-notification crosssell-notification-${type}">
                    <i class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</i>
                    <span class="notification-message">${message}</span>
                </div>
            `);
            
            // Dodaj do crosssell section
            $('.checkout-crosssell-section').prepend($notification);

            // Animacja pojawienia
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);

            // Auto-hide
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, type === 'error' ? 6000 : 4000);

            // Pozwól na ręczne zamknięcie
            $notification.on('click', function() {
                $(this).removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        },

        // Funkcje pomocnicze
        formatPrice: function(price) {
            return universalCrosssell.currency + parseFloat(price).toFixed(2);
        },

        // Animation helpers
        animateCountUp: function($element, start, end, duration) {
            duration = duration || 1000;
            var range = end - start;
            var current = start;
            var increment = end > start ? 1 : -1;
            var stepTime = Math.abs(Math.floor(duration / range));
            
            var timer = setInterval(function() {
                current += increment;
                $element.text(current);
                if (current === end) {
                    clearInterval(timer);
                }
            }, stepTime);
        }
    };

    // Initialize when checkout is ready
    $(document).ready(function() {
        UniversalCrosssell.init();
    });

    // Re-initialize after AJAX updates
    $(document.body).on('updated_checkout', function() {
        // Bind events again for any new elements
        setTimeout(function() {
            UniversalCrosssell.init();
        }, 500);
    });

    // Export to global scope
    window.UniversalCrosssell = UniversalCrosssell;

})(jQuery);