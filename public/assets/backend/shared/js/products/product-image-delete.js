"use strict";

$(document).on("click", ".action-delete-product-image", function () {
    const trigger = $(this);
    const form = trigger.closest("form");
    const imageName = trigger.data("name")?.toString();

    if (!form.length || !imageName) {
        return;
    }

    markImageForRemoval(form, imageName);

    const previewId = trigger.data("preview-id");

    if (previewId) {
        clearColorImageTile(trigger, previewId);
        return;
    }

    trigger.closest(".upload-file").addClass("d-none pending-image-removal");
});

$(document).on("hidden.bs.offcanvas", "#offcanvasEditImages", function () {
    restorePendingImageRemovals($(this).closest("form"));
});

$(document).on("click", '#offcanvasEditImages [data-dismiss="offcanvas"]', function () {
    restorePendingImageRemovals($(this).closest("form"));
});

function markImageForRemoval(form, imageName) {
    const isAlreadyMarked = form.find('input[name="removed_images[]"]').filter(function () {
        return this.value === imageName;
    }).length > 0;

    if (!isAlreadyMarked) {
        $("<input>", {type: "hidden", name: "removed_images[]", value: imageName}).appendTo(form);
    }
}

function clearColorImageTile(trigger, previewId) {
    const tile = trigger.closest(".upload-file");

    $("#" + previewId).attr("src", "").removeClass("d-block").addClass("d-none");
    tile.find(".upload-file-textbox").removeClass("d-none");
    tile.find(".product-image-edit-icon").addClass("d-none");
    trigger.remove();
}

function restorePendingImageRemovals(form) {
    form.find('input[name="removed_images[]"]').remove();
    form.find(".pending-image-removal").removeClass("d-none pending-image-removal");
}
