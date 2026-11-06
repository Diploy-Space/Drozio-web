<div class="border-top p-3">
    <form class="chatting-messages-ajax-form form-advance-validation form-advance-inputs-validation form-advance-file-validation position-relative"
          enctype="multipart/form-data" data-ajax="true">
        @csrf
        <input type="hidden" id="current-user-hidden-id" value="{{ $counterparty['id'] }}"
               name="{{ $userType == 'customer' ? 'user_id' : 'delivery_man_id' }}">

        <div class="chat-composer">
            <div class="chat-composer-attachments">
                <div class="image-array"></div>
                <div class="file-array"></div>
            </div>

            <textarea class="form-control border-0 resize-none" id="msgInputValue" name="message" rows="2"
                      placeholder="{{ translate('Write a message') }}..."></textarea>

            <div class="chat-composer-toolbar">
                <div class="d-flex align-items-center gap-2">
                    <label class="btn btn-outline-secondary icon-btn mb-0" data-bs-toggle="tooltip"
                           data-bs-title="{{ translate('Attach photo or video') }}">
                        <i class="fi fi-rr-picture"></i>
                        <input type="file" id="select-media" hidden multiple
                               data-max-size="{{ getFileUploadMaxSize() }}"
                               accept=".jpg, .jpeg, .jpe, .jif, .jfif, .jfi, .png, .gif, .webp, .tiff, .tif, .bmp, .svg, .mp4, .mkv, .avi, .mov, .wmv, .flv, .webm, .mpeg, .mpg, .m4v, .3gp, .ogv">
                    </label>

                    <label class="btn btn-outline-secondary icon-btn mb-0" data-bs-toggle="tooltip"
                           data-bs-title="{{ translate('Attach file') }}">
                        <i class="fi fi-rr-clip"></i>
                        <input type="file" id="select-file" hidden multiple class="select-file"
                               data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                               accept="{{ getFileUploadFormats(type: 'file', skip: '.txt') }}">
                    </label>

                    <button type="button" class="btn btn-outline-secondary icon-btn" id="trigger"
                            data-bs-toggle="tooltip" data-bs-title="{{ translate('Emoji') }}">
                        <i class="fi fi-rr-smile"></i>
                    </button>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <span class="fs-12 text-body-light d-none d-xl-inline">{{ translate('Press Enter to send') }}</span>
                    <button class="btn btn-primary send-btn" type="submit" id="msgSendBtn" disabled>
                        <i class="fi fi-sr-paper-plane"></i>
                        {{ translate('Send') }}
                    </button>
                </div>
            </div>
        </div>

        <div id="selected-files-container" class="d-none"></div>
        <div id="selected-media-container" class="d-none"></div>

        <div class="circle-progress collapse">
            <div class="inner">
                <div class="text"></div>
                <svg id="svg" width="24" height="24" version="1.1" xmlns="http://www.w3.org/2000/svg">
                    <circle id="bar" r="10" cx="12" cy="12" fill="transparent" stroke-dasharray="100"
                            stroke-dashoffset="100"></circle>
                </svg>
            </div>
        </div>
    </form>
</div>
