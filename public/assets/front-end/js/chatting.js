"use strict";

$(document).ready(function () {
    ajaxFormRenderChattingMessages();

    $("#myInput").on("keyup", function () {
        var value = $(this).val().toLowerCase().trim();

        $(".list_filter").each(function () {
            var name = $(this).find("h5").text().toLowerCase().trim();

            $(this).toggle(name.indexOf(value) > -1);
        });
    });

    $("#chat-search-form").on("submit", function (e) {
        e.preventDefault();
    });


    $(".get-ajax-message-view").on("click", function () {
        $(".get-ajax-message-view").removeClass("active");

        $(this).addClass("active");
        let userId = $(this).data("user-id");
        let actionURL = $("#chatting-post-url").data("url") + userId;
        $("#count-unread-messages-" + userId).remove();
        if ($("#count-unread-messages-" + userId).length > 0) {
            $(".get-ajax-message-view .chat_ib h5").addClass("active-text");
        } else {
            $(this).find(".chat_ib h5").removeClass("active-text");
        }
        $.ajaxSetup({
            headers: {
                "X-XSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
        });
        $.ajax({
            url: actionURL,
            type: "GET",
            beforeSend: function () {
                $("#loading").fadeIn();
            },
            success: function (response) {
                if (response.userData) {
                    $("#chatting-messages-section").html(response.chattingMessages);
                    $(".profile-image").attr("src", response.userData.image);
                    $(".profile-name").text(response.userData.name);
                    $(".profile-verified-badge").toggleClass("d-none", !response.userData.is_verified);
                    $("#profile_phone").text(response.userData.phone);
                    if (
                        parseInt(response.userData["temporary-close-status"]) === 1
                    ) {
                        $(".temporarily-closed-sticky-alert")
                            .removeClass("d-none")
                            .css({
                                display: "",
                            });
                    } else {
                        $(".temporarily-closed-sticky-alert")
                            .addClass("d-none")
                            .css({
                                display: "none",
                            });
                    }
                    $("#current-user-hidden-id").val(userId);
                    $(".get-ajax-message-view.active")[0].scrollIntoView({
                        behavior: "auto",
                        block: "nearest",
                        inline: "center",
                    });
                    imageSlider();
                    toggleVideo();
                    downloadZip();
                    namePdf();
                    toggleDropdown();
                    manipulateTooltip();
                }
            },
            complete: function () {
                $("#loading").fadeOut();
                $('[data-toggle="tooltip"]').tooltip();
            },
        });
    });

    function ajaxFormRenderChattingMessages() {
        $(".chatting-messages-form").on("submit", function (event) {
            event.preventDefault();
            let userId = $(".get-ajax-message-view.active").data("user-id");
            let actionURL = $("#chatting-post-url").data("url") + userId;
            let totalFilesCount = selectedFiles?.length + selectedImages?.length;
            let formData = new FormData(this);
            $.ajaxSetup({
                headers: {
                    "X-XSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
            });
            $.ajax({
                type: "POST",
                url: actionURL,
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener(
                        "progress",
                        function(evt) {
                            if (evt.lengthComputable) {
                                if (totalFilesCount > 0) {
                                    var percentComplete =
                                        (evt.loaded / evt.total) * 100;
                                    $(".circle-progress").show();
                                    $(".circle-progress")
                                        .find(".text")
                                        .text(`Uploading ${totalFilesCount} files`);
                                    $(".circle-progress")
                                        .find("#bar")
                                        .attr(
                                            "stroke-dashoffset",
                                            100 - percentComplete
                                        );
                                }
                            }
                        },
                        false
                    );
                    return xhr;
                },
                beforeSend: function () {
                    $("#msgSendBtn").attr("disabled", true).addClass('opacity-25');
                },
                success: function (response) {
                    if (response?.status?.toString() === 'error' && response?.message?.toString() !== '') {
                        toastMagic.error(response?.message);
                        return;
                    }

                    $("#chatting-messages-section").html(response.chattingMessages);
                    $("#msgInputValue").val("");
                    $(".image-array").empty();
                    $(".file-array").empty();
                    let container = document.getElementById(
                        "selected-files-container"
                    );
                    let containerImage = document.getElementById(
                        "selected-media-container"
                    );
                    container.innerHTML = "";
                    containerImage.innerHTML = "";
                    selectedFiles = [];
                    selectedImages = [];
                    imageSlider();
                    toggleVideo();
                    downloadZip();
                    namePdf();
                    toggleDropdown();
                    manipulateTooltip();

                    if (response.errors) {
                        for (
                            let index = 0;
                            index < response.errors.length;
                            index++
                        ) {
                            toastMagic.error(response.errors[index].message);
                        }
                    } else if (response.error) {
                        toastMagic.error(response.error);
                    }

                    setTimeout(() => {
                        $(".circle-progress").find(".text").text(`Uploaded ${totalFilesCount} files`);
                        $(".circle-progress").hide();
                    }, 1000)
                },
                complete: function () {
                    $(".circle-progress").hide();
                    $("#msgSendBtn").removeAttr("disabled").removeClass('opacity-25');
                    $('[data-toggle="tooltip"]').tooltip();

                    setTimeout(() => {
                        $(".circle-progress").find(".text").text(`Uploaded ${totalFilesCount} files`);
                        $(".circle-progress").hide();
                    }, 1000)
                },
                error: function (error) {
                    if (error.status === 413) {
                        toastMagic.warning($('#message-media-error').data("text"));
                    } else {
                        try {
                            let errorData = JSON.parse(error.responseText);
                            toastMagic.warning(errorData.message);
                        } catch (e) {
                            toastMagic.error($('#message-media-error').data("text"));
                        }
                    }

                    setTimeout(() => {
                        $(".circle-progress").find(".text").text(`Uploaded ${totalFilesCount} files`);
                        $(".circle-progress").hide();
                    }, 1000)
                },
            });
        });
    }

    function imageSlider() {
        $(document).ready(function () {
            $('[data-target^="#imgViewModal"]').on("click", function () {
                var modalId = $(this).data("target");
                var $carousel = $(modalId).find(".imgView-slider");

                var slideCount = $(modalId).find('.imgView-item').length;

                if ($carousel.hasClass("owl-loaded")) {
                    $carousel.trigger("destroy.owl.carousel").removeClass("owl-loaded");
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

                        if (currentIndex === 0) {
                            $(modalId).find(".imgView-owl-prev").prop('disabled', true).addClass('disabled');
                        } else {
                            $(modalId).find(".imgView-owl-prev").prop('disabled', false).removeClass('disabled');
                        }

                        if (currentIndex === slideCount - 1) {
                            $(modalId).find(".imgView-owl-next").prop('disabled', true).addClass('disabled');
                        } else {
                            $(modalId).find(".imgView-owl-next").prop('disabled', false).removeClass('disabled');
                        }
                    }
                });

                if (slideCount <= 1) {
                    $(modalId).find(".imgView-owl-prev, .imgView-owl-next").hide();
                } else {
                    $(modalId).find(".imgView-owl-prev, .imgView-owl-next").show();

                    $(modalId).find(".imgView-owl-prev").on("click", function () {
                        imgView.trigger("prev.owl.carousel");
                    });

                    $(modalId).find(".imgView-owl-next").on("click", function () {
                        imgView.trigger("next.owl.carousel");
                    });
                }

                var index = $(this).data("index");
                if (slideCount > 1) {
                    imgView.trigger("to.owl.carousel", [index, 0]);
                }

                $(modalId).find(".imgView-item").each(function () {
                    var imgSrc = $(this).find("img").attr("src");
                    if (imgSrc) {
                        var imgTitle = imgSrc.split("/").pop();
                        $(this).find(".img-title").text(imgTitle);
                    }
                });
            });
        });
    }

    imageSlider();

    function toggleVideo() {
        $(".modal_video-play-btn").on("click", function () {
            const videoElement = $(this).siblings("video")[0];

            if (videoElement) {
                videoElement.controls = true;
                videoElement.play();
            }

            $(this).remove();
        });

        $("video").on("play", function () {
            $(this).siblings(".modal_video-play-btn").remove();
        });

        $("video").on("pause", function () {
            const videoElement = this;
            if (!$(videoElement).siblings(".modal_video-play-btn").length) {
                const iconSrc = $("#get-video-preview-icon").data("icon");
                const playButton = $("<button>", {
                    type: "button",
                    class: "btn video-play-btn modal_video-play-btn p-1",
                    html: `<img src="${iconSrc}" alt="Play">`,
                });

                playButton.insertAfter(videoElement);

                playButton.on("click", function () {
                    videoElement.controls = true;
                    videoElement.play();
                    $(this).remove();
                });
            }
        });
    }

    toggleVideo();

    function downloadZip() {
        $('.zip-download').on("click", function (event) {
            event.preventDefault();

            const zipWrapper = $(this).closest(".zip-wrapper").find(".zip-images");
            if (!zipWrapper.length) {
                console.error("No .zip-images container found.");
                return;
            }

            const zip = new JSZip();
            const zipFolder = zip.folder("files");
            const images = zipWrapper.find("img");
            const videos = zipWrapper.find("video");

            if (images.length === 0 && videos.length === 0) {
                console.error("No images or videos found to zip.");
                return;
            }

            const mediaPromises = [];

            images.each((index, img) => {
                const imgUrl = $(img).attr("src");
                const filename = `image_${index + 1}.png`;

                mediaPromises.push(
                    fetch(imgUrl)
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error(`Failed to fetch image: ${imgUrl}`);
                            }
                            return response.blob();
                        })
                        .then((blob) => zipFolder.file(filename, blob))
                        .catch((error) =>
                            console.error(
                                `Error fetching image (${filename}):`,
                                error
                            )
                        )
                );
            });

            videos.each((index, video) => {
                const videoUrl = $(video).find("source").first().attr("src");
                const filename = `video_${index + 1}.mp4`;

                if (videoUrl) {
                    mediaPromises.push(
                        fetch(videoUrl)
                            .then((response) => {
                                if (!response.ok) {
                                    throw new Error(
                                        `Failed to fetch video: ${videoUrl}`
                                    );
                                }
                                return response.blob();
                            })
                            .then((blob) => zipFolder.file(filename, blob))
                            .catch((error) =>
                                console.error(
                                    `Error fetching video (${filename}):`,
                                    error
                                )
                            )
                    );
                }
            });

            Promise.all(mediaPromises)
                .then(() => zip.generateAsync({type: "blob"}))
                .then((content) => saveAs(content, "files.zip"))
                .catch((error) => console.error("Error generating ZIP:", error));
        });
    }

    downloadZip();

    function namePdf() {
        $(".pdf-file-name").each(function () {
            const text = $(this).text().trim();
            const lastDotIndex = text.lastIndexOf(".");

            if (lastDotIndex !== -1) {
                const namePart = text.slice(0, lastDotIndex);
                const extension = text.slice(lastDotIndex);
                const maxLength = 20;

                if (namePart.length > maxLength) {
                    const truncatedName =
                        namePart.slice(0, maxLength - extension.length - 3) +
                        "..." +
                        extension;
                    $(this).text(truncatedName);
                } else {
                    $(this).text(text);
                }
            }
        });
    }

    namePdf();

    function toggleDropdown() {
        $("#viewDetailsDropdown").on("click", function (e) {
            e.stopPropagation();
            $(this).next(".dropdown-menu").toggle();
        });
    }

    toggleDropdown();

    function manipulateTooltip() {
        $(document).on("show.bs.modal", ".imgViewModal", function () {
            $('[data-toggle="tooltip"]').tooltip("dispose");
        });
        $(document).on("hidden.bs.modal", ".imgViewModal", function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    }

    manipulateTooltip();
});
