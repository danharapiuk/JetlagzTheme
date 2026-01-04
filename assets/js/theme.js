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