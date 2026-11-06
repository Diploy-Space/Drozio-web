@php($alignment = $isIncoming ? 'justify-content-start' : 'justify-content-end')

@if(count($documents) > 0)
    <div class="d-flex flex-column gap-1 pt-1 {{ $alignment }}">
        @foreach($documents as $document)
            <div class="uploaded-file-item">
                <a class="d-flex gap-2 align-items-center text--title" href="{{ $document['path'] }}" target="_blank">
                    <img class="file-icon" src="{{ $document['icon'] }}" alt="">
                    <span class="upload-file-item-content">
                        <span class="pdf-file-name d-block">{{ $document['name'] }}</span>
                        <small>{{ $document['size'] }}</small>
                    </span>
                </a>
                <a class="btn btn--download icon-btn bg-white d-flex justify-content-center align-items-center"
                   href="{{ $document['path'] }}" download>
                    <i class="fi fi-rr-download"></i>
                </a>
            </div>
        @endforeach
    </div>
@endif

@if(count($media) > 0)
    <div class="d-flex flex-wrap pt-1 {{ $alignment }}">
        <div class="zip-wrapper d-flex gap-3 align-items-center position-relative {{ $alignment }}">
            <div class="zip-images d-flex flex-wrap gap-2 pt-1 align-items-center max-w-150px {{ $alignment }}">
                @foreach($media as $index => $file)
                    <div class="position-relative img_row{{ $index }} {{ $index > 3 ? 'd-none' : '' }}">
                        <a href="javascript:" data-bs-toggle="modal" data-index="{{ $index }}"
                           data-bs-target="#imgViewModal{{ $messageId }}"
                           class="position-relative {{ $file['isVideo'] ? '' : 'aspect-1 overflow-hidden d-block border rounded' }}">
                            @if($file['isVideo'])
                                <video class="rounded video-element" width="100" height="60" preload="metadata">
                                    <source src="{{ $file['path'] }}" type="video/mp4">
                                    <source src="{{ $file['path'] }}" type="video/ogg">
                                    {{ translate('Your browser does not support the video tag') }}
                                </video>
                                <button type="button"
                                        class="btn video-play-btn text-primary rounded-circle bg-white p-1 d-flex justify-content-center align-items-center">
                                    <i class="fi fi-sr-play"></i>
                                </button>
                            @else
                                <img class="img-fit aspect-1 w-65px" src="{{ $file['preview'] }}" alt="">
                            @endif
                            @if($index == 3 && count($media) > 4)
                                <div class="extra-images">
                                    <span class="extra-image-count">+{{ count($media) - 3 }}</span>
                                </div>
                            @endif
                        </a>
                    </div>
                @endforeach
            </div>

            <a class="btn btn--download icon-btn bg-white d-flex justify-content-center align-items-center flex-shrink-0 {{ count($media) === 1 ? '' : 'zip-download' }}"
               href="{{ count($media) === 1 ? $media[0]['path'] : 'javascript:' }}" download>
                <i class="fi fi-rr-download"></i>
            </a>

            <div class="modal fade imgViewModal" id="imgViewModal{{ $messageId }}" tabindex="-1"
                 aria-labelledby="imgViewModal{{ $messageId }}Label" role="dialog" aria-modal="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content bg-transparent border-0">
                        <div class="modal-body pt-0">
                            <div class="imgView-slider owl-theme owl-carousel" dir="ltr">
                                @foreach($media as $file)
                                    <div class="imgView-item">
                                        <div class="d-flex justify-content-between align-items-end">
                                            <a class="d-flex align-items-center gap-2 mb-2" download
                                               href="{{ $file['isVideo'] ? $file['path'] : $file['preview'] }}">
                                                <span class="btn btn--download icon-btn bg-white d-flex justify-content-center align-items-center">
                                                    <i class="fi fi-rr-download"></i>
                                                </span>
                                                <span class="text-white text-decoration-underline">
                                                    {{ $file['isVideo'] ? translate('Download video') : translate('Download image') }}
                                                </span>
                                            </a>
                                            <button type="button" class="btn btn-close p-1 border-0 fs-10"
                                                    data-bs-dismiss="modal" aria-label="{{ translate('Close') }}"></button>
                                        </div>
                                        <div class="image-wrapper">
                                            <div class="position-relative">
                                                @if($file['isVideo'])
                                                    <video class="rounded video-element" width="450" height="260"
                                                           preload="metadata">
                                                        <source src="{{ $file['path'] }}" type="video/mp4">
                                                        <source src="{{ $file['path'] }}" type="video/ogg">
                                                        {{ translate('Your browser does not support the video tag') }}
                                                    </video>
                                                    <button type="button"
                                                            class="btn video-play-btn modal_video-play-btn p-1">
                                                        <img alt=""
                                                             src="{{ dynamicAsset(path: 'public/assets/back-end/img/icons/carbon_play-filled.svg') }}">
                                                    </button>
                                                @else
                                                    <img class="image" src="{{ $file['preview'] }}" alt="">
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(count($media) > 1)
                                <div class="imgView-slider_buttons d-flex justify-content-center" dir="ltr">
                                    <button type="button" class="btn owl-btn imgView-owl-prev">
                                        <i class="fi fi-sr-angle-small-left"></i>
                                    </button>
                                    <button type="button" class="btn owl-btn imgView-owl-next">
                                        <i class="fi fi-sr-angle-small-right"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
