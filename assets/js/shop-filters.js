/**
 * Shop Filters JavaScript
 */

(function($) {
    'use strict';
    

    $(document).ready(function() {
        
        const $filtersToggleInline = $('#filters-toggle-inline');
        const $filters = $('#shop-filters');
        const $filtersClose = $('#filters-close');
        const $filtersOverlay = $('.shop-filters-overlay');
        const $applyFilters = $('#apply-filters');
        const $resetFilters = $('#reset-filters');
        const $filtersCount = $('.filters-count-inline');


        // Toggle filters panel
        $filtersToggleInline.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $filters.toggleClass('active');
            $filtersOverlay.toggleClass('active');
            $('body').toggleClass('filters-open');
        });

        // Close filters
        $filtersClose.on('click', function(e) {
            e.preventDefault();
            $filters.removeClass('active');
            $filtersOverlay.removeClass('active');
            $('body').removeClass('filters-open');
        });

        // Close on overlay click
        $filtersOverlay.on('click', function() {
            $filters.removeClass('active');
            $filtersOverlay.removeClass('active');
            $('body').removeClass('filters-open');
        });

        // Close on outside click
        $(document).on('click', function(e) {
            if ($filters.hasClass('active') && !$(e.target).closest('#shop-filters, .filters-toggle-inline').length) {
                $filters.removeClass('active');
                $filtersOverlay.removeClass('active');
                $('body').removeClass('filters-open');
            }
        });

    // Count active filters
    function updateFiltersCount() {
        let count = 0;

        // Count checkboxes
        $filters.find('input[type="checkbox"]:checked').each(function() {
            count++;
        });

        // Count price range
        if ($('#min_price').val() || $('#max_price').val()) {
            count++;
        }

        // Update display
        if (count > 0) {
            $filtersCount.text(count).show();
        } else {
            $filtersCount.hide();
        }
    }

    // Apply filters
    $applyFilters.on('click', function() {
        const url = new URL(window.location.href);
        const params = new URLSearchParams();

        // Get base URL (shop page or category)
        const baseUrl = url.pathname;

        // Categories
        $filters.find('input[name="product_cat[]"]:checked').each(function() {
            params.append('product_cat[]', $(this).val());
        });

        // Sizes
        $filters.find('input[name="pa_rozmiar[]"]:checked').each(function() {
            params.append('pa_rozmiar[]', $(this).val());
        });

        // Colors
        $filters.find('input[name="pa_kolor[]"]:checked').each(function() {
            params.append('pa_kolor[]', $(this).val());
        });

        // Price
        const minPrice = $('#min_price').val();
        const maxPrice = $('#max_price').val();
        if (minPrice) params.set('min_price', minPrice);
        if (maxPrice) params.set('max_price', maxPrice);

        // Stock status
        if ($filters.find('input[name="stock_status"]:checked').length) {
            params.set('stock_status', 'instock');
        }

        // On sale
        if ($filters.find('input[name="on_sale"]:checked').length) {
            params.set('on_sale', '1');
        }

        // Redirect with filters
        const queryString = params.toString();
        if (queryString) {
            window.location.href = baseUrl + '?' + queryString;
        } else {
            window.location.href = baseUrl;
        }
    });

    // Reset filters
    $resetFilters.on('click', function() {
        // Clear all checkboxes
        $filters.find('input[type="checkbox"]').prop('checked', false);
        
        // Clear price inputs
        $('#min_price, #max_price').val('');
        
        // Redirect to base URL
        window.location.href = window.location.pathname;
    });

    // Update count on page load
    updateFiltersCount();

    // Update count when filters change
    $filters.find('input').on('change', function() {
        updateFiltersCount();
    });

    // Apply filters on Enter key in price inputs
    $('#min_price, #max_price').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $applyFilters.click();
        }
    });

    // Products per page change
    $('#products-per-page').on('change', function() {
        const url = new URL(window.location.href);
        const perPage = $(this).val();
        
        if (perPage) {
            url.searchParams.set('per_page', perPage);
        } else {
            url.searchParams.delete('per_page');
        }
        
        window.location.href = url.toString();
    });

    // Sorting change
    $('#orderby').on('change', function() {
        const url = new URL(window.location.href);
        const orderby = $(this).val();
        
        if (orderby && orderby !== 'menu_order') {
            url.searchParams.set('orderby', orderby);
        } else {
            url.searchParams.delete('orderby');
        }
        
        window.location.href = url.toString();
    });
    });
})(jQuery);
