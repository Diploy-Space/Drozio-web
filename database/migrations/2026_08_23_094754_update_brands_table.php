<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SCOPE_INDEX = 'brands_scope_index';

    private const GLOBAL_SLUG_INDEX = 'brands_slug_unique';

    private const SCOPED_SLUG_INDEX = 'brands_scope_slug_unique';

    public function up(): void
    {
        if (! Schema::hasColumn('brands', 'business_module')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->string('business_module', 20)->default(BusinessModule::RETAIL)->after('id');
            });
        }

        if (! $this->indexExists(self::SCOPE_INDEX)) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->index(['business_module', 'status'], self::SCOPE_INDEX);
            });
        }

        if ($this->indexExists(self::GLOBAL_SLUG_INDEX)) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->dropUnique(self::GLOBAL_SLUG_INDEX);
            });
        }

        if (! $this->indexExists(self::SCOPED_SLUG_INDEX)) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->unique(['business_module', 'slug'], self::SCOPED_SLUG_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('brands', 'business_module') || $this->hasSlugSharedAcrossModules()) {
            return;
        }

        Schema::table('brands', function (Blueprint $table): void {
            if ($this->indexExists(self::SCOPED_SLUG_INDEX)) {
                $table->dropUnique(self::SCOPED_SLUG_INDEX);
            }

            if ($this->indexExists(self::SCOPE_INDEX)) {
                $table->dropIndex(self::SCOPE_INDEX);
            }
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn('business_module');

            if (! $this->indexExists(self::GLOBAL_SLUG_INDEX)) {
                $table->unique('slug', self::GLOBAL_SLUG_INDEX);
            }
        });
    }

    private function hasSlugSharedAcrossModules(): bool
    {
        return DB::table('brands')
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }

    private function indexExists(string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'brands')
            ->where('index_name', $index)
            ->exists();
    }
};
