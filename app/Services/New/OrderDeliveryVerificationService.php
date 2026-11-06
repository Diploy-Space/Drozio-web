<?php

namespace App\Services\New;

use App\Models\OrderDeliveryVerification;
use App\Services\BaseService;
use App\Traits\FileManagerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class OrderDeliveryVerificationService extends BaseService
{
    use FileManagerTrait {
        update as private updateFile;
        delete as private deleteFile;
    }

    public const IMAGE_DIRECTORY = 'delivery-man/verification-image/';

    public function __construct(OrderDeliveryVerification $orderDeliveryVerification)
    {
        parent::__construct($orderDeliveryVerification);
    }

    public function create(array $data, ?UploadedFile $image = null): ?Model
    {
        return $this->query()->create([
            'order_id' => $data['order_id'],
            'image' => $image instanceof UploadedFile
                ? $this->upload(dir: self::IMAGE_DIRECTORY, format: 'webp', image: $image)
                : ($data['image'] ?? null),
        ]);
    }
}
