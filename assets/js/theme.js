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
                console.log('Quick view dla produktu:', productId);
                
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
        }
    };

    // Inicjalizacja po załadowaniu DOM
    $(document).ready(function() {
        UniversalTheme.init();
    });

    // Udostępnij obiekt globalnie
    window.UniversalTheme = UniversalTheme;

})(jQuery);