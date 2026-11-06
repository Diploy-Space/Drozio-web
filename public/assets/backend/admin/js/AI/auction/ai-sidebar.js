function getAuctionAiMessage(key) {
    return $('#message-auction-ai-' + key).data('text');
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('auctionAiAssistantModal');
    const imageUpload = document.getElementById('auctionAiImageUpload');
    const imagePreview = document.getElementById('auctionImagePreview');
    const previewImg = document.getElementById('auctionPreviewImg');
    const chooseImageBtn = document.getElementById('auctionChooseImageBtn');
    const removeImageBtn = document.getElementById('auctionRemoveImageBtn');
    const progressText = document.getElementById('auctionAiProgressText');
    const modalTitle = document.getElementById('auctionAiModalTitle');

    function showAuctionSection(section) {
        $('#auctionAiAssistantModal .ai-modal-content').hide();

        if (section === 'upload') {
            $('#auctionUploadImageContent').show();
            if (modalTitle) modalTitle.textContent = getAuctionAiMessage('upload-analyze-image');
        } else if (section === 'title') {
            $('#auctionGiveTitleContent').show();
            if (modalTitle) modalTitle.textContent = getAuctionAiMessage('generate-product-title');
        } else {
            $('#auctionMainAiContent').show();
            if (modalTitle) modalTitle.textContent = getAuctionAiMessage('assistant');
        }
    }

    function resetAuctionTitleSection() {
        const $keywords = $('#auctionProductKeywords');
        if ($keywords.data('tagsinput')) {
            $keywords.tagsinput('removeAll');
        }
        $keywords.val('');
        $('#auctionTitlesList').empty();
        $('#auctionGeneratedTitles').hide();
        $('#auctionGeneratedTitles .show_generating_text').addClass('d-none');
        $('#auctionGeneratedTitles .titlesList_title').addClass('d-none');
    }

    function resetAuctionSidebar() {
        resetAuctionTitleSection();

        if (!imageUpload || !imagePreview || !chooseImageBtn) {
            return;
        }

        imageUpload.value = '';
        imagePreview.style.display = 'none';
        previewImg.src = '';
        $(chooseImageBtn).find('.text-box').removeClass('d-none');
        $(chooseImageBtn).removeClass('disabled');

        if (progressText) {
            progressText.textContent = '';
            progressText.classList.add('d-none');
        }

        $('.auction-ai-current').removeClass('auction-ai-current');
    }

    showAuctionSection('main');

    if (modal) {
        const onModalShow = function () {
            resetAuctionSidebar();
            showAuctionSection('main');
        };
        modal.addEventListener('show.bs.modal', onModalShow);
        $(modal).on('show.bs.modal', onModalShow);
    }

    $('#auctionAiAssistantModal').on('click', '.auction-ai-action-btn', function () {
        showAuctionSection($(this).data('action'));
    });

    if (imageUpload) {
        imageUpload.addEventListener('change', function (event) {
            $(chooseImageBtn).find('.text-box').addClass('d-none');
            const file = event.target.files[0];

            if (!file) {
                resetAuctionSidebar();
                return;
            }

            const reader = new FileReader();
            reader.onload = function (loadEvent) {
                previewImg.src = loadEvent.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    }

    if (removeImageBtn) {
        removeImageBtn.addEventListener('click', function () {
            resetAuctionSidebar();
        });
    }
});

function showAuctionAiSidebarError(xhr, fallbackMessage) {
    const errors = xhr.responseJSON?.errors;
    const messages = errors ? Object.values(errors).flatMap(function (fieldErrors) {
        if (Array.isArray(fieldErrors)) {
            return fieldErrors;
        }

        if (fieldErrors && typeof fieldErrors === 'object') {
            return [fieldErrors.message];
        }

        return [fieldErrors];
    }).filter(Boolean) : [];

    if (messages.length) {
        messages.forEach(function (message) {
            toastMagic.error(message);
        });
        return;
    }

    toastMagic.error(xhr.responseJSON?.message || fallbackMessage);
}

function runAuctionAiSequence(lang, onFinish) {
    const steps = [
        {
            step: 'description',
            label: getAuctionAiMessage('generating-description'),
            target: '#auction-description-container-' + lang,
            button: function () {
                return $('.auction-basic-setup-description-generate[data-lang="' + lang + '"]').first();
            }
        },
        {
            step: 'general-setup',
            label: getAuctionAiMessage('generating-general-setup'),
            target: '.auction-general-setup-section',
            button: function () {
                return $('.auction-general-setup-generate').first();
            }
        },
        {
            step: 'shipping-policy',
            label: getAuctionAiMessage('generating-shipping-policy'),
            target: '.auction-shipping-policy-section',
            button: function () {
                return $('.auction-shipping-policy-generate').first();
            }
        },
        {
            step: 'auction-info',
            label: getAuctionAiMessage('generating-auction-info'),
            target: '.auction-info-section',
            button: function () {
                return $('.auction-info-generate').first();
            }
        },
        {
            step: 'seo-section',
            label: getAuctionAiMessage('generating-seo-section'),
            target: '.auction-seo-section',
            button: function () {
                return $('.auction-seo-generate').first();
            }
        }
    ];

    let currentIndex = 0;
    const progressText = document.getElementById('auctionAiProgressText');

    function clearActiveSection() {
        $('.auction-ai-current').removeClass('auction-ai-current');
    }

    function scrollToSection(targetSelector) {
        const $target = $(targetSelector).first();

        if (!$target.length) {
            return;
        }

        const $highlightTarget = $target.hasClass('card') ? $target : $target.closest('.card');
        clearActiveSection();

        if ($highlightTarget.length) {
            $highlightTarget.addClass('auction-ai-current');
        }

        $('html, body').stop(true).animate({
            scrollTop: Math.max($target.offset().top - 120, 0)
        }, 500);
    }

    function closeAuctionAiModal() {
        const modalEl = document.getElementById('auctionAiAssistantModal');

        if (!modalEl) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal && typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal && typeof window.bootstrap.Modal.getInstance === 'function') {
            const modalInstance = window.bootstrap.Modal.getInstance(modalEl);

            if (modalInstance) {
                modalInstance.hide();
                return;
            }
        }

        if (typeof $ === 'function' && typeof $(modalEl).modal === 'function') {
            $(modalEl).modal('hide');
        }
    }

    function finishSequence() {
        clearActiveSection();

        if (progressText) {
            progressText.textContent = getAuctionAiMessage('generation-completed');
            progressText.classList.remove('d-none');
        }

        if (typeof onFinish === 'function') {
            onFinish();
        }

        setTimeout(function () {
            closeAuctionAiModal();
        }, 600);
    }

    function runNextStep() {
        if (currentIndex >= steps.length) {
            finishSequence();
            return;
        }

        const currentStep = steps[currentIndex];
        const $trigger = currentStep.button();
        let isSettled = false;

        function settleStep(isSuccessful) {
            if (isSettled) {
                return;
            }

            isSettled = true;
            $(document).off('auction-ai-step-complete', onStepComplete);
            currentIndex += 1;
            setTimeout(runNextStep, isSuccessful ? 500 : 300);
        }

        function onStepComplete(_event, payload) {
            if (payload && payload.step === currentStep.step) {
                settleStep(!!payload.success);
            }
        }

        if (!$trigger.length || $trigger.prop('disabled')) {
            settleStep(false);
            return;
        }

        if (progressText) {
            progressText.textContent = currentStep.label;
            progressText.classList.remove('d-none');
        }

        scrollToSection(currentStep.target);

        $(document).on('auction-ai-step-complete', onStepComplete);
        $trigger.trigger('click');

        if (!isSettled && !$trigger.prop('disabled')) {
            settleStep(false);
        }
    }

    runNextStep();
}

$(document).on('click', '#auctionAnalyzeImageBtn', function () {
    const $button = $(this);
    const route = $button.data('url') || $button.data('route');
    const lang = $button.data('lang');
    const imageInput = document.getElementById('auctionAiImageUpload');
    const $chooseImageBtn = $('#auctionChooseImageBtn');
    const $container = $('#auction-title-container-' + lang);
    const $titleField = $('#' + lang + '_name');
    const $titleCard = $container.closest('.card');
    const progressText = document.getElementById('auctionAiProgressText');

    if ($button.prop('disabled')) {
        return;
    }

    if (!imageInput || !imageInput.files[0]) {
        toastMagic.error(getAuctionAiMessage('image-required'));
        return;
    }

    $chooseImageBtn.addClass('disabled');
    $('.auction-ai-current').removeClass('auction-ai-current');
    $titleCard.addClass('auction-ai-current');

    if (progressText) {
        progressText.textContent = getAuctionAiMessage('generating-title');
        progressText.classList.remove('d-none');
    }

    if ($titleField.length) {
        $('html, body').animate({
            scrollTop: $titleField.offset().top - 100
        }, 500);
    }

    $container.addClass('outline-animating');
    $button.prop('disabled', true);
    $button.find('.btn-text').text(getAuctionAiMessage('generating'));
    $button.find('.ai-btn-animation').removeClass('d-none');

    let isSequenceStarted = false;

    function releaseAnalyzeButton() {
        $chooseImageBtn.removeClass('disabled');
        $button.prop('disabled', false);
        $button.find('.btn-text').text(getAuctionAiMessage('generate-product'));
        $button.find('.ai-btn-animation').addClass('d-none');
    }

    const formData = new FormData();
    formData.append('image', imageInput.files[0]);

    $.ajax({
        url: route,
        type: 'POST',
        dataType: 'json',
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: formData,
        success: function (response) {
            if (response?.data?.remaining_count !== undefined) {
                $('#ai-remaining-count #count').text(response.data.remaining_count);
            }

            const generatedTitle = response?.data?.data;

            if (typeof generatedTitle !== 'string' || !generatedTitle.length) {
                toastMagic.error(getAuctionAiMessage('image-analysis-failed'));
                return;
            }

            $titleField.val(generatedTitle).trigger('input').trigger('focus');
            $('#auction-title-' + lang + '-action-btn .btn-text').text(getAuctionAiMessage('regenerate'));
            isSequenceStarted = true;
            runAuctionAiSequence(lang, releaseAnalyzeButton);
        },
        error: function (xhr) {
            showAuctionAiSidebarError(xhr, getAuctionAiMessage('generation-failed'));
        },
        complete: function () {
            setTimeout(function () {
                $container.removeClass('outline-animating');
            }, 300);

            if (!isSequenceStarted) {
                releaseAnalyzeButton();
            }
        }
    });
});

$(document).on('click', '#auctionGenerateTitleBtn', function () {
    const $button = $(this);
    const route = $button.data('route');
    const lang = $button.data('lang');
    const keywords = $('#auctionProductKeywords').val();
    const $titlesList = $('#auctionTitlesList');
    const $generated = $('#auctionGeneratedTitles');
    const $loader = $button.find('.ai-loader-animation');
    const $icon = $button.find('.text-generate-icon');

    if (!keywords || !keywords.trim().length) {
        toastMagic.error(getAuctionAiMessage('keyword-required'));
        return;
    }

    $button.prop('disabled', true);
    $loader.removeClass('d-none');
    $icon.addClass('d-none');
    $generated.show();
    $generated.find('.show_generating_text').removeClass('d-none');
    $generated.find('.titlesList_title').addClass('d-none');
    $titlesList.empty();

    $.ajax({
        url: route,
        method: 'POST',
        data: {
            keywords: keywords,
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            const titles = response?.data?.data?.titles || [];
            $generated.find('.show_generating_text').addClass('d-none');
            $titlesList.empty();

            if (!titles.length) {
                $titlesList.empty().append($('<div class="text-center py-3">').text(getAuctionAiMessage('no-titles')));
                return;
            }

            $generated.find('.titlesList_title').removeClass('d-none');

            titles.forEach(function (title) {
                const $useBtn = $('<button type="button" class="btn btn-sm btn-outline-primary px-4 auction-use-title-btn">')
                    .text(getAuctionAiMessage('use'))
                    .attr('data-lang', lang)
                    .data('title', title);
                const $row = $('<div class="list-group-item list-group-item-action title-option p-0">')
                    .append(
                        $('<div class="d-flex justify-content-between align-items-center gap-2">')
                            .append($('<span class="overflow-wrap-anywhere">').text(title))
                            .append($useBtn)
                    );
                $titlesList.append($row);
            });

            if (response?.data?.remaining_count !== undefined) {
                $('#ai-remaining-count #count').text(response.data.remaining_count);
            }
        },
        error: function (xhr) {
            $generated.find('.show_generating_text').addClass('d-none');

            showAuctionAiSidebarError(xhr, getAuctionAiMessage('titles-failed'));
        },
        complete: function () {
            $button.prop('disabled', false);
            $loader.addClass('d-none');
            $icon.removeClass('d-none');
        }
    });
});

$(document).on('click', '.auction-use-title-btn', function (e) {
    e.preventDefault();

    const title = $(this).data('title');
    const lang = $(this).data('lang');
    const $titleField = $('#' + lang + '_name');

    if (!$titleField.length) {
        return;
    }

    $titleField.val(title).trigger('input').trigger('focus');
    $('#auction-title-' + lang + '-action-btn .btn-text').text(getAuctionAiMessage('regenerate'));
    $titleField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
});
