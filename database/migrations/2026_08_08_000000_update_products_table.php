<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FULLTEXT_INDEX = 'ft_products_name';

    private const SCOPE_STATUS_INDEX = 'products_scope_status_index';

    private const SCOPE_OWNER_INDEX = 'products_scope_owner_index';

    public function up(): void
    {
        $this->addFulltextIndex();
        $this->addAggregateColumns();
        $this->addBusinessModule();
        $this->addVideoFile();
        $this->addListIndexes();
    }

    public function down(): void
    {
        foreach (array_reverse(array_keys($this->listIndexes())) as $name) {
            if ($this->indexExists($name)) {
                DB::statement("ALTER TABLE `products` DROP INDEX `{$name}`");
            }
        }

        foreach ([self::SCOPE_OWNER_INDEX, self::SCOPE_STATUS_INDEX, self::FULLTEXT_INDEX] as $name) {
            if ($this->indexExists($name)) {
                DB::statement("ALTER TABLE `products` DROP INDEX `{$name}`");
            }
        }

        foreach (['video_file', 'video_file_storage_type', 'business_module', 'rating_avg', 'rating_count', 'order_count'] as $column) {
            if (Schema::hasColumn('products', $column)) {
                Schema::table('products', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    private function addFulltextIndex(): void
    {
        if (! $this->indexExists(self::FULLTEXT_INDEX)) {
            DB::statement('ALTER TABLE `products` ADD FULLTEXT `'.self::FULLTEXT_INDEX.'` (`name`)');
        }
    }

    private function addAggregateColumns(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 2)->default(0)->after('current_stock');
            }
            if (! Schema::hasColumn('products', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            }
            if (! Schema::hasColumn('products', 'order_count')) {
                $table->unsignedInteger('order_count')->default(0)->after('rating_count');
            }
        });
    }

    private function addBusinessModule(): void
    {
        if (! Schema::hasColumn('products', 'business_module')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('id');
            });
        }

        Schema::table('products', function (Blueprint $table) {
            if (! $this->indexExists(self::SCOPE_STATUS_INDEX)) {
                $table->index(['business_module', 'status'], self::SCOPE_STATUS_INDEX);
            }
            if (! $this->indexExists(self::SCOPE_OWNER_INDEX)) {
                $table->index(['business_module', 'added_by', 'user_id'], self::SCOPE_OWNER_INDEX);
            }
        });
    }

    private function addVideoFile(): void
    {
        if (Schema::hasColumn('products', 'video_file')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('video_file', 255)->nullable()->after('video_url');
            $table->string('video_file_storage_type', 10)->nullable()->default('public')->after('video_file');
        });
    }

    private function addListIndexes(): void
    {
        foreach ($this->listIndexes() as $name => $columns) {
            if (! $this->indexExists($name)) {
                DB::statement("ALTER TABLE `products` ADD INDEX `{$name}` {$columns}");
            }
        }
    }

    private function listIndexes(): array
    {
        return [
            'idx_products_status_created' => '(`status`, `created_at`)',
            'idx_products_status_unit_price' => '(`status`, `unit_price`)',
            'idx_products_status_category_created' => '(`status`, `category_id`, `created_at`)',
            'idx_products_status_category_price' => '(`status`, `category_id`, `unit_price`)',
            'idx_products_status_rating_avg' => '(`status`, `rating_avg`)',
            'idx_products_status_order_count' => '(`status`, `order_count`)',
            'idx_products_status_rating_count' => '(`status`, `rating_count`)',
            'idx_products_module_added_by_id' => '(`business_module`, `added_by`, `id`)',
            'idx_products_module_user_id' => '(`business_module`, `user_id`, `id`)',
            'idx_products_module_request_status_id' => '(`business_module`, `request_status`, `id`)',
        ];
    }

    private function indexExists(string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'products')
            ->where('index_name', $index)
            ->exists();
    }
};
