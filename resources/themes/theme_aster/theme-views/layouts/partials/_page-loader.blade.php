@php($loaderGif = getWebConfig(name: 'loader_gif'))
<div class="preloader d--none" id="loading" role="status" aria-live="polite" aria-label="{{ translate('Loading') }}">
    @if(!empty($loaderGif['path']))
        <img class="app-loader__gif" width="200" alt="" src="{{ $loaderGif['path'] }}">
    @else
        <span class="app-loader__spinner"></span>
    @endif
</div>
