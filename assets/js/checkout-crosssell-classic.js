(function($){
  'use strict';

  // Debounce tracker aby uniknąć wielokrotnych kliknięć
  var addingProductIds = {};

  function loadCrosssells() {
    $.ajax({
      url: themeConfig.ajaxUrl || ajaxurl || window.universal_ajax && window.universal_ajax.ajax_url,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'universal_get_crosssells',
        nonce: typeof themeConfig !== 'undefined' ? themeConfig.nonce : (window.universal_ajax && window.universal_ajax.nonce)
      },
      success: function(resp) {
        if (resp && resp.success && resp.data && resp.data.html) {
          // Inject into checkout sidebar or above order review
          var $target = $('.checkout-crosssell-section');
          if (!$target.length) {
            // Fallback: inject before order review
            $target = $('.woocommerce-checkout-review-order').first();
            if (!$target.length) return;
            $target.before(resp.data.html);
          } else {
            $target.html(resp.data.html);
          }
          // Trigger event so other scripts can bind
          $(document).trigger('universalCrosssellsLoaded');
        }
      },
      error: function() {
        // Fail silently - cross-sell is optional
        console.warn('Failed to load cross-sells');
      }
    });
  }

  $(document).ready(function(){
    // Load once checkout is present
    if ($('.woocommerce-checkout').length) {
      loadCrosssells();
    } else {
      $(document.body).on('update_checkout', function(){
        loadCrosssells();
      });
    }

    // Re-bind add buttons after injected
    $(document).on('click', '.crosssell-add-btn', function(e){
      var $btn = $(this);
      
      // Jeśli to link (wariant produktu), pozwól przejść na stronę
      if ($btn.is('a')) {
        return true; // Nie blokuj domyślne zachowanie linku
      }
      
      e.preventDefault();
      
      // Jeśli to button (zwykły produkt)
      if ($btn.is('button')) {
        // Sprawdź czy to nie jest już w trakcie dodawania
        var pid = $btn.data('product-id');
        if (addingProductIds[pid]) {
          return; // Ignoruj kolejne klikania
        }
        
        var qty = 1;
        
        // Zaznacz że jest w trakcie dodawania
        addingProductIds[pid] = true;
        $btn.addClass('adding').prop('disabled', true);

        $.post(themeConfig.ajaxUrl || ajaxurl, {
          action: 'universal_add_crosssell_product',
          product_id: pid,
          quantity: qty,
          nonce: themeConfig.nonce
        }, function(resp){
          $btn.removeClass('adding');
          
          if (resp && resp.success) {
            // Zmień tekst na "Added to cart"
            var originalText = $btn.find('.btn-text').text();
            $btn.find('.btn-text').text(resp.data && resp.data.added_label ? resp.data.added_label : 'W koszyku');
            $btn.find('.btn-icon').text('✓');
            
            // Odświeżenie custom checkout table via AJAX
            $.ajax({
              url: themeConfig.ajaxUrl || ajaxurl || window.universal_ajax && window.universal_ajax.ajax_url,
              type: 'POST',
              dataType: 'json',
              data: {
                action: 'universal_refresh_checkout_table'
              },
              success: function(refreshResp){
                if (refreshResp && refreshResp.success && refreshResp.data.html) {
                  // Zamień TYLKO tabelę z produktami (nie wrapper, coupon form, totals)
                  var $oldTable = $('.universal-checkout-review-table-custom');
                  if ($oldTable.length) {
                    $oldTable.replaceWith(refreshResp.data.html);
                    // Re-bind events dla nowych elementów
                    $(document).trigger('universalCheckoutTableUpdated');
                  }
                }
              }
            });
            
            // Również odświeżenie totals
            $.ajax({
              url: window.universal_ajax && window.universal_ajax.ajax_url,
              type: 'POST',
              dataType: 'json',
              data: {
                action: 'universal_get_checkout_totals',
                nonce: window.universal_ajax && window.universal_ajax.nonce
              },
              success: function(totalsResp){
                if (totalsResp && totalsResp.success && totalsResp.data.totals_html) {
                  // Zamień TYLKO totals, nie cały wrapper
                  var $oldTotals = $('.universal-checkout-totals');
                  if ($oldTotals.length) {
                    $oldTotals.replaceWith(totalsResp.data.totals_html);
                  }
                }
              }
            });
            
            // Po 2 sekundach przywróć do "Add"
            setTimeout(function(){
              $btn.find('.btn-text').text(originalText);
              $btn.find('.btn-icon').text('+');
              $btn.prop('disabled', false);
              delete addingProductIds[pid]; // Pozwól na następne klikanie
            }, 2000);
          } else {
            // Error - przywróć button
            $btn.prop('disabled', false);
            delete addingProductIds[pid];
            alert((resp && resp.data) ? resp.data : 'Błąd dodawania produktu');
          }
        }, 'json').fail(function(){
          $btn.removeClass('adding').prop('disabled', false);
          delete addingProductIds[pid];
        });
      }
    });
  });

})(jQuery);
