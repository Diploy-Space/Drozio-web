@php($announcementText = trim((string) data_get($config, 'announcement')))

@if(data_get($config, 'status') == 1 && $announcementText !== '')
    <div class="sh-announce" id="{{ $barId }}" role="status"
         style="--sa-bg: {{ data_get($config, 'color') }}; --sa-ink: {{ data_get($config, 'text_color') }};">
        <div class="container sh-announce__inner">
            <p class="sh-announce__text">{{ $announcementText }}</p>
            <button class="sh-announce__close" type="button"
                    data-sh-announce-close="{{ $barId }}" data-sh-announce-key="{{ $storageKey }}"
                    aria-label="{{ translate('Close') }}">
                <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M3.5 3.5 12.5 12.5M12.5 3.5 3.5 12.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
            </button>
        </div>
    </div>
    <script>
        (function () {
            try {
                var hideUntil = parseInt(window.localStorage.getItem('{{ $storageKey }}'), 10);
                if (hideUntil && Date.now() < hideUntil) {
                    document.getElementById('{{ $barId }}').hidden = true;
                } else {
                    window.localStorage.removeItem('{{ $storageKey }}');
                }
            } catch (error) {
                return;
            }
        })();
    </script>
@endif
