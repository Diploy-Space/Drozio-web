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

    function selectOptionIfAvailable(selector, value) {
        const $select = $(selector);
        const optionValue = value === null || value === undefined ? '' : String(value);

        if (!$select.find('option').filter(function () {
            return this.value === optionValue;
        }).length) {
            return false;
        }

        $select.val(optionValue).trigger('change');
        return true;
    }

    function restoreSelectValue(selector, value) {
        if (value === null || value === undefined) {
            $(selector).prop('selectedIndex', 0).trigger('change');
            return;
        }

        selectOptionIfAvailable(selector, value);
    }

    function restoreGeneralSetup(existingData) {
        restoreSelectValue('#category_id', existingData.category_id);
        restoreSelectValue('#brand_id', existingData.brand_id);
        restoreSelectValue('#item_condition', existingData.item_condition);
    }

    function updateAiRemainingCount(response) {
        const remaining = response?.data?.remaining_count;

        if (typeof remaining === "number") {
            $("#ai-remaining-count").find("#count").text(remaining);
        }
    }

    $(document).on('click', '.auction-general-setup-generate', function () {
        const $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        const route = $button.data('route');
        const $container = $('.auction-general-setup-section').first();
        const $defaultTitleInput = $('.product-title-default-language').first();
        const title = ($defaultTitleInput.val() || '').trim();
        const description = getDefaultAuctionDescription();
        const existingData = {
            category_id: $('#category_id').val(),
            brand_id: $('#brand_id').val(),
            item_condition: $('#item_condition').val()
        };
        let generationSucceeded = false;

        if (!title.length) {
            toastMagic.error(getAuctionAiMessage('general-setup-name-required'));
            reportStepComplete('general-setup', false);
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

                const isCategoryFilled = selectOptionIfAvailable('#category_id', data.category_id);

                if (Object.prototype.hasOwnProperty.call(data, 'brand_id')) {
                    selectOptionIfAvailable('#brand_id', data.brand_id || '');
                }

                const isConditionFilled = selectOptionIfAvailable('#item_condition', data.item_condition);

                if (!isCategoryFilled || !isConditionFilled) {
                    restoreGeneralSetup(existingData);
                    toastMagic.error(getAuctionAiMessage('generation-failed'));
                    return;
                }

                generationSucceeded = true;
            },
            error: function (xhr) {
                restoreGeneralSetup(existingData);
                showAuctionAiError(xhr, getAuctionAiMessage('generation-failed'));
            },
            complete: function () {
                setTimeout(function () {
                    $container.removeClass('outline-animating');
                }, 500);

                toggleGeneratingState($button, false);
                reportStepComplete('general-setup', generationSucceeded);
            }
        });
    });
});
