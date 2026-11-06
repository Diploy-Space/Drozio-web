<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GenerateEntity extends Command
{
    protected $signature = 'generate:entity {entity}';

    protected $description = 'Generate the model, service and migration for the specified entity';

    public function handle(): void
    {
        $entity = $this->argument('entity');

        $this->writeModel($entity);
        $this->writeService($entity);

        $this->call('make:migration', ['name' => 'create_'.Str::plural(Str::snake($entity)).'_table']);
        $this->info("{$entity} model and service generated successfully.");
    }

    private function writeModel(string $entity): void
    {
        $directory = app_path('Models');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = "{$directory}/{$entity}.php";

        if (File::exists($path)) {
            return;
        }

        File::put($path, <<<PHP
        <?php

        namespace App\Models;

        use Illuminate\Database\Eloquent\Model;

        class {$entity} extends Model
        {
            protected \$fillable = [];
        }

        PHP);
    }

    private function writeService(string $entity): void
    {
        $directory = app_path('Services/New');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = "{$directory}/{$entity}Service.php";

        if (File::exists($path)) {
            return;
        }

        $variable = Str::camel($entity);

        File::put($path, <<<PHP
        <?php

        namespace App\Services\New;

        use App\Models\\{$entity};
        use App\Services\BaseService;
        use Illuminate\Contracts\Pagination\LengthAwarePaginator;
        use Illuminate\Database\Eloquent\Collection;
        use Illuminate\Database\Eloquent\Model;

        class {$entity}Service extends BaseService
        {
            public const SORT_LATEST = 'latest';

            public function __construct({$entity} \${$variable})
            {
                parent::__construct(\${$variable});
            }

            public function getList(
                ?string \$searchValue = null,
                int|string \$dataLimit = DEFAULT_DATA_LIMIT,
                ?int \$page = null,
                array \$relations = [],
                array \$withCount = [],
                array \$filters = [],
                ?string \$sortBy = null,
            ): Collection|LengthAwarePaginator {
                \$query = \$this->query()->with(\$relations)->withCount(\$withCount);

                match (\$sortBy) {
                    default => \$query->latest(),
                };

                \$this->applyFilters(\$query, \$filters);

                return \$dataLimit === 'all'
                    ? \$query->get()
                    : \$query->paginate(perPage: (int) \$dataLimit, page: \$page)
                        ->appends(\$this->listQueryString(\$searchValue, \$sortBy, \$filters));
            }

            public function find(mixed \$id, array \$relations = []): ?Model
            {
                return \$this->query()->with(\$relations)->find(\$id);
            }

            public function findBy(array \$params, array \$relations = []): ?Model
            {
                \$query = \$this->query()->with(\$relations);

                \$this->applyFilters(\$query, \$params);

                return \$query->first();
            }

            public function create(array \$data): ?Model
            {
                return \$this->query()->create(\$data);
            }

            public function update(mixed \$id, array \$data): ?Model
            {
                \$record = \$this->query()->find(\$id);

                if (\$record === null) {
                    return null;
                }

                \$record->update(\$data);

                return \$record->refresh();
            }

            public function delete(mixed \$id): bool
            {
                \$record = \$this->query()->find(\$id);

                return \$record !== null && (bool) \$record->delete();
            }
        }

        PHP);
    }
}
