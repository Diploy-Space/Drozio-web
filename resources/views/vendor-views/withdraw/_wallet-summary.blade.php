<div class="card mb-3">
    <div class="card-header flex-wrap gap-2">
        <h4 class="d-flex align-items-center gap-10 mb-0 text-capitalize">
            <img width="20" src="{{ dynamicAsset(path: 'public/assets/back-end/img/admin-wallet.png') }}" alt="">
            {{ translate('Vendor Wallet') }}
        </h4>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @foreach ($walletBusinessModules as $walletBusinessModule)
                <span class="badge badge-soft-primary">{{ $walletBusinessModule }}</span>
            @endforeach
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-xl-4">
                <div class="card h-100 shadow-none border bg-light">
                    <div class="card-body d-flex flex-column gap-10 align-items-center justify-content-center text-center">
                        <img width="48" class="mb-2" src="{{ dynamicAsset(path: 'public/assets/back-end/img/withdraw.png') }}" alt="">
                        <h3 class="mb-0 fz-24 text-primary">
                            {{ setCurrencySymbol(amount: usdToDefaultCurrency(amount: $walletSummary['withdrawableBalance']), currencyCode: getCurrencyCode(type: 'default')) }}
                        </h3>
                        <div class="font-weight-bold text-capitalize">{{ translate('Withdrawable balance') }}</div>
                        <p class="fs-12 text-muted mb-3">
                            {{ translate('Ready to request as a withdrawal') }}
                        </p>
                        @if ($walletSummary['withdrawableBalance'] > 0)
                            <button type="button" class="btn btn--primary px-4 w-100" data-toggle="offcanvas" data-target=".withdraw-request-offcanvas">
                                {{ translate('Withdraw') }}
                            </button>
                        @else
                            <span class="w-100" data-toggle="tooltip" title="{{ translate('Due to no balance this button is disabled') }}">
                                <button type="button" class="btn btn--primary px-4 w-100" disabled>
                                    {{ translate('Withdraw') }}
                                </button>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="row g-2 h-100">
                    @include('vendor-views.withdraw._wallet-stat-card', ['amount' => $walletSummary['pendingWithdraw'], 'label' => translate('Pending Withdraw'), 'icon' => 'pw.png', 'amountClass' => 'text-warning'])
                    @include('vendor-views.withdraw._wallet-stat-card', ['amount' => $walletSummary['withdrawn'], 'label' => translate('Already Withdrawn'), 'icon' => 'aw.png', 'amountClass' => 'text-success'])
                    @include('vendor-views.withdraw._wallet-stat-card', ['amount' => $walletSummary['commissionGiven'], 'label' => translate('Total Commission'), 'icon' => 'tcg.png'])
                    @include('vendor-views.withdraw._wallet-stat-card', ['amount' => $walletSummary['deliveryChargeEarned'], 'label' => translate('Total delivery charge earned'), 'icon' => 'tdce.png'])
                    @include('vendor-views.withdraw._wallet-stat-card', ['amount' => $walletSummary['totalTaxCollected'], 'label' => translate('Total Tax'), 'icon' => 'ttg.png'])
                    @include('vendor-views.withdraw._wallet-stat-card', ['amount' => $walletSummary['collectedCash'], 'label' => translate('Collected cash'), 'icon' => 'cc.png'])
                </div>
            </div>
        </div>
    </div>
</div>
