"use strict";

(function () {

    function meta() {
        return document.getElementById('auction-notif-meta');
    }

    function bellButton() {
        var target = meta()?.dataset.bellTarget;

        return target ? document.querySelector('[data-bs-target="' + target + '"]') : null;
    }

    function openModal(notif) {
        document.getElementById('auction-notif-title').textContent = notif.title || '';
        document.getElementById('auction-notif-body').textContent = notif.message || '';

        var productBox = document.getElementById('auction-notif-product-box');
        if (notif.product_name) {
            document.getElementById('auction-notif-product-name').textContent = notif.product_name;
            if (notif.product_image) {
                document.getElementById('auction-notif-product-img').src = notif.product_image;
            }
            productBox.classList.remove('d-none');
        } else {
            productBox.classList.add('d-none');
        }

        var footer = document.getElementById('auction-notif-footer');
        var ctaBtn = document.getElementById('auction-notif-cta');

        if (notif.product_url) {
            ctaBtn.textContent = meta()?.dataset.viewDetailsLabel || '';
            ctaBtn.href = notif.product_url;
            footer.classList.remove('d-none');
        } else {
            footer.classList.add('d-none');
        }

        var modalEl = document.getElementById('notification_view');
        if (modalEl && typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function buildSidebarItem(notif) {
        var tpl = document.getElementById('auction-notif-item-template');
        var clone = tpl.content.cloneNode(true).firstElementChild;
        clone.setAttribute('data-notification', JSON.stringify(notif));
        clone.querySelector('.js-notif-title').textContent = notif.title || '';
        clone.querySelector('.js-notif-time').textContent = notif.time || '';
        clone.querySelector('.js-notif-message').textContent = notif.message || '';

        return clone;
    }

    function pushToSidebar(notif) {
        var todayLabel = meta()?.dataset.todayLabel || 'Today';

        var emptyState = document.getElementById('auction-notifications-empty');
        if (emptyState) emptyState.remove();

        var list = document.getElementById('auction-notifications-list');
        if (!list) {
            list = document.createElement('div');
            list.id = 'auction-notifications-list';
            list.className = 'd-flex flex-column gap-xxl-20px gap-3';
            var body = document.getElementById('auction-notification-body');
            if (body) body.prepend(list);
        }

        var todayItems = null;
        list.querySelectorAll(':scope > div').forEach(function (group) {
            var label = group.querySelector(':scope > .auction-notif-group-label');
            if (label && label.textContent.trim() === todayLabel) {
                todayItems = group.querySelector('.auction-notif-group-items');
            }
        });

        if (!todayItems) {
            var labelSpan = document.createElement('span');
            labelSpan.className = 'auction-notif-group-label title-semidark mb-10px fs-13 d-block';
            labelSpan.textContent = todayLabel;

            todayItems = document.createElement('div');
            todayItems.className = 'auction-notif-group-items d-flex flex-column gap-10px';

            var groupDiv = document.createElement('div');
            groupDiv.appendChild(labelSpan);
            groupDiv.appendChild(todayItems);
            list.prepend(groupDiv);
        }

        todayItems.prepend(buildSidebarItem(notif));

        var bellBtn = bellButton();
        if (bellBtn) {
            var badge = bellBtn.querySelector('.auction-unread-badge');
            if (badge) {
                var current = parseInt(badge.textContent, 10) || 0;
                badge.textContent = current >= 99 ? '99+' : String(current + 1);
            }
        }
    }

    function markAsRead(item, notif) {
        item.classList.remove('notification-active');

        var timeSpan = item.querySelector('.auction-notif-time');
        if (timeSpan) timeSpan.classList.replace('text-primary', 'text-light-gray');

        var bellBtn = bellButton();
        var badge = bellBtn ? bellBtn.querySelector('.auction-unread-badge') : null;
        if (badge) {
            var count = parseInt(badge.textContent, 10) || 1;
            if (count <= 1) { badge.remove(); } else { badge.textContent = String(count - 1); }
        }

        var readUrl = meta()?.dataset.readUrl;
        if (!notif.id || !readUrl) return;

        var csrf = (document.querySelector('meta[name="csrf-token"]') || document.querySelector('meta[name="_token"]') || {}).content || '';

        fetch(readUrl.replace('__ID__', notif.id), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        }).catch(function () {});
    }

    document.addEventListener('click', function (e) {
        var item = e.target.closest('.auction-notification-item');
        if (!item) return;

        var rawData = item.getAttribute('data-notification');
        if (!rawData) return;

        var notif;
        try { notif = JSON.parse(rawData); } catch (err) { return; }

        if (item.classList.contains('notification-active')) {
            markAsRead(item, notif);
        }

        openModal(notif);
    });

    window.auctionNotificationUI = {
        openModal: openModal,
        push: pushToSidebar,
    };

})();
