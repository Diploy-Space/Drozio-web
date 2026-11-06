"use strict";

$(document).ready(() => {
    const $input = $("#msgInputValue");
    const $sendButton = $("#msgSendBtn");
    const $charCount = $("#charCount");
    const maxLength = parseInt($input.data("maxlength"), 10) || 0;

    function hasContent() {
        const hasText = ($input.val() || "").trim().length > 0;
        const hasImages = typeof selectedImages !== "undefined" && selectedImages.length > 0;
        const hasFiles = typeof selectedFiles !== "undefined" && selectedFiles.length > 0;

        return hasText || hasImages || hasFiles;
    }

    function refreshComposer() {
        const length = ($input.val() || "").length;

        $charCount
            .text(length + "/" + maxLength)
            .toggleClass("text-danger", maxLength > 0 && length >= maxLength);
        $sendButton.prop("disabled", !hasContent());
    }

    $input.on("input", refreshComposer);
    $(document).on("change", "#select-media, #select-file", () => setTimeout(refreshComposer, 0));
    $(document).on("click", ".img-clear, .remove-file", () => setTimeout(refreshComposer, 0));
    refreshComposer();

    $(document).on("click", '[data-bs-target^="#imgViewModal"]', function() {
        const $modal = $($(this).attr("data-bs-target"));
        const $carousel = $modal.find(".imgView-slider");
        const slideCount = $modal.find(".imgView-item").length;

        if ($carousel.hasClass("owl-loaded")) {
            $carousel.trigger("destroy.owl.carousel").removeClass("owl-loaded");
            $carousel.html($carousel.find(".owl-stage-outer").html());
        }

        const slider = $carousel.owlCarousel({
            items: 1,
            loop: false,
            margin: 0,
            nav: false,
            dots: false,
            mouseDrag: slideCount > 1,
            touchDrag: slideCount > 1,
            autoplay: false,
            smartSpeed: 500,
            onChanged: function(event) {
                const currentIndex = event.item.index;
                $modal
                    .find(".imgView-owl-prev")
                    .prop("disabled", currentIndex === 0)
                    .toggleClass("disabled", currentIndex === 0);
                $modal
                    .find(".imgView-owl-next")
                    .prop("disabled", currentIndex === slideCount - 1)
                    .toggleClass("disabled", currentIndex === slideCount - 1);
            }
        });

        if (slideCount <= 1) {
            $modal.find(".imgView-owl-prev, .imgView-owl-next").hide();
            return;
        }

        $modal.find(".imgView-owl-prev, .imgView-owl-next").show();
        $modal
            .find(".imgView-owl-prev")
            .off("click")
            .on("click", () => slider.trigger("prev.owl.carousel"));
        $modal
            .find(".imgView-owl-next")
            .off("click")
            .on("click", () => slider.trigger("next.owl.carousel"));

        slider.trigger("to.owl.carousel", [$(this).data("index"), 0]);
    });
});
