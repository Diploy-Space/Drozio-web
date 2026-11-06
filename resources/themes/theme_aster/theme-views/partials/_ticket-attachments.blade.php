@php($alignment = $isIncoming ? 'justify-content-start align-items-start' : 'justify-content-end align-items-end')

@if(count($documents) > 0)
    <div class="d-flex flex-column gap-2 pt-1 w-100 {{ $alignment }}">
        @foreach($documents as $document)
            <a class="text--title ticket-attachment-document" href="{{ $document['path'] }}" target="_blank" title="{{ $document['name'] }}">
                <div class="uploaded-file-item">
                    <img class="file-icon" alt=""
                         src="{{ theme_asset('assets/img/'.$document['iconName'].'.png') }}">
                    <div class="upload-file-item-content">
                        <div class="pdf-file-name">{{ $document['name'] }}</div>
                        <small>{{ $document['size'] }}</small>
                    </div>
                    <span class="btn btn--download d-flex justify-content-center align-items-center ms-auto">
                        <i class="bi bi-download"></i>
                    </span>
                </div>
            </a>
        @endforeach
    </div>
@endif

@if(count($media) > 0)
    <div class="d-flex flex-wrap gap-2 pt-2 w-100 custom-image-popup-init {{ $alignment }}">
        @foreach($media as $file)
            @if($file['isVideo'])
                <video class="rounded inbox-image-element" controls preload="metadata">
                    <source src="{{ $file['path'] }}" type="video/mp4">
                    <source src="{{ $file['path'] }}" type="video/ogg">
                    {{ translate('Your browser does not support the video tag') }}
                </video>
            @else
                <a class="inbox-image-element custom-image-popup" href="{{ $file['preview'] }}">
                    <img src="{{ $file['preview'] }}" alt="">
                </a>
            @endif
        @endforeach
    </div>
@endif
