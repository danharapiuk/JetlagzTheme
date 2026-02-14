/**
 * Checkout Invoice Fields - JavaScript
 * Obsługa pokazywania/ukrywania pól faktury VAT
 */

jQuery(document).ready(function ($) {
    'use strict';

    // Ukryj/pokaż pole NIP w zależności od checkboxa
    function toggleInvoiceFields() {
        const invoiceCheckbox = $('#billing_invoice_required_field input[type="checkbox"]');
        const nipField = $('#billing_nip_field');

        if (invoiceCheckbox.is(':checked')) {
            nipField.slideDown(300);
        } else {
            nipField.slideUp(300);
        }
    }

    // Inicjalizacja przy ładowaniu strony
    if ($('#billing_invoice_required_field').length) {
        // Ukryj pole NIP na start
        $('#billing_nip_field').hide();
        
        // Sprawdź stan checkboxa (może być zapamiętany)
        toggleInvoiceFields();

        // Event listener dla checkboxa
        $(document).on('change', '#billing_invoice_required_field input[type="checkbox"]', function () {
            toggleInvoiceFields();
        });

        // Obsługa update_checkout
        $(document.body).on('update_checkout', function () {
            setTimeout(toggleInvoiceFields, 100);
        });
    }

    // Formatowanie NIP podczas wpisywania (opcjonalne)
    $(document).on('input', '#billing_nip', function () {
        let value = $(this).val().replace(/[^0-9]/g, ''); // Usuń wszystko oprócz cyfr
        
        // Formatuj jako XXX-XXX-XX-XX
        if (value.length > 3) {
            value = value.substring(0, 3) + '-' + value.substring(3);
        }
        if (value.length > 7) {
            value = value.substring(0, 7) + '-' + value.substring(7);
        }
        if (value.length > 10) {
            value = value.substring(0, 10) + '-' + value.substring(10);
        }
        if (value.length > 13) {
            value = value.substring(0, 13); // Maksymalnie 10 cyfr + 3 myślniki
        }
        
        $(this).val(value);
    });
});
