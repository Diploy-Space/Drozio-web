<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ Session::get('direction') }}">

<head>
    <meta charset="utf-8">
    <meta name="_token" content="{{ csrf_token() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="robots" content="nofollow, noindex">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, viewport-fit=cover">

    <title>@yield('title')</title>
    <link rel="shortcut icon" href="{{ getStorageImages(path: getWebConfig(name: 'company_fav_icon'), type: 'backend-logo') }}">

    @include("layouts.vendor.partials._style-partials")

    {!! ToastMagic::styles() !!}

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/vendor-v2.css') }}">
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/v2-menu-motion.css') }}">

    @stack('css_or_js')
</head>

<body class="footer-offset {{ config('app.mode')=='demo'?'demo':'' }} v2-active" id="{{ config('app.mode')=='demo'?'demo':'' }}">
<div id="page-progress-bar" aria-hidden="true"></div>

@if(config('app.mode')=='demo')
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-TTLMKMHQ"
                      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
<div class="row">
    <div class="col-12 position-fixed z-9999 mt-10rem">
        <div id="loading" class="d--none">
            <div id="loader"></div>
        </div>
    </div>
</div>
@include('layouts.vendor.partials.v2._body')

    <span class="d-none" id="text-validate-translate"
        data-required="{{ translate('This field is required') }}"
        data-file-size-larger="{{ translate('File size is larger') }}"
        data-max-limit-crossed="{{ translate('Max limit crossed') }}"
        data-something-went-wrong="{{ translate('Something went wrong') }}"
        data-passwords-do-not-match="{{ translate('Passwords do not match') }}"
        data-valid-email="{{ translate('Please enter a valid email') }}"
        data-password-validation="{{ translate('Password must be 8+ chars with upper, lower, number & symbol') }}"
        data-file-type-not-allowed="{{ translate('Invalid file type selected') }}"
    ></span>

    @include("layouts.vendor.partials._modals")
    @include("layouts.vendor.partials._toggle-modal")
    @include("layouts.vendor.partials._sign-out-modal")
    @include("layouts.vendor.partials._alert-message")

    @include("layouts.vendor.partials._translator-for-js")
    @include("layouts.vendor.partials._translated-message-container")
    @include("layouts.vendor.partials._script-partials")

    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/vendor-v2.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/v2-menu-motion.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/v2-pjax.js') }}"></script>
    <script src="{{ dynamicAsset(path: 'public/assets/back-end/js/page-transition.js') }}"></script>

    @stack("script")
    @stack("script_2")

    @if(config('app.mode')=='demo')
        <script src="{{ dynamicAsset(path: 'public/js/demo.js')}}"></script>
    @endif

    <script>
        $('.notification-data-view').on('click',function (){
            let id= $(this).data('id');
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            $.post({
                url: $(this).data('url') || "{{route('vendor.retail.notification.index')}}",
                data: {
                    _token: '{{csrf_token()}}',
                    id: id,
                },
                beforeSend: function () {
                },
                success: function (data) {
                    $('.notification_data_new_badge'+id).fadeOut();
                    $('#NotificationModalContent').empty().html(data.view);
                    $('#NotificationModal').modal('show');
                    let notificationDataCount = $('.notification_data_new_count');
                    let notificationCount = parseInt(data.notification_count);
                    notificationCount === 0 ? notificationDataCount.fadeOut() : notificationDataCount.html(notificationCount);
                },
                complete: function () {
                },
            });
        })
        if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write(
            '<script src="{{ dynamicAsset(path: 'public/assets/back-end') }}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
    </script>

     <script>
       if ($('html').attr('dir') === 'rtl') {
            $('[data-toggle="tooltip"]').each(function () {

                let placement = $(this).attr('data-placement');

                if (placement === 'right') {
                    $(this).attr('data-placement', 'left');
                } else if (placement === 'left') {
                    $(this).attr('data-placement', 'right');
                }
            });
        }

        $('[data-toggle="tooltip"]').tooltip();
    </script>

    <script>
        function checkNavOverflow() {
            try {
                $(".nav--tab").each(function() {
                    let $nav = $(this);
                    let $btnNext = $nav
                        .closest(".position-relative")
                        .find(".nav--tab__next");
                    let $btnPrev = $nav
                        .closest(".position-relative")
                        .find(".nav--tab__prev");
                    let isRTL = $("html").attr("dir") === "rtl";
                    let navScrollWidth = $nav[0].scrollWidth;
                    let navClientWidth = $nav[0].clientWidth;
                    let scrollLeft = Math.abs($nav.scrollLeft());

                    if (isRTL) {
                        let maxScrollLeft = navScrollWidth - navClientWidth;
                        let scrollRight = maxScrollLeft - scrollLeft;

                        $btnNext.toggle(scrollRight > 1);
                        $btnPrev.toggle(scrollLeft > 1);
                    } else {
                        $btnNext.toggle(
                            navScrollWidth > navClientWidth &&
                            scrollLeft + navClientWidth < navScrollWidth
                        );
                        $btnPrev.toggle(scrollLeft > 1);
                    }
                });
            } catch (error) {
                console.error(error);
            }
        }

        $(".nav--tab").each(function() {
            let $nav = $(this);
            let $activeItem = $nav.find(".nav-link.active");

            if ($activeItem.length) {
                let isRTL = $("html").attr("dir") === "rtl";
                let nav = $nav[0];
                let activeItem = $activeItem[0];

                let navRect = nav.getBoundingClientRect();
                let itemRect = activeItem.getBoundingClientRect();

                let offset = itemRect.left - navRect.left - 60;

                nav.scrollLeft += offset;

                checkNavOverflow($nav);
            }

            $(window).on("resize", function() {
                checkNavOverflow($nav);
            });

            $nav.on("scroll", function() {
                checkNavOverflow($nav);
            });

            $nav.siblings(".nav--tab__next").on("click", function() {
                let scrollWidth = $nav.find("li").outerWidth(true);
                let isRTL = $("html").attr("dir") === "rtl";

                if (isRTL) {
                    $nav.animate(
                        { scrollLeft: $nav.scrollLeft() - scrollWidth },
                        300,
                        function() {
                            checkNavOverflow($nav);
                        }
                    );
                } else {
                    $nav.animate(
                        { scrollLeft: $nav.scrollLeft() + scrollWidth },
                        300,
                        function() {
                            checkNavOverflow($nav);
                        }
                    );
                }
            });

            $nav.siblings(".nav--tab__prev").on("click", function() {
                let scrollWidth = $nav.find("li").outerWidth(true);
                let isRTL = $("html").attr("dir") === "rtl";

                if (isRTL) {
                    $nav.animate(
                        { scrollLeft: $nav.scrollLeft() + scrollWidth },
                        300,
                        function() {
                            checkNavOverflow($nav);
                        }
                    );
                } else {
                    $nav.animate(
                        { scrollLeft: $nav.scrollLeft() - scrollWidth },
                        300,
                        function() {
                            checkNavOverflow($nav);
                        }
                    );
                }
            });
        });
        setTimeout(function() {
            checkNavOverflow();
        }, 100);
    </script>
</body>

</html>
