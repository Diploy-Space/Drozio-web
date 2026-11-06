<div class="card card-body mt-3 rest-part">
    <div>
       <h2 class="mb-1">{{ translate('Product video') }}</h2>
       <p class="fs-12 mb-3">
           {{ translate('Only a YouTube embed link is supported.') }}
       </p>
    </div>
    <div class="bg-section rounded-10 p-12 p-sm-20">
        <div class="mb-3">
            <label class="form-label mb-0">
                {{ translate('Youtube video link') }}
            </label>
            <span class="text-info"> ({{ translate('Optional, please provide an embed link, not a direct link') }})</span>
        </div>
        <input type="text" name="video_url" value="{{ $product['video_url'] }}"
                placeholder="{{ translate('Ex').': https://www.youtube.com/embed/5R06LRdUCSE' }}"
                class="form-control" required>
        <p class="mt-1 mb-0">{{ translate('Please provide embed link not direct link.') }}</p>
    </div>
</div>
