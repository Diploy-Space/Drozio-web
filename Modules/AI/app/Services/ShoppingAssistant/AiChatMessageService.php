<?php

namespace Modules\AI\app\Services\ShoppingAssistant;

use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\AI\app\Models\AiChatMessage;

class AiChatMessageService extends BaseService
{
    public const FILTER_SESSION_ID = 'session_id';

    public const FILTER_WITHOUT_TOOL_CALLS = 'without_tool_calls';

    public const ROLE_USER = 'user';

    public const ROLE_ASSISTANT = 'assistant';

    private const CONVERSATION_ROLES = [self::ROLE_USER, self::ROLE_ASSISTANT];

    public function __construct(AiChatMessage $aiChatMessage)
    {
        parent::__construct($aiChatMessage);
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
            ->when($searchValue, fn (Builder $builder) => $builder->where('content', 'like', "%{$searchValue}%"))
            ->orderBy('created_at');

        if (! empty($filters[self::FILTER_WITHOUT_TOOL_CALLS])) {
            $query->whereNull('meta->tool_calls');
        }

        unset($filters[self::FILTER_WITHOUT_TOOL_CALLS]);

        $this->applyFilters($query, $filters);

        return $dataLimit === 'all'
            ? $query->get()
            : $query->paginate(perPage: (int) $dataLimit, page: $page)
                ->appends($this->listQueryString($searchValue, $sortBy, $filters));
    }

    public function create(array $data): ?Model
    {
        return $this->query()->create([
            'session_id' => $data['session_id'],
            'role' => $data['role'],
            'content' => $data['content'],
            'meta' => ($data['meta'] ?? []) ?: null,
        ]);
    }

    public function getLatestConversationMessages(int $sessionId, int $count): Collection
    {
        return $this->getLatestMessages(sessionId: $sessionId, count: $count)
            ->filter(fn (AiChatMessage $message) => in_array($message->role, self::CONVERSATION_ROLES, strict: true))
            ->filter(fn (AiChatMessage $message) => empty($message->meta['tool_calls']))
            ->values();
    }

    public function getAssistantMessagesAmongLatest(int $sessionId, int $count): Collection
    {
        return $this->getLatestMessages(sessionId: $sessionId, count: $count)
            ->filter(fn (AiChatMessage $message) => $message->role === self::ROLE_ASSISTANT)
            ->values();
    }

    private function getLatestMessages(int $sessionId, int $count): Collection
    {
        return $this->query()
            ->where('session_id', $sessionId)
            ->orderByDesc('created_at')
            ->limit($count)
            ->get()
            ->sortBy('created_at')
            ->values();
    }
}
