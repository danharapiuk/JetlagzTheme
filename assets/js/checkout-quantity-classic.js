/**
 * WooCommerce Classic Checkout - Quantity Controls
 * Dodaje przyciski +/- do custom Order Summary
 * Pracuje z nowym custom checkout table layout
 */

jQuery(document).ready(function($) {
    
    console.log('🚨 CLASSIC CHECKOUT QUANTITY CONTROLS LOADED! 🚨');
    console.log('🔍 jQuery version:', $.fn.jquery);
    console.log('🔍 Is checkout page?', $('body').hasClass('woocommerce-checkout'));
    console.log('🔍 universal_ajax available?', typeof universal_ajax !== 'undefined' ? universal_ajax : 'NOT AVAILABLE!');
    
    if ($('body').hasClass('woocommerce-checkout')) {
        console.log('✅ On classic checkout page - initializing quantity controls...');
        
        // Initialize quantity controls
        setTimeout(function() {
            bindQuantityEvents();
        }, 500); // Małe opóźnienie dla pewności że DOM się załadował
        
        // Re-initialize after checkout updates
        $(document.body).on('updated_checkout', function() {
            console.log('🔄 Checkout updated - re-binding quantity events');
            setTimeout(bindQuantityEvents, 300);
        });
        
        // Re-initialize after custom checkout table update (z cross-sell)
        $(document).on('universalCheckoutTableUpdated', function() {
            console.log('🔄 Custom checkout table updated - re-binding quantity events');
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
        console.log('🔧 Binding quantity button events...');
        
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
            
            console.log(`🔄 ${action} clicked: ${productName} from ${currentQty} to ${newQty}`);
            
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
            
            console.log(`✏️ Editing quantity: current=${currentQty}, cartKey=${cartKey}`);
            
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
        
        console.log('🔗 Quantity button events bound');
    }
    
    /**
     * Zatwierdź edycję ilości
     */
    function submitQtyEdit($display, cartKey) {
        const $input = $display.find('.qty-input');
        const newQty = parseInt($input.val()) || 1;
        const $item = $display.closest('.universal-checkout-item');
        const currentQty = parseInt($display.attr('data-qty')) || 1;
        
        console.log(`✅ Quantity edit submitted: ${currentQty} -> ${newQty}`);
        
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
        
        console.log(`📡 Sending AJAX request: cartKey="${cartKey}", qty=${newQty}`);
        
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
                console.log('✅ AJAX Success:', response);
                
                if (response.success) {
                    // Aktualizuj wyświetlanie ilości
                    $qtyDisplay.removeClass('editing').text(newQty).attr('data-qty', newQty);
                    
                    // Aktualizuj ceny
                    const $priceUnit = $item.find('.checkout-item-price-unit');
                    const unitPrice = parseFloat($priceUnit.attr('data-unit-price')) || 0;
                    const totalPrice = (unitPrice * newQty).toFixed(2);
                    
                    // Formatuj cenę (dodaj separator tysięcy i walutę)
                    const $priceTotal = $item.find('.checkout-item-price-total .price');
                    const $priceTotalContainer = $item.find('.checkout-item-price-total');
                    
                    // Pokaż/ukryj razem w zależności od ilości
                    if (newQty > 1) {
                        $priceTotalContainer.show();
                        // Prosta konwersja - polegaj na WooCommerce formatowaniu
                        $priceTotal.text(formatPrice(totalPrice));
                    } else {
                        $priceTotalContainer.hide();
                    }
                    
                    // ⭐ WAŻNE: Odświeżenie TOTALS na ekranie
                    refreshCheckoutTotals();
                    
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
        console.log('🔧 Binding remove button events...');
        
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
            
            console.log(`🗑️ Remove button clicked for: ${productName}`);
            
            // Pokaż modal potwierdzenia
            showRemoveConfirmModal(productName, productQty, cartKey, $item);
        });
        
        console.log('🗑️ Remove button events bound');
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
        
        console.log(`📡 Removing item from cart: cartKey="${cartKey}"`);
        
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
                console.log('✅ Item removed:', response);
                
                if (response.success) {
                    // Animuj usunięcie
                    $item.animate({
                        opacity: 0,
                        height: 0,
                        marginBottom: 0
                    }, 300, function() {
                        $(this).remove();
                        
                        // ⭐ Odświeżenie TOTALS
                        refreshCheckoutTotals();
                        
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
        console.log('🔄 Refreshing checkout totals...');
        
        $.ajax({
            url: universal_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'universal_get_checkout_totals',
                nonce: universal_ajax.nonce
            },
            success: function(response) {
                console.log('✅ Totals refreshed:', response);
                
                if (response.success && response.data.totals_html) {
                    // Zamień stary HTML totals na nowy
                    $('.universal-checkout-totals').replaceWith(response.data.totals_html);
                    console.log('💰 Totals updated:', response.data.cart_total);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Error refreshing totals:', { xhr, status, error });
            }
        });
    }
    /**
     * Bind events dla Apply Coupon button
     */
    function bindCouponEvents() {
        console.log('🔧 Binding coupon events...');
        
        // Usuń poprzednie eventy
        $(document).off('click.couponBtn', '#apply-coupon-btn');
        $(document).off('keypress.couponInput', '#coupon_code');
        
        // Click na Apply button
        $(document).on('click.couponBtn', '#apply-coupon-btn', function(e) {
            e.preventDefault();
            const couponCode = $('#coupon_code').val().trim();
            
            if (!couponCode) {
                showNotification('Wpisz kod kuponu', 'error');
                return;
            }
            
            applyCouponCode(couponCode);
        });
        
        // Enter w input fieldie
        $(document).on('keypress.couponInput', '#coupon_code', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#apply-coupon-btn').click();
            }
        });
        
        console.log('🎫 Coupon events bound');
    }
    
    /**
     * Zastosuj kupon
     */
    function applyCouponCode(couponCode) {
        console.log(`🎫 Applying coupon: ${couponCode}`);
        
        const $button = $('#apply-coupon-btn');
        const $input = $('#coupon_code');
        
        $button.addClass('loading').prop('disabled', true);
        $input.prop('disabled', true);
        
        $.ajax({
            url: wc_checkout_params.checkout_url || '/checkout/',
            type: 'POST',
            dataType: 'json',
            data: {
                post_data: {
                    coupon_code: couponCode,
                    post_data: $('form.checkout').serialize()
                }
            },
            success: function(response) {
                console.log('✅ Coupon applied:', response);
                
                // Odświeżenie totals
                refreshCheckoutTotals();
                
                // Clear input i pokaż sukces
                $input.val('');
                showNotification(`Kupon "${couponCode}" zastosowany`, 'success');
            },
            error: function(xhr, status, error) {
                console.error('❌ Coupon error:', { xhr, status, error });
                
                // WooCommerce zwraca error w response
                let errorMsg = 'Kod kuponu jest nieprawidłowy';
                if (xhr.responseJSON && xhr.responseJSON.messages) {
                    errorMsg = xhr.responseJSON.messages;
                }
                
                showNotification(errorMsg, 'error');
            },
            complete: function() {
                $button.removeClass('loading').prop('disabled', false);
                $input.prop('disabled', false);
            }
        });
    }
    
    // Initialize coupon events on page load
    bindCouponEvents();
    
    // Re-bind on checkout update
    $(document.body).on('updated_checkout', function() {
        console.log('🔄 Checkout updated - re-binding coupon events');
        bindCouponEvents();
    });
    
    // Initialize remove events on page load
    bindRemoveEvents();
    
    // Re-bind on checkout update
    $(document.body).on('updated_checkout', function() {
        console.log('🔄 Checkout updated - re-binding remove events');
        bindRemoveEvents();
    });
});
