/**
 * Admin Panel JavaScript
 * Obsługuje media uploader dla panelu administracyjnego motywu
 */

jQuery(document).ready(function($) {
    
    // Debug info
    console.log('🎨 Universal Theme Admin Panel initializing...');
    console.log('jQuery version:', $.fn.jquery);
    console.log('WordPress Media API available:', typeof wp !== 'undefined' && typeof wp.media !== 'undefined');
    
    // Sprawdź czy wp.media jest dostępne
    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        console.error('❌ WordPress Media API not loaded!');
        alert('Błąd: WordPress Media API nie został załadowany. Funkcja wyboru obrazów nie będzie działać.');
        return;
    }
    
    // Konfiguracja media uploadera
    var mediaUploader;
    
    /**
     * Funkcja generyczna do obsługi upload obrazów
     */
    function setupImageUploader(uploadButtonId, previewId, inputId, removeButtonId) {
        
        console.log('Setting up uploader for:', uploadButtonId);
        
        // Sprawdź czy element istnieje
        if ($(uploadButtonId).length === 0) {
            console.warn('Button not found:', uploadButtonId);
            return;
        }
        
        // Upload button
        $(uploadButtonId).click(function(e) {
            e.preventDefault();
            console.log('Upload button clicked:', uploadButtonId);
            
            // Jeśli media uploader już istnieje, otwórz go ponownie
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Stwórz nowy media uploader
            mediaUploader = wp.media({
                title: 'Wybierz obraz',
                button: {
                    text: 'Użyj tego obrazu'
                },
                library: {
                    type: 'image'
                },
                multiple: false
            });
            
            // Callback po wybraniu obrazu
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                console.log('Image selected:', attachment);
                
                // Ustaw URL w hidden input
                $(inputId).val(attachment.url);
                
                // Pokaż podgląd
                $(previewId).attr('src', attachment.url).show();
                
                // Pokaż przycisk usuwania
                $(removeButtonId).show();
            });
            
            // Otwórz media uploader
            mediaUploader.open();
        });
        
        // Remove button
        $(removeButtonId).click(function(e) {
            e.preventDefault();
            console.log('Remove button clicked:', removeButtonId);
            
            // Wyczyść input i podgląd
            $(inputId).val('');
            $(previewId).hide();
            $(this).hide();
        });
        
        console.log('✅ Uploader configured for:', uploadButtonId);
    }
    
    // Setup uploaders dla wszystkich obrazów
    if (typeof wp !== 'undefined' && typeof wp.media !== 'undefined') {
        setupImageUploader('#upload_logo_button', '#logo_preview', '#logo_url', '#remove_logo_button');
        setupImageUploader('#upload_logo_mobile_button', '#logo_mobile_preview', '#logo_mobile_url', '#remove_logo_mobile_button');
        setupImageUploader('#upload_favicon_button', '#favicon_preview', '#favicon_url', '#remove_favicon_button');
        setupImageUploader('#upload_header_bg_button', '#header_bg_preview', '#header_bg_url', '#remove_header_bg_button');
        setupImageUploader('#upload_footer_bg_button', '#footer_bg_preview', '#footer_bg_url', '#remove_footer_bg_button');
        
        console.log('✅ Media uploaders initialized successfully');
    } else {
        console.error('❌ Cannot initialize media uploaders - WordPress Media API not available');
        
        // Dodaj informację dla użytkownika
        $('.button').filter('[id*="upload"]').each(function() {
            $(this).prop('disabled', true).text('Błąd: Media API niedostępne');
        });
    }
    
    /**
     * Live preview kolorów
     */
    $('input[type="color"]').on('change', function() {
        var colorName = $(this).attr('name').match(/\[(.*?)\]/)[1];
        var colorValue = $(this).val();
        
        // Dodaj preview style do head
        var previewStyle = $('#color-preview-styles');
        if (previewStyle.length === 0) {
            previewStyle = $('<style id="color-preview-styles"></style>').appendTo('head');
        }
        
        var currentStyles = previewStyle.html();
        var newRule = '.color-preview-' + colorName + ' { background-color: ' + colorValue + ' !important; }';
        
        // Zastąp istniejącą regułę lub dodaj nową
        if (currentStyles.indexOf('.color-preview-' + colorName) !== -1) {
            currentStyles = currentStyles.replace(new RegExp('\\.color-preview-' + colorName + '.*?}'), newRule);
        } else {
            currentStyles += newRule;
        }
        
        previewStyle.html(currentStyles);
        
        // Dodaj preview box jeśli nie istnieje
        var previewBox = $(this).next('.color-preview-box');
        if (previewBox.length === 0) {
            previewBox = $('<div class="color-preview-box color-preview-' + colorName + '" style="display: inline-block; width: 30px; height: 30px; margin-left: 10px; border: 2px solid #ddd; border-radius: 3px; vertical-align: middle;"></div>');
            $(this).after(previewBox);
        }
        
        previewBox.css('background-color', colorValue);
    });
    
    // Trigger dla początkowych kolorów
    $('input[type="color"]').trigger('change');
    
    /**
     * Slider overlay opacity z live update
     */
    $('input[name="universal_theme_options[header_overlay_opacity]"]').on('input', function() {
        var opacity = $(this).val();
        $(this).next('output').text(opacity);
        
        // Live preview overlay
        var previewStyle = $('#overlay-preview-style');
        if (previewStyle.length === 0) {
            previewStyle = $('<style id="overlay-preview-style"></style>').appendTo('head');
        }
        
        previewStyle.html('.header-overlay-preview { background: rgba(0,0,0,' + (opacity/100) + ') !important; }');
    });
    
    /**
     * Dodaj tooltips dla lepszego UX
     */
    $('[title]').each(function() {
        $(this).tooltip();
    });
    
    /**
     * Accordion dla sekcji (opcjonalnie)
     */
    $('.form-table').each(function() {
        var $table = $(this);
        var $heading = $table.prev('h2');
        
        if ($heading.length) {
            $heading.css({
                'cursor': 'pointer',
                'position': 'relative'
            }).append(' <span class="toggle-indicator">▼</span>');
            
            $heading.click(function() {
                $table.slideToggle();
                var $indicator = $(this).find('.toggle-indicator');
                $indicator.text($table.is(':visible') ? '▼' : '▶');
            });
        }
    });
    
    /**
     * Auto-save notification
     */
    $('#submit').click(function() {
        var $button = $(this);
        var originalText = $button.val();
        
        $button.val('Zapisywanie...');
        
        // Po submit przywróć tekst (strona się przeładuje, ale dla UX)
        setTimeout(function() {
            $button.val(originalText);
        }, 2000);
    });
    
    /**
     * Walidacja formularza przed wysłaniem
     */
    $('form').submit(function() {
        var errors = [];
        
        // Sprawdź czy wszystkie kolory są poprawne
        $('input[type="color"]').each(function() {
            var colorValue = $(this).val();
            if (!/^#[0-9A-F]{6}$/i.test(colorValue)) {
                errors.push('Nieprawidłowy format koloru: ' + $(this).prev('label').text());
            }
        });
        
        // Sprawdź threshold darmowej wysyłki
        var threshold = $('input[name="universal_theme_options[free_shipping_threshold]"]').val();
        if (threshold < 0) {
            errors.push('Próg darmowej wysyłki nie może być ujemny');
        }
        
        // Sprawdź overlay opacity
        var opacity = $('input[name="universal_theme_options[header_overlay_opacity]"]').val();
        if (opacity < 0 || opacity > 100) {
            errors.push('Przezroczystość nakładki musi być między 0 a 100%');
        }
        
        if (errors.length > 0) {
            alert('Proszę poprawić następujące błędy:\n\n' + errors.join('\n'));
            return false;
        }
        
        return true;
    });
    
    /**
     * Podgląd obrazów w lepszej jakości
     */
    $('img[id*="_preview"]').click(function() {
        var src = $(this).attr('src');
        if (src) {
            window.open(src, '_blank');
        }
    }).css({
        'cursor': 'pointer',
        'border': '2px solid #ddd',
        'border-radius': '4px',
        'padding': '2px'
    }).hover(
        function() { $(this).css('border-color', '#0073aa'); },
        function() { $(this).css('border-color', '#ddd'); }
    );
    
    /**
     * Dodaj informacje o rozmiarach obrazów
     */
    function addImageSizeInfo() {
        var sizeInfo = {
            'logo': 'Logo główne: 300×100px',
            'logo_mobile': 'Logo mobilne: 200×60px',
            'favicon': 'Favicon: 32×32px',
            'header_background_image': 'Tło header: 1920×300px',
            'footer_background_image': 'Tło footer: 1920×400px'
        };
        
        $.each(sizeInfo, function(field, info) {
            var $field = $('input[name="universal_theme_options[' + field + ']"]').parent();
            if ($field.find('.size-info').length === 0) {
                $field.append('<br><small class="size-info" style="color: #0073aa; font-weight: bold;">💡 ' + info + '</small>');
            }
        });
    }
    
    addImageSizeInfo();
    
    /**
     * Test ustawień motywu
     */
    $('#test-theme-settings').click(function() {
        var $button = $(this);
        var $result = $('#test-result');
        
        $button.prop('disabled', true).text('🔄 Testuję...');
        $result.text('').removeClass('success error');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'universal_theme_test_settings',
                nonce: $('#test-theme-settings').data('nonce') || 'test'
            },
            success: function(response) {
                if (response.success) {
                    $result.html('✅ <span style="color: green;">Ustawienia działają poprawnie!</span>');
                    
                    // Pokaż szczegóły w konsoli
                    console.log('Theme Settings Test:', response.data);
                } else {
                    $result.html('❌ <span style="color: red;">Błąd: ' + (response.data || 'Nieznany błąd') + '</span>');
                }
            },
            error: function() {
                $result.html('❌ <span style="color: red;">Błąd połączenia z serwerem</span>');
            },
            complete: function() {
                $button.prop('disabled', false).text('🧪 Testuj Ustawienia');
            }
        });
    });
    
    // Komunikat o załadowaniu panelu
    console.log('🎨 Universal Theme Admin Panel loaded successfully!');
});