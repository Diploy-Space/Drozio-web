'use strict';

$(function () {
    function refreshLengthCounter($field) {
        const maxLength = parseInt($field.attr('maxlength'), 10);
        const currentLength = String($field.val() || '').length;
        const isLimitReached = currentLength >= maxLength;

        $($field.data('length-counter'))
            .text(currentLength + '/' + maxLength)
            .toggleClass('text-danger', isLimitReached)
            .toggleClass('text-muted', !isLimitReached);
        $($field.data('length-limit-message')).toggleClass('d-none', !isLimitReached);
    }

    $('[data-length-counter]').each(function () {
        refreshLengthCounter($(this));
    }).on('input', function () {
        refreshLengthCounter($(this));
    });
});
