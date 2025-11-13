/**
 * Checkout Order Review - Remove & Update Products
 */
jQuery(document).ready(function($) {
    
    // Remove product from cart
    $(document).on('click', '.remove-product', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var cartItemKey = $button.data('cart-item-key');
        var $row = $button.closest('tr');
        
        // Show loading state
        $button.addClass('removing');
        $button.find('.remove-text').text('Usuwanie...');
        
        // AJAX request to remove item
        $.ajax({
            url: checkoutOrderConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'universal_remove_cart_item',
                cart_item_key: cartItemKey,
                security: checkoutOrderConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Remove row with animation
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Update checkout
                        $('body').trigger('update_checkout');
                        
                        // Update cart count in header if exists
                        if (response.data.cart_count !== undefined) {
                            $('.cart-count').text(response.data.cart_count);
                            if (response.data.cart_count == 0) {
                                $('.cart-dropdown').hide();
                            }
                        }
                        
                        // Show notification
                        showNotification('Produkt został usunięty z koszyka', 'success');
                    });
                } else {
                    showNotification('Nie udało się usunąć produktu', 'error');
                    $button.removeClass('removing');
                    $button.find('.remove-text').text('Usuń');
                }
            },
            error: function() {
                showNotification('Wystąpił błąd podczas usuwania produktu', 'error');
                $button.removeClass('removing');
                $button.find('.remove-text').text('Usuń');
            }
        });
    });
    
    // Update quantity
    $(document).on('click', '.update-quantity-btn', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var cartItemKey = $button.data('cart-item-key');
        var $qtyInput = $button.siblings('.qty-input');
        var newQuantity = parseInt($qtyInput.val());
        var $row = $button.closest('tr');
        
        if (newQuantity < 0) {
            newQuantity = 0;
        }
        
        // Show loading state
        $button.addClass('updating');
        $button.text('Aktualizacja...');
        
        // If quantity is 0, remove item
        if (newQuantity === 0) {
            $row.find('.remove-product').trigger('click');
            return;
        }
        
        // AJAX request to update quantity
        $.ajax({
            url: checkoutOrderConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'universal_update_cart_quantity',
                cart_item_key: cartItemKey,
                quantity: newQuantity,
                security: checkoutOrderConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update checkout
                    $('body').trigger('update_checkout');
                    showNotification('Ilość zaktualizowana', 'success');
                } else {
                    showNotification('Nie udało się zaktualizować ilości', 'error');
                }
                
                $button.removeClass('updating');
                $button.text('Aktualizuj');
            },
            error: function() {
                showNotification('Wystąpił błąd podczas aktualizacji', 'error');
                $button.removeClass('updating');
                $button.text('Aktualizuj');
            }
        });
    });
    
    // Quick quantity adjustment with +/- buttons
    $(document).on('keyup change', '.qty-input', function() {
        var $input = $(this);
        var $updateBtn = $input.siblings('.update-quantity-btn');
        var originalValue = $input.data('original-value') || $input.val();
        
        if ($input.val() != originalValue) {
            $updateBtn.addClass('changed');
        } else {
            $updateBtn.removeClass('changed');
        }
    });
    
    // Store original values
    $('.qty-input').each(function() {
        $(this).data('original-value', $(this).val());
    });
    
    // Helper function for notifications
    function showNotification(message, type) {
        // Remove existing notifications
        $('.checkout-notification').remove();
        
        var notificationClass = type === 'success' ? 'success' : 'error';
        var notification = $('<div class="checkout-notification ' + notificationClass + '">' + message + '</div>');
        
        $('.order-review-wrapper').prepend(notification);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Handle checkout update events
    $(document.body).on('updated_checkout', function() {
        // Re-store original values after checkout update
        $('.qty-input').each(function() {
            $(this).data('original-value', $(this).val());
        });
        
        // Remove changed state from buttons
        $('.update-quantity-btn').removeClass('changed updating');
    });
});