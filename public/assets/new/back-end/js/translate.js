"use strict";

function escapeHtml(text) {
    return $("<div>").text(text == null ? "" : text).html();
}

function applyTranslationStats(stats) {
    if (!stats) return;
    $('[data-stat="total"]').text(stats.total);
    $('[data-stat="translated"]').text(stats.translated);
    $('[data-stat="untranslated"]').text(stats.untranslated);
    let percent = stats.total > 0 ? Math.round((stats.translated / stats.total) * 100) : 0;
    $('[data-stat="completion"]').text(percent + "%");
    $('[data-stat="completion-bar"]').css("width", percent + "%");
}

function autoGrow(el) {
    el.style.height = "auto";
    el.style.height = el.scrollHeight + "px";
}

function activeFilterStatus() {
    return $(".table-responsive[data-active-status]").data("active-status") || "all";
}

// Table-title count reflects the count for the currently active filter, straight from server stats.
function syncTableCount(stats) {
    if (!stats) return;
    let status = activeFilterStatus();
    let value = status === "pending" ? stats.untranslated
        : status === "translated" ? stats.translated
        : stats.total;
    $("#content-table-count").text(value);
}

// Renumber the visible rows' SL column after a row is removed so the sequence has no gaps.
function resequenceSerials() {
    let first = parseInt($(".table-responsive[data-active-status]").data("first-item"), 10) || 1;
    $(".translation-content-table tbody tr").each(function (i) {
        $(this).children("td").first().text(first + i);
    });
}

// Shared post-save/auto-translate UI update: refresh card counts + table count together, flip the badge,
// and drop the row from the Untranslated view when it no longer belongs there.
function handlePromotion(row, response) {
    applyTranslationStats(response.stats);
    syncTableCount(response.stats);
    row.removeClass("row-edited");
    if (!response.promoted) return;

    let labels = $("#status-labels");
    row.attr("data-status", "translated").find(".status-cell").html(
        `<span class="badge badge-soft-success text-success"><i class="fi fi-sr-check-circle"></i> ${labels.data("translated")}</span>`
    );

    if (activeFilterStatus() === "pending") {
        row.fadeOut(250, function () {
            $(this).remove();
            if ($(".translation-content-table tbody tr").length === 0) {
                location.reload();
            } else {
                resequenceSerials();
            }
        });
    }
}

$(document).on("click", ".autoTranslate", function () {
    let row = $(this).closest("tr");
    let autoTranslate = $("#get-auto-translate-route-and-text");

    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    });

    $.ajax({
        url: autoTranslate.data("route"),
        method: "POST",
        data: { key: $(this).data("key") },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (response) {
            if (typeof response.translated_data === "undefined") {
                toastMagic.error(response.message || autoTranslate.data("error-text"));
                return;
            }
            toastMagic.success(autoTranslate.data("success-text"));
            let textarea = row.find("textarea");
            textarea.val(response.translated_data);
            if (textarea[0]) autoGrow(textarea[0]);
            handlePromotion(row, response);
        },
        error: function (xhr) {
            toastMagic.error(
                (xhr.responseJSON && xhr.responseJSON.message) ||
                    autoTranslate.data("error-text")
            );
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
});

$(document).on("click", ".update-lang", function () {
    let row = $(this).closest("tr");
    const translate = $("#get-translate-route-and-text");

    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    });

    $.ajax({
        url: translate.data("route"),
        method: "POST",
        data: { key: $(this).data("key"), value: row.find("textarea").val() },
        beforeSend: function () {
            $("#loading").fadeIn();
        },
        success: function (response) {
            toastMagic.success(translate.data("success-text"));
            handlePromotion(row, response);
        },
        complete: function () {
            $("#loading").fadeOut();
        },
    });
});

// Row editing niceties: auto-grow value boxes, highlight edited rows, save on Enter.
$(function () {
    $(".translation-value").each(function () {
        autoGrow(this);
    });
});

$(document).on("input", ".translation-value", function () {
    autoGrow(this);
    $(this).closest("tr").addClass("row-edited");
});

$(document).on("keydown", ".translation-value", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        $(this).closest("tr").find(".update-lang").trigger("click");
    }
});

let totalMessagesOfCurrentLanguageElement = $(
    "#total-messages-of-current-language"
);
var translateTotal =
    parseInt(totalMessagesOfCurrentLanguageElement.data("total")) || 0;
var lastDueMessage = Infinity;
var translateStartTime = 0;

$("#translating-modal-start").on("click", function () {
    lastDueMessage = Infinity;
    translateStartTime = Date.now();
    $(".translating-modal-success-rate").html("0%");
    $(".translating-modal-success-bar").attr("style", "width:0%");
    $("#translating-eta").text(
        totalMessagesOfCurrentLanguageElement.data("eta-calculating")
    );
    autoTranslationFunction();
});

function formatEtaText(remainingSec) {
    let el = totalMessagesOfCurrentLanguageElement;
    if (!isFinite(remainingSec) || remainingSec <= 1) {
        return el.data("eta-almost");
    }
    let prefix = el.data("eta-prefix");
    let mins = Math.floor(remainingSec / 60);
    let secs = Math.round(remainingSec % 60);
    if (mins >= 60) {
        let hrs = Math.floor(mins / 60);
        let m = mins % 60;
        return prefix + " " + hrs + " " + el.data("eta-hour") +
            (m ? " " + m + " " + el.data("eta-min") : "");
    }
    if (mins >= 1) {
        return prefix + " " + mins + " " + el.data("eta-min") +
            (secs ? " " + secs + " " + el.data("eta-sec") : "");
    }
    return prefix + " " + secs + " " + el.data("eta-sec");
}

