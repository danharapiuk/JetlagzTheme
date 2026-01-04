/**
 * One-Click Checkout JavaScript
 * Obsługuje funkcjonalność zakupów w jeden klik + Modal Checkout
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
            $(document).on('click', '.universal-one-click-btn, .universal-one-click-btn-loop', this.handleOneClickButton);
            
            // Obsługa zamknięcia modala
            $(document).on('click', '.universal-close, .universal-modal', this.closeModal);
            $(document).on('click', '.universal-modal-content', function(e) { e.stopPropagation(); });
            
            // Obsługa formularza w modalu
            $(document).on('submit', '#universal-modal-checkout-form', this.handleModalSubmit);
            
            // Obsługa wyboru metody płatności
            $(document).on('change', 'input[name="payment_method"]', this.handlePaymentMethodChange);
            
            // Obsługa ESC key
            $(document).on('keyup', function(e) {
                if (e.keyCode === 27) { // ESC key
                    UniversalOneClick.closeModal();
                }
            });

            // Auto-fill danych dla zalogowanych użytkowników
            this.autoFillUserData();
        },

        checkUserStatus: function() {
            // Ten kod już nie jest potrzebny - pokazujemy przyciski dla wszystkich
        },

        handleOneClickButton: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var productId = $button.data('product-id');
            var quantity = $button.data('quantity') || 1;
            var action = $button.data('action') || 'direct-order';

            // Sprawdź czy przycisk nie jest w trakcie przetwarzania
            if ($button.hasClass('loading')) {
                return false;
            }

            // Walidacja danych
            if (!productId || productId <= 0) {
                UniversalOneClick.showNotification('Nieprawidłowy produkt', 'error');
                return false;
            }

            if (action === 'add-and-redirect') {
                // Dodaj do koszyka i przekieruj na checkout
                UniversalOneClick.addToCartAndRedirect($button, productId, quantity);
            } else if (action === 'open-modal') {
                // Otwórz modal checkout (stara opcja)
                UniversalOneClick.openCheckoutModal(productId, quantity);
            } else {
                // Klasyczny one-click (fallback)
                UniversalOneClick.processOneClickOrder($button, productId, quantity);
            }
        },

        addToCartAndRedirect: function($button, productId, quantity) {
            // Ustaw loading state
            this.setButtonState($button, 'loading');

            // AJAX request
            $.ajax({
                url: universalOneClick.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'universal_add_to_cart_redirect',
                    nonce: universalOneClick.nonce,
                    product_id: productId,
                    quantity: quantity
                },
                success: function(response) {
                    if (response.success) {
                        // Pokazuje sukces
                        UniversalOneClick.showNotification(response.data.message, 'success');
                        
                        // Aktualizuj licznik koszyka
                        UniversalOneClick.updateCartCount(response.data.cart_count);
                        
                        // Przekieruj na checkout po krótkim opóźnieniu
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                        
                        // Trigger event
                        $(document).trigger('universalAddToCartSuccess', [response.data, $button]);
                        
                    } else {
                        UniversalOneClick.handleError($button, response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    UniversalOneClick.handleError($button, 'Wystąpił błąd podczas dodawania do koszyka');
                },
                timeout: 15000
            });
        },

        openCheckoutModal: function(productId, quantity) {
            var $modal = $('#universal-checkout-modal');
            
            if ($modal.length === 0) {
                console.error('Modal checkout nie został znaleziony');
                return;
            }

            // Ustaw dane produktu
            $('#modal-product-id').val(productId);
            $('#modal-quantity').val(quantity);

            // Pobierz dane produktu i wypełnij podsumowanie
            this.loadProductData(productId, quantity);

            // Pokaż modal
            $modal.addClass('show');
            $('body').addClass('modal-open');

            // Focus na pierwszym polu
            setTimeout(function() {
                $('#billing_first_name').focus();
            }, 300);
        },

        loadProductData: function(productId, quantity) {
            var $orderItems = $('.order-items');
            var $orderTotals = $('.order-totals');

            // Pokaż loading
            $orderItems.html('<div class="loading">Ładowanie danych produktu...</div>');

            // AJAX request po dane produktu
            $.ajax({
                url: universalOneClick.ajax_url,
                type: 'POST',
                data: {
                    action: 'universal_get_product_data',
                    product_id: productId,
                    quantity: quantity,
                    nonce: universalOneClick.nonce
                },
                success: function(response) {
                    if (response.success) {
                        UniversalOneClick.displayProductData(response.data);
                    } else {
                        $orderItems.html('<div class="error">Błąd ładowania danych produktu</div>');
                    }
                },
                error: function() {
                    $orderItems.html('<div class="error">Błąd połączenia</div>');
                }
            });
        },

        displayProductData: function(data) {
            var $orderItems = $('.order-items');
            var $orderTotals = $('.order-totals');

            // Wyświetl produkt
            var productHtml = '<div class="order-item">' +
                '<div class="order-item-info">' +
                    '<div class="order-item-name">' + data.name + '</div>' +
                    '<div class="order-item-details">Ilość: ' + data.quantity + '</div>' +
                '</div>' +
                '<div class="order-item-price">' + data.price_html + '</div>' +
            '</div>';

            $orderItems.html(productHtml);

            // Wyświetl sumy
            var totalsHtml = '<div class="total-row">' +
                '<span>Subtotal:</span>' +
                '<span>' + data.subtotal + '</span>' +
            '</div>';

            if (data.tax && data.tax !== '0') {
                totalsHtml += '<div class="total-row">' +
                    '<span>Podatek:</span>' +
                    '<span>' + data.tax + '</span>' +
                '</div>';
            }

            totalsHtml += '<div class="total-row">' +
                '<span>Łącznie:</span>' +
                '<span>' + data.total + '</span>' +
            '</div>';

            $orderTotals.html(totalsHtml);
        },

        autoFillUserData: function() {
            // Jeśli użytkownik jest zalogowany, wypełnij dane z profilu
            if ($('body').hasClass('logged-in') && typeof wc_checkout_params !== 'undefined') {
                // Można tutaj dodać logic wypełniania danych z WordPress user meta
                // Na razie zostawiamy puste - użytkownik wypełni ręcznie
            }
        },

        handlePaymentMethodChange: function() {
            var $selected = $(this);
            
            // Usuń poprzednie zaznaczenie
            $('.payment-method').removeClass('selected');
            
            // Dodaj zaznaczenie do wybranego
            $selected.closest('.payment-method').addClass('selected');
        },

        handleModalSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('.universal-submit-order');

            // Walidacja formularza
            if (!UniversalOneClick.validateForm($form)) {
                return false;
            }

            // Ustaw loading state
            $submitBtn.addClass('loading').text('Przetwarzanie...');
            $form.find('input, select').prop('disabled', true);

            // Przygotuj dane
            var formData = $form.serialize();

            // Wyślij AJAX request
            $.ajax({
                url: universalOneClick.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        UniversalOneClick.handleModalSuccess(response.data);
                    } else {
                        UniversalOneClick.handleModalError(response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    UniversalOneClick.handleModalError('Wystąpił błąd połączenia. Spróbuj ponownie.');
                },
                complete: function() {
                    $submitBtn.removeClass('loading').text('Złóż zamówienie');
                    $form.find('input, select').prop('disabled', false);
                }
            });
        },

        validateForm: function($form) {
            var isValid = true;
            var requiredFields = $form.find('input[required]');

            requiredFields.each(function() {
                var $field = $(this);
                var value = $field.val().trim();

                if (value === '') {
                    $field.addClass('error');
                    isValid = false;
                } else {
                    $field.removeClass('error');
                }

                // Walidacja email
                if ($field.attr('type') === 'email' && value !== '') {
                    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        $field.addClass('error');
                        isValid = false;
                    }
                }
            });

            if (!isValid) {
                this.showNotification('Wypełnij wszystkie wymagane pola prawidłowo', 'error');
            }

            return isValid;
        },

        handleModalSuccess: function(data) {
            // Pokazuje sukces
            this.showNotification(data.message || 'Zamówienie zostało złożone pomyślnie!', 'success');
            
            // Aktualizuj licznik koszyka
            this.updateCartCount();
            
            // Zamknij modal po 2 sekundach
            setTimeout(function() {
                UniversalOneClick.closeModal();
            }, 2000);

            // Opcjonalnie przekieruj
            if (data.redirect_url) {
                setTimeout(function() {
                    window.location.href = data.redirect_url;
                }, 3000);
            }

            // Trigger event
            $(document).trigger('universalModalCheckoutSuccess', [data]);
        },

        handleModalError: function(message) {
            this.showNotification(message || 'Wystąpił błąd podczas składania zamówienia', 'error');
            
            // Trigger event
            $(document).trigger('universalModalCheckoutError', [message]);
        },

        closeModal: function(e) {
            if (e && $(e.target).closest('.universal-modal-content').length > 0) {
                return; // Kliknięto wewnątrz modala
            }

            var $modal = $('#universal-checkout-modal');
            $modal.removeClass('show');
            $('body').removeClass('modal-open');

            // Reset formularza po zamknięciu
            setTimeout(function() {
                $('#universal-modal-checkout-form')[0].reset();
                $('.form-group input').removeClass('error');
                $('.payment-method').removeClass('selected');
                $('input[name="payment_method"]:first').prop('checked', true).trigger('change');
            }, 300);
        },

        // Stare funkcje (fallback dla klasycznego one-click)
        processOneClickOrder: function($button, productId, quantity) {
            this.setButtonState($button, 'loading');

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
                timeout: 30000
            });
        },

        handleSuccess: function($button, data) {
            this.setButtonState($button, 'success');
            this.showNotification(data.message || universalOneClick.messages.success, 'success');
            this.updateCartCount();

            if (data.redirect_url) {
                setTimeout(function() {
                    window.location.href = data.redirect_url;
                }, 2000);
            } else {
                setTimeout(function() {
                    UniversalOneClick.resetButtons();
                }, 3000);
            }

            $(document).trigger('universalOneClickSuccess', [data, $button]);
        },

        handleError: function($button, message) {
            this.setButtonState($button, 'error');
            this.showNotification(message || universalOneClick.messages.error, 'error');

            setTimeout(function() {
                UniversalOneClick.resetButtons();
            }, 2000);

            $(document).trigger('universalOneClickError', [message, $button]);
        },

        setButtonState: function($button, state) {
            $button.removeClass('loading success error');
            
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
                
                if (!$button.data('original-text')) {
                    $button.data('original-text', originalText);
                }
                
                UniversalOneClick.setButtonState($button, 'default');
                $button.find('.btn-text').text(originalText);
                $button.removeClass('loading success error');
            });
        },

        updateCartCount: function(newCount) {
            var $cartCount = $('.cart-contents-count, .count');
            if ($cartCount.length && typeof newCount !== 'undefined') {
                $cartCount.text(newCount).addClass('bounce');
            } else {
                // Fallback - zwiększ o 1
                var currentCount = parseInt($cartCount.text()) || 0;
                $cartCount.text(currentCount + 1).addClass('bounce');
            }
            
            setTimeout(function() {
                $cartCount.removeClass('bounce');
            }, 600);

            $(document.body).trigger('wc_fragment_refresh');
        },

        showNotification: function(message, type) {
            type = type || 'info';
            
            if ($('.universal-notifications').length === 0) {
                $('body').append('<div class="universal-notifications"></div>');
            }

            var $notification = $('<div class="universal-notification universal-notification-' + type + '">' + message + '</div>');
            
            $('.universal-notifications').append($notification);

            setTimeout(function() {
                $notification.addClass('show');
            }, 100);

            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 5000);

            $notification.on('click', function() {
                $(this).removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        },

        debug: function(message, data) {
            // Debug disabled in production
        }
    };

    // Inicjalizacja
    $(document).ready(function() {
        UniversalOneClick.init();
    });

    $(document).ajaxComplete(function() {
        // Ponowna inicjalizacja po AJAX (dla stron z infinite scroll)
    });

    // Eksport do global scope
    window.UniversalOneClick = UniversalOneClick;

})(jQuery);