<div class="card h-100">
    <div class="card-body d-flex flex-column gap-20">
        @include('admin-views.vendor.partials._setting-section-heading', [
            'sectionTitle' => translate('GST number'),
            'sectionDescription' => translate('Show this vendor GST number on the invoice.'),
            'sectionToggle' => [
                'id' => 'gst-status',
                'name' => 'gst_status',
                'checked' => $seller['gst'] !== null,
            ],
        ])

        <div class="p-12 p-sm-20 bg-section rounded">
            <div class="form-group mb-0">
                <label class="form-label text-capitalize" for="gst-number">{{ translate('Number') }}</label>
                <input type="text" class="form-control" id="gst-number" name="gst" value="{{ $seller['gst'] }}"
                       placeholder="{{ translate('Ex').' : 6464945' }}">
            </div>
        </div>

        @include('admin-views.vendor.partials._setting-hint', [
            'hintText' => translate('If GST number is disabled here it will not be shown in the invoice'),
        ])
    </div>
</div>
