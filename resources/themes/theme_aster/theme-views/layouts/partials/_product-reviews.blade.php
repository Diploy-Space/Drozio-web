@foreach ($productReviews as $item)
<div class="card border-primary-light flex-grow-1">
    <div class="media flex-wrap align-items-center gap-3 p-3">
        <div class="avatar border rounded-circle size-3-437rem">
            <img src="{{ getStorageImages(path: (isset($item->user)?$item->user->image_full_url : null), type:'avatar') }}" alt="{{translate('Image')}}"
            class="img-fit dark-support rounded-circle">
        </div>
        <div class="media-body d-flex flex-column gap-2">
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <div>
                    <h6 class="mb-1">{{isset($item->user)?$item->user->f_name:translate('User Not Exist')}}</h6>
                    <div class="d-flex gap-2 align-items-center">
                        @include('theme-views.partials._rating-display', [
                            'rating' => $item->rating,
                            'count' => 0,
                            'size' => 'sm',
                            'showEmpty' => true,
                        ])
                    </div>
                </div>
                <div>{{$item->created_at->format("d M Y h:i:s A")}}</div>
            </div>
            <p>{{$item->comment}}</p>
        </div>
    </div>
</div>
@endforeach
