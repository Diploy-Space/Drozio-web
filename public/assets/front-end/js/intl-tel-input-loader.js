"use strict";

/*
 * intl-tel-input is 383 KB of country data and only the checkout, registration, address,
 * tracking and contact screens carry a phone field. Fetching it on every storefront page
 * cost every visitor that payload to render a home page with no phone input on it, so the
 * bundle is now loaded the first time a tel input actually exists — including one that
 * arrives later inside an AJAX-rendered modal, which is what the observer is watching for.
 */
(function () {
    var config = document.getElementById("intl-tel-input-assets");

    if (!config) {
        return;
    }

    var observer = null;
    var requested = false;

    function loadAssets() {
        if (requested) {
            return;
        }

        requested = true;

        if (observer) {
            observer.disconnect();
        }

        var stylesheet = document.createElement("link");
        stylesheet.rel = "stylesheet";
        stylesheet.href = config.dataset.css;
        document.head.appendChild(stylesheet);

        var scripts = [];

        try {
            scripts = JSON.parse(config.dataset.scripts) || [];
        } catch (error) {
            return;
        }

        // Sequential: the validation script needs the library, and the library needs its
        // utils data, so they cannot race.
        scripts.reduce(function (chain, source) {
            return chain.then(function () {
                return new Promise(function (resolve, reject) {
                    var script = document.createElement("script");
                    script.src = source;
                    script.onload = resolve;
                    script.onerror = reject;
                    document.body.appendChild(script);
                });
            });
        }, Promise.resolve());
    }

    function hasPhoneInput() {
        return document.querySelector('input[type="tel"]') !== null;
    }

    if (hasPhoneInput()) {
        loadAssets();

        return;
    }

    observer = new MutationObserver(function () {
        if (hasPhoneInput()) {
            loadAssets();
        }
    });

    observer.observe(document.documentElement, { childList: true, subtree: true });
})();
