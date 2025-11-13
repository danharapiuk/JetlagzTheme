/**
 * Enhanced Checkout Experience JavaScript
 * Rozszerza funkcjonalność strony checkout
 */

(function($) {
    'use strict';

    var UniversalCheckout = {
        
        init: function() {
            this.bindEvents();
            this.initProgressSteps();
            this.enhanceFormValidation();
            this.initShippingToggle();
        },

        bindEvents: function() {
            // Obsługa zmiany shipping address
            $(document).on('change', '#ship-to-different-address-checkbox', this.toggleShippingAddress);
            
            // Obsługa wyboru payment method
            $(document).on('change', 'input[name="payment_method"]', this.handlePaymentMethodChange);
            
            // Obsługa formularza checkout
            $(document).on('submit', 'form.checkout', this.handleCheckoutSubmit);
            
            // Live validation
            $(document).on('blur', '.checkout-section input[required]', this.validateField);
            $(document).on('input', '.checkout-section input[type="email"]', this.validateEmail);
            
            // Update order review on field changes
            $(document).on('change', '.checkout-section input, .checkout-section select', 
                this.debounce(this.updateOrderReview, 1000));
        },

        initProgressSteps: function() {
            // Automatyczne przejście przez kroki na podstawie wypełnienia formularza
            this.updateProgressSteps();
            
            // Monitoruj zmiany w formularzu
            $(document).on('input change', '.checkout-section input, .checkout-section select', 
                this.debounce(this.updateProgressSteps, 500));
        },

        updateProgressSteps: function() {
            var $steps = $('.progress-step');
            var currentStep = 1;

            // Krok 1: Dane kontaktowe i adres
            var hasRequiredInfo = $('#billing_email').val() && 
                                 $('#billing_first_name').val() && 
                                 $('#billing_last_name').val() &&
                                 $('#billing_address_1').val() &&
                                 $('#billing_city').val() &&
                                 $('#billing_postcode').val();

            if (hasRequiredInfo) {
                currentStep = 2;
                
                // Krok 2: Metoda płatności
                var hasPaymentMethod = $('input[name="payment_method"]:checked').length > 0;
                
                if (hasPaymentMethod) {
                    currentStep = 3;
                }
            }

            // Aktualizuj wizualnie
            $steps.removeClass('active').slice(0, currentStep).addClass('active');
        },

        enhanceFormValidation: function() {
            // Dodaj custom validation styles
            $('.checkout-section input[required]').each(function() {
                $(this).on('invalid', function() {
                    $(this).addClass('error');
                }).on('input', function() {
                    if (this.validity.valid) {
                        $(this).removeClass('error');
                    }
                });
            });
        },

        validateField: function(e) {
            var $field = $(e.target);
            var value = $field.val().trim();
            var isValid = true;

            // Remove previous error state
            $field.removeClass('error');
            $field.next('.error-message').remove();

            // Required field validation
            if ($field.prop('required') && !value) {
                isValid = false;
                UniversalCheckout.showFieldError($field, 'To pole jest wymagane');
            }

            // Email validation
            if ($field.attr('type') === 'email' && value) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    UniversalCheckout.showFieldError($field, 'Wprowadź prawidłowy adres email');
                }
            }

            // Phone validation (if required)
            if ($field.attr('type') === 'tel' && value) {
                var phoneRegex = /^[\d\s\-\+\(\)]{9,}$/;
                if (!phoneRegex.test(value)) {
                    isValid = false;
                    UniversalCheckout.showFieldError($field, 'Wprowadź prawidłowy numer telefonu');
                }
            }

            return isValid;
        },

        validateEmail: function(e) {
            var $field = $(e.target);
            var value = $field.val();

            // Live email validation
            if (value.length > 3) {
                var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(value)) {
                    $field.removeClass('error').addClass('valid');
                } else {
                    $field.removeClass('valid').addClass('error');
                }
            } else {
                $field.removeClass('valid error');
            }
        },

        showFieldError: function($field, message) {
            $field.addClass('error');
            $field.after('<div class="error-message" style="color: #e74c3c; font-size: 0.85rem; margin-top: 0.3rem;">' + message + '</div>');
        },

        initShippingToggle: function() {
            // Initialny stan shipping address
            var $checkbox = $('#ship-to-different-address-checkbox');
            var $shippingDiv = $('.shipping-address');
            
            if ($checkbox.length && $shippingDiv.length) {
                if ($checkbox.is(':checked')) {
                    $shippingDiv.slideDown(300);
                } else {
                    $shippingDiv.hide();
                }
            }
        },

        toggleShippingAddress: function(e) {
            var $checkbox = $(e.target);
            var $shippingDiv = $('.shipping-address');

            if ($checkbox.is(':checked')) {
                $shippingDiv.slideDown(300);
            } else {
                $shippingDiv.slideUp(300);
            }
        },

        handlePaymentMethodChange: function(e) {
            var $selectedMethod = $(e.target);
            
            // Remove previous selection styling
            $('.wc_payment_method').removeClass('selected');
            
            // Add selection styling
            $selectedMethod.closest('.wc_payment_method').addClass('selected');
            
            // Update progress
            UniversalCheckout.updateProgressSteps();
            
            // Trigger WooCommerce update
            $('body').trigger('update_checkout');
        },

        handleCheckoutSubmit: function(e) {
            var $form = $(e.target);
            var $submitButton = $form.find('#place_order');
            
            // Validate all required fields
            var isValid = true;
            $form.find('input[required]').each(function() {
                if (!UniversalCheckout.validateField({target: this})) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                UniversalCheckout.showNotification('Wypełnij wszystkie wymagane pola prawidłowo', 'error');
                
                // Scroll to first error
                var $firstError = $form.find('.error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
                return false;
            }

            // Add loading state
            $submitButton.addClass('loading').prop('disabled', true);
            $form.find('.checkout-section').addClass('processing');
            
            // Show processing notification
            UniversalCheckout.showNotification('Przetwarzanie zamówienia...', 'info');
            
            // Let WooCommerce handle the rest
            return true;
        },

        updateOrderReview: function() {
            // Trigger WooCommerce order review update
            $('body').trigger('update_checkout');
        },

        showNotification: function(message, type) {
            type = type || 'info';
            
            // Create notification container if it doesn't exist
            if ($('.universal-notifications').length === 0) {
                $('body').append('<div class="universal-notifications"></div>');
            }

            var $notification = $('<div class="universal-notification universal-notification-' + type + '">' + message + '</div>');
            
            $('.universal-notifications').append($notification);

            // Show with animation
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);

            // Auto hide
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, type === 'error' ? 8000 : 4000);

            // Allow manual close
            $notification.on('click', function() {
                $(this).removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        },

        // Utility function to debounce rapid function calls
        debounce: function(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // Enhanced form autosave (localStorage)
        initAutosave: function() {
            var formData = localStorage.getItem('checkout_form_data');
            if (formData) {
                try {
                    var data = JSON.parse(formData);
                    
                    // Restore form values (except payment info)
                    Object.keys(data).forEach(function(key) {
                        if (!key.includes('payment') && !key.includes('card')) {
                            var $field = $('[name="' + key + '"]');
                            if ($field.length && !$field.val()) {
                                $field.val(data[key]);
                            }
                        }
                    });
                }
                catch (e) {
                    localStorage.removeItem('checkout_form_data');
                }
            }

            // Save form data on changes
            $(document).on('input change', '.checkout-section input, .checkout-section select', 
                this.debounce(this.saveFormData, 2000));
        },

        saveFormData: function() {
            var formData = {};
            $('.checkout-section input, .checkout-section select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                
                // Don't save sensitive payment data
                if (name && !name.includes('payment') && !name.includes('card') && !name.includes('cvv')) {
                    formData[name] = $field.val();
                }
            });

            localStorage.setItem('checkout_form_data', JSON.stringify(formData));
        },

        // Clear saved data after successful order
        clearSavedData: function() {
            localStorage.removeItem('checkout_form_data');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        UniversalCheckout.init();
        // UniversalCheckout.initAutosave(); // Uncomment if you want autosave feature
    });

    // Clear saved data on order completion
    $(window).on('beforeunload', function() {
        // Only clear if on order received page
        if (window.location.href.includes('order-received')) {
            UniversalCheckout.clearSavedData();
        }
    });

    // Export to global scope
    window.UniversalCheckout = UniversalCheckout;

})(jQuery);