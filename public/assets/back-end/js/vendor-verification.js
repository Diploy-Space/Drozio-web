"use strict";

(function () {
    const TOAST_VISIBLE_DELAY = 2000;

    const holder = document.getElementById('verification-offcanvas-holder');

    const rosetteSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="80" height="80">' +
        '<path fill="#0A9D6D" d="M12 .8l2.4 2.05 3.13-.4.94 3.02 2.9 1.28-.86 3.04L22.4 12l-1.89 2.21.86 3.04-2.9 1.28-.94 3.02-3.13-.4L12 23.2l-2.4-2.05-3.13.4-.94-3.02-2.9-1.28.86-3.04L1.6 12l1.89-2.21-.86-3.04 2.9-1.28.94-3.02 3.13.4z"/>' +
        '<path fill="#fff" d="M10.78 15.48l-3.1-3.1 1.42-1.41 1.68 1.68 4.12-4.12 1.42 1.41z"/></svg>';

    const rosetteImage = 'data:image/svg+xml;utf8,' + encodeURIComponent(rosetteSvg);

    let shouldReloadOnClose = false;

    function openPanel(url) {
        if (!holder) {
            return;
        }

        $.get(url, function (html) {
            holder.innerHTML = html;

            const panel = holder.querySelector('.offcanvas');

            if (!panel) {
                return;
            }

            panel.addEventListener('hidden.bs.offcanvas', function () {
                holder.innerHTML = '';

                if (shouldReloadOnClose) {
                    location.reload();
                }
            });

            const scroller = panel.querySelector('#verification-candidate-scroller');

            if (scroller) {
                scroller.addEventListener('scroll', function () {
                    loadNextPage(scroller);
                });
            }

            bootstrap.Offcanvas.getOrCreateInstance(panel).show();
        });
    }

    function setRowSelected($row, isSelected) {
        $row.attr('data-selected', isSelected ? '1' : '0');
        $row.toggleClass('bg-section', !isSelected);
        $row.toggleClass('bg-primary', isSelected);
        $row.toggleClass('bg-opacity-10', isSelected);
        $row.find('.verification-give-badge').toggleClass('d-none', isSelected);
        $row.find('.verification-selected-mark').toggleClass('d-none', !isSelected).toggleClass('d-inline-flex', isSelected);
    }

    function selectedIds() {
        return $('.verification-candidate[data-selected="1"]').map(function () {
            return parseInt($(this).attr('data-vendor-id'), 10);
        }).get();
    }

    function syncFooter() {
        const ids = selectedIds();

        $('#verification-selected-count').text(ids.length);
        $('#verification-verify-all').attr('data-verification-payload', JSON.stringify({vendor_ids: ids}));
        $('#verification-candidate-footer').toggleClass('d-none', ids.length === 0);
    }

    function syncEmptyState() {
        const isEmpty = $('#verification-candidate-list').children().length === 0;

        $('#verification-candidate-empty').toggleClass('d-none', !isEmpty);
        $('#verification-select-all').prop('disabled', isEmpty);

        if (isEmpty) {
            $('#verification-select-all').prop('checked', false);
        }
    }

    function updateCounts(candidateCount) {
        if (typeof candidateCount !== 'number') {
            return;
        }

        $('#verification-recommendation-count').text(candidateCount);
        $('#verification-candidate-chip').text(candidateCount);
    }

    function removeGrantedRows(ids) {
        ids.forEach(function (id) {
            $('.verification-candidate[data-vendor-id="' + id + '"]').remove();
        });

        syncEmptyState();
        syncFooter();
    }

    function postVerification($trigger, payload) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
            }
        });

        $.ajax({
            url: $trigger.attr('data-verification-url'),
            method: 'POST',
            data: payload,
            success: function (response) {
                if (response.error) {
                    toastMagic.error(response.message);

                    return;
                }

                toastMagic.success(response.message);

                if (!holder) {
                    setTimeout(function () {
                        location.reload();
                    }, TOAST_VISIBLE_DELAY);

                    return;
                }

                shouldReloadOnClose = true;
                updateCounts(response.candidate_count);
                removeGrantedRows(payload.vendor_ids || []);
            }
        });
    }

    $(document).on('click', '#verification-criteria-trigger, #verification-candidates-trigger', function () {
        openPanel($(this).attr('data-url'));
    });

    $(document).on('click', '.verification-candidate', function (event) {
        if ($(event.target).closest('.verification-give-badge').length > 0) {
            return;
        }

        const $row = $(this);

        setRowSelected($row, $row.attr('data-selected') !== '1');
        $('#verification-select-all').prop('checked', $('.verification-candidate').length === selectedIds().length);
        syncFooter();
    });

    $(document).on('change', '#verification-select-all', function () {
        const isChecked = $(this).is(':checked');

        $('.verification-candidate').each(function () {
            setRowSelected($(this), isChecked);
        });

        syncFooter();
    });

    function loadNextPage(scroller) {
        const $scroller = $(scroller);

        if ($scroller.attr('data-has-more') !== '1' || $scroller.attr('data-loading') === '1') {
            return;
        }

        if (scroller.scrollTop + scroller.clientHeight < scroller.scrollHeight - 120) {
            return;
        }

        const nextPage = parseInt($scroller.attr('data-page'), 10) + 1;

        $scroller.attr('data-loading', '1');

        $.get($scroller.attr('data-url'), {page: nextPage}, function (response) {
            $('#verification-candidate-list').append(response.html);
            $scroller.attr('data-page', nextPage);
            $scroller.attr('data-has-more', response.has_more ? '1' : '0');
            $scroller.attr('data-loading', '0');

            if ($('#verification-select-all').is(':checked')) {
                $('.verification-candidate[data-selected!="1"]').each(function () {
                    setRowSelected($(this), true);
                });
            }

            syncFooter();
        });
    }

    $(document).on('click', '.verification-confirm', function () {
        const $trigger = $(this);
        const payload = JSON.parse($trigger.attr('data-verification-payload') || '{}');
        const isDanger = $trigger.attr('data-verification-variant') === 'danger';

        Swal.fire({
            imageUrl: rosetteImage,
            imageWidth: 80,
            imageHeight: 80,
            title: $trigger.attr('data-verification-title'),
            html: $trigger.attr('data-verification-message'),
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: $trigger.attr('data-verification-confirm'),
            cancelButtonText: $trigger.attr('data-verification-cancel'),
            confirmButtonColor: isDanger ? '#EF4444' : '#1455AC',
            cancelButtonColor: '#f4f5f7',
            customClass: {
                cancelButton: 'text-dark',
                confirmButton: isDanger ? 'btn btn-danger' : ''
            }
        }).then(function (result) {
            if (!result.value) {
                return;
            }

            postVerification($trigger, payload);
        });
    });
})();
