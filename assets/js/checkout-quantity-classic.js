/**
 * WooCommerce Classic Checkout - Quantity Controls
 * Dodaje przyciski +/- do custom Order Summary
 * Pracuje z nowym custom checkout table layout
 */

jQuery(document).ready(function($) {
    let autoCouponAttempted = false;
    let initialCouponUiSyncDone = false;

    function getSelectedCheckoutCouponState() {
        const inputCouponCode = (($('#coupon_code').first().val() || '') + '').trim();
        const debugCouponCode = window.jetlagzCheckoutCouponDebug && window.jetlagzCheckoutCouponDebug.selected_coupon_code
            ? (window.jetlagzCheckoutCouponDebug.selected_coupon_code + '').trim()
            : '';
        const couponCode = inputCouponCode || debugCouponCode;

        return {
            couponCode: couponCode,
            source: inputCouponCode ? 'input' : (debugCouponCode ? 'debug-payload' : 'none')
        };
    }

    function syncCouponInputWithSelectedState() {
        const state = getSelectedCheckoutCouponState();
        const $input = $('#coupon_code').first();

        if ($input.length && state.couponCode && !$input.val()) {
            $input.val(state.couponCode);
        }

        return state;
    }
    
    
    if ($('body').hasClass('woocommerce-checkout')) {
        
        // Initialize quantity controls
        setTimeout(function() {
            bindQuantityEvents();
        }, 500); // Małe opóźnienie dla pewności że DOM się załadował
        
        // Re-initialize after checkout updates
        $(document.body).on('updated_checkout', function() {
            setTimeout(bindQuantityEvents, 300);
        });
        
        // Re-initialize after custom checkout table update (z cross-sell)
        $(document).on('universalCheckoutTableUpdated', function() {
            setTimeout(function(){
              bindQuantityEvents();
              bindRemoveEvents();
              bindCouponEvents();
            }, 300);
        });
    }
    
    /**
     * Bind events dla przycisków ilości (nowy custom layout)
     */
    function bindQuantityEvents() {
        
        // Usuń poprzednie eventy żeby uniknąć duplikacji
        $(document).off('click.qtyControls', '.checkout-item-quantity-controls .qty-btn');
        $(document).off('click.qtyDisplay', '.checkout-item-quantity-controls .qty-display');
        $(document).off('keypress.qtyInput', '.checkout-item-quantity-controls .qty-input');
        $(document).off('blur.qtyInput', '.checkout-item-quantity-controls .qty-input');
        
        // Nowe eventy dla nowego layout'u
        $(document).on('click.qtyControls', '.checkout-item-quantity-controls .qty-btn', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const action = $button.data('action');
            const cartKey = $button.data('cart-key');
            
            // Pobierz rodzica item
            const $item = $button.closest('.universal-checkout-item');
            const $qtyDisplay = $item.find('.qty-display');
            const currentQty = parseInt($qtyDisplay.attr('data-qty')) || 1;
            const newQty = action === 'plus' ? currentQty + 1 : Math.max(0, currentQty - 1);
            
            const productName = $item.find('.checkout-item-name').text().trim();
            
            
            if (newQty === 0) {
                // Potwierdzenie usunięcia
                if (!confirm(`Czy na pewno chcesz usunąć "${productName}" z koszyka?`)) {
                    return;
                }
            }
            
            // Wyłącz przyciski podczas aktualizacji
            $item.find('.qty-btn').prop('disabled', true);
            $qtyDisplay.addClass('updating');
            
            // Wyślij AJAX request
            updateQuantityAjax(cartKey, newQty, $qtyDisplay, $item);
        });
        
        // Click na ilość aby edytować
        $(document).on('click.qtyDisplay', '.checkout-item-quantity-controls .qty-display', function(e) {
            e.preventDefault();
            
            const $display = $(this);
            
            // Jeśli już jest w trybie edycji, ignoruj
            if ($display.hasClass('editing')) {
                return;
            }
            
            const currentQty = $display.text();
            const cartKey = $display.data('cart-key');
            
            
            // Zamień span na input
            $display.addClass('editing');
            $display.html(`<input type="number" class="qty-input" value="${currentQty}" min="0" max="999">`);
            
            const $input = $display.find('.qty-input');
            $input.focus().select();
            
            // Enter - zatwierdź
            $input.on('keypress.qtyInput', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    submitQtyEdit($display, cartKey);
                }
            });
            
            // Blur - zatwierdź
            $input.on('blur.qtyInput', function() {
                setTimeout(() => {
                    if ($display.hasClass('editing')) {
                        submitQtyEdit($display, cartKey);
                    }
                }, 100);
            });
        });
        
    }
    
    /**
     * Zatwierdź edycję ilości
     */
    function submitQtyEdit($display, cartKey) {
        const $input = $display.find('.qty-input');
        const newQty = parseInt($input.val()) || 1;
        const $item = $display.closest('.universal-checkout-item');
        const currentQty = parseInt($display.attr('data-qty')) || 1;
        
        
        // Jeśli ilość się nie zmieniła, cofnij
        if (newQty === currentQty) {
            $display.removeClass('editing');
            $display.text(currentQty);
            return;
        }
        
        // Wyłącz UI
        $item.find('.qty-btn').prop('disabled', true);
        $display.addClass('updating');
        
        // Wyślij AJAX
        updateQuantityAjax(cartKey, newQty, $display, $item);
    }
    
    /**
     * Aktualizuj ilość przez AJAX
     */
    function updateQuantityAjax(cartKey, newQty, $qtyDisplay, $item) {
        // Pobierz product ID i inne info jeśli dostępne
        const productName = $item.find('.checkout-item-name').text().trim();
        
        
        $.ajax({
            url: universal_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'universal_update_cart_quantity',
                cart_item_key: cartKey,
                product_name: productName,
                quantity: newQty,
                nonce: universal_ajax.nonce
            },
            success: function(response) {
                
                if (response.success) {
                    // Sprawdź czy koszyk jest pusty (ilość zmieniona na 0)
                    if (response.data.cart_empty === true) {
                        window.location.href = universal_ajax.empty_cart_url;
                        return;
                    }
                    
                    // Aktualizuj wyświetlanie ilości
                    $qtyDisplay.removeClass('editing').text(newQty).attr('data-qty', newQty);
                    
                    // Aktualizuj cenę całkowitą (nowy layout)
                    const $totalPrice = $item.find('.checkout-item-total-price');
                    const unitPrice = parseFloat($totalPrice.attr('data-unit-price')) || 0;
                    const totalPrice = unitPrice * newQty;
                    
                    // Formatuj cenę z walutą (np. "30.00 zł")
                    const formatted = new Intl.NumberFormat('pl-PL', {
                        style: 'currency',
                        currency: 'PLN'
                    }).format(totalPrice);
                    
                    $totalPrice.text(formatted);
                    
                    // ⭐ WAŻNE: Odświeżenie TOTALS na ekranie
                    refreshCheckoutTotals();
                    
                    // ⭐ Wymuś przeliczenie shipping (np. darmowa wysyłka od 299 zł)
                    $('body').trigger('update_checkout');
                    
                    // Pokaż sukces
                    showNotification(response.data.message || 'Ilość zaktualizowana', 'success');
                    
                } else {
                    console.error('❌ Server error:', response.data);
                    // Przywróć poprzednią wartość
                    $qtyDisplay.removeClass('editing').text(parseInt($qtyDisplay.attr('data-qty')));
                    showNotification(response.data || 'Wystąpił błąd', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error:', { xhr, status, error });
                // Tylko pokazuj error jeśli to rzeczywisty błąd, a nie success!
                if (status !== 'parsererror' && status !== 'error') {
                    return; // ignoruj false errors
                }
                showNotification('Błąd połączenia', 'error');
            },
            complete: function() {
                // Włącz przyciski z powrotem
                $item.find('.qty-btn').prop('disabled', false);
                $qtyDisplay.removeClass('updating');
            }
        });
    }
    
    /**
     * Pokaż notyfikację
     */
    function showNotification(message, type = 'info') {
        // Usuń poprzednie notyfikacje
        $('.qty-notification').remove();
        
        const $notification = $(`
            <div class="qty-notification ${type}" style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007cba'};
                color: white;
                padding: 12px 20px;
                border-radius: 4px;
                z-index: 9999;
                font-weight: bold;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            ">
                ${message}
            </div>
        `);
        
        $('body').append($notification);
        
        // Auto-hide po 3 sekundach
        setTimeout(() => {
            $notification.fadeOut(300, () => $notification.remove());
        }, 3000);
    }

    function normalizeCouponMessage(payload, fallbackMessage) {
        const fallback = fallbackMessage || 'Kod kuponu jest nieprawidłowy';

        if (!payload) {
            return fallback;
        }

        if (typeof payload.message === 'string') {
            const trimmedMessage = payload.message.trim();
            if (trimmedMessage && trimmedMessage !== 'coupon_code') {
                return trimmedMessage;
            }
        }

        if (typeof payload.notices_html === 'string' && payload.notices_html.trim()) {
            const textFromHtml = $('<div>').html(payload.notices_html).text().replace(/\s+/g, ' ').trim();
            if (textFromHtml && textFromHtml !== 'coupon_code') {
                return textFromHtml;
            }
        }

        return fallback;
    }
    
    /**
     * Formatuj cenę (helper do wyświetlania cen w formacie lokalnym)
     */
    function formatPrice(price) {
        // Jeśli WooCommerce jest dostępny, użyj jego formatowania
        if (typeof wc_cart_fragments_params !== 'undefined') {
            // Użyj WooCommerce formatowania
            return wc_cart_fragments_params.currency_format_symbol ? 
                wc_cart_fragments_params.currency_format_symbol + ' ' + parseFloat(price).toFixed(2) :
                parseFloat(price).toFixed(2);
        }
        // Fallback - prosty format
        return parseFloat(price).toFixed(2);
    }
    
    /**
     * Bind events dla Remove button
     */
    function bindRemoveEvents() {
        
        // Usuń poprzednie eventy
        $(document).off('click.removeBtn', '.checkout-item-remove-btn');
        
        // Nowe eventy dla remove button
        $(document).on('click.removeBtn', '.checkout-item-remove-btn', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const cartKey = $button.data('cart-key');
            const $item = $button.closest('.universal-checkout-item');
            const productName = $item.find('.checkout-item-name').text().trim();
            const productQty = parseInt($item.find('.qty-display').attr('data-qty')) || 1;
            
            
            // Pokaż modal potwierdzenia
            showRemoveConfirmModal(productName, productQty, cartKey, $item);
        });
        
    }
    
    /**
     * Pokaż modal potwierdzenia usunięcia
     */
    function showRemoveConfirmModal(productName, productQty, cartKey, $item) {
        // Utwórz modal jeśli go jeszcze nie ma
        let $modal = $('#remove-confirm-modal');
        if ($modal.length === 0) {
            $modal = $(`
                <div id="remove-confirm-modal" class="remove-confirm-modal">
                    <div class="remove-confirm-content">
                        <div class="remove-confirm-header">
                            <span class="remove-confirm-icon">⚠️</span>
                            <h2 class="remove-confirm-header-title">Usunąć z koszyka?</h2>
                        </div>
                        <div class="remove-confirm-body">
                            <div class="remove-confirm-product-info">
                                <p class="remove-confirm-product-name"></p>
                                <p class="remove-confirm-product-qty"></p>
                            </div>
                            <p class="remove-confirm-message">
                                Czy na pewno chcesz usunąć ten produkt z koszyka?<br>
                                Tej operacji nie można cofnąć.
                            </p>
                            <div class="remove-confirm-actions">
                                <button type="button" class="remove-confirm-btn remove-confirm-btn-no" data-action="no">
                                    NIE
                                </button>
                                <button type="button" class="remove-confirm-btn remove-confirm-btn-yes" data-action="yes">
                                    TAK, USUŃ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            $('body').append($modal);
            
            // Bind close events
            $(document).on('click', '#remove-confirm-modal', function(e) {
                if (e.target === this) {
                    hideRemoveConfirmModal();
                }
            });
            
            $(document).on('click', '.remove-confirm-btn-no', function() {
                hideRemoveConfirmModal();
            });
            
            $(document).on('click', '.remove-confirm-btn-yes', function() {
                // Pobierz cartKey z ukrytego atrybutu
                const cartKeyToRemove = $(this).data('cart-key');
                removeItemFromCart(cartKeyToRemove, $item);
            });
        }
        
        // Aktualizuj modal content
        $modal.find('.remove-confirm-product-name').text(productName);
        $modal.find('.remove-confirm-product-qty').text(`Ilość: ${productQty}`);
        $modal.find('.remove-confirm-btn-yes').data('cart-key', cartKey);
        
        // Pokaż modal
        $modal.addClass('show');
    }
    
    /**
     * Ukryj modal potwierdzenia
     */
    function hideRemoveConfirmModal() {
        $('#remove-confirm-modal').removeClass('show');
    }
    
    /**
     * Usuń produkt z koszyka
     */
    function removeItemFromCart(cartKey, $item) {
        const $button = $('.remove-confirm-btn-yes');
        $button.addClass('loading');
        
        
        $.ajax({
            url: universal_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'universal_update_cart_quantity',
                cart_item_key: cartKey,
                quantity: 0, // 0 = usuń
                nonce: universal_ajax.nonce
            },
            success: function(response) {
                
                if (response.success) {
                    // Sprawdź czy koszyk jest pusty
                    if (response.data.cart_empty === true) {
                        window.location.href = universal_ajax.empty_cart_url;
                        return;
                    }
                    
                    // Animuj usunięcie
                    $item.animate({
                        opacity: 0,
                        height: 0,
                        marginBottom: 0
                    }, 300, function() {
                        $(this).remove();
                        
                        // ⭐ Odświeżenie TOTALS
                        refreshCheckoutTotals();
                        
                        // ⭐ Wymuś przeliczenie shipping (np. darmowa wysyłka od 299 zł)
                        $('body').trigger('update_checkout');
                        
                        // Ukryj modal
                        hideRemoveConfirmModal();
                        
                        // Pokaż notyfikację sukcesu
                        showNotification('Produkt usunięty z koszyka', 'success');
                        
                        // Reset button
                        $button.removeClass('loading');
                    });
                } else {
                    console.error('❌ Remove failed:', response.data);
                    showNotification(response.data || 'Nie udało się usunąć produktu', 'error');
                    $button.removeClass('loading');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ AJAX Error:', { xhr, status, error });
                showNotification('Błąd połączenia', 'error');
                $button.removeClass('loading');
            }
        });
    }
    
    /**
     * Odświeżenie totals (podsumowania koszyka)
     * Wysyła żądanie do serwera aby pobrać nowy HTML totals
     */
    function refreshCheckoutTotals() {
        
        $.ajax({
            url: universal_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'universal_get_checkout_totals',
                nonce: universal_ajax.nonce
            },
            success: function(response) {
                
                if (response.success && response.data.totals_html) {
                    // Zamień stary HTML totals na nowy
                    $('.universal-checkout-totals').replaceWith(response.data.totals_html);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error refreshing totals:', { xhr, status, error });
            }
        });
    }

    function refreshCheckoutTable() {
        $.ajax({
            url: universal_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'universal_refresh_checkout_table'
            },
            success: function(response) {
                if (response && response.success && response.data && response.data.html) {
                    const $oldTable = $('.universal-checkout-review-table-custom');
                    if ($oldTable.length) {
                        $oldTable.replaceWith(response.data.html);
                        $(document).trigger('universalCheckoutTableUpdated');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error refreshing checkout table:', { xhr, status, error });
            }
        });
    }

    function refreshAppliedCouponsHtml(appliedCouponsHtml) {
        const html = typeof appliedCouponsHtml === 'string' ? appliedCouponsHtml : '';
        const $existing = $('.universal-applied-coupons').first();
        const $couponWrapper = $('.universal-coupon-wrapper').first();

        if (!$couponWrapper.length) {
            return;
        }

        if (html.trim()) {
            if ($existing.length) {
                $existing.replaceWith(html);
            } else {
                $couponWrapper.after(html);
            }
        } else if ($existing.length) {
            $existing.remove();
        }
    }

    /**
     * Bind events dla Apply Coupon button
     */
    function bindCouponEvents() {
        
        // Usuń poprzednie eventy
        $(document).off('click.couponBtn', '#apply-coupon-btn');
        $(document).off('keypress.couponInput', '#coupon_code');
        $(document).off('click.removeCoupon', '.universal-applied-coupon-remove');
        
        // Click na Apply button
        $(document).on('click.couponBtn', '#apply-coupon-btn', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $form = $button.closest('.universal-coupon-form');
            const $input = $form.find('#coupon_code');
            const couponCode = (($input.val() || '') + '').trim();
            
            if (!couponCode) {
                showNotification('Wpisz kod kuponu', 'error');
                return;
            }
            
            applyCouponCode(couponCode, $input, $button);
        });
        
        // Enter w input fieldie
        $(document).on('keypress.couponInput', '#coupon_code', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#apply-coupon-btn').click();
            }
        });

        $(document).on('click.removeCoupon', '.universal-applied-coupon-remove', function(e) {
            e.preventDefault();

            const $button = $(this);
            const couponCode = (($button.data('coupon-code') || '') + '').trim();

            if (!couponCode || $button.prop('disabled')) {
                return;
            }

            $button.prop('disabled', true).addClass('is-loading');

            $.ajax({
                url: universal_ajax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'universal_remove_checkout_coupon',
                    nonce: universal_ajax.nonce,
                    coupon_code: couponCode
                },
                success: function(response) {
                    if (!response || !response.success) {
                        showNotification('Nie udało się usunąć kuponu', 'error');
                        return;
                    }

                    if (response.data && response.data.totals_html) {
                        $('.universal-checkout-totals').replaceWith(response.data.totals_html);
                    } else {
                        refreshCheckoutTotals();
                    }

                    refreshAppliedCouponsHtml(response.data && response.data.applied_coupons_html ? response.data.applied_coupons_html : '');
                    refreshCheckoutTable();
                    $(document.body).trigger('update_checkout');

                    if (window.jetlagzCheckoutCouponDebug && window.jetlagzCheckoutCouponDebug.selected_coupon_code === couponCode) {
                        window.jetlagzCheckoutCouponDebug.selected_coupon_code = '';
                    }

                    const $couponInput = $('#coupon_code').first();
                    if ($couponInput.length && (($couponInput.val() || '') + '').trim() === couponCode) {
                        $couponInput.val('');
                    }

                    showNotification(normalizeCouponMessage(response.data, `Kupon "${couponCode}" usunięty`), 'success');
                },
                error: function(xhr) {
                    const errorPayload = xhr.responseJSON && xhr.responseJSON.data
                        ? xhr.responseJSON.data
                        : (xhr.responseJSON || null);
                    showNotification(normalizeCouponMessage(errorPayload, 'Nie udało się usunąć kuponu'), 'error');
                    refreshAppliedCouponsHtml(errorPayload && errorPayload.applied_coupons_html ? errorPayload.applied_coupons_html : '');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('is-loading');
                }
            });
        });
        
    }
    
    /**
     * Zastosuj kupon
     */
    function applyCouponCode(couponCode, $input, $button, options) {

        const settings = $.extend({
            silent: false
        }, options || {});

        function debugCouponLog(stage, payload) {
            if (!window.console || !console.log) {
                return;
            }

            console.log('[Jetlagz coupon debug][checkout][' + stage + ']', payload);
        }

        function getCouponDomSnapshot() {
            const totalsText = $('.universal-checkout-totals').first().text().replace(/\s+/g, ' ').trim();
            const reviewText = $('.universal-checkout-review-table-custom').first().text().replace(/\s+/g, ' ').trim();

            return {
                couponInputValue: (($('#coupon_code').first().val() || '') + '').trim(),
                hasDiscountTextInTotals: /wartość kupon|rabat|zniżk/i.test(totalsText),
                hasDiscountTextInReview: /old-price|checkout-price-discounted|wartość kupon|rabat|zniżk/i.test(reviewText),
                totalsText: totalsText,
                reviewText: reviewText.slice(0, 500)
            };
        }
        
        $button = $button && $button.length ? $button : $('#apply-coupon-btn');
        $input = $input && $input.length ? $input : $('#coupon_code');

        debugCouponLog('apply-start', {
            couponCode: couponCode,
            snapshot: getCouponDomSnapshot(),
            checkoutRenderDebug: window.jetlagzCheckoutCouponDebug || null
        });
        
        $button.addClass('loading').prop('disabled', true);
        $input.prop('disabled', true);
        
        $.ajax({
            url: universal_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'universal_apply_checkout_coupon',
                nonce: universal_ajax.nonce,
                coupon_code: couponCode
            },
            success: function(response) {
                debugCouponLog('ajax-success', response);

                if (!response || !response.success) {
                    const fallbackMessage = response && response.data && response.data.message
                        ? response.data.message
                        : 'Kod kuponu jest nieprawidłowy';
                    if (!settings.silent) {
                        showNotification(fallbackMessage, 'error');
                    }
                    return;
                }

                if (response.data && response.data.totals_html) {
                    $('.universal-checkout-totals').replaceWith(response.data.totals_html);
                } else {
                    refreshCheckoutTotals();
                }

                refreshAppliedCouponsHtml(response.data && response.data.applied_coupons_html ? response.data.applied_coupons_html : '');

                refreshCheckoutTable();

                $(document.body).trigger('update_checkout');

                setTimeout(function() {
                    syncCouponInputWithSelectedState();
                    debugCouponLog('post-refresh', {
                        response: response,
                        snapshot: getCouponDomSnapshot(),
                        checkoutRenderDebug: window.jetlagzCheckoutCouponDebug || null
                    });
                }, 300);
                
                $input.val(couponCode);

                if (!settings.silent) {
                    showNotification(normalizeCouponMessage(response.data, `Kupon "${couponCode}" zastosowany`), 'success');
                }
            },
            error: function(xhr, status, error) {
                debugCouponLog('ajax-error', {
                    xhr: xhr,
                    status: status,
                    error: error,
                    responseJSON: xhr && xhr.responseJSON ? xhr.responseJSON : null,
                    snapshot: getCouponDomSnapshot(),
                    checkoutRenderDebug: window.jetlagzCheckoutCouponDebug || null
                });
                
                const errorPayload = xhr.responseJSON && xhr.responseJSON.data
                    ? xhr.responseJSON.data
                    : (xhr.responseJSON || null);
                const errorMsg = normalizeCouponMessage(errorPayload, 'Kod kuponu jest nieprawidłowy');

                if (!settings.silent) {
                    showNotification(errorMsg, 'error');
                }
            },
            complete: function() {
                $button.removeClass('loading').prop('disabled', false);
                $input.prop('disabled', false);
            }
        });
    }

    function maybeAutoApplySelectedCoupon() {
        if (autoCouponAttempted || !$('body').hasClass('woocommerce-checkout')) {
            return;
        }

        const $input = $('#coupon_code').first();
        const $button = $('#apply-coupon-btn').first();

        if (!$input.length || !$button.length) {
            return;
        }

        const couponState = syncCouponInputWithSelectedState();
        const couponCode = couponState.couponCode;
        const normalizedCouponCode = couponCode.toLowerCase();

        if (!couponCode) {
            return;
        }

        const debugAppliedCoupons = window.jetlagzCheckoutCouponDebug && Array.isArray(window.jetlagzCheckoutCouponDebug.applied_coupons)
            ? window.jetlagzCheckoutCouponDebug.applied_coupons
            : [];
        const alreadyAppliedInDebug = debugAppliedCoupons.some(function(appliedCouponCode) {
            return ((appliedCouponCode || '') + '').trim().toLowerCase() === normalizedCouponCode;
        });
        const alreadyAppliedInDom = $('.universal-applied-coupon-pill').filter(function() {
            return (((($(this).data('coupon-code') || '') + '').trim().toLowerCase()) === normalizedCouponCode);
        }).length > 0;

        if (alreadyAppliedInDebug || alreadyAppliedInDom) {
            autoCouponAttempted = true;
            return;
        }

        autoCouponAttempted = true;
        if (window.console && console.log) {
            console.log('[Jetlagz coupon debug][checkout][auto-apply]', {
                couponCode: couponCode,
                source: couponState.source,
                checkoutRenderDebug: window.jetlagzCheckoutCouponDebug || null
            });
        }
        applyCouponCode(couponCode, $input, $button, {
            silent: true
        });
    }

    function maybeSyncCheckoutUiForSelectedCoupon() {
        if (initialCouponUiSyncDone || !$('body').hasClass('woocommerce-checkout')) {
            return;
        }

        const couponState = syncCouponInputWithSelectedState();
        const couponCode = couponState.couponCode;

        if (!couponCode) {
            return;
        }

        initialCouponUiSyncDone = true;

        if (window.console && console.log) {
            console.log('[Jetlagz coupon debug][checkout][ui-sync-start]', {
                couponCode: couponCode,
                source: couponState.source,
                checkoutRenderDebug: window.jetlagzCheckoutCouponDebug || null
            });
        }

        refreshCheckoutTotals();
        refreshCheckoutTable();

        setTimeout(function() {
            $(document.body).trigger('update_checkout');
            if (window.console && console.log) {
                console.log('[Jetlagz coupon debug][checkout][ui-sync-triggered-update]', {
                    couponCode: couponCode,
                    checkoutRenderDebug: window.jetlagzCheckoutCouponDebug || null
                });
            }
        }, 150);
    }
    
    // Initialize coupon events on page load
    bindCouponEvents();
    maybeAutoApplySelectedCoupon();
    maybeSyncCheckoutUiForSelectedCoupon();
    
    // Re-bind on checkout update
    $(document.body).on('updated_checkout', function() {
        const couponState = syncCouponInputWithSelectedState();
        if (window.console && console.log) {
            console.log('[Jetlagz coupon debug][checkout][updated_checkout]', {
                couponInputValue: (($('#coupon_code').first().val() || '') + '').trim(),
                selectedCouponCode: couponState.couponCode,
                source: couponState.source,
                checkoutRenderDebug: window.jetlagzCheckoutCouponDebug || null
            });
        }
        bindCouponEvents();
        maybeAutoApplySelectedCoupon();
        maybeSyncCheckoutUiForSelectedCoupon();
    });
    
    // Initialize remove events on page load
    bindRemoveEvents();
    
    // Re-bind on checkout update
    $(document.body).on('updated_checkout', function() {
        bindRemoveEvents();
    });
});
