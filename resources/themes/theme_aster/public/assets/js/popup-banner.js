'use strict';

/**
 * Storefront popup banner. Holds the panel back until the artwork has decoded so it never
 * animates in around an empty frame, and opens regardless once MAX_WAIT elapses. The
 * `is-loaded` class is applied only after both the image settled and the panel finished
 * opening, so the image's fade-in is actually visible instead of running while hidden.
 */
(function () {
    var MIN_DELAY = 600;
    var MAX_WAIT = 2600;

    window.showPopupBanner = function () {
        var modal = document.querySelector('[data-popup-banner]');

        if (!modal) {
            return;
        }

        var media = modal.querySelector('.pb-media');
        var image = modal.querySelector('.pb-image');
        var isImageSettled = !image || image.complete;
        var isPanelShown = false;
        var isOpened = false;
        var isMinDelayPassed = false;

        function reveal() {
            if (media && isImageSettled && isPanelShown) {
                media.classList.add('is-loaded');
            }
        }

        function open() {
            if (isOpened) {
                return;
            }

            isOpened = true;
            modal.addEventListener('shown.bs.modal', function () {
                isPanelShown = true;
                reveal();
            }, {once: true});
            bootstrap.Modal.getOrCreateInstance(modal).show();
        }

        function openWhenReady() {
            if (isImageSettled && isMinDelayPassed) {
                open();
            }
        }

        if (!isImageSettled) {
            ['load', 'error'].forEach(function (eventName) {
                image.addEventListener(eventName, function () {
                    isImageSettled = true;
                    reveal();
                    openWhenReady();
                }, {once: true});
            });
        }

        setTimeout(function () {
            isMinDelayPassed = true;
            openWhenReady();
        }, MIN_DELAY);

        setTimeout(open, MAX_WAIT);
    };
})();
