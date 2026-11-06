"use strict";

$(document).ready(function () {
    const $ticketForm = $('#open-ticket');
    const $attachmentPicker = $ticketForm.find('.ticket-attachment-picker');
    const $submitButton = $('#ticket-submit-button');

    function countSelectedAttachments() {
        return $attachmentPicker.find('input[type="file"]').filter(function () {
            return this.files && this.files.length > 0;
        }).length;
    }

    function toggleTicketSubmitButton() {
        const hasSubject = $.trim($('#ticket-subject').val()) !== '';
        const hasPriority = !!$('#ticket-priority').val();
        const hasDescription = $.trim($('#ticket-description').val()) !== '';
        const hasAttachment = countSelectedAttachments() > 0;

        $submitButton.prop('disabled', !(hasSubject && hasPriority && (hasDescription || hasAttachment)));
    }

    function showTicketDescriptionLength() {
        $('#ticket-description-count').text($('#ticket-description').val().length);
    }

    function showUploadError(message) {
        if (typeof toastMagic !== 'undefined') {
            toastMagic.error(message);
        }
    }

    if ($attachmentPicker.length) {
        $attachmentPicker.spartanMultiImagePicker({
            fieldName: $attachmentPicker.data('field-name'),
            maxCount: 5,
            rowHeight: '70px',
            groupClassName: 'upload-custom-img',
            maxFileSize: Number($attachmentPicker.data('max-filesize')) * 1024 * 1024,
            allowedExt: 'png|jpg|jpeg|gif',
            placeholderImage: {
                image: $('#get-place-holder-image').data('src'),
                width: '100%'
            },
            onRenderedPreview: toggleTicketSubmitButton,
            onRemoveRow: function () {
                setTimeout(toggleTicketSubmitButton);
            },
            onExtensionErr: function () {
                showUploadError($attachmentPicker.data('extension-error'));
            },
            onSizeErr: function () {
                showUploadError($attachmentPicker.data('size-error'));
            }
        });
    }

    $('#ticket-subject, #ticket-priority, #ticket-description').on('input change', toggleTicketSubmitButton);
    $('#ticket-description').on('input', showTicketDescriptionLength);
    $attachmentPicker.on('change', 'input[type="file"]', toggleTicketSubmitButton);
});
