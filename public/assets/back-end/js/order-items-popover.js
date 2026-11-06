"use strict";

$(function () {
    const itemsPopovers = $(".order-items-popover");

    function showItemsPopover(itemsWrapper) {
        const toggleButton = itemsWrapper.find(".order-items-popover-toggle");
        const popover = itemsWrapper.find(".order-items-popover-menu");
        const triggerRect = toggleButton[0].getBoundingClientRect();

        popover.css({ position: "fixed", display: "block", visibility: "hidden", top: 0, left: 0, zIndex: 9999 });

        const popoverRect = popover[0].getBoundingClientRect();
        let top = triggerRect.bottom;
        const isRightToLeft = document.documentElement.getAttribute("dir") === "rtl";
        let left = isRightToLeft ? triggerRect.right - popoverRect.width : triggerRect.left;

        if (top + popoverRect.height > window.innerHeight && triggerRect.top - popoverRect.height > 0) {
            top = triggerRect.top - popoverRect.height;
        }

        if (left + popoverRect.width > window.innerWidth) {
            left = Math.max(0, window.innerWidth - popoverRect.width);
        }

        if (left < 0) {
            left = 0;
        }

        popover.css({ top: top + "px", left: left + "px", visibility: "visible" });
        toggleButton.attr("aria-expanded", "true");
    }

    function hideItemsPopover(itemsWrapper) {
        itemsWrapper.find(".order-items-popover-menu").css({ display: "", visibility: "", position: "", top: "", left: "", zIndex: "" });
        itemsWrapper.find(".order-items-popover-toggle").attr("aria-expanded", "false");
    }

    itemsPopovers
        .on("mouseenter focusin", function () {
            showItemsPopover($(this));
        })
        .on("mouseleave", function () {
            if (!this.contains(document.activeElement)) {
                hideItemsPopover($(this));
            }
        })
        .on("focusout", function (event) {
            if (!this.contains(event.relatedTarget)) {
                hideItemsPopover($(this));
            }
        })
        .on("keydown", function (event) {
            if (event.key === "Escape") {
                hideItemsPopover($(this));
            }
        });

    function repositionOpenItemsPopovers() {
        itemsPopovers.filter(function () {
            return $(this).find(".order-items-popover-toggle").attr("aria-expanded") === "true";
        }).each(function () {
            showItemsPopover($(this));
        });
    }

    document.addEventListener("scroll", repositionOpenItemsPopovers, true);
    $(window).on("resize", repositionOpenItemsPopovers);
});
