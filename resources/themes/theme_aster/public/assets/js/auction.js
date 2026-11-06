"use strict";

(function () {
    const flashContainers = document.querySelectorAll('.flash-deal-countdown');
    if (!flashContainers.length) return;
    flashContainers.forEach(function (flashContainer) {

        const targetDate = new Date(flashContainer.getAttribute('data-countdown')).getTime();
        function updateTimer() {

            const now = new Date().getTime();
            const diff = targetDate - now;

            if (diff < 0) {
                return;
            }
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            flashContainer.querySelector('.cz-countdown-days .cz-countdown-value').innerText = days.toString().padStart(2, '0');
            flashContainer.querySelector('.cz-countdown-hours .cz-countdown-value').innerText = hours.toString().padStart(2, '0');
            flashContainer.querySelector('.cz-countdown-minutes .cz-countdown-value').innerText = minutes.toString().padStart(2, '0');
            flashContainer.querySelector('.cz-countdown-seconds .cz-countdown-value').innerText = seconds.toString().padStart(2, '0');
        }
        updateTimer();
        setInterval(updateTimer, 1000);

    });
})();

(function() {
    if (document.getElementById('intl-tel-input-assets') || typeof window.intlTelInput !== 'function') {
        return;
    }

    const telInput = document.querySelector('input[type="tel"]');
    if (telInput) {
        window.intlTelInput(telInput, {
            initialCountry: "bd",
            preferredCountries: ["bd", "us", "gb"],
            autoPlaceholder: "polite",
        });
    }
})();

$(document).on('click', 'button[type="reset"]', function () {

    const form = $(this).closest('form');

    form.find('.multiple-select2').each(function () {
        $(this).val($(this).data('old') || []).trigger('change');
    });

});

$('.multiple-select2').each(function () {
    $(this).data('old', $(this).val());
});

$("#reset").on("click", function () {
    let placeholderImg = $("#placeholderImg").data("img");
    $("#viewer").attr("src", placeholderImg);
    $(".spartan_remove_row").click();
});

$("img.svg").each(function () {
    let $img = jQuery(this);
    let imgID = $img.attr("id");
    let imgClass = $img.attr("class");
    let imgURL = $img.attr("src");

    jQuery.get(
        imgURL,
        function (data) {
            let $svg = jQuery(data).find("svg");
            if (typeof imgID !== "undefined") {
                $svg = $svg.attr("id", imgID);
            }
            if (typeof imgClass !== "undefined") {
                $svg = $svg.attr("class", imgClass + " replaced-svg");
            }

            $svg = $svg.removeAttr("xmlns:a");
            if (
                !$svg.attr("viewBox") &&
                $svg.attr("height") &&
                $svg.attr("width")
            ) {
                $svg.attr(
                    "viewBox",
                    "0 0 " + $svg.attr("height") + " " + $svg.attr("width")
                );
            }

            $img.replaceWith($svg);
        },
        "xml"
    );
});

$(document).ready(function () {
    function parseEndDate(endValue) {
        if (endValue == null) return null;

        if (typeof endValue === 'number' || /^[0-9]+$/.test(String(endValue).trim())) {
            let timestamp = Number(endValue);
            if (timestamp > 0 && timestamp < 1e12) {
                timestamp *= 1000;
            }
            return new Date(timestamp);
        }

        let date = new Date(endValue);
        if (!isNaN(date)) return date;

        date = new Date(String(endValue).replace(' ', 'T'));
        if (!isNaN(date)) return date;

        const parts = String(endValue).split(/[- :]/);
        if (parts.length < 3) return null;

        return new Date(
            parseInt(parts[0], 10),
            parseInt(parts[1], 10) - 1,
            parseInt(parts[2], 10),
            parseInt(parts[3] || 0, 10),
            parseInt(parts[4] || 0, 10),
            parseInt(parts[5] || 0, 10)
        );
    }

    function startCountdown() {
        $('.countdown').each(function () {
            let $this = $(this);
            let endDate = parseEndDate($this.data('end'));
            if (!endDate || isNaN(endDate.getTime())) return;

            const endTime = endDate.getTime();
            let timer = null;

            function updateTimer() {
                let now = Date.now();
                let distance = endTime - now;

                if (distance <= 0) {
                    if (timer) clearInterval(timer);
                    $this.html("<span class='text-danger fw-bold'>Ended</span>");
                    return;
                }

                let hours = Math.floor(distance / (1000 * 60 * 60));
                let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                let seconds = Math.floor((distance % (1000 * 60)) / 1000);

                $this.find('.hours').text(hours.toString().padStart(2, '0'));
                $this.find('.minutes').text(minutes.toString().padStart(2, '0'));
                $this.find('.seconds').text(seconds.toString().padStart(2, '0'));
            }

            updateTimer();
            timer = setInterval(updateTimer, 1000);
        });
    }
    startCountdown();
});

