<?php

namespace App\Services\New;

use App\Models\ReviewReply;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;

class ReviewReplyService extends BaseService
{
    public const ADDED_BY_ADMIN = 'admin';

    public const ADDED_BY_SELLER = 'seller';

    public function __construct(ReviewReply $reviewReply)
    {
        parent::__construct($reviewReply);
    }

    public function findBy(array $params, array $relations = []): ?Model
    {
        return $this->query()->with($relations)->where($params)->first();
    }

    public function updateBy(array $params, array $data): ?Model
    {
        $reply = $this->query()->updateOrCreate($params, $data);

        return $reply->refresh();
    }

    public function delete(mixed $id): bool
    {
        $reply = $this->query()->find($id);

        return $reply !== null && (bool) $reply->delete();
    }
}
