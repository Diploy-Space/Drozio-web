@extends('layouts.front-end.profile')

@section('title',translate('Submit a review'))

@section('content')

<div class="container pb-5 mb-2 mb-md-4 mt-2 rtl text-align-direction">
    <div class="row g-3">
    @include('web-views.partials._profile-aside')
        <section class="col-lg-9 col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="__ml-20">{{translate('Submit a review')}}</h5>
                </div>
                <div class="card-body">
                    <form action="{{route('review.store')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">

                            <div class="form-group">
                                <label for="exampleInputEmail1">{{translate('Rating')}}</label>
                                <select class="form-control" name="rating">
                                    <option value="1">{{translate('1')}}</option>
                                    <option value="2">{{translate('2')}}</option>
                                    <option value="3">{{translate('3')}}</option>
                                    <option value="4">{{translate('4')}}</option>
                                    <option value="5">{{translate('5')}}</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputEmail1">{{translate('Comment')}}</label>
                                <input name="product_id" value="{{$order_details->product_id}}" hidden>
                                <input name="order_id" value="{{$order_details->order_id}}" hidden>
                                <textarea class="form-control" name="comment"></textarea>
                            </div>

                            <div class="form-group">
                                <label for="exampleInputEmail1">{{translate('Attachment')}}</label>
                                <div class="row coba"></div>
                                <div class="mt-1 text-info">{{translate('File type')}}: jpg, jpeg, png. {{ translate('Maximum size')}}: 2MB</div>
                            </div>

                        </div>
                        <div class="modal-footer">
                            <a href="{{ URL::previous() }}" class="btn btn-secondary">{{translate('Back')}}</a>

                            <button type="submit" class="btn btn--primary">{{translate('Submit')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </div>

</div>
@endsection

@push('script')
    <script src="{{theme_asset(path: 'public/assets/front-end/js/spartan-multi-image-picker.js')}}" defer></script>
    <script type="text/javascript">
        $(function () {
            $(".coba").spartanMultiImagePicker({
                fieldName: 'fileUpload[]',
                maxCount: 5,
                rowHeight: '150px',
                groupClassName: 'col-md-4',
                placeholderImage: {
                    image: '{{theme_asset(path: 'public/assets/front-end/img/image-place-holder.png')}}',
                    width: '100%'
                },
                dropFileLabel: "{{translate('Drop here')}}",
                onAddRow: function (index, file) {
                },
                onRenderedPreview: function (index) {
                },
                onRemoveRow: function (index) {
                },
                onExtensionErr: function (index, file) {
                    toastMagic.error('{{translate('Input png or jpg')}}');
                },
                onSizeErr: function (index, file) {
                    toastMagic.error('{{translate('File size is too large')}}');
                }
            });
        });
    </script>
@endpush
