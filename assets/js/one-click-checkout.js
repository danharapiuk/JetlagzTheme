/**
 * One-Click Checkout JavaScript
 * Obsługuje funkcjonalność zakupów w jeden klik
 */

(function($) {
    'use strict';

    var UniversalOneClick = {
        
        init: function() {
            this.bindEvents();
            this.checkUserStatus();
        },

        bindEvents: function() {
            // Obsługa kliknięcia przycisków one-click
            $(document).on('click', '.universal-one-click-btn, .universal-one-click-btn-loop', this.handleOneClickCheckout);
            
            // Obsługa ESC key dla cancelowania
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) { // ESC key
                    UniversalOneClick.resetButtons();
                }
            });
        },

        checkUserStatus: function() {
            // Sprawdź czy wymagane jest logowanie
            if (universalOneClick.settings.require_login && !$('body').hasClass('logged-in')) {
                $('.universal-one-click-btn, .universal-one-click-btn-loop').each(function() {
                    $(this).addClass('disabled').attr('title', universalOneClick.messages.login_required);
                });
            }
        },

        handleOneClickCheckout: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var productId = $button.data('product-id');
            var quantity = $button.data('quantity') || 1;

            // Sprawdź czy przycisk nie jest wyłączony lub w trakcie przetwarzania
            if ($button.hasClass('disabled') || $button.hasClass('loading')) {
                return false;
            }

            // Sprawdź wymagania logowania
            if (universalOneClick.settings.require_login && !$('body').hasClass('logged-in')) {
                UniversalOneClick.showNotification(universalOneClick.messages.login_required, 'error');
                return false;
            }

            // Walidacja danych
            if (!productId || productId <= 0) {
                UniversalOneClick.showNotification('Nieprawidłowy produkt', 'error');
                return false;
            }

            // Rozpocznij proces zamówienia
            UniversalOneClick.processOneClickOrder($button, productId, quantity);
        },

        processOneClickOrder: function($button, productId, quantity) {
            // Ustaw stan loading
            this.setButtonState($button, 'loading');

            // Wyślij AJAX request
            $.ajax({
                url: universalOneClick.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'universal_one_click_checkout',
                    nonce: universalOneClick.nonce,
                    product_id: productId,
                    quantity: quantity,
                    universal_one_click: 'true'
                },
                success: function(response) {
                    if (response.success) {
                        UniversalOneClick.handleSuccess($button, response.data);
                    } else {
                        UniversalOneClick.handleError($button, response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    UniversalOneClick.handleError($button, universalOneClick.messages.error);
                },
                timeout: 30000 // 30 sekund timeout
            });
        },

        handleSuccess: function($button, data) {
            this.setButtonState($button, 'success');
            this.showNotification(data.message || universalOneClick.messages.success, 'success');

            // Aktualizuj licznik koszyka jeśli istnieje
            this.updateCartCount();

            // Opcjonalnie przekieruj po sukcesie
            if (data.redirect_url) {
                setTimeout(function() {
                    window.location.href = data.redirect_url;
                }, 2000);
            } else {
                // Reset przycisku po 3 sekundach
                setTimeout(function() {
                    UniversalOneClick.resetButtons();
                }, 3000);
            }

            // Trigger event dla innych skryptów
            $(document).trigger('universalOneClickSuccess', [data, $button]);
        },

        handleError: function($button, message) {
            this.setButtonState($button, 'error');
            this.showNotification(message || universalOneClick.messages.error, 'error');

            // Reset przycisku po 2 sekundach
            setTimeout(function() {
                UniversalOneClick.resetButtons();
            }, 2000);

            // Trigger event dla innych skryptów
            $(document).trigger('universalOneClickError', [message, $button]);
        },

        setButtonState: function($button, state) {
            // Reset wszystkich stanów
            $button.removeClass('loading success error');
            
            // Ukryj/pokaż odpowiednie elementy
            var $text = $button.find('.btn-text');
            var $loading = $button.find('.btn-loading');

            switch(state) {
                case 'loading':
                    $button.addClass('loading');
                    $text.hide();
                    $loading.show();
                    $button.attr('disabled', true);
                    break;
                    
                case 'success':
                    $button.addClass('success');
                    $text.text('Zamówione!').show();
                    $loading.hide();
                    $button.attr('disabled', true);
                    break;
                    
                case 'error':
                    $button.addClass('error');
                    $text.text('Błąd!').show();
                    $loading.hide();
                    $button.attr('disabled', true);
                    break;
                    
                default:
                    $text.show();
                    $loading.hide();
                    $button.attr('disabled', false);
            }
        },

        resetButtons: function() {
            $('.universal-one-click-btn, .universal-one-click-btn-loop').each(function() {
                var $button = $(this);
                var originalText = $button.data('original-text') || $button.find('.btn-text').text();
                
                // Zapisz oryginalny tekst jeśli nie został zapisany
                if (!$button.data('original-text')) {
                    $button.data('original-text', originalText);
                }
                
                // Reset stanu
                UniversalOneClick.setButtonState($button, 'default');
                $button.find('.btn-text').text(originalText);
                $button.removeClass('loading success error');
            });
        },

        updateCartCount: function() {
            // Aktualizuj licznik koszyka w headerze
            var $cartCount = $('.cart-contents-count, .count');
            if ($cartCount.length) {
                var currentCount = parseInt($cartCount.text()) || 0;
                $cartCount.text(currentCount + 1).addClass('bounce');
                
                // Usuń klasę bounce po animacji
                setTimeout(function() {
                    $cartCount.removeClass('bounce');
                }, 600);
            }

            // Trigger fragmenty koszyka do odświeżenia
            $(document.body).trigger('wc_fragment_refresh');
        },

        showNotification: function(message, type) {
            type = type || 'info';
            
            // Utwórz kontener na notyfikacje jeśli nie istnieje
            if ($('.universal-notifications').length === 0) {
                $('body').append('<div class="universal-notifications"></div>');
            }

            var $notification = $('<div class="universal-notification universal-notification-' + type + '">' + message + '</div>');
            
            $('.universal-notifications').append($notification);

            // Pokaż notyfikację z animacją
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);

            // Ukryj po 5 sekundach
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 5000);

            // Pozwól na ręczne zamknięcie
            $notification.on('click', function() {
                $(this).removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        },

        // Funkcja pomocnicza do debugowania
        debug: function(message, data) {
            if (window.console && console.log) {
                console.log('[Universal One-Click]', message, data || '');
            }
        }
    };

    // Inicjalizacja po załadowaniu DOM
    $(document).ready(function() {
        UniversalOneClick.init();
    });

    // Ponowna inicjalizacja po AJAX load (dla stron z infinite scroll itp.)
    $(document).ajaxComplete(function() {
        UniversalOneClick.checkUserStatus();
    });

    // Eksportuj do globalnego scope dla dostępu z zewnątrz
    window.UniversalOneClick = UniversalOneClick;

})(jQuery);