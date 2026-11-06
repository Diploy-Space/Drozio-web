<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BusinessModule;
use App\Exports\VendorWithdrawRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\Withdraw\AddRequest;
use App\Http\Requests\Vendor\Withdraw\UpdateRequest;
use App\Services\New\VendorService;
use App\Services\New\VendorWalletService;
use App\Services\New\VendorWithdrawMethodInfoService;
use App\Services\New\WithdrawalMethodService;
use App\Services\New\WithdrawRequestService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WithdrawController extends Controller
{
    private const METHOD_TYPE_CUSTOM = 'custom';

    public function __construct(
        private readonly WithdrawRequestService $withdrawRequestService,
        private readonly VendorWalletService $vendorWalletService,
        private readonly VendorService $vendorService,
        private readonly VendorWithdrawMethodInfoService $vendorWithdrawMethodInfoService,
        private readonly WithdrawalMethodService $withdrawalMethodService,
    ) {}

    public function index(Request $request): View
    {
        $vendorId = $this->vendorId();
        $this->keepSingleDefaultMethod(vendorId: $vendorId);
        updateSetupGuideCacheKey(key: 'withdraw_setup', panel: 'vendor');

        $vendorWallet = $this->vendorWalletService->findBy(params: ['seller_id' => $vendorId]);
        $withdrawRequests = $this->withdrawRequestService->getList(
            searchValue: $this->searchableValue(searchValue: $request['searchValue']),
            dataLimit: getWebConfig(name: 'pagination_limit'),
            relations: ['seller'],
            filters: $this->listFilters(status: $request['status']),
            sortBy: WithdrawRequestService::SORT_NEWEST_ID,
        )->appends($this->listQueryString($request));

        return view('vendor-views.withdraw.index', [
            'vendorWallet' => $vendorWallet,
            'walletSummary' => $this->walletSummary(wallet: $vendorWallet),
            'walletBusinessModules' => $this->walletBusinessModuleLabels(),
            'auctionWithdrawUrl' => businessModuleManager()->isAvailableInPanel(BusinessModule::AUCTION)
                ? route('vendor.business-settings.withdraw.auction')
                : null,
            'withdrawRequests' => $withdrawRequests,
            'activeStatus' => $request['status'] ?? WithdrawRequestService::STATUS_ALL,
            'withdrawRequestCount' => $withdrawRequests->total(),
            'searchValue' => $request['searchValue'],
            'searchClearUrl' => route('vendor.business-settings.withdraw.index', array_diff_key(
                $this->listQueryString($request),
                ['searchValue' => null],
            )),
            'withdrawalMethods' => $this->withdrawalMethodService->getList(
                dataLimit: 'all',
                filters: [
                    'is_active' => WithdrawalMethodService::STATUS_ACTIVE,
                    WithdrawalMethodService::FILTER_USER_TYPE => WithdrawalMethodService::USER_TYPE_VENDOR,
                ],
            ),
            'vendorWithdrawMethods' => $this->vendorWithdrawMethodInfoService->getList(
                dataLimit: 'all',
                relations: ['withdraw_method'],
                filters: [
                    'user_id' => $vendorId,
                    'is_active' => VendorWithdrawMethodInfoService::STATUS_ACTIVE,
                ],
                sortBy: VendorWithdrawMethodInfoService::SORT_NAME_ASC,
            ),
        ]);
    }

    public function getListByStatus(Request $request): JsonResponse
    {
        $withdrawRequests = $this->withdrawRequestService->getList(
            searchValue: $this->searchableValue(searchValue: $request['searchValue']),
            dataLimit: getWebConfig(name: 'pagination_limit'),
            relations: ['seller'],
            filters: $this->listFilters(status: $request['status']),
            sortBy: WithdrawRequestService::SORT_NEWEST_ID,
        )->appends($this->listQueryString($request));

        return response()->json([
            'view' => view('vendor-views.withdraw._table', ['withdrawRequests' => $withdrawRequests])->render(),
            'count' => $withdrawRequests->count(),
        ], 200);
    }

    public function renderInfosView(Request $request): JsonResponse
    {
        $vendorId = $this->vendorId();
        $isCustomMethod = $request['method_type'] === self::METHOD_TYPE_CUSTOM;

        $withdrawRequest = $request['current_request']
            ? $this->withdrawRequestService->findBy(params: [
                'id' => $request['current_request'],
                'seller_id' => $vendorId,
            ])
            : null;

        $vendorWithdrawMethod = $isCustomMethod
            ? $this->vendorWithdrawMethodInfoService->find(id: $request['method_id'], userId: $vendorId)
            : null;

        $withdrawalMethod = $this->withdrawalMethodService->find(
            id: $isCustomMethod ? $vendorWithdrawMethod?->withdraw_method_id : $request['method_id'],
        );

        return response()->json([
            'htmlView' => view('vendor-views.withdraw._withdraw-request-method-filed', [
                'method_type' => $request['method_type'],
                'vendorWallet' => $this->vendorWalletService->findBy(params: ['seller_id' => $vendorId]),
                'withdrawalMethod' => $withdrawalMethod,
                'vendorWithdrawMethod' => $vendorWithdrawMethod,
                'withdrawRequest' => $withdrawRequest,
                'withdrawalTotalAmount' => $withdrawRequest?->amount ?? 0,
            ])->render(),
        ]);
    }

    public function add(AddRequest $request): RedirectResponse
    {
        $vendorId = $this->vendorId();
        $withdrawalMethod = $this->withdrawalMethodService->find(id: $request['withdraw_method']);
        $amount = (float) currencyConverter(amount: $request['amount']);

        $withdrawRequest = DB::transaction(function () use ($vendorId, $withdrawalMethod, $amount, $request) {
            if (! $this->vendorWalletService->reserveWithdraw(sellerId: $vendorId, amount: $amount)) {
                return null;
            }

            return $this->withdrawRequestService->create(data: [
                'seller_id' => $vendorId,
                'amount' => $amount,
                'withdrawal_method_id' => $request['withdraw_method'],
                'withdrawal_method_fields' => json_encode($this->withdrawRequestService->getWithdrawalMethodFields(
                    withdrawalMethod: $withdrawalMethod,
                    methodInfo: (array) ($request['method_info'] ?? []),
                )),
                'approved' => WithdrawRequestService::APPROVAL_PENDING,
                'request_method_type' => $request['request_method_type'] ?? WithdrawRequestService::REQUEST_METHOD_TYPE_SYSTEM,
            ]);
        });

        $withdrawRequest === null
            ? ToastMagic::error(translate('Invalid request').'!')
            : ToastMagic::success(translate('Withdraw request has been sent'));

        return redirect()->back();
    }

    public function update(UpdateRequest $request): RedirectResponse
    {
        $vendorId = $this->vendorId();
        $withdrawRequest = $this->withdrawRequestService->findBy(params: [
            'id' => $request['withdraw_request_id'],
            'seller_id' => $vendorId,
            'approved' => WithdrawRequestService::APPROVAL_PENDING,
        ]);

        if ($withdrawRequest === null) {
            ToastMagic::error(translate('Invalid request').'!');

            return redirect()->back();
        }

        $withdrawalMethod = $this->withdrawalMethodService->find(id: $request['withdraw_method']);
        $amount = (float) currencyConverter(amount: $request['amount']);

        $isUpdated = DB::transaction(function () use ($vendorId, $withdrawRequest, $withdrawalMethod, $amount, $request): bool {
            if (! $this->moveWithdrawReservation(vendorId: $vendorId, fromAmount: (float) $withdrawRequest->amount, toAmount: $amount)) {
                return false;
            }

            $this->withdrawRequestService->updateWhere(params: ['id' => $withdrawRequest->id], data: [
                'seller_id' => $vendorId,
                'amount' => $amount,
                'transaction_note' => null,
                'withdrawal_method_id' => $request['withdraw_method'],
                'withdrawal_method_fields' => json_encode($this->withdrawRequestService->getWithdrawalMethodFields(
                    withdrawalMethod: $withdrawalMethod,
                    methodInfo: (array) ($request['method_info'] ?? []),
                )),
                'approved' => WithdrawRequestService::APPROVAL_PENDING,
                'request_method_type' => $request['request_method_type'] ?? WithdrawRequestService::REQUEST_METHOD_TYPE_SYSTEM,
                'created_at' => now(),
            ]);

            return true;
        });

        $isUpdated
            ? ToastMagic::success(translate('Withdraw request has been update'))
            : ToastMagic::error(translate('Currently this amount not withdrawable').'!');

        return redirect()->back();
    }

    public function closeWithdrawRequest(string|int $id): RedirectResponse
    {
        $vendorId = $this->vendorId();
        $withdrawRequest = $this->withdrawRequestService->findBy(params: [
            'id' => $id,
            'seller_id' => $vendorId,
            'approved' => WithdrawRequestService::APPROVAL_PENDING,
        ]);
        $vendorWallet = $this->vendorWalletService->findBy(params: ['seller_id' => $vendorId]);

        $isClosed = $withdrawRequest !== null
            && $vendorWallet !== null
            && DB::transaction(function () use ($withdrawRequest, $vendorWallet, $vendorId): bool {
                $this->vendorWalletService->updateWhere(
                    params: ['seller_id' => $vendorId],
                    data: $this->releasedWalletData(wallet: $vendorWallet, amount: (float) $withdrawRequest->amount),
                );

                return $this->withdrawRequestService->delete(id: $withdrawRequest->id);
            });

        $isClosed
            ? ToastMagic::success(translate('Withdraw request closed').'!')
            : ToastMagic::error(translate('Invalid withdraw request'));

        return redirect()->back();
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $withdrawRequests = $this->withdrawRequestService->getList(
            searchValue: $this->searchableValue(searchValue: $request['searchValue']),
            dataLimit: 'all',
            relations: ['seller'],
            filters: $this->listFilters(status: $request['status']),
            sortBy: WithdrawRequestService::SORT_NEWEST_ID,
        );

        return Excel::download(
            export: new VendorWithdrawRequest([
                'data-from' => 'vendor',
                'vendor' => $this->vendorService->find(id: $this->vendorId()),
                'withdraw_request' => $withdrawRequests,
                'filter' => $request['status'],
                'searchValue' => $request['searchValue'],
                'pending' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_PENDING)->count(),
                'approved' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_APPROVED)->count(),
                'denied' => $withdrawRequests->where('approved', WithdrawRequestService::APPROVAL_DENIED)->count(),
            ]),
            fileName: 'Vendor-Withdraw-Request.xlsx',
        );
    }

    private function listFilters(?string $status): array
    {
        $approval = match ($status) {
            WithdrawRequestService::STATUS_PENDING => WithdrawRequestService::APPROVAL_PENDING,
            WithdrawRequestService::STATUS_APPROVED => WithdrawRequestService::APPROVAL_APPROVED,
            WithdrawRequestService::STATUS_DENIED => WithdrawRequestService::APPROVAL_DENIED,
            default => null,
        };

        return [
            'seller_id' => $this->vendorId(),
            'delivery_man_id' => null,
        ] + ($approval === null ? [] : ['approved' => $approval]);
    }

    private function listQueryString(Request $request): array
    {
        return array_filter([
            'searchValue' => $request['searchValue'],
            'status' => $request['status'],
        ], static fn ($value) => $value !== null);
    }

    private function searchableValue(?string $searchValue): ?string
    {
        if ($searchValue === null) {
            return null;
        }

        $normalizedValue = str_replace(',', '', $searchValue);

        return is_numeric($normalizedValue)
            ? (string) round(currencyConverter(amount: (float) $normalizedValue), 2)
            : $searchValue;
    }

    private function walletSummary(?Model $wallet): array
    {
        return [
            'withdrawableBalance' => (float) ($wallet?->total_earning ?? 0),
            'pendingWithdraw' => (float) ($wallet?->pending_withdraw ?? 0),
            'withdrawn' => (float) ($wallet?->withdrawn ?? 0),
            'commissionGiven' => (float) ($wallet?->commission_given ?? 0),
            'deliveryChargeEarned' => (float) ($wallet?->delivery_charge_earned ?? 0),
            'totalTaxCollected' => (float) ($wallet?->total_tax_collected ?? 0),
            'collectedCash' => (float) ($wallet?->collected_cash ?? 0),
        ];
    }

    private function walletBusinessModuleLabels(): array
    {
        $labels = [BusinessModule::RETAIL => translate('Retail')];

        if (businessModuleManager()->isAvailableInPanel(BusinessModule::WHOLESALE)) {
            $labels[BusinessModule::WHOLESALE] = translate('Wholesale');
        }

        return $labels;
    }

    private function moveWithdrawReservation(int $vendorId, float $fromAmount, float $toAmount): bool
    {
        $difference = $toAmount - $fromAmount;

        return match (true) {
            $difference > 0 => $this->vendorWalletService->reserveWithdraw(sellerId: $vendorId, amount: $difference),
            $difference < 0 => $this->vendorWalletService->releaseWithdraw(sellerId: $vendorId, amount: abs($difference)),
            default => true,
        };
    }

    private function releasedWalletData(Model $wallet, float $amount): array
    {
        return [
            'total_earning' => $wallet->total_earning + $amount,
            'pending_withdraw' => $wallet->pending_withdraw - $amount,
        ];
    }

    private function keepSingleDefaultMethod(int $vendorId): void
    {
        $defaultMethods = $this->vendorWithdrawMethodInfoService->getList(
            dataLimit: 'all',
            filters: [
                'user_id' => $vendorId,
                'is_active' => VendorWithdrawMethodInfoService::STATUS_ACTIVE,
                'is_default' => 1,
            ],
            sortBy: VendorWithdrawMethodInfoService::SORT_NAME_ASC,
        );

        if ($defaultMethods->count() < 2) {
            return;
        }

        foreach ($defaultMethods->skip(1) as $method) {
            $this->vendorWithdrawMethodInfoService->updateBy(
                params: ['id' => $method->id, 'user_id' => $vendorId],
                data: ['is_default' => 0],
            );
        }
    }

    private function vendorId(): int
    {
        return auth('seller')->id();
    }
}
