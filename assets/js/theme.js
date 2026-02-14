/**
 * JavaScript dla Universal Theme
 */

(function($) {
    'use strict';

    // Obiekt główny motywu
    const UniversalTheme = {
        
        // Inicjalizacja
        init: function() {
            this.preloader();
            this.quickView();
            this.smoothScroll();
            this.mobileMenu();
            this.lazyLoading();
            this.productGallery();
            this.cartUpdates();
            this.stockAvailabilityDropdown();
        },

        // Preloader
        preloader: function() {
            $(window).on('load', function() {
                setTimeout(function() {
                    $('#universal-preloader').addClass('hidden');
                    setTimeout(function() {
                        $('#universal-preloader').remove();
                    }, 500);
                }, 300);
            });
        },

        // Quick View dla produktów
        quickView: function() {
            $(document).on('click', '.universal-quick-view', function(e) {
                e.preventDefault();
                
                const productId = $(this).data('product-id');
                
                // Tutaj możesz dodać AJAX do ładowania quick view
                
                // Przykład prostego modala
                if (!$('#universal-quick-view-modal').length) {
                    $('body').append(`
                        <div id="universal-quick-view-modal" class="universal-modal">
                            <div class="universal-modal-content">
                                <span class="universal-close">&times;</span>
                                <div class="universal-modal-body">
                                    <p>Ładowanie...</p>
                                </div>
                            </div>
                        </div>
                    `);
                }
                
                $('#universal-quick-view-modal').show();
            });

            // Zamknięcie modala
            $(document).on('click', '.universal-close, .universal-modal', function(e) {
                if (e.target === this) {
                    $('#universal-quick-view-modal').hide();
                }
            });
        },

        // Płynne przewijanie
        smoothScroll: function() {
            $('a[href*="#"]').not('[href="#"]').not('[href="#0"]').click(function(event) {
                if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') && 
                    location.hostname === this.hostname) {
                    
                    let target = $(this.hash);
                    target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
                    
                    if (target.length) {
                        event.preventDefault();
                        
                        // Jeśli to link do reviews, otwórz zakładkę
                        if (this.hash === '#reviews' || this.hash === '#reviews') {
                            const reviewsTab = $('.woocommerce-tabs .tabs li.reviews_tab a');
                            if (reviewsTab.length && !reviewsTab.parent().hasClass('active')) {
                                reviewsTab.trigger('click');
                            }
                        }
                        
                        $('html, body').animate({
                            scrollTop: target.offset().top - 100
                        }, 1000);
                    }
                }
            });
        },

        // Menu mobilne
        mobileMenu: function() {
            // Dodaj przycisk do toggle menu mobilnego
            if (!$('.universal-mobile-toggle').length) {
                $('.site-header').append('<button class="universal-mobile-toggle">☰</button>');
            }

            $('.universal-mobile-toggle').on('click', function() {
                $('.main-navigation').toggleClass('mobile-open');
                $(this).toggleClass('active');
            });

            // Zamknij menu przy kliknięciu na link
            $('.main-navigation a').on('click', function() {
                if ($(window).width() <= 768) {
                    $('.main-navigation').removeClass('mobile-open');
                    $('.universal-mobile-toggle').removeClass('active');
                }
            });
        },

        // Lazy loading dla obrazów
        lazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },

        // Ulepszenia galerii produktów
        productGallery: function() {
            // Dodaj efekt hover na zdjęcia produktów
            $('.woocommerce ul.products li.product img').hover(
                function() {
                    $(this).addClass('hovered');
                },
                function() {
                    $(this).removeClass('hovered');
                }
            );

            // Zwiększ zdjęcie przy hover (jeśli nie ma zoom)
            $('.single-product .woocommerce-product-gallery__image').hover(
                function() {
                    $(this).find('img').addClass('zoom-hover');
                },
                function() {
                    $(this).find('img').removeClass('zoom-hover');
                }
            );
        },

        // Aktualizacje koszyka
        cartUpdates: function() {
            // Dodaj animację przy dodawaniu do koszyka
            $(document).on('added_to_cart', function(event, fragments, cart_hash, $button) {
                $button.addClass('added');
                
                setTimeout(function() {
                    $button.removeClass('added');
                }, 2000);

                // Pokaż mini powiadomienie
                UniversalTheme.showNotification('Produkt dodany do koszyka!', 'success');
            });

            // Aktualizacja licznika koszyka z animacją
            $(document).on('wc_fragments_refreshed', function() {
                $('.cart-contents-count').addClass('bounce');
                setTimeout(function() {
                    $('.cart-contents-count').removeClass('bounce');
                }, 600);
            });
        },

        // Powiadomienia
        showNotification: function(message, type = 'info') {
            const notification = $(`
                <div class="universal-notification universal-notification-${type}">
                    ${message}
                </div>
            `);

            if (!$('.universal-notifications').length) {
                $('body').append('<div class="universal-notifications"></div>');
            }

            $('.universal-notifications').append(notification);

            // Animacja pojawienia
            setTimeout(function() {
                notification.addClass('show');
            }, 100);

            // Automatyczne usunięcie
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 3000);
        },

        // Funkcje pomocnicze
        utils: {
            // Debounce function
            debounce: function(func, wait, immediate) {
                let timeout;
                return function() {
                    const context = this, args = arguments;
                    const later = function() {
                        timeout = null;
                        if (!immediate) func.apply(context, args);
                    };
                    const callNow = immediate && !timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                    if (callNow) func.apply(context, args);
                };
            },

            // Sprawdź czy element jest w viewport
            isInViewport: function(element) {
                const rect = element.getBoundingClientRect();
                return (
                    rect.top >= 0 &&
                    rect.left >= 0 &&
                    rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                    rect.right <= (window.innerWidth || document.documentElement.clientWidth)
                );
            }
        },

        // Stock Availability Dropdown
        stockAvailabilityDropdown: function() {
            // Toggle dropdown przy kliknięciu w trigger
            $(document).on('click', '.stock-availability-trigger', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $trigger = $(this);
                const $wrapper = $trigger.closest('.stock-availability-wrapper');
                const $dropdown = $wrapper.find('.stock-availability-dropdown');
                
                if ($dropdown.length === 0) {
                    return;
                }
                
                // Toggle dropdown
                $dropdown.toggleClass('show');
                $trigger.toggleClass('active');
                
                // Jeśli dropdown został otwarty, wypełnij ukryte pola
                if ($dropdown.hasClass('show')) {
                    // Znajdź informację o rozmiarze z różnych źródeł
                    let sizeInfo = '';
                    
                    // Próba 1: WooCommerce variations form - select dla rozmiaru
                    const $sizeSelect = $('select[name*="attribute_pa_rozmiar"], select[name*="attribute_pa_size"], select[name*="pa_rozmiar"], select[name*="pa_size"]');
                    if ($sizeSelect.length && $sizeSelect.val()) {
                        sizeInfo = $sizeSelect.find('option:selected').text().trim();
                    }
                    
                    // Próba 2: Sprawdź inne selektory wariantów
                    if (!sizeInfo) {
                        const $selectedVariation = $('.variations select:visible option:selected').not('[value=""]');
                        if ($selectedVariation.length) {
                            sizeInfo = $selectedVariation.text().trim();
                        }
                    }
                    
                    // Próba 3: Sprawdź czy jest informacja o wariancie w summary
                    if (!sizeInfo) {
                        const $variationDescription = $('.woocommerce-variation-description, .single_variation_wrap .woocommerce-variation');
                        if ($variationDescription.length) {
                            const variationText = $variationDescription.text();
                            const sizeMatch = variationText.match(/rozmiar[:\s]*([A-Z0-9\/\-\s]+)/i);
                            if (sizeMatch) {
                                sizeInfo = sizeMatch[1].trim();
                            }
                        }
                    }
                    
                    // Fallback: jeśli nadal nie ma rozmiaru, sprawdź czy to produkt bez wariantów
                    if (!sizeInfo) {
                        // Sprawdź tytuł produktu czy zawiera rozmiar
                        const productTitle = $('.product_title.entry-title').text();
                        const titleSizeMatch = productTitle.match(/[A-Z]{1,3}$|rozmiar\s+([A-Z0-9\/\-\s]+)/i);
                        if (titleSizeMatch) {
                            sizeInfo = titleSizeMatch[1] || titleSizeMatch[0];
                        } else {
                            sizeInfo = 'Rozmiar nie został wybrany';
                        }
                    }
                    
                    // Dodaj ukryte pole do formularza CF7 z informacją o rozmiarze
                    const $form = $dropdown.find('.wpcf7-form');
                    if ($form.length) {
                        // Usuń istniejące ukryte pole jeśli istnieje
                        $form.find('input[name="requested-size"]').remove();
                        $form.find('input[name="product-name"]').remove();
                        
                        // Dodaj nowe ukryte pola
                        const productTitle = $('.product_title.entry-title').text() || 'Nieznany produkt';
                        $form.append(`<input type="hidden" name="requested-size" value="${sizeInfo}">`);
                        $form.append(`<input type="hidden" name="product-name" value="${productTitle}">`);
                    }
                }
            });
            
            // Zamknij dropdown przy kliknięciu poza nim (ale nie podczas submitu formularza)
            $(document).on('click', function(e) {
                const $target = $(e.target);
                
                // Nie zamykaj jeśli kliknięto w formularzu lub jeśli formularz jest w trakcie wysyłania
                if (!$target.closest('.stock-availability-wrapper').length && 
                    !$target.closest('.wpcf7-form').length &&
                    !$('.wpcf7-form').hasClass('submitting')) {
                    $('.stock-availability-dropdown').removeClass('show');
                    $('.stock-availability-trigger').removeClass('active');
                }
            });
            
            // Debug - sprawdź wszystkie eventy CF7
            $(document).on('wpcf7submit wpcf7mailsent wpcf7mailfailed', '.wpcf7-form', function(event) {
                console.log('CF7 Event:', event.type, event);
            });
            
            // Obsługa sukcesu Contact Form 7 - próbuj różne eventy
            $(document).on('wpcf7mailsent wpcf7submit', '.wpcf7-form', function(event) {
                console.log('CF7 Success event triggered:', event.type);
                
                // Sprawdź czy formularz został pomyślnie wysłany
                const isSuccess = event.type === 'wpcf7mailsent' || 
                                (event.type === 'wpcf7submit' && 
                                 $(this).closest('.wpcf7').hasClass('sent'));
                
                if (!isSuccess) {
                    console.log('Not a success event, skipping');
                    return;
                }
                
                console.log('Showing success message');
                
                const $dropdown = $(this).closest('.stock-availability-dropdown');
                const $trigger = $('.stock-availability-trigger.active');
                
                // Zatrzymaj inne eventy które mogłyby zamknąć dropdown
                event.stopPropagation();
                event.preventDefault();
                
                // Pokaż komunikat sukcesu
                $(this).parent().html(`
                    <div style="text-align: center; color: #28a745; padding: 30px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">✓</div>
                        <h4 style="margin: 0 0 10px 0; color: #28a745; font-size: 18px;">Mail został wysłany!</h4>
                        <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.4;">
                            Sprawdzimy dostępność tego rozmiaru u producenta<br>
                            i powiadomimy Cię mailem gdy będzie dostępny w naszym sklepie.
                        </p>
                        <div style="margin-top: 15px; font-size: 12px; color: #999;">
                            To okno zamknie się za 5 sekund...
                        </div>
                    </div>
                `);
                
                // Zamknij dropdown po 5 sekundach
                setTimeout(() => {
                    console.log('Closing dropdown after success');
                    $dropdown.removeClass('show');
                    $trigger.removeClass('active');
                }, 5000);
            });
            
            // Fallback - sprawdzaj status formularza co sekundę
            setInterval(function() {
                $('.wpcf7-mail-sent-ok:visible').each(function() {
                    const $message = $(this);
                    const $dropdown = $message.closest('.stock-availability-dropdown');
                    const $trigger = $('.stock-availability-trigger.active');
                    
                    if ($dropdown.length && !$dropdown.data('success-shown')) {
                        console.log('Fallback: Found success message, showing custom message');
                        $dropdown.data('success-shown', true);
                        
                        $message.closest('.cf7-form-wrapper').html(`
                            <div style="text-align: center; color: #28a745; padding: 30px;">
                                <div style="font-size: 48px; margin-bottom: 15px;">✓</div>
                                <h4 style="margin: 0 0 10px 0; color: #28a745; font-size: 18px;">Mail został wysłany!</h4>
                                <p style="margin: 0; color: #666; font-size: 14px; line-height: 1.4;">
                                    Sprawdzimy dostępność tego rozmiaru u producenta<br>
                                    i powiadomimy Cię mailem gdy będzie dostępny w naszym sklepie.
                                </p>
                                <div style="margin-top: 15px; font-size: 12px; color: #999;">
                                    To okno zamknie się za 5 sekund...
                                </div>
                            </div>
                        `);
                        
                        setTimeout(() => {
                            $dropdown.removeClass('show');
                            $trigger.removeClass('active');
                        }, 5000);
                    }
                });
            }, 1000);
            
            // Obsługa błędu Contact Form 7
            $(document).on('wpcf7mailfailed', '.wpcf7-form', function(event) {
                const $dropdown = $(this).closest('.stock-availability-dropdown');
                
                // Pokaż komunikat błędu
                $(this).parent().html(`
                    <div style="text-align: center; color: #dc3545; padding: 30px;">
                        <div style="font-size: 48px; margin-bottom: 15px;">✗</div>
                        <h4 style="margin: 0 0 10px 0; color: #dc3545; font-size: 18px;">Wystąpił błąd</h4>
                        <p style="margin: 0 0 15px 0; color: #666; font-size: 14px; line-height: 1.4;">
                            Nie udało się wysłać wiadomości.<br>
                            Spróbuj ponownie lub skontaktuj się z nami bezpośrednio.
                        </p>
                        <button onclick="location.reload()" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-size: 14px;">
                            Spróbuj ponownie
                        </button>
                    </div>
                `);
            });
        }
    };

    // Inicjalizacja po załadowaniu DOM
    $(function() {
        UniversalTheme.init();
        
        // Lazy load reviews section when it comes into viewport
        var reviewsSection = document.getElementById('product-reviews');
        
        if (reviewsSection) {
            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        // Add loaded class to show the section
                        entry.target.classList.add('loaded');
                        
                        // Initialize Swiper after section becomes visible
                        setTimeout(function() {
                            initReviewsSwiper();
                        }, 100);
                        
                        // Stop observing once loaded
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '200px' // Start loading 200px before it enters viewport
            });
            
            observer.observe(reviewsSection);
        }
        
        // Scroll to reviews functionality
        $(document).on('click', 'a.reviews-link, a[href="#product-reviews"], .woocommerce-review-link', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var reviewsSection = document.getElementById('product-reviews');
            
            if (reviewsSection) {
                // Make sure section is visible before scrolling
                reviewsSection.classList.add('loaded');
                
                // Use scrollIntoView instead of window.scrollTo
                reviewsSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Add small offset by scrolling up a bit after
                setTimeout(function() {
                    window.scrollBy(0, -100);
                }, 100);
            }
            return false;
        });
    
        // Reviews Swiper Slider - initialization function
    function initReviewsSwiper() {
        if (typeof Swiper === 'undefined' || !$('.reviews-swiper').length) {
            return;
        }
        
        var reviewsSwiper = new Swiper('.reviews-swiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            centeredSlides: false,
            width: null,
            navigation: {
                nextEl: '.reviews-slider-next',
                prevEl: '.reviews-slider-prev',
            },
            pagination: {
                el: '.reviews-slider-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            autoHeight: true,
            loop: false,
            keyboard: {
                enabled: true,
            },
            a11y: {
                enabled: true,
            },
            watchOverflow: true,
            observer: true,
            observeParents: true
        });
        
        // Update slider when sorting reviews
        $('#reviews-sort').on('change', function() {
            const sortBy = $(this).val();
            const slides = $('.reviews-swiper .swiper-slide').get();
            
            slides.sort(function(a, b) {
                const $reviewA = $(a).find('.review-card');
                const $reviewB = $(b).find('.review-card');
                
                switch(sortBy) {
                    case 'newest':
                        return $reviewB.data('date') - $reviewA.data('date');
                    case 'oldest':
                        return $reviewA.data('date') - $reviewB.data('date');
                    case 'highest':
                        return $reviewB.data('rating') - $reviewA.data('rating');
                    case 'lowest':
                        return $reviewA.data('rating') - $reviewB.data('rating');
                    default:
                        return 0;
                }
            });
            
            const $wrapper = $('.reviews-swiper .swiper-wrapper');
            $wrapper.empty();
            $.each(slides, function(idx, slide) {
                $wrapper.append(slide);
            });
            
            reviewsSwiper.update();
            reviewsSwiper.slideTo(0);
        });
        
        // Filter reviews by rating
        $('.rating-bar-item').on('click keypress', function(e) {
            if (e.type === 'keypress' && e.which !== 13 && e.which !== 32) {
                return;
            }
            
            e.preventDefault();
            
            const rating = $(this).data('rating');
            const isActive = $(this).hasClass('active');
            
            if (isActive) {
                // Show all slides
                $(this).removeClass('active').attr('aria-pressed', 'false');
                $('.reviews-swiper .swiper-slide').show();
            } else {
                // Filter by rating
                $('.rating-bar-item').removeClass('active').attr('aria-pressed', 'false');
                $(this).addClass('active').attr('aria-pressed', 'true');
                
                $('.reviews-swiper .swiper-slide').each(function() {
                    const slideRating = $(this).find('.review-card').data('rating');
                    if (slideRating == rating) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
            
            reviewsSwiper.update();
            reviewsSwiper.slideTo(0);
        });
    }
    
    // Read more functionality for long reviews
        $(document).on('click', '.read-more-toggle', function(e) {
            e.preventDefault();
            const $content = $(this).closest('.review-content');
            const isExpanded = $content.hasClass('expanded');
            
            $content.toggleClass('expanded');
            $(this)
                .text(isExpanded ? 'Read more' : 'Read less')
                .attr('aria-expanded', !isExpanded);
        });
    }); // End of $(function() {

    // Udostępnij obiekt globalnie
    window.UniversalTheme = UniversalTheme;

})(jQuery);