// Live progress + a time estimate recalculated from the actual measured translation rate.
function updateTranslateProgress(due) {
    let done = translateTotal > 0 ? Math.max(0, translateTotal - due) : translateTotal;
    let percent = translateTotal > 0 ? Math.min(100, Math.round((done / translateTotal) * 100)) : 100;
    $(".translating-modal-success-bar").attr("style", "width:" + percent + "%");
    $(".translating-modal-success-rate").html(percent + "% (" + done + "/" + translateTotal + ")");

    if (due <= 0) {
        $("#translating-eta").text(totalMessagesOfCurrentLanguageElement.data("eta-almost"));
    } else if (done > 0 && translateStartTime) {
        let elapsedSec = (Date.now() - translateStartTime) / 1000;
        let rate = done / elapsedSec;
        if (rate > 0) {
            $("#translating-eta").text(formatEtaText(due / rate));
        }
    }
}

function finishTranslateAll(message) {
    $(".translating-modal-success-bar").attr("style", "width:100%");
    $(".translating-modal-success-rate").html("100%");
    toastMagic.success(message);
    setTimeout(() => {
        $("#translating-modal").modal("hide");
        setTimeout(() => {
            $("#complete-modal").modal("show");
            setTimeout(() => location.reload(), 1500);
        }, 400);
    }, 1200);
}

function abortTranslateAll(message) {
    $("#translating-modal").modal("hide");
    toastMagic.error(
        message || $("#get-auto-translate-all-route-and-text").data("error-text")
    );
}

function autoTranslationFunction() {
    let autoTranslateAll = $("#get-auto-translate-all-route-and-text");

    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    });
    $.ajax({
        url: autoTranslateAll.data("route"),
        method: "GET",
        beforeSend: function () {
            $("#translating-modal").modal("show");
        },
        success: function (response) {
            // The provider is a third-party service that rate-limits; a failed run must report the
            // failure instead of closing on a success toast that nothing on disk backs up.
            if (!parseInt(response.status)) {
                abortTranslateAll(response.message);
                return;
            }

            let due = parseInt(response.due_message) || 0;
            updateTranslateProgress(due);
            // Continue only while progress is being made; stop on completion (due=0) or a stall
            // (due stopped decreasing — remaining keys can't be auto-translated) to avoid an endless loop.
            if (due > 0 && due < lastDueMessage) {
                lastDueMessage = due;
                autoTranslationFunction();
            } else {
                $(".translateCountSuccess").html(response.translate_success_message);
                finishTranslateAll(response.message);
            }
        },
        error: function (xhr) {
            abortTranslateAll(xhr.responseJSON && xhr.responseJSON.message);
        },
    });
}

let unusedKeys = [];

$("#scan-unused-keys-btn").on("click", function () {
    let el = $("#get-unused-keys-route-and-text");
    unusedKeys = [];
    $("#unused-keys-loading").removeClass("d-none");
    $("#unused-keys-result, #unused-keys-empty").addClass("d-none");
    $("#unused-keys-footer").addClass("d-none");

    $.ajax({
        url: el.data("route"),
        method: "GET",
        success: function (response) {
            unusedKeys = response.keys || [];
            $("#unused-keys-loading").addClass("d-none");
            $(".unused-keys-count").text(response.count);
            $(".unused-keys-total").text(response.total);
            $(".unused-keys-junk").text(response.junk_count || 0);

            if (response.count > 0) {
                let html = unusedKeys
                    .map(function (key) {
                        return `<div class="unused-key-item">${escapeHtml(key)}</div>`;
                    })
                    .join("");
                $("#unused-keys-list").html(html);
                $("#unused-keys-result").removeClass("d-none");
                $("#unused-keys-footer").removeClass("d-none");
            } else {
                $("#unused-keys-empty").removeClass("d-none");
            }
        },
        error: function () {
            $("#unused-keys-loading").addClass("d-none");
            $("#unused-keys-empty").removeClass("d-none");
        },
    });
});

$("#remove-unused-keys-confirm").on("click", function () {
    let el = $("#get-unused-keys-route-and-text");
    if (unusedKeys.length === 0) return;

    $("#unused-keys-modal").modal("hide");
    $("#removing-unused-modal").modal("show");

    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    });

    $.ajax({
        url: el.data("remove-route"),
        method: "POST",
        data: { keys: unusedKeys },
        success: function (response) {
            toastMagic.success(response.message || el.data("success-text"));
            setTimeout(() => location.reload(), 800);
        },
        error: function () {
            $("#removing-unused-modal").modal("hide");
        },
    });
});

$("#sync-languages-confirm").on("click", function () {
    let el = $("#get-sync-languages-route-and-text");

    $("#sync-languages-modal").modal("hide");
    $("#syncing-languages-modal").modal("show");

    $.ajaxSetup({
        headers: { "X-CSRF-TOKEN": $('meta[name="_token"]').attr("content") },
    });

    $.ajax({
        url: el.data("route"),
        method: "POST",
        success: function (response) {
            toastMagic.success(response.message);
            setTimeout(() => location.reload(), 800);
        },
        error: function () {
            $("#syncing-languages-modal").modal("hide");
        },
    });
});
