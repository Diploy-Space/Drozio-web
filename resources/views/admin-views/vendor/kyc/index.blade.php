@extends('layouts.admin.app')
@section('title', translate('Vendor KYC Management'))
@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
            {{ translate('Vendor KYC Management') }}
            <span class="badge badge-info text-bg-info">{{ $profiles->total() }}</span>
        </h2>
    </div>
    <div class="card">
        <div class="card-body">
            <form action="{{ url()->current() }}" method="GET" class="row g-2 mb-3">
                <div class="col-md-4">
                    <input type="search" name="searchValue" value="{{ $searchValue }}" class="form-control" placeholder="{{ translate('Search by vendor, shop, GST, PAN, phone, email') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">{{ translate('All Status') }}</option>
                        @foreach(['draft','pending','under_review','approved','rejected','correction_requested'] as $st)
                            <option value="{{ $st }}" {{ $status===$st ? 'selected':'' }}>{{ translate(ucwords(str_replace('_',' ',$st))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn--primary w-100" type="submit">{{ translate('Filter') }}</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover table-borderless table-thead-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('SL') }}</th>
                            <th>{{ translate('Vendor') }}</th>
                            <th>{{ translate('Shop') }}</th>
                            <th>{{ translate('Business / GST') }}</th>
                            <th>{{ translate('Status') }}</th>
                            <th>{{ translate('Submitted') }}</th>
                            <th class="text-center">{{ translate('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($profiles as $key => $profile)
                            <tr>
                                <td>{{ $profiles->firstItem()+$key }}</td>
                                <td>
                                    <div>{{ $profile->seller ? ($profile->seller->f_name.' '.$profile->seller->l_name) : '-' }}</div>
                                    <small class="text-muted">{{ $profile->seller->email ?? '' }}</small>
                                </td>
                                <td>{{ $profile->shop->name ?? $profile->trade_name ?? '-' }}</td>
                                <td>
                                    <div>{{ $profile->legal_name ?? '-' }}</div>
                                    <small class="text-muted">{{ translate('GST') }}: {{ $profile->gst_number ?? '-' }} | {{ translate('PAN') }}: {{ $profile->pan_number ?? '-' }}</small>
                                </td>
                                <td><span class="badge badge-soft-info">{{ translate(ucwords(str_replace('_',' ',$profile->status))) }}</span></td>
                                <td>{{ $profile->submitted_at ? $profile->submitted_at->format('Y-m-d H:i') : '-' }}</td>
                                <td class="text-center">
                                    <a class="btn btn-sm btn-outline--primary" href="{{ route('admin.people.vendors.kyc.view', $profile->id) }}">{{ translate('View') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">{{ translate('No KYC records found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{!! $profiles->links() !!}</div>
        </div>
    </div>
</div>
@endsection
