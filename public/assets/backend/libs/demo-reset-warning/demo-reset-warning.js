'use strict';

(function () {
    const dismissedAtStorageKey = 'demoResetWarningDismissedAt';
    const snoozeDuration = 3 * 60 * 1000;
    const refreshInterval = 10 * 1000;
    const firstWarningMinute = 56;
    const warning = document.getElementById('demo-reset-warning');

    if (!warning) {
        return;
    }

    function isSnoozed() {
        let dismissedAt = parseInt(localStorage.getItem(dismissedAtStorageKey), 10);

        return !isNaN(dismissedAt) && (Date.now() - dismissedAt) < snoozeDuration;
    }

    function isResetApproaching() {
        return new Date().getMinutes() >= firstWarningMinute;
    }

    function refreshWarning() {
        warning.classList.toggle('active', isResetApproaching() && !isSnoozed());
    }

    warning.querySelectorAll('.demo-reset-warning-close').forEach(function (closeButton) {
        closeButton.addEventListener('click', function (event) {
            event.preventDefault();
            localStorage.setItem(dismissedAtStorageKey, Date.now().toString());
            refreshWarning();
        });
    });

    refreshWarning();
    setInterval(refreshWarning, refreshInterval);
})();
