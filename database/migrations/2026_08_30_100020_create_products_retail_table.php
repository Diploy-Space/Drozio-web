<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MOVED_COLUMNS = [
        'digital_product_type',
        'digital_file_ready',
        'digital_file_ready_storage_type',
        'digital_product_file_types',
        'digital_product_extensions',
        'preview_file',
        'preview_file_storage_type',
        'featured',
        'flash_deal',
        'color_image',
        'variation',
        'purchase_price',
    ];

    private const CHUNK_SIZE = 1000;

    public function up(): void
    {
        $this->createRetailTable();

        if (! Schema::hasColumn('products', 'variation')) {
            return;
        }

        $this->copyRetailColumns();
        $this->assertRetailCopyIsComplete();

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(self::MOVED_COLUMNS);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'variation')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->string('digital_product_type', 30)->nullable();
            $table->string('digital_file_ready', 191)->nullable();
            $table->string('digital_file_ready_storage_type', 10)->nullable()->default('public');
            $table->longText('digital_product_file_types')->nullable();
            $table->longText('digital_product_extensions')->nullable();
            $table->string('preview_file', 255)->nullable();
            $table->string('preview_file_storage_type', 255)->nullable()->default('public');
            $table->string('featured', 255)->nullable();
            $table->string('flash_deal', 255)->nullable();
            $table->text('color_image');
            $table->text('variation')->nullable();
            $table->decimal('purchase_price', 40, 20)->default(0);
        });

        if (! Schema::hasTable('products_retail')) {
            return;
        }

        $assignments = implode(', ', array_map(
            fn (string $column): string => "p.`{$column}` = pr.`{$column}`",
            self::MOVED_COLUMNS,
        ));

        DB::statement("UPDATE `products` p INNER JOIN `products_retail` pr ON pr.`product_id` = p.`id` SET {$assignments}");
    }

    private function createRetailTable(): void
    {
        if (Schema::hasTable('products_retail')) {
            return;
        }

        Schema::create('products_retail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique('products_retail_product_id_unique');
            $table->string('digital_product_type', 30)->nullable();
            $table->string('digital_file_ready', 191)->nullable();
            $table->string('digital_file_ready_storage_type', 10)->nullable()->default('public');
            $table->longText('digital_product_file_types')->nullable();
            $table->longText('digital_product_extensions')->nullable();
            $table->string('preview_file', 255)->nullable();
            $table->string('preview_file_storage_type', 255)->nullable()->default('public');
            $table->string('featured', 255)->nullable();
            $table->string('flash_deal', 255)->nullable();
            $table->text('color_image')->nullable();
            $table->text('variation')->nullable();
            $table->decimal('purchase_price', 40, 20)->default(0);
            $table->timestamps();
        });
    }

    private function copyRetailColumns(): void
    {
        $lastId = 0;

        while (true) {
            $products = DB::table('products')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(self::CHUNK_SIZE)
                ->get(array_merge(['id', 'created_at', 'updated_at'], self::MOVED_COLUMNS));

            if ($products->isEmpty()) {
                break;
            }

            $lastId = $products->last()->id;

            $rows = $products->map(function (object $product): array {
                $row = ['product_id' => $product->id];

                foreach (self::MOVED_COLUMNS as $column) {
                    $row[$column] = $product->{$column};
                }

                return $row + ['created_at' => $product->created_at, 'updated_at' => $product->updated_at];
            })->all();

            DB::table('products_retail')->upsert(
                $rows,
                ['product_id'],
                array_merge(self::MOVED_COLUMNS, ['updated_at']),
            );
        }
    }

    private function assertRetailCopyIsComplete(): void
    {
        $missing = DB::table('products as p')
            ->leftJoin('products_retail as pr', 'pr.product_id', '=', 'p.id')
            ->whereNull('pr.product_id')
            ->count();

        if ($missing > 0) {
            throw new RuntimeException("Refusing to drop the retail columns: {$missing} product(s) have no products_retail row.");
        }

        $orphans = DB::table('products_retail as pr')
            ->leftJoin('products as p', 'p.id', '=', 'pr.product_id')
            ->whereNull('p.id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException("Refusing to drop the retail columns: {$orphans} products_retail row(s) reference a product that does not exist.");
        }

        foreach (self::MOVED_COLUMNS as $column) {
            $mismatched = DB::table('products as p')
                ->join('products_retail as pr', 'pr.product_id', '=', 'p.id')
                ->whereRaw("NOT (p.`{$column}` <=> pr.`{$column}`)")
                ->count();

            if ($mismatched > 0) {
                throw new RuntimeException("Refusing to drop the retail columns: {$mismatched} product(s) disagree with products_retail on [{$column}].");
            }
        }
    }
};
