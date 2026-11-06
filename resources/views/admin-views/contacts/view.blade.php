@extends('layouts.admin.app')

@section('title', translate('Contact View'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ dynamicAsset(path: 'public/assets/back-end/css/admin/contact.css') }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="mb-4 d-flex align-items-center gap-3 min-w-0">
            <a class="btn btn-outline-secondary icon-btn flex-shrink-0"
               href="{{ route('admin.people.contact.list') }}"
               data-bs-toggle="tooltip" data-bs-title="{{ translate('Back to list') }}">
                <i class="fi fi-rr-angle-left"></i>
            </a>
            <div class="min-w-0">
                <h2 class="h1 mb-1 d-flex align-items-center gap-2 min-w-0">
                    <span class="text-body-light flex-shrink-0">#{{ $contact['id'] }}</span>
                    <span class="line-1">{{ $contact['subject'] }}</span>
                </h2>
                <span class="fs-12 text-body-light d-flex align-items-center gap-1">
                    <i class="fi fi-rr-clock d-flex"></i>
                    {{ translate('Received') }} {{ date('d M Y, h:i A', strtotime($contact['created_at'])) }}
                </span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="fs-16 mb-3">{{ translate('Message status') }}</h3>

                        <form action="{{ route('admin.people.contact.update', $contact->id) }}" method="post"
                              id="submit-form">
                            @csrf
                            <div class="bg-section rounded p-3">
                                <span class="badge {{ $contact->seen ? 'badge-soft-success' : 'badge-soft-warning' }} fw-semibold mb-1">
                                    {{ $contact->seen ? translate('Checked') : translate('Unchecked') }}
                                </span>
                                <span class="fs-12 text-body-light d-block">
                                    {{ $contact->seen
                                        ? translate('This message has been reviewed')
                                        : translate('Mark it once you have reviewed this message') }}
                                </span>
                                @unless($contact->seen)
                                    <button type="button"
                                            class="btn btn-sm btn-primary w-100 mt-3 d-flex align-items-center justify-content-center gap-2 form-alert"
                                            data-id="submit-form"
                                            data-message="{{ translate('Want check this message').'?' }}">
                                        <i class="fi fi-rr-check-circle d-flex"></i>
                                        {{ translate('Mark as checked') }}
                                    </button>
                                @endunless
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <h3 class="fs-16 mb-3">{{ translate('Customer') }}</h3>

                        <span class="fw-semibold d-block line-1">{{ $contact['name'] }}</span>

                        <div class="d-flex justify-content-between align-items-center gap-2 pt-3 mt-3 border-top">
                            <span class="fs-12 text-body-light">{{ translate('Email') }}</span>
                            <a class="fs-12 text-dark text-break text-end"
                               href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-top mt-2">
                            <span class="fs-12 text-body-light">{{ translate('Mobile no') }}</span>
                            <a class="fs-12 text-dark" href="tel:{{ $contact['mobile_number'] }}">
                                {{ $contact['mobile_number'] }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="fs-16 mb-3">{{ translate('Message details') }}</h3>

                        <div class="d-flex justify-content-between align-items-center gap-2 pb-2">
                            <span class="fs-12 text-body-light">{{ translate('Reply status') }}</span>
                            <span class="badge {{ $reply ? 'badge-soft-success' : 'badge-soft-warning' }} fw-semibold">
                                {{ $reply ? translate('Sent') : translate('Not sent') }}
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center gap-2 pt-2 border-top">
                            <span class="fs-12 text-body-light">{{ translate('Received on') }}</span>
                            <span class="fs-12 text-dark">
                                {{ date('d M Y, h:i A', strtotime($contact['created_at'])) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <section class="col-xl-8">
                <div class="card">
                    <div class="card-header py-3">
                        <h3 class="fs-16 mb-0 d-flex align-items-center gap-2">
                            <i class="fi fi-rr-comment-alt d-flex"></i>
                            {{ translate('Conversation') }}
                        </h3>
                    </div>

                    <div class="card-body">
                        <div class="contact-thread-item">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                <span class="badge badge-soft-info fs-12 fw-semibold text-capitalize">{{ $contact['name'] }}</span>
                                <span class="text-body-light fs-12">{{ date('d M, Y h:i A', strtotime($contact['created_at'])) }}</span>
                            </div>
                            <div class="contact-thread-bubble">
                                <div class="fw-semibold mb-1">{{ $contact['subject'] }}</div>
                                <p class="mb-0 text-muted contact-thread-text">{{ $contact['message'] }}</p>
                            </div>
                        </div>

                        <div class="contact-thread-item mt-3">
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                <span class="badge badge-soft-dark fs-12 fw-semibold text-capitalize">{{ translate('Admin') }}</span>
                            </div>
                            @if($reply)
                                <div class="contact-thread-bubble contact-thread-bubble-admin">
                                    <div class="fw-semibold mb-1">{{ $reply['subject'] ?? '' }}</div>
                                    <p class="mb-0 text-muted contact-thread-text">{{ $reply['body'] ?? '' }}</p>
                                </div>
                            @else
                                <div class="contact-thread-empty text-body-light fs-12 d-flex align-items-center gap-2">
                                    <i class="fi fi-rr-clock d-flex"></i>
                                    {{ translate('No reply sent yet') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="border-top p-3">
                        <form action="{{ route('admin.people.contact.send-mail', $contact->id) }}" method="post">
                            @csrf
                            <div class="form-group mb-1">
                                <label class="mb-2 fs-12 text-body-light" for="subject">{{ translate('Subject') }}</label>
                                <input id="subject" class="form-control" name="subject" required
                                       value="{{ old('subject', $contact['subject']) }}"
                                       placeholder="{{ translate('Subject') }}">
                            </div>
                            <div class="form-group mb-2">
                                <label class="mb-2 fs-12 text-body-light" for="mail-body">{{ translate('Mail Body') }}</label>
                                <textarea id="mail-body" class="form-control contact-mail-body" name="mail_body" required
                                          placeholder="{{ translate('Write your reply to the customer') }}">{{ old('mail_body') }}</textarea>
                            </div>

                            @unless($isMailConfigured)
                            <div>
                                <div class="bg-warning bg-opacity-10 fs-12 px-12 py-10 text-dark rounded d-flex gap-2 align-items-center mb-3">
                                    <i class="fi fi-sr-info text-warning d-flex"></i>
                                    <span>{{ translate('First, configure your mail setup to respond to customers').'.' }}</span>
                                </div>
                            </div>
                            @endunless

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary d-flex align-items-center gap-2"
                                        @disabled(!$isMailConfigured)>
                                    <i class="fi fi-sr-paper-plane d-flex"></i>
                                    {{ translate('Send reply') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
