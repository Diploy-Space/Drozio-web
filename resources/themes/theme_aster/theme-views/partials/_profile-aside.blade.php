@php
    use function App\Utils\customer_info;
    $customer = customer_info();
    $customerId   = $customer?->id ?? 0;
    $firstName    = $customer?->f_name ?? 'Guest';
    $lastName     = $customer?->l_name ?? 'User';
    $createdAt    = $customer?->created_at ? date('d M, Y', strtotime($customer->created_at)) : 'N/A';
@endphp
<div class="col-lg-3">
    <div class="card profile-sidebar-sticky">
        <div class="card-body position-relative">
            <div class="d-flex justify-content-end">
                <div class="dropdown">
                    <button class="btn-circle p-0 bg-primary absolute-white size-1-125rem" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-three-dots fs-10 grid-center"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end profile-sidebar-actions">
                        <li>
                            <a href="javascript:"
                               class="dropdown-item profile-sidebar-actions__delete delete-action"
                               data-action="{{ $customerId ? route('account-delete',[$customerId]) : '' }}"
                               data-message="{{ translate('Want to delete this account').'?' }}">
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                <span>{{ translate('Delete account') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="d-flex flex-row flex-lg-column gap-2 gap-lg-4 align-items-center pb-2">
                <div class="avatar overflow-hidden profile-sidebar-avatar border border-primary rounded-circle d-flex">
                    <img class="img-fit dark-support"
                         src="{{ getStorageImages(path: $customer?->image_full_url, type:'avatar') }}" alt="">
                </div>

                <div class="text-lg-center">
                    <h5 class="mb-1 text-break line-clamp-2">{{$firstName}} {{$lastName}}</h5>
                    <p class="fw-medium">{{ translate('Joined') }} <span class="d-inline-block direction-ltr">{{ $createdAt}}</span></p>
                </div>
            </div>
            <div class="profile-menu-aside">
                <div class="profile-menu-aside-close d-lg-none d-flex">
                    <i class="bi bi-x-lg text-primary"></i>
                </div>
                <ul class="list-unstyled profile-menu gap-1 mt-3">
                    @foreach ($profileMenu as $item)
                        <li class="{{ $item['is_active'] ? 'active' : '' }}">
                            <a href="{{ $item['url'] }}">
                                <img width="20" src="{{ theme_asset($item['icon_image']) }}" class="dark-support" alt="">
                                <span class="text-capitalize">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                    <li class="w-100">
                        @includeWhen(function_exists('shouldShowAuctionMenu') && shouldShowAuctionMenu(), 'theme-views.partials._auction-menu-block', ['variant' => 'sidebar'])
                    </li>
                </ul>
            </div><div class="aside-overlay"></div>
        </div>
    </div>
</div>
