"use strict";

(function () {
    function markSubItems() {
        $(".header .nav-wrapper, .aside .main-nav, .common-nav")
            .find(".sub-menu, .sub_menu")
            .parents("ul li")
            .addClass("has-sub-item");
    }

    function bindStickyHeader() {
        const navClearedBar = function () {
            const nav = document.querySelector(".header-main");
            const bar = document.querySelector(".header-bar");

            if (!nav || !bar) {
                return false;
            }

            return nav.getBoundingClientRect().bottom <= bar.getBoundingClientRect().bottom;
        };

        $(window).on("scroll", function () {
            $(".header").toggleClass("header--scrolled", navClearedBar());
        });
    }

    function bindDrawer() {
        $("[data-account-panel-toggle]").on("click", function () {
            $(".header-account-panel").toggleClass("active");
            $(".aside").removeClass("active");
        });

        $("[data-account-panel-close]").on("click", function () {
            $(".header-account-panel").removeClass("active");
        });

        $(document).on("click", ".header-account-panel [data-bs-toggle='modal']", function () {
            $(".header-account-panel").removeClass("active");
        });

        $(document).on("keydown", function (event) {
            if (event.key === "Escape") {
                $(".header-account-panel").removeClass("active");
                $(".aside").removeClass("active");
            }
        });

        $(".menu-btn").on("click", function () {
            $(".aside").toggleClass("active");
            $(".filter-toggle-aside").removeClass("active");
            $(".header-account-panel").removeClass("active");
        });

        $("[data-drawer-close]").on("click", function () {
            $(".aside").removeClass("active");
        });

        $(".aside-overlay").on("click", function () {
            $(".aside").removeClass("active");
            $(".profile-menu-aside").removeClass("active");
            $(".header-account-panel").removeClass("active");
        });

        $(window).on("resize", function () {
            if ($(window).width() > 1199) {
                $(".aside").removeClass("active");
            }

            if ($(window).width() >= 992) {
                $(".header-account-panel").removeClass("active");
            }
        });
    }

    function isHoverViewport() {
        return window.matchMedia('(min-width: 992px)').matches;
    }

    function releaseFocusWithin(container) {
        if (container.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    }

    function initShoppingModeSwitcher() {
        document.querySelectorAll('.shopping-mode-switcher').forEach(bindShoppingModeSwitcher);

        document.addEventListener('click', function (event) {
            if (event.target.closest('[data-shopping-mode-dismiss]')) {
                event.target.closest('.shopping-mode-switcher').classList.remove('show');

                return;
            }

            if (event.target.closest('.shopping-mode-switcher')) {
                return;
            }

            document.querySelectorAll('.shopping-mode-switcher.show').forEach(function (switcher) {
                switcher.classList.remove('show');
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('.shopping-mode-switcher.show').forEach(function (switcher) {
                switcher.classList.remove('show');
            });
        });
    }

    function bindShoppingModeSwitcher(switcher) {
        const toggle = switcher.querySelector('.shopping-mode-toggle');
        const menu = switcher.querySelector('.shopping-mode-menu');

        if (!toggle || !menu) {
            return;
        }

        const alignMenuWithinViewport = function () {
            menu.classList.remove('shopping-mode-menu--start');
            menu.style.visibility = 'hidden';
            menu.style.display = 'block';

            const switcherRect = switcher.getBoundingClientRect();
            const menuWidth = menu.getBoundingClientRect().width;
            const isRtl = getComputedStyle(document.documentElement).direction === 'rtl';
            const overflowsViewport = isRtl
                ? switcherRect.left + menuWidth > window.innerWidth - 20
                : switcherRect.right - menuWidth < 20;

            if (overflowsViewport) {
                menu.classList.add('shopping-mode-menu--start');
            }

            menu.style.visibility = '';
            menu.style.display = '';
        };

        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            alignMenuWithinViewport();
            switcher.classList.toggle('show');
        });

        switcher.addEventListener('mouseenter', function () {
            if (isHoverViewport()) {
                alignMenuWithinViewport();
            }
        });

        switcher.addEventListener('mouseleave', function () {
            if (isHoverViewport()) {
                switcher.classList.remove('show');
                releaseFocusWithin(switcher);
            }
        });
    }

    function bindAccountMenuHover() {
        $(document).on('mouseleave', '.header-account > .dropdown', function () {
            if (!isHoverViewport() || typeof bootstrap === 'undefined') {
                return;
            }

            const toggle = this.querySelector('[data-bs-toggle="dropdown"]');

            if (toggle) {
                bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            }

            releaseFocusWithin(this);
        });
    }

    function bindThemeSwitcher() {
        const applyChoice = function (barSelector, button, attributeTarget, attributeName, value, storageKey) {
            const root = document.documentElement;

            root.classList.add("appearance-switching");
            $(barSelector).find("button").removeClass("active");
            $(button).addClass("active");
            $(attributeTarget).attr(attributeName, value);
            localStorage.setItem(storageKey, value);
            void root.offsetHeight;

            window.setTimeout(function () {
                root.classList.remove("appearance-switching");
            }, 0);
        };

        $(document).on("click", ".light_button, .dark_button", function () {
            applyChoice(".theme-bar", this, "body", "theme", $(this).hasClass("dark_button") ? "dark" : "light", "theme");
        });

        $(document).on("click", ".ltr_button, .rtl_button", function () {
            applyChoice(".dir-bar", this, "html", "dir", $(this).hasClass("rtl_button") ? "rtl" : "ltr", "dir");
        });

        const isDark = localStorage.getItem("theme") === "dark";
        $(".dark_button").toggleClass("active", isDark);
        $(".light_button").toggleClass("active", !isDark);
        $(".settings-sidebar .theme-bar").addClass("d-flex");
    }

    function bindCategoryFlyouts() {
        const VIEWPORT_MARGIN = 16;
        const MIN_VISIBLE_HEIGHT = 120;

        const placeFlyout = function (item) {
            const submenu = item.querySelector(":scope > ul");

            if (!submenu) {
                return;
            }

            const list = item.parentElement.getBoundingClientRect();
            const rect = item.getBoundingClientRect();
            const isRtl = getComputedStyle(document.documentElement).direction === "rtl";

            submenu.style.maxHeight = "";

            const headerNav = item.closest(".header-main");
            const topLimit = Math.max(VIEWPORT_MARGIN, headerNav ? headerNav.getBoundingClientRect().bottom : 0);
            const naturalHeight = submenu.offsetHeight;
            const width = submenu.offsetWidth;
            const spaceBelow = window.innerHeight - rect.top - VIEWPORT_MARGIN;
            const spaceAbove = rect.bottom - topLimit;
            const opensUpward = naturalHeight > spaceBelow && spaceAbove > spaceBelow;
            const available = Math.max(opensUpward ? spaceAbove : spaceBelow, MIN_VISIBLE_HEIGHT);
            const top = opensUpward ? rect.bottom - Math.min(naturalHeight, available) : rect.top;
            const inlineStart = isRtl ? list.left - width : list.right;

            submenu.style.top = Math.max(topLimit, top) + "px";
            submenu.style.left = Math.max(VIEWPORT_MARGIN, Math.min(inlineStart, window.innerWidth - width - VIEWPORT_MARGIN)) + "px";
            submenu.style.maxHeight = available + "px";
        };

        const FLYOUT_ITEM = ".header .dropdown--menu li.menu-item-has-children";

        $(document).on("mouseenter focusin", FLYOUT_ITEM, function () {
            placeFlyout(this);
        });

        $(document).on("scroll", ".header .dropdown--menu", function () {
            this.querySelectorAll("li.menu-item-has-children").forEach(function (item) {
                if (item.matches(":hover")) {
                    placeFlyout(item);
                }
            });
        });
    }

    function bindSearchCategorySelect() {
        $(document).on("click", ".search_dropdown .dropdown-menu a[data-value]", function () {
            const $option = $(this);
            const $dropdown = $option.closest(".search_dropdown");

            $dropdown.find(".header-search-dropdown-button").text($option.text().trim());
            $dropdown.find('input[name="category_ids[]"]').val($option.attr("data-value"));

            requestSearchSuggestions($dropdown.closest("form").find(".search-bar-input, .search-bar-input-mobile"));
        });
    }

    function setSearchLoading($form, isLoading) {
        const pending = Math.max(($form.data("search-pending") || 0) + (isLoading ? 1 : -1), 0);

        $form.data("search-pending", pending);
        $form.toggleClass("is-searching", pending > 0);
    }

    function requestSearchSuggestions($input) {
        const $form = $input.closest("form");
        const url = $form.attr("data-search-suggestion-url");
        const $panel = $form.find(".search-result-box").add($form.siblings(".search-result-box"));
        const keyword = $input.val().trim();
        const categoryId = $form.find('input[name="category_ids[]"]').val();
        const requestKey = keyword + "|" + (categoryId || "");

        if (!url || !$panel.length || requestKey === $input.data("search-request")) {
            return;
        }

        $input.data("search-request", requestKey);

        if (keyword === "") {
            $panel.empty().addClass("d--none");

            return;
        }

        $.get({
            url: url,
            dataType: "json",
            data: categoryId ? {name: keyword, category_id: categoryId} : {name: keyword},
            beforeSend: function () {
                setSearchLoading($form, true);
            },
            success: function (data) {
                $panel.removeClass("d--none").html(data.result);
            },
            complete: function () {
                setSearchLoading($form, false);
            },
        });
    }

    function bindSearchSuggestions() {
        const SEARCH_DEBOUNCE_MS = 250;

        $(document).on("input search", ".search-bar-input, .search-bar-input-mobile", function () {
            const $input = $(this);

            clearTimeout($input.data("search-timer"));
            $input.data("search-timer", setTimeout(function () {
                requestSearchSuggestions($input);
            }, SEARCH_DEBOUNCE_MS));
        });

        $(document).on("click", function (event) {
            if ($(event.target).closest(".search-box, .search-bar, .search-result-box").length) {
                return;
            }

            $(".search-result-box").empty().addClass("d--none");
        });
    }

    function bindModuleCartDismiss() {
        $(document).on('click', '[data-module-cart-close]', function () {
            $(this).closest('.module-cart').addClass('is-dismissed');
        });

        $(document).on('mouseleave', '.module-cart', function () {
            $(this).removeClass('is-dismissed');
        });
    }

    function bindMobileSearch() {
        const MOBILE_SEARCH_OPEN = 'mobile-search-open';

        const setToggleIcon = function ($header, isOpen) {
            $header.find('[data-mobile-search-toggle] .bi')
                .toggleClass('bi-search', !isOpen)
                .toggleClass('bi-x-lg', isOpen);
        };

        const closeMobileSearch = function ($header) {
            $header.removeClass(MOBILE_SEARCH_OPEN);
            $header.find('.header-mobile-search .search-result-box').addClass('d--none');
            setToggleIcon($header, false);
        };

        $(document).on('click', '[data-mobile-search-toggle]', function () {
            const $header = $(this).closest('.header-main');

            if ($header.hasClass(MOBILE_SEARCH_OPEN)) {
                closeMobileSearch($header);

                return;
            }

            $header.addClass(MOBILE_SEARCH_OPEN);
            setToggleIcon($header, true);
            $header.find('.header-mobile-search .search-bar-input-mobile').trigger('focus');
        });

        $(document).on('click', function (event) {
            const $header = $('.header-main.' + MOBILE_SEARCH_OPEN);

            if (!$header.length || $(event.target).closest('[data-mobile-search-toggle], .header-mobile-search').length) {
                return;
            }

            closeMobileSearch($header);
        });

        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMobileSearch($('.header-main.' + MOBILE_SEARCH_OPEN));
            }
        });
    }

    function bindFloatingActions() {
        const REVEAL_AFTER = 400;

        $(window).on('scroll', function () {
            const isScrolled = $(window).scrollTop() > REVEAL_AFTER;

            $('.social-chat-icons').toggleClass('active', isScrolled);
            $('.back-to-top').toggleClass('show', isScrolled);
        });

        $(document).on('click', '.back-to-top', function (event) {
            event.preventDefault();
            $('html, body').stop().animate({ scrollTop: 0 }, 0);
        });
    }

    function bindAppBarCartSheet() {
        const TRIGGER = "#mobile_app_bar .dropup > a";
        const SHEET = "#mobile_app_bar .cart-dropdown-mobile-view";

        window.closeCart = function () {
            $("#cart_overlay").hide();
            $(SHEET).removeClass("show").stop(true, true).slideUp(300);
            $(TRIGGER).removeClass("cart-open").addClass("collapsed");
        };

        window.openCart = function (trigger) {
            const $trigger = $(trigger || TRIGGER).first();
            const $sheet = $trigger.siblings(".cart-dropdown-mobile-view");

            $(SHEET).not($sheet).removeClass("show").stop(true, true).slideUp(300);
            $(TRIGGER).not($trigger).removeClass("cart-open").addClass("collapsed");

            $("#cart_overlay").show();
            $sheet.addClass("show").stop(true, true).slideDown(300);
            $trigger.addClass("cart-open").removeClass("collapsed");
        };

        $(document).on("click", TRIGGER, function (event) {
            event.preventDefault();

            if ($(this).hasClass("cart-open")) {
                window.closeCart();

                return;
            }

            window.openCart(this);
        });

        $(document).on("click", ".close-cart-btn, #cart_overlay", function () {
            window.closeCart();
        });
    }

    function bindCollapsibleNav() {
        $(document).on("click", ".aside .aside-body .has-sub-item > a, .aside .aside-body .has-sub-item > label, .common-nav .has-sub-item > a, .common-nav .has-sub-item > label", function (event) {
            event.preventDefault();

            const item = $(this).parent(".has-sub-item");
            const submenu = $(this).siblings("ul");

            item.siblings("li").removeClass("sub-menu-opened");
            item.toggleClass("sub-menu-opened");

            if (submenu.hasClass("open")) {
                submenu.removeClass("open").slideUp(200);

                return;
            }

            item.siblings("li").find("ul").removeClass("open").slideUp(200);
            submenu.addClass("open").slideDown(200);
        });
    }

    function bindCaretAccordion() {
        $(document).on("click", ".menu--caret", function () {
            const accordion = $(this).closest(".menu--caret-accordion");

            if (accordion.hasClass("open")) {
                accordion.removeClass("open");
                accordion.find(".menu--caret-accordion").removeClass("open");
                accordion.find(".card-body").slideUp(300, "swing");

                return;
            }

            accordion.addClass("open");
            accordion.children(".card-body").slideDown(300, "swing");
            accordion.siblings(".menu--caret-accordion").removeClass("open");
            accordion.siblings(".menu--caret-accordion").find(".menu--caret-accordion").removeClass("open");
            accordion.siblings(".menu--caret-accordion").children(".card-body").slideUp(300, "swing");
            accordion.siblings(".menu--caret-accordion").find(".card-body").slideUp(300, "swing");
        });
    }

    function bindHeaderSearchReveal() {
        const anchor = document.querySelector("[data-header-search-anchor]");
        const search = document.querySelector(".header-bar .search-box");

        if (!anchor || !search || typeof IntersectionObserver !== "function") {
            return;
        }

        const bar = document.querySelector(".header-bar");

        new IntersectionObserver(function (entries) {
            search.classList.toggle("search-box--revealed", !entries[0].isIntersecting);
        }, {
            rootMargin: "-" + Math.round(bar.getBoundingClientRect().height) + "px 0px 0px 0px",
        }).observe(anchor);
    }

    markSubItems();

    $(function () {
        bindStickyHeader();
        bindDrawer();
        bindCollapsibleNav();
        bindCaretAccordion();
        bindAppBarCartSheet();
        initShoppingModeSwitcher();
        bindAccountMenuHover();
        bindThemeSwitcher();
        bindSearchCategorySelect();
        bindCategoryFlyouts();
        bindSearchSuggestions();
        bindModuleCartDismiss();
        bindMobileSearch();
        bindFloatingActions();
        bindHeaderSearchReveal();
    });
})();
