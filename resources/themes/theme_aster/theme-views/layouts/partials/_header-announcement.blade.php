@php($announcementConfig = $announcementConfig ?? ($web_config['announcement'] ?? null))
@php($announcementBarId = $announcementBarId ?? 'storefrontAnnouncement')
@php($announcementStorageKey = $announcementStorageKey ?? 'offerBarHideUntil')
@php($announcementText = trim((string) data_get($announcementConfig, 'announcement')))

@if(data_get($announcementConfig, 'status') == 1 && $announcementText !== '')
    <div class="offer-bar py-1 py-sm-2" id="{{ $announcementBarId }}"
         style="background-color: {{ data_get($announcementConfig, 'color') }}; color: {{ data_get($announcementConfig, 'text_color') }};">
        <div class="d-flex align-items-center">
            <div class="top-offer-text flex-grow-1 d-flex justify-content-center fw-semibold">
                {{ $announcementText }}
            </div>
            <button type="button" class="offer-bar-close" aria-label="{{ translate('Close') }}"
                    data-announcement-bar="{{ $announcementBarId }}"
                    data-announcement-key="{{ $announcementStorageKey }}">
                <i class="bi bi-x-lg" aria-hidden="true"></i>
            </button>
        </div>
    </div>
    <script>
        (function () {
            var bar = document.getElementById('{{ $announcementBarId }}');

            if (!bar) {
                return;
            }

            try {
                var hideUntil = parseInt(window.localStorage.getItem('{{ $announcementStorageKey }}'), 10);

                if (hideUntil && Date.now() < hideUntil) {
                    bar.style.display = 'none';
                } else if (hideUntil) {
                    window.localStorage.removeItem('{{ $announcementStorageKey }}');
                }
            } catch (error) {
                return;
            }

            bar.querySelector('[data-announcement-close], .offer-bar-close').addEventListener('click', function () {
                bar.style.display = 'none';

                try {
                    window.localStorage.setItem('{{ $announcementStorageKey }}', Date.now() + 600000);
                } catch (error) {
                    return;
                }
            });
        })();
    </script>
@endif
