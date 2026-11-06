$(document).ready(function() {
    const DROPDOWN_HEIGHT = 280;
    const STATIONARY_POINTER_TOLERANCE = 5;

    let pointerPress = null;

    document.addEventListener('mousedown', function (event) {
        pointerPress = { x: event.clientX, y: event.clientY, opensDropdown: false };
    }, true);

    document.addEventListener('mouseup', function (event) {
        let press = pointerPress;
        pointerPress = null;

        if (!press || !press.opensDropdown || !(event.target instanceof Element)) {
            return;
        }

        let isStationary = Math.abs(event.clientX - press.x) <= STATIONARY_POINTER_TOLERANCE
            && Math.abs(event.clientY - press.y) <= STATIONARY_POINTER_TOLERANCE;

        if (isStationary && event.target.closest('.select2-results')) {
            event.stopPropagation();
        }
    }, true);

    function scrollingAncestorOf(element) {
        let node = element.parentElement;

        while (node && node !== document.body) {
            let overflowY = window.getComputedStyle(node).overflowY;

            if ((overflowY === 'auto' || overflowY === 'scroll') && node.scrollHeight > node.clientHeight) {
                return node;
            }

            node = node.parentElement;
        }

        return document.scrollingElement || document.documentElement;
    }

    function makeRoomForDropdown($select) {
        let field = $select.next('.select2-container')[0];

        if (!field) {
            return;
        }

        let scroller = scrollingAncestorOf($select[0]);
        let scrollerBottom = scroller === (document.scrollingElement || document.documentElement)
            ? window.innerHeight
            : scroller.getBoundingClientRect().bottom;
        let shortfall = DROPDOWN_HEIGHT - (scrollerBottom - field.getBoundingClientRect().bottom);

        if (shortfall > 0) {
            scroller.scrollTop += shortfall;
        }
    }

    $(".custom-select").each(function() {
        let $select = $(this);

        const val = $select.val();
        const initialSelected = Array.isArray(val) ? val.map(v => v.toString()) : val ? [val.toString()] : [];
        $select.data('initialSelected', initialSelected);

        let isInsideOffcanvas = $select.closest(".offcanvas").length > 0;
        let isInsideModal = $select.closest(".modal").length > 0;
        let enableTags = $select.hasClass("tags");
        let isColorSelect = $select.hasClass("color-var-select");
        let isImageSelect = $select.hasClass("image-var-select");

        $select.select2({
            placeholder: $select.data("placeholder"),
            width: "100%",
            allowClear: true,
            minimumResultsForSearch: $select.data("without-search") || 0,
            tags: enableTags,
            maximumSelectionLength:
                $select.data("max-length") !== undefined
                    ? $select.data("max-length")
                    : 0,
            dropdownParent: isInsideOffcanvas
                ? $select.closest(".offcanvas")
                : isInsideModal
                ? $select.closest(".modal")
                : null,
            templateResult: isColorSelect
                ? formatColor
                : isImageSelect
                ? formatImage
                : undefined,
            templateSelection: isColorSelect
                ? formatColor
                : isImageSelect
                ? formatImage
                : undefined
        });

        $select.on('select2:opening', function () {
            if (pointerPress) {
                pointerPress.opensDropdown = true;
            }

            makeRoomForDropdown($select);
        });

        function formatColor(option) {
            if (!option.id) return option.text;

            let colorCode = $(option.element).data("color");
            if (!colorCode) return option.text;

            return $(
                `<div style="display: flex; align-items: center; gap: 5px;">
                <span style="width: 16px; height: 16px; background-color: ${colorCode}; display: inline-block; border-radius: 100px; margin-inline-end: 8px;"></span>
                ${option.text}
            </div>`
            );
        }

        function formatImage(option) {
            if (!option.id) return option.text;

            let imageUrl = $(option.element).data("image-url");
            if (!imageUrl) return option.text;

            return $(
                `<div style="display: flex; align-items: center; gap: 5px;">
                    <img src="${imageUrl}" alt="${option.text}" style="width: 14px; height: 14px; object-fit: contain;">
                    ${option.text}
                </div>`
            );
        }

        if ($select.prop("multiple")) {
            let $selection = $select
                .next(".select2-container")
                .find(".select2-selection");

            if ($selection.find(".select2-selection__arrow").length === 0) {
                $selection.append(
                    '<span class="select2-selection__arrow"><b role="presentation"></b></span>'
                );
            }

            let updateMoreTag = () => {
                $selection.find(".more").remove();

                let $rendered = $selection.find(".select2-selection__rendered");
                let $choices = $rendered.find(".select2-selection__choice, .name");
                let totalChoices = $choices.length;

                if (totalChoices === 0) return;

                let totalWidth = Math.max($selection.outerWidth() - 100, 0);
                let currentWidth = 0;
                let hiddenCount = 0;

                $choices.each(function() {
                    currentWidth += $(this).outerWidth(true);

                    if (currentWidth >= totalWidth) {
                        hiddenCount++;
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });

                if (hiddenCount > 0) {
                    let $more = $(`<li class="more">+${hiddenCount}</li>`);
                    $rendered.append($more);
                }
            };

            $select.data("updateMoreTag", updateMoreTag);
            setTimeout(updateMoreTag, 50);
            $select.on("change select2:select select2:unselect select2:open", updateMoreTag);
            $select.on('select2:select', function (e) {
                if (e.params.data.id === 'all') {
                    let allValues = [];
                    $select.find('option').each(function () {
                        if ($(this).val() !== 'all') {
                            allValues.push($(this).val());
                        }
                    });
                    $select.val(allValues).trigger('change');
                }
            });

            $select.on('select2:unselect', function (e) {
                if (e.params.data.id === 'all') {
                    $select.val(null).trigger('change');
                }
            });
            $(window).on("resize", () => setTimeout(updateMoreTag, 0));

        }
    });

    $('#select_vat_type').on('click',function() {
        $(".custom-select").each(function () {
            let updateFn = $(this).data("updateMoreTag");
            if (typeof updateFn === "function") {
                setTimeout(updateFn, 50);
            }
        });

    });


    const $toggle = $('.product_variation_toggle');
    if ($toggle.length) {
        $toggle.on('click', function () {
            $(".custom-select").each(function () {
                let updateFn = $(this).data("updateMoreTag");
                if (typeof updateFn === "function") {
                    setTimeout(updateFn, 50);
                }
            });
        });
    }


    $('button[type="reset"]').on('click', function () {
        const form = $(this).closest('form');

        setTimeout(function () {
            form.find('select.custom-select').each(function () {
                const $select = $(this);
                const initialValues = $select.data('initialSelected') || [];

                $select.val(initialValues).trigger('change');

                const updateFn = $select.data("updateMoreTag");
                if (typeof updateFn === "function") updateFn();
            });
        }, 10);
    });


    function releaseAncestorScrollLock(event) {
        let select2 = $(event.target).data('select2');

        if (select2 && select2.$container) {
            select2.$container.parents().off('scroll.select2.' + select2.id);
        }
    }

    $(document).on('select2:open', releaseAncestorScrollLock);

    function repositionOpenDropdowns() {
        $('select.select2-hidden-accessible').each(function () {
            let s2 = $(this).data('select2');
            if (s2 && s2.isOpen() && s2.dropdown) {
                s2.dropdown._positionDropdown();
                s2.dropdown._resizeDropdown();
            }
        });
    }

    document.addEventListener('scroll', repositionOpenDropdowns, true);
    $(window).on('resize', repositionOpenDropdowns);
});
