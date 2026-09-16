(function () {
    'use strict';

    function initBranchProductsSliders() {
        if (typeof Swiper === 'undefined') {
            return;
        }

        const sliders = document.querySelectorAll(
            '.abm-branch-products .abm-products-swiper'
        );

        sliders.forEach(function (slider) {
            if (slider.classList.contains('swiper-initialized')) {
                return;
            }

            const container = slider.closest('.abm-branch-products');

            if (!container) {
                return;
            }

            const nextButton = container.querySelector(
                '.swiper-button-next'
            );

            const prevButton = container.querySelector(
                '.swiper-button-prev'
            );

            const pagination = container.querySelector(
                '.swiper-pagination'
            );

            const slideCount = slider.querySelectorAll(
                '.swiper-slide'
            ).length;

            /*
             * The Elementor container that contains
             * the second shortcode has:
             *
             * massar-products-grid
             *
             * This instance must always show 2 products.
             */
            const isTwoColumnSlider = document.querySelector(
                '.massar-products-grid .abm-branch-products .abm-products-swiper'
            ) === slider;

            const options = {
                slidesPerView: 2,
                spaceBetween: 12,

                watchOverflow: true,

                grabCursor: true,

                loop: slideCount > 4,

                navigation: {
                    nextEl: nextButton,
                    prevEl: prevButton
                },

                pagination: {
                    el: pagination,
                    clickable: true
                }
            };

            /*
             * Only the normal shortcode gets
             * the responsive 2 / 3 / 4 behavior.
             *
             * The slider inside .massar-products-grid
             * stays at 2 products on every screen.
             */
            if (!isTwoColumnSlider) {
                options.breakpoints = {
                    768: {
                        slidesPerView: 3,
                        spaceBetween: 18
                    },

                    1024: {
                        slidesPerView: 4,
                        spaceBetween: 24
                    }
                };
            }

            new Swiper(
                slider,
                options
            );
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initBranchProductsSliders
        );
    } else {
        initBranchProductsSliders();
    }

    /*
     * Elementor frontend support.
     */
    window.addEventListener(
        'elementor/frontend/init',
        initBranchProductsSliders
    );
})();