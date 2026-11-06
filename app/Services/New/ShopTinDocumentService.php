<?php

namespace App\Services\New;

use App\Models\ShopTinDocument;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ShopTinDocumentService extends BaseService
{
    use FileManagerTrait {
        update as updateFile;
        delete as deleteFile;
    }

    public const DOCUMENT_DIRECTORY = 'shop/documents/';

    public function __construct(ShopTinDocument $shopTinDocument)
    {
        parent::__construct($shopTinDocument);
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
        $query = $this->query()->with($relations)->withCount($withCount);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page);
    }

    public function find(mixed $id, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->find($id);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        $query = $this->query()->with($relations);

        $this->applyFilters($query, $params);

        return $query->first();
    }

    public function create(array $data, ?UploadedFile $certificate = null): ?Model
    {
        return $this->query()->create([
            'shop_id' => $data['shop_id'],
            'business_module' => $data['business_module'],
            'tax_identification_number' => $data['tax_identification_number'] ?? null,
            'tin_expire_date' => $this->expireDate($data['tin_expire_date'] ?? null),
            ...$this->uploadedCertificateData(certificate: $certificate),
        ]);
    }

    public function update(mixed $id, array $data, ?UploadedFile $certificate = null): ?Model
    {
        $document = $this->query()->find($id);

        if ($document === null) {
            return null;
        }

        $columns = [];

        if (array_key_exists('tax_identification_number', $data)) {
            $columns['tax_identification_number'] = $data['tax_identification_number'];
        }

        if (array_key_exists('tin_expire_date', $data)) {
            $columns['tin_expire_date'] = $this->expireDate($data['tin_expire_date']);
        }

        $document->fill([
            ...$columns,
            ...$this->uploadedCertificateData(certificate: $certificate, document: $document),
            ...$this->removedCertificateData(document: $document, isRemoved: $certificate === null && ! empty($data['remove_certificate'])),
        ])->save();

        return $document;
    }

    public function delete(mixed $id): bool
    {
        $document = $this->query()->find($id);

        if ($document === null) {
            return false;
        }

        if ($document->tin_certificate) {
            $this->deleteFile(filePath: self::DOCUMENT_DIRECTORY.$document->tin_certificate);
        }

        return (bool) $document->delete();
    }

    /**
     * @param  array<string, array<string, string|null>>  $documents
     * @param  array<string, UploadedFile|null>  $certificates
     */
    public function syncForShop(int $shopId, array $documents, array $certificates = []): void
    {
        DB::transaction(function () use ($shopId, $documents, $certificates): void {
            foreach ($documents as $businessModule => $document) {
                $certificate = $certificates[$businessModule] ?? null;
                $existing = $this->findBy(params: ['shop_id' => $shopId, 'business_module' => $businessModule]);

                if ($existing === null && $this->isBlankDocument(document: $document, certificate: $certificate)) {
                    continue;
                }

                $existing === null
                    ? $this->create(
                        data: ['shop_id' => $shopId, 'business_module' => $businessModule, ...$document],
                        certificate: $certificate,
                    )
                    : $this->update(id: $existing->id, data: $document, certificate: $certificate);
            }
        });
    }

    /** @return array<string, ShopTinDocument> */
    public function getByShop(int $shopId): array
    {
        return $this->getList(dataLimit: 'all', filters: ['shop_id' => $shopId])
            ->keyBy('business_module')
            ->all();
    }

    private function isBlankDocument(array $document, ?UploadedFile $certificate): bool
    {
        return $certificate === null
            && empty($document['tax_identification_number'])
            && empty($document['tin_expire_date']);
    }

    private function expireDate(?string $expireDate): ?Carbon
    {
        return empty($expireDate) ? null : Carbon::parse($expireDate);
    }

    /** @return array<string, null> */
    private function removedCertificateData(Model $document, bool $isRemoved): array
    {
        if (! $isRemoved || empty($document->tin_certificate)) {
            return [];
        }

        $this->deleteFile(filePath: self::DOCUMENT_DIRECTORY.$document->tin_certificate);

        return [
            'tin_certificate' => null,
            'tin_certificate_storage_type' => null,
        ];
    }

    /** @return array<string, string> */
    private function uploadedCertificateData(?UploadedFile $certificate, ?Model $document = null): array
    {
        if ($certificate === null) {
            return [];
        }

        return [
            'tin_certificate' => $document?->tin_certificate
                ? $this->updateFile(
                    dir: self::DOCUMENT_DIRECTORY,
                    oldImage: $document->tin_certificate,
                    format: $certificate->getClientOriginalExtension(),
                    image: $certificate,
                    fileType: 'file',
                )
                : $this->fileUpload(
                    dir: self::DOCUMENT_DIRECTORY,
                    format: $certificate->getClientOriginalExtension(),
                    file: $certificate,
                ),
            'tin_certificate_storage_type' => $this->storageDisk(),
        ];
    }
}
