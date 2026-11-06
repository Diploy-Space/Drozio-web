'use strict';

(function () {
    var bar = document.getElementById('page-progress-bar');
    if (!bar) return;

    var WATCHDOG_MS = 10000;
    var DOWNLOAD_MS = 1500;
    var DOWNLOAD_PATH = /(^|[\/-])(export|exports|download|invoice)([\/-]|$)/i;
    var timer = null;

    function clearTimer() {
        if (timer) {
            window.clearTimeout(timer);
            timer = null;
        }
    }

    function inFullscreen() {
        return !!(document.fullscreenElement || document.webkitFullscreenElement);
    }

    function isDownloadUrl(value) {
        if (!value) return false;

        try {
            return DOWNLOAD_PATH.test(new URL(value, window.location.href).pathname);
        } catch (error) {
            return false;
        }
    }

    function startProgress(autoFinishAfter) {
        clearTimer();
        bar.classList.remove('done');
        void bar.offsetWidth;
        bar.classList.add('active');
        timer = window.setTimeout(finishProgress, autoFinishAfter);
    }

    function finishProgress() {
        clearTimer();
        if (!bar.classList.contains('active')) return;
        bar.classList.remove('active');
        bar.classList.add('done');
    }

    window.pageProgressBar = {
        start: function () {
            startProgress(WATCHDOG_MS);
        },
        finish: finishProgress,
    };

    function startUnlessPrevented(event, autoFinishAfter) {
        window.setTimeout(function () {
            if (event.defaultPrevented) return;
            startProgress(autoFinishAfter);
        }, 0);
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0) return;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        if (inFullscreen()) return;

        var link = event.target.closest ? event.target.closest('a[href]') : null;
        if (!link) return;
        if (link.dataset.bsToggle || link.hasAttribute('data-no-progress')) return;
        if (link.hasAttribute('download')) return;
        if (link.target && link.target !== '' && link.target !== '_self') return;

        var href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^(javascript:|mailto:|tel:)/i.test(href)) return;
        if (link.origin && link.origin !== window.location.origin) return;

        try {
            var url = new URL(link.href, window.location.href);
            if (url.pathname === window.location.pathname && url.search === window.location.search) return;
        } catch (error) {
            return;
        }

        startUnlessPrevented(event, isDownloadUrl(link.href) ? DOWNLOAD_MS : WATCHDOG_MS);
    });

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented || inFullscreen()) return;
        var form = event.target;
        if (!form || form.tagName !== 'FORM') return;
        if (form.getAttribute('target') === '_blank') return;

        startUnlessPrevented(event, isDownloadUrl(form.getAttribute('action')) ? DOWNLOAD_MS : WATCHDOG_MS);
    });

    window.addEventListener('pageshow', finishProgress);
    window.addEventListener('pagehide', clearTimer);
})();
