(function () {
    'use strict';

    var ANNOUNCEMENT_HIDE_DURATION = 600000;
    var backdrop = null;

    function getBackdrop() {
        if (backdrop === null) {
            backdrop = document.querySelector('.sh-backdrop');
            if (backdrop === null) {
                backdrop = document.createElement('div');
                backdrop.className = 'sh-backdrop';
                document.body.appendChild(backdrop);
            }
        }

        return backdrop;
    }

    function openDrawer(drawer) {
        closeDrawers();
        drawer.classList.add('is-open');
        getBackdrop().classList.add('is-open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawers() {
        document.querySelectorAll('.sh-drawer.is-open').forEach(function (open) {
            open.classList.remove('is-open');
        });
        getBackdrop().classList.remove('is-open');
        document.body.style.overflow = '';
    }

    function closeSearchBar() {
        document.querySelectorAll('.sh-searchbar.is-open').forEach(function (bar) {
            bar.classList.remove('is-open');
        });
    }

    document.addEventListener('click', function (event) {
        var drawerTrigger = event.target.closest('[data-sh-drawer]');
        if (drawerTrigger !== null) {
            var drawer = document.querySelector(drawerTrigger.getAttribute('data-sh-drawer'));
            if (drawer !== null) {
                event.preventDefault();
                drawer.classList.contains('is-open') ? closeDrawers() : openDrawer(drawer);
                return;
            }
        }

        if (event.target.closest('[data-sh-drawer-close]') !== null || event.target.classList.contains('sh-backdrop')) {
            event.preventDefault();
            closeDrawers();
            return;
        }

        var searchToggle = event.target.closest('[data-sh-search]');
        if (searchToggle !== null) {
            var searchBar = document.querySelector(searchToggle.getAttribute('data-sh-search'));
            if (searchBar !== null) {
                event.preventDefault();
                searchBar.classList.add('is-open');
                var field = searchBar.querySelector('input');
                if (field !== null) {
                    field.focus();
                }

                return;
            }
        }

        if (event.target.closest('[data-sh-search-close]') !== null) {
            event.preventDefault();
            closeSearchBar();
            return;
        }

        var announceClose = event.target.closest('[data-sh-announce-close]');
        if (announceClose !== null) {
            event.preventDefault();
            var bar = document.getElementById(announceClose.getAttribute('data-sh-announce-close'));
            if (bar !== null) {
                bar.hidden = true;
            }

            try {
                window.localStorage.setItem(
                    announceClose.getAttribute('data-sh-announce-key'),
                    String(Date.now() + ANNOUNCEMENT_HIDE_DURATION)
                );
            } catch (error) {
                return;
            }
        }
    });

    document.addEventListener('click', function (event) {
        var treeToggle = event.target.closest('[data-sh-tree]');
        if (treeToggle === null) {
            return;
        }

        var panel = document.querySelector(treeToggle.getAttribute('data-sh-tree'));
        if (panel === null) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();

        var isOpen = panel.classList.toggle('is-open');
        treeToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }, true);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeDrawers();
            closeSearchBar();
        }
    });

    var header = document.querySelector('.sh-header');
    if (header !== null) {
        var syncStuck = function () {
            header.classList.toggle('is-stuck', header.getBoundingClientRect().top <= 0);
        };

        syncStuck();
        window.addEventListener('scroll', syncStuck, { passive: true });
        window.addEventListener('resize', syncStuck, { passive: true });
    }
})();
