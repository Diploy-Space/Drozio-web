$(document).ready(function () {
    function getAuctionAiMessage(key) {
        return $('#message-auction-ai-' + key).data('text');
    }

    function getAuctionEditorInstance(lang) {
        const editorElement = document.getElementById('description-' + lang + '-editor');

        if (!editorElement) {
            return null;
        }

        return $(editorElement).data('quill') || Quill.find(editorElement) || null;
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

    $(document).on('click', '.auction-basic-setup-title-generate', function () {
        const $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        const lang = $button.data('lang');
        const route = $button.data('route');
        const $nameInput = $('#' + lang + '_name');
        const $container = $('#auction-title-container-' + lang);
        const currentTitle = ($nameInput.val() || '').trim();
        const existingTitle = $nameInput.val() || '';
        let generationSucceeded = false;

        if (!currentTitle.length) {
            toastMagic.error(getAuctionAiMessage('title-name-required'));
            reportStepComplete('title', false, lang);
            return;
        }

        $container.addClass('outline-animating');
        toggleGeneratingState($button, true);

        $.ajax({
            url: route,
            type: 'GET',
            dataType: 'json',
            data: {
                name: currentTitle,
                langCode: lang
            },
            success: function (response) {
                updateAiRemainingCount(response);
                const generatedTitle = response?.data?.data;

                if (typeof generatedTitle === 'string' && generatedTitle.length) {
                    $nameInput.val(generatedTitle).trigger('input');
                    generationSucceeded = true;
                    return;
                }

                toastMagic.error(getAuctionAiMessage('generation-failed'));
            },
            error: function (xhr) {
                $nameInput.val(existingTitle).trigger('input');
                showAuctionAiError(xhr, getAuctionAiMessage('generation-failed'));
            },
            complete: function () {
                setTimeout(function () {
                    $container.removeClass('outline-animating');
                }, 500);

                toggleGeneratingState($button, false);
                reportStepComplete('title', generationSucceeded, lang);
            }
        });
    });

    $(document).on('click', '.auction-basic-setup-description-generate', function () {
        const $button = $(this);

        if ($button.prop('disabled')) {
            return;
        }

        const lang = $button.data('lang');
        const route = $button.data('route');
        const $nameInput = $('#' + lang + '_name');
        const $textarea = $('#description-' + lang);
        const $container = $('#auction-description-container-' + lang);
        const quillEditor = getAuctionEditorInstance(lang);
        const currentTitle = ($nameInput.val() || '').trim();
        const existingDescription = quillEditor ? quillEditor.root.innerHTML : ($textarea.val() || '');
        let generationSucceeded = false;

        if (!currentTitle.length) {
            toastMagic.error(getAuctionAiMessage('description-name-required'));
            reportStepComplete('description', false, lang);
            return;
        }

        $container.addClass('outline-animating');
        toggleGeneratingState($button, true);

        $.ajax({
            url: route,
            type: 'GET',
            dataType: 'json',
            data: {
                name: currentTitle,
                langCode: lang
            },
            success: function (response) {
                updateAiRemainingCount(response);
                const generatedDescription = response?.data?.data;

                if (typeof generatedDescription !== 'string' || !generatedDescription.length) {
                    toastMagic.error(getAuctionAiMessage('generation-failed'));
                    return;
                }

                if (quillEditor) {
                    quillEditor.root.innerHTML = generatedDescription;
                }

                $textarea.val(generatedDescription).trigger('input');
                generationSucceeded = true;
            },
            error: function (xhr) {
                if (quillEditor) {
                    quillEditor.root.innerHTML = existingDescription;
                }
                $textarea.val(existingDescription).trigger('input');
                showAuctionAiError(xhr, getAuctionAiMessage('generation-failed'));
            },
            complete: function () {
                setTimeout(function () {
                    $container.removeClass('outline-animating');
                }, 500);

                toggleGeneratingState($button, false);
                reportStepComplete('description', generationSucceeded, lang);
            }
        });
    });
});
