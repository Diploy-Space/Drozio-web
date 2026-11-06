@php($alignment = $isIncoming ? 'justify-content-start align-items-start' : 'justify-content-end align-items-end')

@if(count($documents) > 0)
    <div class="d-flex flex-column gap-2 mt-2 {{ $alignment }}">
        @foreach($documents as $document)
            <a class="text--title ticket-attachment-document" href="{{ $document['path'] }}" target="_blank" title="{{ $document['name'] }}">
                <div class="uploaded-file-item">
                    <div class="d-flex align-items-center justify-content-between gap-2 w-100">
                        <div class="d-flex gap-2 align-items-center ticket-attachment-document__body">
                            <img class="file-icon" alt=""
                                 src="{{ theme_asset(path: 'public/assets/front-end/img/'.$document['iconName'].'.png') }}">
                            <div class="upload-file-item-content">
                                <div class="pdf-file-name">{{ $document['name'] }}</div>
                                <small>{{ $document['size'] }}</small>
                            </div>
                        </div>
                        <span class="btn btn--download d-flex justify-content-center align-items-center">
                            <i class="tio-download-to"></i>
                        </span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
@endif

@if(count($media) > 0)
    <div class="row g-2 flex-wrap mt-3 {{ $alignment }}">
        @foreach($media as $key => $file)
            <div class="col-sm-6 col-md-4 position-relative img_row{{ $key }}">
                @if($file['isVideo'])
                    <video class="rounded w-100" controls preload="metadata">
                        <source src="{{ $file['path'] }}" type="video/mp4">
                        <source src="{{ $file['path'] }}" type="video/ogg">
                        {{ translate('Your browser does not support the video tag') }}
                    </video>
                @else
                    <a data-lightbox="mygallery{{ $messageId }}" href="{{ $file['path'] }}"
                       class="aspect-1 overflow-hidden d-block border rounded">
                        <img class="img-fit" alt="{{ translate('Ticket') }}" src="{{ $file['preview'] }}">
                    </a>
                @endif
            </div>
        @endforeach
    </div>
@endif
