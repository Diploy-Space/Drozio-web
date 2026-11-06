@push('script')
    <script>
        "use strict";

        (function () {
            const toolSelector = '#cart_items .dropdown, #wishlist_items .dropdown';

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest(toolSelector + ' > a, ' + toolSelector + ' > span');

                if (trigger === null) {
                    document.querySelectorAll(toolSelector + ' > .dropdown-menu.is-open')
                        .forEach(function (menu) {
                            menu.classList.remove('is-open');
                        });

                    return;
                }

                if (window.matchMedia('(hover: hover)').matches) {
                    return;
                }

                const menu = trigger.parentElement.querySelector(':scope > .dropdown-menu');

                if (menu === null) {
                    return;
                }

                event.preventDefault();
                menu.classList.toggle('is-open');
                menu.removeAttribute('style');
            });
        })();
    </script>
@endpush
