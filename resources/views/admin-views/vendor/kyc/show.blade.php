@extends('layouts.admin.app')
@section('title', translate('Vendor KYC Details'))
@section('content')
<div class="content container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <h2 class="h1 mb-0">{{ translate('Vendor KYC Details') }}</h2>
        <a href="{{ route('admin.people.vendors.kyc.list') }}" class="btn btn--secondary">{{ translate('Back') }}</a>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ translate('Profile Information') }}</h5></div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6"><strong>{{ translate('Status') }}:</strong> {{ translate(ucwords(str_replace('_',' ',$profile->status))) }}</div>
                        <div class="col-md-6"><strong>{{ translate('Submitted At') }}:</strong> {{ $profile->submitted_at ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Vendor') }}:</strong> {{ $profile->seller ? ($profile->seller->f_name.' '.$profile->seller->l_name) : '-' }} ({{ $profile->seller->email ?? '' }})</div>
                        <div class="col-md-6"><strong>{{ translate('Shop') }}:</strong> {{ $profile->shop->name ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Business Type') }}:</strong> {{ $profile->business_type ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Registration Type') }}:</strong> {{ $profile->registration_type ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Legal Name') }}:</strong> {{ $profile->legal_name ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Trade Name') }}:</strong> {{ $profile->trade_name ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('GST Number') }}:</strong> {{ $profile->gst_number ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('PAN Number') }}:</strong> {{ $profile->pan_number ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('PAN Holder') }}:</strong> {{ $profile->pan_holder_name ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('PAN Verification') }}:</strong> {{ $profile->pan_verification_status ?? '-' }}</div>
                        <div class="col-12"><hr></div>
                        <div class="col-12"><strong>{{ translate('Business Address') }}:</strong>
                            <div>{{ $profile->business_address_line1 }} {{ $profile->business_address_line2 }}</div>
                            <div>{{ $profile->business_city }} {{ $profile->business_state }} {{ $profile->business_pincode }} {{ $profile->business_country }}</div>
                        </div>
                        <div class="col-12"><hr></div>
                        <div class="col-md-6"><strong>{{ translate('Bank Account Name') }}:</strong> {{ $profile->bank_account_name ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Bank Name') }}:</strong> {{ $profile->bank_name ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Branch') }}:</strong> {{ $profile->bank_branch ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Account No') }}:</strong> {{ $profile->bank_account_no ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('IFSC') }}:</strong> {{ $profile->bank_ifsc ?? '-' }}</div>
                        <div class="col-md-6"><strong>{{ translate('Bank Verification') }}:</strong> {{ $profile->bank_verification_status ?? '-' }}</div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">{{ translate('Uploaded Documents') }}</h5></div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>{{ translate('Type') }}</th><th>{{ translate('Status') }}</th><th>{{ translate('File') }}</th><th>{{ translate('Note') }}</th></tr></thead>
                        <tbody>
                        @forelse($profile->documents as $doc)
                            <tr>
                                <td>{{ $doc->document_type }}</td>
                                <td>{{ $doc->status }}</td>
                                <td>
                                    @if($doc->file_full_url)
                                        <a href="{{ is_array($doc->file_full_url)?($doc->file_full_url['path']??'#'):$doc->file_full_url }}" target="_blank">{{ $doc->original_name ?? translate('View') }}</a>
                                    @else - @endif
                                </td>
                                <td>{{ $doc->review_note ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center">{{ translate('No documents uploaded') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ translate('Review History') }}</h5></div>
                <div class="card-body">
                    <ul class="list-group">
                        @forelse($profile->reviews as $rev)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between"><strong>{{ $rev->action }}</strong><small>{{ $rev->created_at }}</small></div>
                                <div>{{ translate('From') }}: {{ $rev->from_status ?? '-' }} → {{ translate('To') }}: {{ $rev->to_status ?? '-' }}</div>
                                @if($rev->note)<div>{{ translate('Note') }}: {{ $rev->note }}</div>@endif
                            </li>
                        @empty
                            <li class="list-group-item">{{ translate('No review history') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ translate('KYC Review Actions') }}</h5></div>
                <div class="card-body">
                    @if($profile->rejection_reason)
                        <div class="alert alert-danger"><strong>{{ translate('Rejection Reason') }}:</strong> {{ $profile->rejection_reason }}</div>
                    @endif
                    @if($profile->correction_note)
                        <div class="alert alert-warning"><strong>{{ translate('Correction Note') }}:</strong> {{ $profile->correction_note }}</div>
                    @endif
                    <form action="{{ route('admin.people.vendors.kyc.review', $profile->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Action') }}</label>
                            <select name="action" class="form-control" required>
                                <option value="under_review">{{ translate('Mark Under Review') }}</option>
                                <option value="approve">{{ translate('Approve') }}</option>
                                <option value="request_correction">{{ translate('Request Correction') }}</option>
                                <option value="reject">{{ translate('Reject') }}</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ translate('Reason / Note') }} <small class="text-muted">({{ translate('required for reject or correction') }})</small></label>
                            <textarea name="note" class="form-control" rows="4" placeholder="{{ translate('Enter reason or correction note') }}"></textarea>
                        </div>
                        <button type="submit" class="btn btn--primary w-100">{{ translate('Submit Review') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
