"use strict";

(function () {
    var OPEN_TOGGLE_SELECTOR = 'header [data-bs-toggle="dropdown"][aria-expanded="true"]';

    function closeOpenHeaderDropdowns() {
        if (typeof bootstrap === 'undefined') return;

        document.querySelectorAll(OPEN_TOGGLE_SELECTOR).forEach(function (toggle) {
            bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
        });
    }

    window.addEventListener('scroll', closeOpenHeaderDropdowns, { passive: true });
})();
