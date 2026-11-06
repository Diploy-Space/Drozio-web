/* =====================================================================
   6Valley v2 shell — menu motion (admin + vendor)

   Additive layer over admin-v2.js / vendor-v2.js. It only listens:
   the 'v2:section:change' event those files dispatch, plus clicks on
   the rail and the menu rows. Nothing here owns menu state, so a
   failure to load costs the motion and nothing else.
   ===================================================================== */
(function () {
    'use strict';

    var ROOT = document.querySelector('.app-v2');
    if (!ROOT) return;

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    var isRTL = (document.documentElement.getAttribute('dir') || '').toLowerCase() === 'rtl';

    /* Section changes fired while the shell is still hydrating from the
       URL are start-up state, not a user switching menus — animating
       them would replay the transition on every page load. */
    var booted = document.readyState !== 'loading';
    document.addEventListener('DOMContentLoaded', function () { booted = true; });

    function farthestCorner(x, y, width, height) {
        return Math.max(
            Math.hypot(x, y),
            Math.hypot(width - x, y),
            Math.hypot(x, height - y),
            Math.hypot(width - x, height - y)
        );
    }

    function layers(parent, classNames) {
        classNames.forEach(function (name) {
            var layer = document.createElement('i');
            layer.className = name;
            parent.appendChild(layer);
        });
    }

    /* -------------------- 1 · water-drop ripple -------------------- */

    var DROP_LIFETIME = 900;

    function dropHost(target) {
        var host = target.querySelector(':scope > .v2-drop-host');
        if (!host) {
            host = document.createElement('span');
            host.className = 'v2-drop-host';
            host.setAttribute('aria-hidden', 'true');
            target.appendChild(host);
        }
        return host;
    }

    function spawnDrop(target, clientX, clientY) {
        var rect = target.getBoundingClientRect();
        if (!rect.width || !rect.height) return;

        // Keyboard activation reports 0/0 — drop from the centre instead.
        var x = clientX ? clientX - rect.left : rect.width / 2;
        var y = clientY ? clientY - rect.top : rect.height / 2;
        var host = dropHost(target);

        // Rapid clicking must not stack ripples on one row.
        while (host.childElementCount > 2) { host.removeChild(host.firstElementChild); }

        var drop = document.createElement('span');
        drop.className = 'v2-drop';
        drop.style.setProperty('--v2-drop-x', x + 'px');
        drop.style.setProperty('--v2-drop-y', y + 'px');
        drop.style.setProperty('--v2-drop-size', (farthestCorner(x, y, rect.width, rect.height) * 2) + 'px');
        layers(drop, ['v2-drop-core', 'v2-drop-ring', 'v2-drop-ring v2-drop-ring-late']);
        host.appendChild(drop);

        setTimeout(function () { drop.remove(); }, DROP_LIFETIME);
    }

    /* Bubble phase on document, so this runs after the shell's own click
       handler: by now the tapped rail button already carries v2-is-active
       and the ripple picks up the on-primary tint. */
    document.addEventListener('click', function (event) {
        if (reduced.matches || event.button) return;

        var target = event.target.closest
            ? event.target.closest('.v2-rail-btn, .v2-nav-item, .v2-nav-child, .v2-rail-setup')
            : null;
        if (!target) return;
        if (!ROOT.contains(target) && !target.closest('.v2-rail-flyout')) return;
        // The pin toggle is its own affordance and sits inside the row.
        if (event.target.closest('.v2-pin-btn')) return;

        spawnDrop(target, event.clientX, event.clientY);
    });

    /* -------------------- 2 · sliding rail marker -------------------- */

    var rail = ROOT.querySelector('.v2-rail');
    var railItems = rail && rail.querySelector('.v2-rail-items');
    var marker = null;
    var squashTimer = 0;

    if (railItems) {
        marker = document.createElement('span');
        marker.className = 'v2-rail-marker v2-no-anim';
        marker.setAttribute('aria-hidden', 'true');
        railItems.appendChild(marker);
        rail.classList.add('v2-has-marker');
    }

    function moveMarker(animate) {
        if (!marker) return;

        var active = railItems.querySelector('.v2-rail-btn.v2-is-active');
        // offsetParent is null while the rail is hidden (mobile) — parking
        // the marker then would animate it in from the corner later.
        if (!active || !active.offsetParent) {
            marker.classList.remove('v2-is-ready');
            return;
        }

        var x = active.offsetLeft + 'px';
        var y = active.offsetTop + 'px';
        // A re-measure that lands where the marker is already headed must not
        // interrupt a glide in progress — pjax re-hydration fires one of these
        // right after the section change that started the animation.
        if (marker.classList.contains('v2-is-ready')
            && marker.style.getPropertyValue('--v2-marker-x') === x
            && marker.style.getPropertyValue('--v2-marker-y') === y) {
            return;
        }

        var wasIdle = !marker.classList.contains('v2-is-ready');
        if (!animate || wasIdle || reduced.matches) { marker.classList.add('v2-no-anim'); }

        marker.style.width = active.offsetWidth + 'px';
        marker.style.height = active.offsetHeight + 'px';
        marker.style.setProperty('--v2-marker-x', x);
        marker.style.setProperty('--v2-marker-y', y);

        if (marker.classList.contains('v2-no-anim')) {
            void marker.offsetWidth; // commit the jump before transitions resume
            marker.classList.remove('v2-no-anim');
            marker.classList.add('v2-is-ready');
            return;
        }

        marker.classList.add('v2-is-ready');

        // Squash along the direction of travel, then settle — the pill
        // reads as liquid rather than as a sliding rectangle.
        marker.style.setProperty('--v2-marker-sx', '0.9');
        marker.style.setProperty('--v2-marker-sy', '1.16');
        clearTimeout(squashTimer);
        squashTimer = setTimeout(function () {
            marker.style.setProperty('--v2-marker-sx', '1');
            marker.style.setProperty('--v2-marker-sy', '1');
        }, 170);
    }

    /* -------------------- 3 · section transition -------------------- */

    var GHOST_LIFETIME = 280;
    var ENTER_LIFETIME = 660;
    var WAVE_LIFETIME = 1050;
    var enterTimer = 0;
    var enteringSection = null;

    function panelParts() {
        var panel = ROOT.querySelector('.v2-ctxpanel');
        return { panel: panel, scroll: panel && panel.querySelector('.v2-ctx-scroll') };
    }

    /* Clone the outgoing menu into an inert copy pinned where it stood,
       so it can dissolve after the real section has already been hidden.
       Every attribute the shell queries by is stripped from the clone —
       a live pin or palette lookup must never reach into a ghost. */
    function dissolveOutgoing(parts, previousId) {
        var existing = parts.panel.querySelector('.v2-ctx-ghost');
        if (existing) { existing.remove(); }
        if (!previousId) return;

        var outgoing = parts.scroll.querySelector('.v2-ctx-section[data-section="' + CSS.escape(previousId) + '"]');
        if (!outgoing) return;

        var ghost = document.createElement('div');
        ghost.className = 'v2-ctx-ghost';
        ghost.setAttribute('aria-hidden', 'true');
        ghost.innerHTML = outgoing.innerHTML;
        ghost.querySelectorAll('[id], [data-pin], [data-item], [data-child-item]').forEach(function (node) {
            node.removeAttribute('id');
            node.removeAttribute('data-pin');
            node.removeAttribute('data-item');
            node.removeAttribute('data-child-item');
        });
        ghost.style.width = parts.scroll.clientWidth + 'px';
        ghost.style.setProperty('--v2-ghost-shift', (-parts.scroll.scrollTop) + 'px');

        parts.panel.appendChild(ghost);
        setTimeout(function () { ghost.remove(); }, GHOST_LIFETIME);
    }

    function releaseWave(parts, sectionId) {
        var source = ROOT.querySelector('.v2-rail-btn[data-section="' + CSS.escape(sectionId) + '"]');
        var panelRect = parts.panel.getBoundingClientRect();
        if (!panelRect.width) return;

        var x = isRTL ? panelRect.width : 0;
        var y = panelRect.height / 2;
        if (source) {
            var sourceRect = source.getBoundingClientRect();
            if (sourceRect.height) { y = sourceRect.top + sourceRect.height / 2 - panelRect.top; }
        }

        var wave = document.createElement('span');
        wave.className = 'v2-ctx-wave';
        wave.setAttribute('aria-hidden', 'true');
        wave.style.setProperty('--v2-wave-x', x + 'px');
        wave.style.setProperty('--v2-wave-y', y + 'px');
        wave.style.setProperty('--v2-wave-d', (farthestCorner(x, y, panelRect.width, panelRect.height) * 2) + 'px');
        layers(wave, ['v2-wave-fill', 'v2-wave-ring', 'v2-wave-ring v2-wave-ring-late']);

        parts.panel.appendChild(wave);
        setTimeout(function () { wave.remove(); }, WAVE_LIFETIME);
    }

    function raiseIncoming(parts, sectionId) {
        var incoming = parts.scroll.querySelector('.v2-ctx-section[data-section="' + CSS.escape(sectionId) + '"]');
        if (!incoming) return;

        if (enteringSection) { enteringSection.classList.remove('v2-sec-enter'); }
        clearTimeout(enterTimer);

        incoming.classList.remove('v2-sec-enter');
        void incoming.offsetWidth; // restart the stagger on a rapid re-switch
        incoming.classList.add('v2-sec-enter');
        enteringSection = incoming;

        enterTimer = setTimeout(function () {
            incoming.classList.remove('v2-sec-enter');
            if (enteringSection === incoming) { enteringSection = null; }
        }, ENTER_LIFETIME);
    }

    document.addEventListener('v2:section:change', function (event) {
        moveMarker(true);

        if (!booted || reduced.matches) return;

        var detail = event.detail || {};

        /* One frame late on purpose: tapping a rail icon while the sidebar is
           collapsed expands it in the same click, and the panel has to be laid
           out before the wave can be measured against it. */
        requestAnimationFrame(function () {
            var parts = panelParts();
            if (!parts.panel || !parts.scroll || !parts.panel.offsetParent) return;

            dissolveOutgoing(parts, detail.previous);
            releaseWave(parts, detail.id);
            raiseIncoming(parts, detail.id);
        });
    });

    /* The marker is measured from live layout, so anything that moves the
       rail has to re-measure: boot, resize, and the shell's own re-hydration
       after pjax / back-forward navigation. */
    function syncMarker() { moveMarker(false); }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncMarker);
    } else {
        syncMarker();
    }
    window.addEventListener('load', syncMarker);
    window.addEventListener('resize', syncMarker);
    document.addEventListener('v2:rehydrated', syncMarker);
    document.addEventListener('v2:page-loaded', syncMarker);
})();
