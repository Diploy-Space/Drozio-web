"use strict";

$(document).ready(() => {
    const progressText = $("#chatting-upload-progress");

    function reinitTooltips() {
        $('[data-toggle="tooltip"]')
            .tooltip("dispose")
            .tooltip({ container: "body", placement: "top" });
    }

    $("#chatting-messages-section").on("scroll", function() {
        $('[data-toggle="tooltip"]').tooltip("hide");
    });

    const rowLabels = $("#chatting-row-labels");

    function syncSelectedConversation(userId) {
        if (!window.history?.replaceState) return;

        const url = new URL(window.location.href);
        url.searchParams.set($("#chatting-post-url").data("param"), userId);
        window.history.replaceState({}, "", url);
    }

    function composerHasContent() {
        const hasText = ($("#msgInputValue").val() || "").trim().length > 0;
        const hasMedia = typeof selectedImages !== "undefined" && selectedImages.length > 0;
        const hasFiles = typeof selectedFiles !== "undefined" && selectedFiles.length > 0;

        return hasText || hasMedia || hasFiles;
    }

    function updateSendState() {
        $("#msgSendBtn").prop("disabled", !composerHasContent());
    }

    $("#msgInputValue").on("input", updateSendState);
    $(document).on("change", "#select-media, #select-file", () => setTimeout(updateSendState, 0));
    $(document).on("click", ".img-clear, .remove-file", () => setTimeout(updateSendState, 0));
    updateSendState();

    function refreshActiveRow(text) {
        const $row = $(".get-ajax-message-view.active");
        const preview = (text || "").trim() || rowLabels.data("attachment");

        $row.find(".chat-row-preview").html(
            $("<span>").addClass("text-dark").text(rowLabels.data("you") + ":")
        ).append(" " + preview);
        $row.find(".chat-row-time").text(rowLabels.data("just-now"));
    }

    function filterConversations() {
        const value = $("#myInput")
            .val()
            .toLowerCase()
            .trim();

        $(".list_filter").each(function() {
            const haystack = ($(this).data("search") || "").toString().toLowerCase();
            $(this).toggle(haystack.indexOf(value) > -1);
        });

        $(".empty-state-for-chatting-msg").toggleClass(
            "d-none",
            $(".list_filter:visible").length > 0
        );
    }

    let filterTimer = null;
    $("#myInput").on("input", function() {
        clearTimeout(filterTimer);
        filterTimer = setTimeout(filterConversations, 150);
    });

    $("#chat-search-form").on("submit", function(event) {
        event.preventDefault();
        filterConversations();
    });

    $(".get-ajax-message-view").on("click", function(event) {
        event.preventDefault();

        const userId = $(this).data("user-id");

        $(".get-ajax-message-view").removeClass("bg-soft-secondary active");
        $(this).addClass("bg-soft-secondary active");
        $("#count-unread-messages-" + userId).remove();

        $.ajaxSetup({
            headers: {
                "X-XSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        $.ajax({
            url: $("#chatting-post-url").data("url") + userId,
            type: "GET",
            beforeSend: function() {
                $("#loading").fadeIn();
            },
            success: function(response) {
                if (!response.userData) {
                    return;
                }

                $("#chatting-messages-section").html(response.chattingMessages);
                $("#profile_image").attr("src", response.userData.image);
                $("#profile_name")
                    .text(response.userData.name)
                    .attr("href", response.userData.detailsRoute);
                $("#profile_phone")
                    .text(response.userData.phone)
                    .attr("href", "tel:" + response.userData.phone.replace(/\s+/g, ""));
                $("#current-user-hidden-id").val(userId);
                syncSelectedConversation(userId);
                $(".get-ajax-message-view.active")[0].scrollIntoView({
                    behavior: "auto",
                    block: "nearest",
                    inline: "center"
                });
            },
            complete: function() {
                $("#loading").fadeOut();
                reinitTooltips();
            }
        });
    });

    $(".chatting-messages-ajax-form").on("submit", async function(event) {
        event.preventDefault();

        if (!composerHasContent()) {
            toastMagic.warning(rowLabels.data("empty"));
            return false;
        }

        const totalFilesCount = selectedFiles?.length + selectedImages?.length;
        if (!(await validateFormHelper($(this)))) return false;
        const formData = new FormData(this);
        const $form = $(this);

        function hideProgress() {
            $(".circle-progress")
                .find(".text")
                .text(progressText.data("uploaded"));
            $(".circle-progress").hide();
        }

        $.ajaxSetup({
            headers: {
                "X-XSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        $.ajax({
            type: "POST",
            url: $("#chatting-post-url").data("url"),
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener(
                    "progress",
                    function(event) {
                        if (!event.lengthComputable || totalFilesCount < 1) return;

                        const percentComplete = (event.loaded / event.total) * 100;
                        $(".circle-progress").show();
                        $(".circle-progress")
                            .find(".text")
                            .text(progressText.data("uploading"));
                        $(".circle-progress")
                            .find("#bar")
                            .attr("stroke-dashoffset", 100 - percentComplete);
                    },
                    false
                );
                return xhr;
            },
            beforeSend: function() {
                $("#msgSendBtn").attr("disabled", true);
                $("#loading").fadeIn();
            },
            success: function(response) {
                if (
                    response?.status?.toString() === "error" &&
                    response?.message?.toString() !== ""
                ) {
                    toastMagic.error(response?.message);
                    return;
                }

                $("#chatting-messages-section").html(response.chattingMessages);
                refreshActiveRow($("#msgInputValue").val());
                $("#msgInputValue").val("");
                $(".image-array").empty();
                $(".file-array").empty();
                document.getElementById("selected-files-container").innerHTML = "";
                document.getElementById("selected-media-container").innerHTML = "";
                selectedFiles = [];
                selectedImages = [];
                updateSendState();
                setTimeout(hideProgress, 1000);
            },
            error: function(error) {
                if (error.status === 413) {
                    toastMagic.warning($("#message-media-error").data("text"));
                } else {
                    try {
                        toastMagic.warning(JSON.parse(error.responseText).message);
                    } catch (exception) {
                        toastMagic.error($("#message-media-error").data("text"));
                    }
                }

                setTimeout(hideProgress, 1000);
            },
            complete: function() {
                $("#loading").fadeOut();
                $(".circle-progress").hide();
                updateSendState();
                reinitTooltips();
                formSubmitCleanup($form);
            }
        });
    });

    $("#msgInputValue").on("keydown", function(event) {
        if (event.key !== "Enter" || event.shiftKey) return;

        event.preventDefault();

        if (!composerHasContent()) return;

        $(".chatting-messages-ajax-form").submit();
    });

    $(document).on("click", '[data-target^="#imgViewModal"]', function() {
        const $modal = $($(this).attr("data-target"));
        const $carousel = $modal.find(".imgView-slider");
        const slideCount = $modal.find(".imgView-item").length;

        if ($carousel.hasClass("owl-loaded")) {
            $carousel.trigger("destroy.owl.carousel").removeClass("owl-loaded");
            $carousel.html($carousel.find(".owl-stage-outer").html());
        }

        const imgView = $carousel.owlCarousel({
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
            .on("click", function() {
                imgView.trigger("prev.owl.carousel");
            });
        $modal
            .find(".imgView-owl-next")
            .off("click")
            .on("click", function() {
                imgView.trigger("next.owl.carousel");
            });

        imgView.trigger("to.owl.carousel", [$(this).data("index"), 0]);
    });

    $(document).on("show.bs.modal", ".imgViewModal", function() {
        $('[data-toggle="tooltip"]').tooltip("dispose");
    });

    $(document).on("hidden.bs.modal", ".imgViewModal", function() {
        reinitTooltips();
    });
});
