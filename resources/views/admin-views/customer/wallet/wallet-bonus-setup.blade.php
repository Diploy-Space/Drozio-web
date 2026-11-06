@extends('layouts.admin.app')

@section('title',translate('Customer Wallet Bonus Setup'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-2">
            <h2 class="h1 mb-0 text-capitalize d-flex align-items-center gap-2">
                <img width="20" src="{{dynamicAsset(path: 'public/assets/back-end/img/admin-wallet.png')}}" alt="">
                {{translate('Wallet Bonus Setup')}}
            </h2>
            <div class="d-flex flex-wrap align-items-center gap-3">
                <div class="text-primary d-flex align-items-center gap-3 font-weight-bolder">
                    {{translate('How it works')}}
                    <div class="ripple-animation" data-bs-toggle="modal" data-bs-target="#howItWorksModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none"
                             class="svg replaced-svg">
                            <path d="M9.00033 9.83268C9.23644 9.83268 9.43449 9.75268 9.59449 9.59268C9.75449 9.43268 9.83421 9.2349 9.83366 8.99935V5.64518C9.83366 5.40907 9.75366 5.21463 9.59366 5.06185C9.43366 4.90907 9.23588 4.83268 9.00033 4.83268C8.76421 4.83268 8.56616 4.91268 8.40616 5.07268C8.24616 5.23268 8.16644 5.43046 8.16699 5.66602V9.02018C8.16699 9.25629 8.24699 9.45074 8.40699 9.60352C8.56699 9.75629 8.76477 9.83268 9.00033 9.83268ZM9.00033 13.166C9.23644 13.166 9.43449 13.086 9.59449 12.926C9.75449 12.766 9.83421 12.5682 9.83366 12.3327C9.83366 12.0966 9.75366 11.8985 9.59366 11.7385C9.43366 11.5785 9.23588 11.4988 9.00033 11.4993C8.76421 11.4993 8.56616 11.5793 8.40616 11.7393C8.24616 11.8993 8.16644 12.0971 8.16699 12.3327C8.16699 12.5688 8.24699 12.7668 8.40699 12.9268C8.56699 13.0868 8.76477 13.1666 9.00033 13.166ZM9.00033 17.3327C7.84755 17.3327 6.76421 17.1138 5.75033 16.676C4.73644 16.2382 3.85449 15.6446 3.10449 14.8952C2.35449 14.1452 1.76088 13.2632 1.32366 12.2493C0.886437 11.2355 0.667548 10.1521 0.666992 8.99935C0.666992 7.84657 0.885881 6.76324 1.32366 5.74935C1.76144 4.73546 2.35505 3.85352 3.10449 3.10352C3.85449 2.35352 4.73644 1.7599 5.75033 1.32268C6.76421 0.88546 7.84755 0.666571 9.00033 0.666016C10.1531 0.666016 11.2364 0.884905 12.2503 1.32268C13.2642 1.76046 14.1462 2.35407 14.8962 3.10352C15.6462 3.85352 16.24 4.73546 16.6778 5.74935C17.1156 6.76324 17.3342 7.84657 17.3337 8.99935C17.3337 10.1521 17.1148 11.2355 16.677 12.2493C16.2392 13.2632 15.6456 14.1452 14.8962 14.8952C14.1462 15.6452 13.2642 16.2391 12.2503 16.6768C11.2364 17.1146 10.1531 17.3332 9.00033 17.3327ZM9.00033 15.666C10.8475 15.666 12.4206 15.0168 13.7195 13.7185C15.0184 12.4202 15.6675 10.8471 15.667 8.99935C15.667 7.15213 15.0178 5.57907 13.7195 4.28018C12.4212 2.98129 10.8481 2.33213 9.00033 2.33268C7.1531 2.33268 5.58005 2.98185 4.28116 4.28018C2.98227 5.57852 2.3331 7.15157 2.33366 8.99935C2.33366 10.8466 2.98283 12.4196 4.28116 13.7185C5.57949 15.0174 7.15255 15.6666 9.00033 15.666Z"
                                  fill="currentColor"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-end flex-wrap gap-3 align-items-center mb-4">
                    <h3 class="mb-0 text-nowrap text-capitalize d-flex gap-1 align-items-center flex-grow-1">
                        {{ translate('Wallet Bonus Table') }}
                        <span class="badge badge-info text-bg-info">{{ $data->total() }}</span>
                    </h3>

                    <form action="{{ url()->current() }}" method="GET" class="min-w-100-mobile min-w-280">
                        <div class="input-group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control"
                                   placeholder="{{translate('Search by bonus title')}}"
                                   value="{{ request('search') }}"
                                   aria-label="{{translate('Search by bonus title')}}">
                            <div class="input-group-append search-submit">
                                <button type="submit">
                                    <i class="fi fi-rr-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    <button type="button" class="btn btn-primary" title="{{translate('Add Bonus')}}"
                            data-bs-toggle="offcanvas" data-bs-target="#bonusAddOffcanvas">
                        + {{translate('Add Bonus')}}
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                        <thead class="thead-light thead-50 text-capitalize">
                        <tr>
                            <th>{{translate('Serial')}}</th>
                            <th>{{translate('Bonus title')}}</th>
                            <th>{{translate('Bonus information')}}</th>
                            <th class="text-center">{{translate('Bonus amount')}}</th>
                            <th class="text-center">{{translate('Started on')}}</th>
                            <th class="text-center">{{translate('Expires on')}}</th>
                            <th class="text-center">{{translate('Status')}}</th>
                            <th class="text-center">{{translate('Action')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($data as $key=>$item)
                            @php($isExpired = in_array($item->id, $expiredBonusIds))
                            <tr>
                                <td>{{$data->firstItem()+$key}}</td>
                                <td>{{ $item->title }}</td>
                                <td>
                                    <div class="d-flex flex-column gap-1">
                                        <div>{{translate('Minimum Add Amount').' '.'-'}}
                                             {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->min_add_money_amount)) }}</div>
                                        @if ($item->bonus_type != "fixed")
                                            <div>{{translate('Maximum Bonus').' '.'-'}}
                                                 {{setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->max_bonus_amount)) }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-center">{{ $item->bonus_type == 'percentage' ? $item->bonus_amount.'%' : setCurrencySymbol(amount: usdToDefaultCurrency(amount: $item->bonus_amount)) }}</td>
                                <td class="text-center">{{ date('d M, Y',strtotime($item->start_date_time)) }}</td>
                                <td class="text-center">{{ date('d M, Y',strtotime($item->end_date_time)) }}</td>
                                <td>
                                    <form action="{{route('admin.people.customer.wallet.bonus-setup-status')}}"
                                          method="post"
                                          id="bonus-setup-{{ $item->id }}-form"
                                          class="no-reload-form">
                                        @csrf
                                        <input name="id" value="{{$item['id']}}" hidden>
                                        <label class="switcher mx-auto"
                                               for="bonus-setup-{{ $item->id }}"
                                               @if($isExpired)
                                                   data-toggle="tooltip"
                                               data-placement="top"
                                               title="{{ translate('End date time has expired, update the end time') }}"
                                               style="cursor: not-allowed; opacity: 0.6;"
                                            @endif
                                        >

                                            <input
                                                class="switcher_input custom-modal-plugin"
                                                type="checkbox"
                                                value="1"
                                                name="status"
                                                id="bonus-setup-{{ $item->id }}"
                                                {{ $item->is_active == 1 ? 'checked' : '' }}
                                                {{ $isExpired ? 'disabled' : '' }}
                                                @unless($isExpired)
                                                    data-modal-type="input-change-form"
                                                data-modal-form="#bonus-setup-{{ $item->id }}-form"
                                                data-on-image="{{dynamicAsset(path: 'public/assets/back-end/img/modal/bonus-turn-on.png')}}"
                                                data-off-image="{{dynamicAsset(path: 'public/assets/back-end/img/modal/bonus-turn-off.png')}}"
                                                data-on-title="{{translate('Want to Turn ON :item', ['item' => translate('Bonus status')]).'?'}}"
                                                data-off-title="{{translate('Want to Turn OFF :item', ['item' => translate('Bonus status')]).'?'}}"
                                                data-on-message="<p>{{translate('Turning the bonus on gives customers the offer.').'.'}}</p>"
                                                data-off-message="<p>{{translate('Turning off the bonus hides the offer from all customers.').'.'}}</p>"
                                                data-on-button-text="{{ translate('Turn On') }}"
                                                data-off-button-text="{{ translate('Turn Off') }}"
                                                @endunless
                                            >
                                            <span class="switcher_control"></span>
                                        </label>
                                    </form>
                                </td>
                                <td>
                                    <div class="d-flex gap-10 justify-content-center">
                                        <a title="{{translate('Edit')}}"
                                           class="btn btn-outline-primary icon-btn edit"
                                           data-bs-toggle="offcanvas" href="#bonusEditOffcanvas-{{ $item->id }}">
                                           <i class="fi fi-rr-pencil"></i>
                                        </a>
                                        <a title="{{translate('Delete')}}" class="btn btn-outline-danger icon-btn delete-data"
                                           data-id="wallet-bonus-{{$item['id']}}">
                                           <i class="fi fi-rr-trash"></i>
                                        </a>
                                    </div>
                                    <form action="{{route('admin.people.customer.wallet.bonus-setup-delete',['id'=>$item['id']])}}"
                                          method="post" id="wallet-bonus-{{$item['id']}}">
                                        @csrf @method('delete')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-end p-4">
                        {!! $data->links() !!}
                    </div>
                </div>
                @if(count($data) == 0)
                    @include('layouts.admin.partials._empty-state',['text'=>'no_data_found'],['image'=>'default'])
                @endif
            </div>
        </div>

        @include('admin-views.customer.wallet.partials._bonus-how-it-works-modal')
        @include('admin-views.customer.wallet.partials._bonus-add-offcanvas')
        @foreach ($data as $item)
            @include('admin-views.customer.wallet.partials._bonus-edit-offcanvas', ['bonus' => $item])
        @endforeach
    </div>
@endsection

@push('script')
    <script src="{{dynamicAsset(path: 'public/assets/back-end/js/admin/customer/wallet.js')}}"></script>
@endpush
