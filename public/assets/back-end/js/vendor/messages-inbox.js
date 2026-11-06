"use strict";

$(document).ready(() => {
    function filterInboxPanel($panel) {
        const search = ($panel.find(".inbox-search-input").val() || "")
            .toString()
            .toLowerCase()
            .trim();

        let visibleCount = 0;

        $panel.find(".list_filter").each(function() {
            const haystack = ($(this).data("search") || "").toString().toLowerCase();
            const matched = haystack.indexOf(search) > -1;

            $(this).toggle(matched);
            visibleCount += matched ? 1 : 0;
        });

        $panel
            .find(".inbox-no-result")
            .toggleClass("d-none", visibleCount > 0 || $panel.find(".list_filter").length === 0);
    }

    let filterTimer = null;

    $(".inbox-search-input").on("input", function() {
        const $panel = $(this).closest("[data-inbox-panel]");

        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => filterInboxPanel($panel), 150);
    });

    $(".inbox-search-form").on("submit", function(event) {
        event.preventDefault();
        filterInboxPanel($(this).closest("[data-inbox-panel]"));
    });
});
