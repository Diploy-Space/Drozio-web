<?php

namespace App\Http\Controllers\Vendor\DeliveryMan;

use App\Enums\WebConfigKey;
use App\Exports\DeliveryManListExport;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Vendor\DeliveryMan\AddRequest;
use App\Http\Requests\Vendor\DeliveryMan\UpdateRequest;
use App\Services\New\BusinessSettingService;
use App\Services\New\DeliveryManService;
use App\Services\New\ReviewService;
use App\Services\New\VendorService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DeliveryManController extends BaseController
{
    private const LIST_FILE_NAME = 'Delivery-Man-List.xlsx';

    /** @var array<int, string> */
    private const LIST_RELATIONS = ['rating'];

    /** @var array<int, string> */
    private const LIST_WITH_COUNT = ['orders'];

    /** @var array<string, string> */
    private const SORT_KEYS = [
        'latest' => DeliveryManService::SORT_LATEST,
        'oldest' => DeliveryManService::SORT_OLDEST,
        'rating' => DeliveryManService::SORT_TOP_RATED,
    ];

    public function __construct(
        private readonly DeliveryManService $deliveryManService,
        private readonly ReviewService $reviewService,
        private readonly VendorService $vendorService,
        private readonly BusinessSettingService $businessSettingService,
    ) {}

    public function index(?Request $request, ?string $type = null): View|Collection|LengthAwarePaginator|null|callable|RedirectResponse
    {
        return $this->getAddView();
    }

    public function getAddView(): View
    {
        $telephoneCodes = TELEPHONE_CODES;

        return view('vendor-views.delivery-man.index', compact('telephoneCodes'));
    }

    public function add(AddRequest $request): JsonResponse
    {
        $data = $this->deliveryManData(request: $request);

        if ($this->deliveryManService->exists(params: ['phone' => $data['phone']])) {
            return response()->json(['errors' => translate('Phone number has already been taken')]);
        }

        $this->deliveryManService->create(
            data: $data + ['seller_id' => $this->sellerId()],
            image: $request->file('image'),
            identityImages: $request->file('identity_image') ?? [],
        );

        return response()->json(['message' => translate(':item added successfully', ['item' => translate('Delivery Man')])]);
    }

    public function getListView(Request $request): RedirectResponse|View
    {
        $searchValue = $request['search'];
        $deliveryMen = $this->deliveryManService->getList(
            searchValue: $searchValue,
            dataLimit: $this->paginationLimit(),
            relations: self::LIST_RELATIONS,
            withCount: self::LIST_WITH_COUNT,
            filters: $this->listFilters(sortBy: $request['sort_by']),
            sortBy: $this->sortKey(sortBy: $request['sort_by']),
        );

        return view('vendor-views.delivery-man.list', compact('deliveryMen', 'searchValue'));
    }

    public function getUpdateView(string|int $id): RedirectResponse|View
    {
        $telephoneCodes = TELEPHONE_CODES;
        $deliveryMan = $this->ownDeliveryMan(id: $id);

        return view('vendor-views.delivery-man.update-view', compact('deliveryMan', 'telephoneCodes'));
    }

    public function update(UpdateRequest $request, string|int $id): JsonResponse
    {
        $deliveryMan = $this->ownDeliveryMan(id: $id);

        if ($deliveryMan === null) {
            return response()->json(['errors' => translate('Delivery man not found')]);
        }

        $data = $this->deliveryManData(request: $request);
        $phoneOwner = $this->deliveryManService->findBy(params: [
            'phone' => $data['phone'],
            'country_code' => $data['country_code'],
        ]);

        if ($phoneOwner !== null && $phoneOwner->id !== $deliveryMan->id) {
            return response()->json(['errors' => translate('Phone number has already been taken')]);
        }

        $this->deliveryManService->update(
            id: $deliveryMan->id,
            data: $data,
            image: $request->file('image'),
            identityImages: $request->file('identity_image') ?? [],
        );

        return response()->json(['message' => translate(':item updated successfully', ['item' => translate('Delivery Man')])]);
    }

    public function updateStatus(Request $request, string|int $id): JsonResponse
    {
        $this->deliveryManService->updateWhere(
            params: ['id' => $id, 'seller_id' => $this->sellerId()],
            data: ['is_active' => (int) $request['status']],
        );

        return response()->json([], 200);
    }

    public function delete(string|int $id): RedirectResponse|View
    {
        $deliveryMan = $this->ownDeliveryMan(id: $id);

        $deliveryMan !== null && $this->deliveryManService->delete(id: $deliveryMan->id)
            ? ToastMagic::success(translate('Delivery-man removed').'!')
            : ToastMagic::error(translate('Delivery man not found'));

        return redirect()->back();
    }

    public function getRatingView(Request $request, string|int $id): RedirectResponse|View
    {
        $deliveryMan = $this->ownDeliveryMan(id: $id);

        if ($deliveryMan === null) {
            ToastMagic::warning(translate('Invalid review').'!');

            return redirect()->route('vendor.delivery-man.list');
        }

        $searchValue = $request['search'];
        $filters = [
            'from' => $request['from_date'],
            'to' => $request['to_date'],
            'rating' => $request['rating'],
        ];

        $reviews = $this->reviewService->getList(
            searchValue: $searchValue,
            dataLimit: 'all',
            relations: ['customer'],
            filters: $this->reviewFilters(deliverymanId: $id, filters: $filters),
            sortBy: ReviewService::SORT_RECENTLY_UPDATED,
        );

        return view('vendor-views.delivery-man.rating', [
            'deliveryMan' => $deliveryMan,
            'searchValue' => $searchValue,
            'filters' => $filters,
            'reviews' => $reviews->paginate($this->paginationLimit()),
            ...$this->ratingBreakdown(reviews: $reviews),
        ]);
    }

    public function exportList(Request $request): BinaryFileResponse
    {
        $deliveryMens = $this->deliveryManService->getList(
            searchValue: $request['search'],
            dataLimit: $this->paginationLimit(),
            relations: self::LIST_RELATIONS,
            withCount: self::LIST_WITH_COUNT,
            filters: $this->listFilters(sortBy: $request['sort_by']),
            sortBy: $this->sortKey(sortBy: $request['sort_by']),
        );

        return Excel::download(new DeliveryManListExport([
            'vendor' => $this->vendorService->find(id: $this->sellerId(), relations: ['shop']),
            'delivery_men' => $deliveryMens,
            'search' => $request['search'],
            'active' => $deliveryMens->where('is_active', DeliveryManService::STATUS_ACTIVE)->count(),
            'inactive' => $deliveryMens->where('is_active', DeliveryManService::STATUS_INACTIVE)->count(),
        ]), self::LIST_FILE_NAME);
    }

    private function sellerId(): int
    {
        return (int) auth('seller')->id();
    }

    private function ownDeliveryMan(string|int $id): ?Model
    {
        return $this->deliveryManService->findBy(params: ['id' => $id, 'seller_id' => $this->sellerId()]);
    }

    private function paginationLimit(): int
    {
        return (int) ($this->businessSettingService->getValueByType(type: WebConfigKey::PAGINATION_LIMIT) ?? DEFAULT_DATA_LIMIT);
    }

    private function sortKey(?string $sortBy): string
    {
        return self::SORT_KEYS[$sortBy] ?? DeliveryManService::SORT_LATEST;
    }

    /** @return array<string, mixed> */
    private function listFilters(?string $sortBy): array
    {
        $filters = ['seller_id' => $this->sellerId()];

        if ($this->sortKey(sortBy: $sortBy) === DeliveryManService::SORT_TOP_RATED) {
            $filters[DeliveryManService::FILTER_HAS_DELIVERED_ORDERS] = true;
        }

        return $filters;
    }

    /** @return array<string, mixed> */
    private function deliveryManData(Request $request): array
    {
        $countryCode = '+'.$request['country_code'];

        $data = [
            'f_name' => $request['f_name'],
            'l_name' => $request['l_name'],
            'address' => $request['address'],
            'email' => $request['email'],
            'country_code' => $countryCode,
            'phone' => str_replace($countryCode, '', $request['phone']),
            'full_phone' => $request['phone'],
            'identity_number' => $request['identity_number'],
            'identity_type' => $request['identity_type'],
        ];

        return empty($request['password']) ? $data : $data + ['password' => $request['password']];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function reviewFilters(string|int $deliverymanId, array $filters): array
    {
        return array_filter([
            'delivery_man_id' => $deliverymanId,
            'rating' => $filters['rating'],
            ReviewService::FILTER_DATE_FROM => $filters['from'],
            ReviewService::FILTER_DATE_TO => $filters['to'],
        ], static fn ($value) => $value !== null && $value !== '');
    }

    /** @return array<string, mixed> */
    private function ratingBreakdown(Collection $reviews): array
    {
        $countsByRating = $reviews->countBy('rating');

        return [
            'total' => $reviews->count(),
            'averageRatting' => (float) $reviews->avg('rating'),
            'one' => $countsByRating[1] ?? 0,
            'two' => $countsByRating[2] ?? 0,
            'three' => $countsByRating[3] ?? 0,
            'four' => $countsByRating[4] ?? 0,
            'five' => $countsByRating[5] ?? 0,
        ];
    }
}
