$(document).ready(function () {
    function getAuctionAiMessage(key) {
        return $('#message-auction-ai-' + key).data('text');
    }

    const DATERANGEPICKER_FORMAT = 'DD MMM YYYY, hh:mm A';
    const AMOUNT_FIELDS = ['starting_price', 'minimum_increment_amount', 'maximum_decrement_amount'];

    function getAuctionDateInput(fieldName) {
        return $('#' + fieldName).length ? $('#' + fieldName) : $('[name="' + fieldName + '"]').first();
    }

    function normalizeAuctionDateValue(value, $input) {
        if (!$input.length || !value) {
            return value || '';
        }

        if ($input.attr('type') === 'datetime-local') {
            if (typeof moment === 'function') {
                const parsedValue = moment(value);

                if (parsedValue.isValid()) {
                    return parsedValue.format('YYYY-MM-DDTHH:mm');
                }
            }

            return String(value).replace(' ', 'T').slice(0, 16);
        }

        if ($input.hasClass('js-select2-date') && typeof moment === 'function') {
            const parsed = moment(value, ['YYYY-MM-DD HH:mm:ss', 'YYYY-MM-DD HH:mm', 'YYYY-MM-DDTHH:mm', DATERANGEPICKER_FORMAT, moment.ISO_8601], true);

            if (parsed.isValid()) {
                return parsed.format(DATERANGEPICKER_FORMAT);
            }
        }

        return value;
    }

    function setAuctionDateField(fieldName, value) {
        const $input = getAuctionDateInput(fieldName);

        if (!$input.length) {
            return;
        }

        const formattedValue = normalizeAuctionDateValue(value, $input);
        $input.val(formattedValue).trigger('input').trigger('change');

        const picker = $input.data('daterangepicker');

        if (picker && formattedValue && typeof moment === 'function') {
            const parsed = moment(formattedValue, DATERANGEPICKER_FORMAT);

            if (parsed.isValid()) {
                picker.setStartDate(parsed);
                picker.setEndDate(parsed);
            }
        }
    }

    function setAuctionTaxIds(taxIds) {
        const $taxSelect = $('#tax_ids');

        if (!Array.isArray(taxIds) || !$taxSelect.length) {
            return;
        }

        $taxSelect.val(taxIds.map(String)).trigger('change');
    }

    function setAuctionTags(tags) {
        const $tagsInput = $('#tags');

        if (!$tagsInput.length || !tags.length) {
            return;
        }

        if ($tagsInput.data('tagsinput')) {
            $tagsInput.tagsinput('removeAll');
            tags.forEach(function (tag) {
                $tagsInput.tagsinput('add', tag);
            });
            return;
        }

        $tagsInput.val(tags.join(', ')).trigger('change');
    }

    function getGeneratedTags(data) {
        const tagsArray = Array.isArray(data.tags)
            ? data.tags
            : (typeof data.tags_csv === 'string' ? data.tags_csv.split(',') : []);

        return tagsArray
            .map(function (tag) { return String(tag || '').trim(); })
            .filter(function (tag) { return tag.length > 0; });
    }

    function applyAuctionInfo(data) {
        AMOUNT_FIELDS.forEach(function (field) {
            if (Object.prototype.hasOwnProperty.call(data, field)) {
                $('#' + field).val(data[field] ?? '').trigger('input').trigger('change');
            }
        });

        setAuctionTaxIds(data.tax_ids);

        if (Object.prototype.hasOwnProperty.call(data, 'start_time')) {
            setAuctionDateField('auction_start_time', data.start_time || '');
        }

        if (Object.prototype.hasOwnProperty.call(data, 'end_time')) {
            setAuctionDateField('auction_end_time', data.end_time || '');
        }

        setAuctionTags(getGeneratedTags(data));
    }

    function restoreAuctionInfo(existingData) {
        AMOUNT_FIELDS.forEach(function (field) {
            if (Object.prototype.hasOwnProperty.call(existingData, field)) {
                $('#' + field).val(existingData[field] ?? '').trigger('input').trigger('change');
            }
        });

        setAuctionTaxIds(existingData.tax_ids);

        if (Object.prototype.hasOwnProperty.call(existingData, 'auction_start_time')) {
            setAuctionDateField('auction_start_time', existingData.auction_start_time || '');
        }

        if (Object.prototype.hasOwnProperty.call(existingData, 'auction_end_time')) {
            setAuctionDateField('auction_end_time', existingData.auction_end_time || '');
        }
    }

    function getDefaultAuctionLanguageCode() {
        const $defaultInput = $('.product-title-default-language').first();

        if (!$defaultInput.length) {
            return null;
        }

        return ($defaultInput.attr('id') || '').replace(/_name$/, '');
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

    function updateAiRemainingCount(response) {
        const remaining = response?.data?.remaining_count;

        if (typeof remaining === 'number') {
            $('#ai-remaining-count').find('#count').text(remaining);
        }
    }

    function reportStepComplete(isSuccessful) {
        $(document).trigger('auction-ai-step-complete', [{
            step: 'auction-info',
            success: isSuccessful
        }]);
    }

    $(document).on('click', '.auction-info-generate', function () {
        const $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        const route = $button.data('route');
        const $container = $('.auction-info-section').first();
        const title = ($('.product-title-default-language').first().val() || '').trim();
        const existingData = $button.data('item') || {};
        let generationSucceeded = false;

        if (!title.length) {
            toastMagic.error(getAuctionAiMessage('auction-info-name-required'));
            reportStepComplete(false);
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
                description: getDefaultAuctionDescription()
            },
            success: function (response) {
                updateAiRemainingCount(response);
                const data = response?.data?.data;

                if (!data || typeof data !== 'object') {
                    toastMagic.error(getAuctionAiMessage('auction-info-failed'));
                    return;
                }

                applyAuctionInfo(data);
                generationSucceeded = true;
            },
            error: function (xhr) {
                restoreAuctionInfo(existingData);
                showAuctionAiError(xhr, getAuctionAiMessage('generation-failed'));
            },
            complete: function () {
                setTimeout(function () {
                    $container.removeClass('outline-animating');
                }, 500);

                toggleGeneratingState($button, false);
                reportStepComplete(generationSucceeded);
            }
        });
    });
});
