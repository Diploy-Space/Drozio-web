<div class="table-responsive">
    <table id="datatable"
           class="table table-hover table-borderless table-nowrap align-middle card-table w-100">
        <thead class="thead-light thead-50 text-capitalize">
        <tr>
            <th>{{translate('Serial')}}</th>
            <th>{{translate('Customer info')}}</th>
            <th>{{translate('Message')}}</th>
            <th>{{translate('Time & Date')}}</th>
            <th class="text-center">{{translate('Reply status')}}</th>
            <th class="text-center">{{translate('Action')}}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($contacts as $key => $contact)
            <tr>
                <td>{{$contacts->firstItem()+$key}}</td>
                <td class="text-wrap">
                    <div class="max-w-200">
                        <div class="fw-semibold line--limit-1">{{$contact['name']}}</div>
                        <div class="text-muted fs-12 line--limit-1">{{$contact['email']}}</div>
                        <div class="text-muted fs-12">{{$contact['mobile_number']}}</div>
                    </div>
                </td>
                <td class="text-wrap">
                    <div class="max-w-300">
                        <div class="fw-semibold line--limit-1">
                            {{ \Illuminate\Support\Str::limit($contact['subject'] ?? '', 60, '...') }}
                        </div>
                        <div class="text-muted fs-12 line--limit-2">
                            {{ \Illuminate\Support\Str::limit($contact['message'] ?? '', 80, '...') }}
                        </div>
                    </div>
                </td>
                <td class="text-wrap">
                    <div class="max-w-130">
                        {{date('d M, Y',strtotime($contact['created_at']))}}
                        <div class="text-muted fs-12">{{date('h:i A',strtotime($contact['created_at']))}}</div>
                    </div>
                </td>
                <td class="text-center">
                    @if(empty($contact['reply']))
                        <span class="badge badge-soft-warning fs-12 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="fi fi-rr-clock d-flex"></i>
                            {{translate('Not sent')}}
                        </span>
                    @else
                        <span class="badge badge-soft-success fs-12 fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="fi fi-rr-check-circle d-flex"></i>
                            {{translate('Sent')}}
                        </span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-10 justify-content-center">
                        <a title="{{translate('View')}}"
                           class="btn btn-outline-info icon-btn"
                           href="{{route('admin.people.contact.view',$contact->id)}}">
                           <i class="fi fi-rr-eye"></i>
                        </a>
                        <a class="btn btn-outline-danger icon-btn delete delete-data-without-form"
                           data-id="{{$contact['id']}}"
                           data-action="{{route('admin.people.contact.delete')}}"
                           title="{{ translate('Delete')}}">
                           <i class="fi fi-rr-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="table-responsive mt-4">
    <div class="px-4 d-flex justify-content-lg-end">
        {{$contacts->links()}}
    </div>
</div>
@if(count($contacts)==0)
    <div class="text-center p-4">
        <img class="mb-3 w-160"
             src="{{dynamicAsset(path: 'public/assets/back-end/svg/illustrations/sorry.svg')}}"
             alt="{{translate('Image description')}}">
        <p class="mb-0">{{translate('No data to show')}}</p>
    </div>
@endif
