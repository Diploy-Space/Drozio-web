$(document).ready(function () {
    function getAuctionAiMessage(key) {
        return $('#message-auction-ai-' + key).data('text');
    }

    function getDefaultAuctionLanguageCode() {
        const $defaultInput = $('.product-title-default-language').first();

        if (!$defaultInput.length) {
            return null;
        }

        const inputId = $defaultInput.attr('id') || '';
        return inputId.replace(/_name$/, '');
    }

    function getDefaultAuctionDescription() {
        const lang = getDefaultAuctionLanguageCode();

        if (!lang) {
            return '';
        }

        const editorElement = document.getElementById('description-' + lang + '-editor');
        const textareaValue = $('#description-' + lang).val() || '';

        if (!editorElement) {
            return textareaValue;
        }

        const quillEditor = $(editorElement).data('quill') || Quill.find(editorElement);
        return quillEditor ? quillEditor.root.innerHTML : textareaValue;
    }

    function toggleGeneratingState($button, isGenerating) {
        const $aiText = $button.find('.ai-text-animation');
        const $buttonText = $button.find('.btn-text');

        $button.prop('disabled', isGenerating);

        if (isGenerating) {
            $buttonText.text('');
            $aiText.removeClass('d-none').addClass('ai-text-animation-visible');
            return;
        }

        $buttonText.text(getAuctionAiMessage('regenerate'));
        $aiText.addClass('d-none').removeClass('ai-text-animation-visible');
    }

    function getErrorMessages(xhr) {
        const errors = xhr.responseJSON?.errors;

        if (!errors) {
            return [];
        }

        return Object.values(errors).flatMap(function (fieldErrors) {
            if (Array.isArray(fieldErrors)) {
                return fieldErrors;
            }

            if (fieldErrors && typeof fieldErrors === 'object') {
                return [fieldErrors.message];
            }

            return [fieldErrors];
        }).filter(Boolean);
    }

    function showAuctionAiError(xhr, fallbackMessage) {
        const messages = getErrorMessages(xhr);

        if (messages.length) {
            messages.forEach(function (message) {
                toastMagic.error(message);
            });
            return;
        }

        toastMagic.error(xhr.responseJSON?.message || fallbackMessage);
    }

    function reportStepComplete(step, isSuccessful, lang) {
        const payload = { step: step, success: isSuccessful };

        if (lang) {
            payload.lang = lang;
        }

        $(document).trigger('auction-ai-step-complete', [payload]);
    }

    function updateAiRemainingCount(response) {
        const remaining = response?.data?.remaining_count;

        if (typeof remaining === "number") {
            $("#ai-remaining-count").find("#count").text(remaining);
        }
    }

    $(document).on('click', '.auction-shipping-policy-generate', function () {
        const $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        const route = $button.data('route');
        const $container = $('.auction-shipping-policy-section').first();
        const $defaultTitleInput = $('.product-title-default-language').first();
        const title = ($defaultTitleInput.val() || '').trim();
        const description = getDefaultAuctionDescription();
        const existingData = {
            shipping_fee: $('#shipping_fee').val(),
            return_policy: $('#return_policy').val()
        };
        let generationSucceeded = false;

        if (!title.length) {
            toastMagic.error(getAuctionAiMessage('shipping-policy-name-required'));
            reportStepComplete('shipping-policy', false);
            return;
        }

        $container.addClass('outline-animating');
        toggleGeneratingState($button, true);

        $.ajax({
            url: route,
            type: 'GET',
            dataType: 'json',
            data: {
                name: title,
                description: description
            },
            success: function (response) {
                updateAiRemainingCount(response);
                const data = response?.data?.data;

                if (!data || typeof data !== 'object') {
                    toastMagic.error(getAuctionAiMessage('generation-failed'));
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(data, 'shipping_fee')) {
                    $('#shipping_fee').val(data.shipping_fee ?? '').trigger('input');
                }

                if (Object.prototype.hasOwnProperty.call(data, 'return_policy')) {
                    $('#return_policy').val(data.return_policy || '').trigger('input');
                }

                generationSucceeded = true;
            },
            error: function (xhr) {
                $('#shipping_fee').val(existingData.shipping_fee ?? '').trigger('input');
                $('#return_policy').val(existingData.return_policy ?? '').trigger('input');

                showAuctionAiError(xhr, getAuctionAiMessage('generation-failed'));
            },
            complete: function () {
                setTimeout(function () {
                    $container.removeClass('outline-animating');
                }, 500);

                toggleGeneratingState($button, false);
                reportStepComplete('shipping-policy', generationSucceeded);
            }
        });
    });
});
