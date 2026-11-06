"use strict";



$(document).ready(function () {
    const DESKTOP_BREAKPOINT = 767;
    const ANIMATION_DELAY = 150;

    const $window = $(window);
    const $stickyTop = $('.product-details-sticky-top');
    const $stickySection = $('.product-details-sticky');

    function bindStickyHover() {
        if ($stickySection.hasClass('multi-variation-product')) {
            $stickySection.hover(
                function () {
                    $stickyTop.stop(true, true).delay(ANIMATION_DELAY).slideDown();
                },
                function () {
                    $stickyTop.stop(true, true).delay(ANIMATION_DELAY).slideUp();
                }
            );
        }
    }

    function unbindStickyHover() {
        $stickySection.off('mouseenter mouseleave');
        $stickyTop.stop(true, true).hide();
    }

    function handleBreakpoint() {
        const windowWidth = $window.width();

        if (windowWidth > DESKTOP_BREAKPOINT) {
            bindStickyHover();
        } else {
            unbindStickyHover();
        }
    }

    let resizeTimeout;
    $window.on('resize', function () {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(handleBreakpoint, 100);
    });

    handleBreakpoint();
});


const targetElement = document.querySelector('.product-add-and-buy-section-parent');
const stickyPurchaseBar = document.querySelector('.product-details-sticky');

if (stickyPurchaseBar) {
    new ResizeObserver(function () {
        document.body.style.setProperty('--product-sticky-height', stickyPurchaseBar.offsetHeight + 'px');
    }).observe(stickyPurchaseBar);
}

function handleIntersect(entries) {
    let getHeight = $('.product-details-sticky-bottom').height();
    entries.forEach(entry => {
        if (entry.intersectionRatio >= PURCHASE_BUTTONS_VISIBLE_RATIO) {
            $('.product-details-sticky').removeClass('active');
            $('body').css('padding-bottom', "0px");
        } else {
            $('.product-details-sticky').addClass('active');
            $('body').css('padding-bottom', `calc(${getHeight}px + 2rem)`);
        }
    });
}

const PURCHASE_BUTTONS_VISIBLE_RATIO = 0.9;
const mobileAppBar = document.querySelector('#mobile_app_bar');
const mobileAppBarHeight = mobileAppBar ? mobileAppBar.offsetHeight : 0;

const observer = new IntersectionObserver(handleIntersect, {
    root: null,
    rootMargin: `0px 0px -${mobileAppBarHeight}px 0px`,
    threshold: PURCHASE_BUTTONS_VISIBLE_RATIO,
});

if (targetElement) {
    observer.observe(targetElement);
}
document.addEventListener('DOMContentLoaded', function () {
    if (window.location.search.includes('review')) {
        const reviewTab = document.getElementById('reviews-tab');
        const tabNav = document.getElementById('nav-tab');

        if (reviewTab && tabNav) {
            const tabInstance = new bootstrap.Tab(reviewTab);
            tabInstance.show();
            const navOffset = tabNav.getBoundingClientRect().top + window.scrollY;
            window.scrollTo({
                top: 550,
                behavior: 'smooth'
            });
        }
    }
});
