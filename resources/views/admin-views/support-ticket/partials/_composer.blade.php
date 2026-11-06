<div class="border-top p-3">
    <form class="needs-validation form-advance-validation form-advance-file-validation non-ajax-form-validate"
          action="{{ route('admin.people.support-ticket.replay', $ticket['id']) }}" method="post"
          enctype="multipart/form-data" novalidate="novalidate">
        @csrf

        <div class="chat-composer">
            <div class="chat-composer-attachments">
                <div class="image-array"></div>
                <div class="file-array"></div>
            </div>

            <textarea class="form-control border-0 resize-none" id="msgInputValue" name="replay" rows="2"
                      maxlength="{{ $replyMaxLength }}" data-maxlength="{{ $replyMaxLength }}"
                      placeholder="{{ translate('Write a reply') }}..."></textarea>

            <div class="chat-composer-toolbar">
                <div class="d-flex align-items-center gap-2">
                    <label class="btn btn-outline-secondary icon-btn mb-0" data-bs-toggle="tooltip"
                           data-bs-title="{{ translate('Attach photo or video') }}">
                        <i class="fi fi-rr-picture"></i>
                        <input type="file" id="select-media" hidden multiple
                               data-max-size="{{ getFileUploadMaxSize() }}"
                               accept="{{ getFileUploadFormats(skip: '.svg') }}">
                    </label>

                    <label class="btn btn-outline-secondary icon-btn mb-0" data-bs-toggle="tooltip"
                           data-bs-title="{{ translate('Attach file') }}">
                        <i class="fi fi-rr-clip"></i>
                        <input type="file" id="select-file" hidden multiple class="select-file"
                               data-max-size="{{ getFileUploadMaxSize(type: 'file') }}"
                               accept="{{ getFileUploadFormats(type: 'file') }}">
                    </label>

                    <span class="fs-12 text-body-light" id="charCount">0/{{ $replyMaxLength }}</span>
                </div>

                <button class="btn btn-primary send-btn" type="submit" id="msgSendBtn" disabled>
                    <i class="fi fi-sr-paper-plane"></i>
                    {{ translate('Send reply') }}
                </button>
            </div>
        </div>

        <div id="selected-files-container" class="d-none"></div>
        <div id="selected-media-container" class="d-none"></div>
    </form>
</div>
