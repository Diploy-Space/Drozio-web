'use strict';

$(document).ready(function () {
    const offcanvas = document.getElementById('offcanvasEmployeeDetails');

    if (!offcanvas) {
        return;
    }

    const panel = new bootstrap.Offcanvas(offcanvas);

    $('.get-employee-details').on('click', function (e) {
        e.preventDefault();

        const id = $(this).data('id');
        const url = $('#get-employee-details-url').data('url');

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
                    toastMagic.warning($('#employee-not-found-message').data('text'));

                    return;
                }

                $('#offcanvasEmployeeDetails').html(response.view);
                panel.show();
            },
            error: function () {
                $('#loading').fadeOut();
                toastMagic.error($('#employee-not-found-message').data('text'));
            },
        });
    });
});
