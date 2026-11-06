<?php

namespace App\Services\New;

use App\Models\RefundTransaction;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class RefundTransactionService extends BaseService
{
    public const PAYMENT_FOR_REFUND_REQUEST = 'Refund Request';

    public const PAID_TO_CUSTOMER = 'customer';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    public const TRANSACTION_TYPE_REFUND = 'Refund';

    public const SORT_NEWEST_ID = 'newest-id';

    public const FILTER_BUSINESS_MODULE = 'business_module';

    public function __construct(RefundTransaction $refundTransaction)
    {
        parent::__construct($refundTransaction);
    }

    public function getList(
        ?string $searchValue = null,
        int|string $dataLimit = DEFAULT_DATA_LIMIT,
        ?int $page = null,
        array $relations = [],
        array $withCount = [],
        array $filters = [],
        ?string $sortBy = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->query()
            ->with($relations)
            ->withCount($withCount)
            ->when($searchValue, fn (Builder $builder) => $builder->where(
                fn (Builder $inner) => $this->applySearch($inner, $searchValue),
            ));

        match ($sortBy) {
            self::SORT_NEWEST_ID => $query->orderByDesc('id'),
            default => null,
        };

        if (isset($filters[self::FILTER_BUSINESS_MODULE])) {
            $query->whereHas('order', fn (Builder $order) => $order->businessModule($filters[self::FILTER_BUSINESS_MODULE]));
        }

        unset($filters[self::FILTER_BUSINESS_MODULE]);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    private function applySearch(Builder $query, string $searchValue): void
    {
        $keywords = explode(' ', $searchValue);

        foreach ($keywords as $keyword) {
            $query->orWhere('order_id', 'like', "%{$keyword}%")
                ->orWhere('refund_id', 'like', "%{$keyword}%");
        }

        $query->orWhereHas('orderDetails.product', function (Builder $product) use ($keywords): void {
            foreach ($keywords as $keyword) {
                $product->where('name', 'like', "%{$keyword}%");
            }
        });
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create($data);
    }

    public function createForRefund(Model $refund, Model $order, ?string $paymentMethod): Model
    {
        return $this->query()->create([
            'order_id' => $refund->order_id,
            'payment_for' => self::PAYMENT_FOR_REFUND_REQUEST,
            'payer_id' => $order->seller_id,
            'payment_receiver_id' => $refund->customer_id,
            'paid_by' => $order->seller_is,
            'paid_to' => self::PAID_TO_CUSTOMER,
            'payment_method' => $paymentMethod,
            'payment_status' => empty($paymentMethod) ? self::PAYMENT_STATUS_UNPAID : self::PAYMENT_STATUS_PAID,
            'amount' => $refund->amount,
            'transaction_type' => self::TRANSACTION_TYPE_REFUND,
            'order_details_id' => $refund->order_details_id,
            'refund_id' => $refund->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPdfData(array $company, iterable $refundTransactions): array
    {
        $totalAmount = 0;

        foreach ($refundTransactions as $transaction) {
            $totalAmount += $transaction['amount'];
        }

        return [
            'total_amount' => $totalAmount,
            'company_phone' => $company['phone'],
            'company_email' => $company['email'],
            'company_name' => $company['name'],
            'company_web_logo' => $company['web_logo'],
        ];
    }

    public function count(array $filters = []): int
    {
        $query = $this->query();

        $this->applyFilters($query, $filters);

        return $query->count();
    }
}
