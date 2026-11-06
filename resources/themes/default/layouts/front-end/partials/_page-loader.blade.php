@php($loaderGif = getWebConfig(name: 'loader_gif'))
<div class="row">
    <div class="col-12 loading-parent d--none" id="loading">
        <div class="loading-parent-first-div">
            <div class="app-loader" role="status" aria-live="polite" aria-label="{{ translate('Loading') }}">
                @if(!empty($loaderGif['path']))
                    <img class="app-loader__gif" width="200" alt="" src="{{ $loaderGif['path'] }}">
                @else
                    <span class="app-loader__spinner"></span>
                @endif
            </div>
        </div>
    </div>
</div>