$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip({ trigger: 'hover' })
})

$(document).on('focus', '.js-select2-date', function () {
    if (!$(this).data('daterangepicker')) {
        $(this).daterangepicker({
            parentEl: 'body',
            singleDatePicker: true,
            timePicker: false,
            opens: 'left',
            drops: 'down',
            locale: {
                format: 'DD MMM YYYY, hh:mm A'
            }
        });
    }
});

$(document).ready(function () {
    $(".image-uploader__zip").on("change", function (event) {
        const file = event.target.files[0];
        const wrapper = $(this).closest(".image-uploader");
        const target = wrapper.find(".image-uploader__title");
        const removeBtn = wrapper.find(".zip-remove-btn");
        const icon = wrapper.find(".upload-preview-icon");

        const allowedTypes = ["application/pdf", "audio/mpeg", "video/mp4", "video/webm"];

        if (file) {

            if (!allowedTypes.includes(file.type)) {
                toastMagic.error("Only PDF, MP3, MP4 and WEBM files are allowed");
                $(this).val(null);

                target.text("Upload File").addClass("text-info");
                icon.attr("src", wrapper.data("default-icon"));
                removeBtn.hide();
                return;
            }

            target.text(file.name).removeClass("text-info");

            let newIcon = wrapper.data("default-icon");

            if (file.type === "application/pdf") {
                newIcon = wrapper.data("pdf-icon");
            }
            if (file.type === "audio/mpeg") {
                newIcon = wrapper.data("mp3-icon");
            }
            if (file.type === "video/mp4") {
                newIcon = wrapper.data("mp4-icon");
            }
            if (file.type === "video/webm") {
                newIcon = wrapper.data("mp4-icon");
            }

            icon.attr("src", newIcon);

            removeBtn.show();
        } else {
            target.text("Upload File").addClass("text-info");
            icon.attr("src", wrapper.data("default-icon"));
            removeBtn.hide();
        }
    });

    $(".image-uploader .zip-remove-btn").on("click", function () {
        const wrapper = $(this).closest(".image-uploader");

        wrapper.find(".image-uploader__zip").val(null);
        wrapper.find(".image-uploader__title")
            .text("Upload File")
            .addClass("text-info");

        wrapper.find(".upload-preview-icon")
            .attr("src", wrapper.data("default-icon"));

        $(this).hide();
    });

    function toggleVideoType() {
        let selected = $('input[name="upload_video_type"]:checked').val();

        if (selected === 'option1') {
            $('.youtube-link-here').show();
            $('.upload-video-file-here').hide();
        } else if (selected === 'option2') {
            $('.youtube-link-here').hide();
            $('.upload-video-file-here').show();
        }
    }

    toggleVideoType();

    $(document).on('change', 'input[name="upload_video_type"]', function () {
        toggleVideoType();
    });

    $(document).on('reset', 'form', function () {
        let form = $(this);

        setTimeout(function () {
            toggleVideoType();
        }, 0);
    });

});

