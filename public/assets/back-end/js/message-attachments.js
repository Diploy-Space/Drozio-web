"use strict";

$(document).ready(() => {
    $(document).on("click", ".modal_video-play-btn", function() {
        const videoElement = $(this).siblings("video")[0];

        if (videoElement) {
            videoElement.controls = true;
            videoElement.play();
        }

        $(this).remove();
    });

    $(document).on("play", "video", function() {
        $(this)
            .siblings(".modal_video-play-btn")
            .remove();
    });

    $(document).on("pause", "video", function() {
        if ($(this).siblings(".modal_video-play-btn").length) return;

        const videoElement = this;
        const playButton = $("<button>", {
            type: "button",
            class: "btn video-play-btn modal_video-play-btn p-1",
            html: `<img src="${$("#get-video-preview-icon").data("icon")}" alt="">`
        });

        playButton.insertAfter(videoElement);
    });

    $(document).on("click", ".zip-download", function(event) {
        event.preventDefault();

        const zipWrapper = $(this)
            .closest(".zip-wrapper")
            .find(".zip-images");
        if (!zipWrapper.length) return;

        const images = zipWrapper.find("img");
        const videos = zipWrapper.find("video");
        if (images.length === 0 && videos.length === 0) return;

        const zip = new JSZip();
        const zipFolder = zip.folder("files");
        const mediaPromises = [];

        function addToZip(url, filename) {
            if (!url) return;

            mediaPromises.push(
                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error(url);
                        return response.blob();
                    })
                    .then(blob => zipFolder.file(filename, blob))
                    .catch(() => null)
            );
        }

        images.each((index, image) => addToZip($(image).attr("src"), `image_${index + 1}.png`));
        videos.each((index, video) =>
            addToZip(
                $(video)
                    .find("source")
                    .first()
                    .attr("src"),
                `video_${index + 1}.mp4`
            )
        );

        Promise.all(mediaPromises)
            .then(() => zip.generateAsync({ type: "blob" }))
            .then(content => saveAs(content, "files.zip"));
    });
});
