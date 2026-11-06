"use strict";

function imageSlider() {
    $(document).on(
        "click",
        '[data-bs-target^="#imgViewModal"]',
        function() {
            var modalId = $(this).attr("data-bs-target");
            var $modal = $(modalId);
            var $carousel = $modal.find(".imgView-slider");

            var slideCount = $modal.find(".imgView-item").length;

            if ($carousel.hasClass("owl-loaded")) {
                $carousel
                    .trigger("destroy.owl.carousel")
                    .removeClass("owl-loaded");
                $carousel.html($carousel.find(".owl-stage-outer").html());
            }

            var imgView = $carousel.owlCarousel({
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
                    var currentIndex = event.item.index;
                    $modal
                        .find(".imgView-owl-prev")
                        .prop("disabled", currentIndex === 0)
                        .toggleClass("disabled", currentIndex === 0);
                    $modal
                        .find(".imgView-owl-next")
                        .prop("disabled", currentIndex === slideCount - 1)
                        .toggleClass(
                            "disabled",
                            currentIndex === slideCount - 1
                        );
                }
            });

            if (slideCount <= 1) {
                $modal.find(".imgView-owl-prev, .imgView-owl-next").hide();
            } else {
                $modal.find(".imgView-owl-prev, .imgView-owl-next").show();

                $modal
                    .find(".imgView-owl-prev")
                    .off("click")
                    .on("click", function() {
                        imgView.trigger("prev.owl.carousel");
                    });

                $modal
                    .find(".imgView-owl-next")
                    .off("click")
                    .on("click", function() {
                        imgView.trigger("next.owl.carousel");
                    });
            }

            var index = $(this).data("index");
            if (slideCount > 1) {
                imgView.trigger("to.owl.carousel", [index, 0]);
            }

            $modal.find(".imgView-item").each(function() {
                var imgSrc = $(this)
                    .find("img")
                    .attr("src");
                if (imgSrc) {
                    var imgTitle = imgSrc.split("/").pop();
                    $(this)
                        .find(".img-title")
                        .text(imgTitle);
                }
            });
        }
    );
}
imageSlider();

function checkImageOverflow() {
    $(".refund-image-wrapper").each(function () {
        let $nav = $(this);
        let $wrapper = $nav.closest(".position-relative");
        let $btnNext = $wrapper.find(".next_btn");
        let $btnPrev = $wrapper.find(".prev_btn");

        let isRTL = $("html").attr("dir") === "rtl";
        let nav = $nav[0];

        let navScrollWidth = nav.scrollWidth;
        let navClientWidth = nav.clientWidth;
        let scrollLeft = Math.abs($nav.scrollLeft());

        if (isRTL) {
            let maxScrollLeft = navScrollWidth - navClientWidth;
            let scrollRight = maxScrollLeft - scrollLeft;

            $btnNext.toggle(scrollRight > 1);
            $btnPrev.toggle(scrollLeft > 1);
        } else {
            $btnNext.toggle(
                navScrollWidth > navClientWidth &&
                scrollLeft + navClientWidth < navScrollWidth - 1
            );
            $btnPrev.toggle(scrollLeft > 1);
        }
    });
}

$(".refund-image-wrapper").each(function () {
    let $nav = $(this);
    let $wrapper = $nav.closest(".position-relative");
    let $btnNext = $wrapper.find(".next_btn");
    let $btnPrev = $wrapper.find(".prev_btn");

    checkImageOverflow();

    $(window).on("resize", function () {
        checkImageOverflow();
    });

    $nav.on("scroll", function () {
        checkImageOverflow();
    });

    let scrollAmount = $nav.find("a").outerWidth(true);

    $btnNext.on("click", function () {
        let isRTL = $("html").attr("dir") === "rtl";

        $nav.animate(
            {
                scrollLeft: isRTL
                    ? $nav.scrollLeft() - scrollAmount
                    : $nav.scrollLeft() + scrollAmount
            },
            300
        );
    });

    $btnPrev.on("click", function () {
        let isRTL = $("html").attr("dir") === "rtl";

        $nav.animate(
            {
                scrollLeft: isRTL
                    ? $nav.scrollLeft() + scrollAmount
                    : $nav.scrollLeft() - scrollAmount
            },
            300
        );
    });
});
