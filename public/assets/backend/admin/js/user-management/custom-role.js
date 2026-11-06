'use strict';

function permissionGroupOf($input) {
    return $input.closest('[data-permission-group]');
}

function anchorInput($group) {
    const anchor = $group.data('permission-anchor');

    return anchor ? $group.find(".module-permission[value='" + anchor + "']") : $();
}

function syncSelectAllStates() {
    $('[data-permission-group]').each(function () {
        const $group = $(this);
        const $inputs = $group.find('.module-permission');
        const selected = $inputs.filter(':checked').length;

        $group.find('.select-all-group').prop('checked', $inputs.length > 0 && selected === $inputs.length);
        $group.find('.permission-group-count').text(selected + '/' + $inputs.length);
    });

    const $all = $('.module-permission');
    const selectedAll = $all.filter(':checked').length;

    $('#select-all-module').prop('checked', $all.length > 0 && selectedAll === $all.length);
    $('#selected-permission-count').text(selectedAll + '/' + $all.length);
}

$(document).ready(function () {
    syncSelectAllStates();

    $('.module-permission').on('change', function () {
        const $input = $(this);
        const $group = permissionGroupOf($input);
        const $anchor = anchorInput($group);

        if ($anchor.length === 0) {
            syncSelectAllStates();

            return;
        }

        const isAnchor = $input.is($anchor);

        if (isAnchor && !$input.is(':checked')) {
            $group.find('.module-permission').prop('checked', false);
        } else if (!isAnchor && $input.is(':checked')) {
            $anchor.prop('checked', true);
        }

        syncSelectAllStates();
    });

    $('.select-all-group').on('change', function () {
        const $group = permissionGroupOf($(this));

        $group.find('.module-permission').prop('checked', $(this).is(':checked'));
        syncSelectAllStates();
    });

    $('#select-all-module').on('change', function () {
        $('.module-permission').prop('checked', $(this).is(':checked'));
        syncSelectAllStates();
    });

    const detailsPanel = document.getElementById('offcanvasRoleDetails');

    if (detailsPanel) {
        const panel = new bootstrap.Offcanvas(detailsPanel);

        $('.get-role-details').on('click', function (e) {
            e.preventDefault();

            const id = $(this).data('id');
            const url = $('#get-role-details-url').data('url');

            $.ajax({
                type: 'GET',
                url: url,
                data: {id: id},
                beforeSend: function () {
                    $('#loading').fadeIn();
                },
                success: function (response) {
                    $('#loading').fadeOut();

                    if (!response.view) {
                        toastMagic.warning($('#role-not-found-message').data('text'));

                        return;
                    }

                    $('#offcanvasRoleDetails').html(response.view);
                    panel.show();
                },
                error: function () {
                    $('#loading').fadeOut();
                    toastMagic.error($('#role-not-found-message').data('text'));
                },
            });
        });
    }

    $('.delete-role').on('click', function () {
        const action = $(this).data('action');
        const id = $(this).data('id');
        const text = $('#get-confirm-and-cancel-button-text-for-delete');

        Swal.fire({
            title: text.data('sure'),
            text: text.data('text'),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1455AC',
            cancelButtonColor: '#d33',
            cancelButtonText: text.data('cancel'),
            confirmButtonText: text.data('confirm'),
            reverseButtons: true,
        }).then(function (result) {
            if (!result.value) {
                return;
            }

            $.ajaxSetup({headers: {'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});

            $.ajax({
                url: action,
                method: 'POST',
                data: {id: id},
                success: function (response) {
                    if (response.success === 1) {
                        toastMagic.success(response.message);
                        setTimeout(function () {
                            location.reload();
                        }, 1500);

                        return;
                    }

                    toastMagic.warning(response.message);
                },
            });
        });
    });

    $('#submit-create-role').on('submit', function (e) {
        const name = $("input[name='name']").val();

        if (name.trim() === '') {
            e.preventDefault();
            toastMagic.error($('#role-name-required-message').data('error'));

            return false;
        }

        if ($('.module-permission:checked').length === 0) {
            e.preventDefault();
            toastMagic.warning($('#select-minimum-one-box-message').data('warning'));

            return false;
        }
    });
});
