<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_tin_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shop_id');
            $table->string('business_module', 20);
            $table->string('tax_identification_number', 255)->nullable();
            $table->date('tin_expire_date')->nullable();
            $table->string('tin_certificate', 255)->nullable();
            $table->string('tin_certificate_storage_type', 20)->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'business_module']);
        });

        $this->backfillRetailDocuments();
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_tin_documents');
    }

    private function backfillRetailDocuments(): void
    {
        DB::table('shops')
            ->select('id', 'tax_identification_number', 'tin_expire_date', 'tin_certificate', 'tin_certificate_storage_type')
            ->where(function ($query): void {
                $query->whereNotNull('tax_identification_number')
                    ->orWhereNotNull('tin_expire_date')
                    ->orWhereNotNull('tin_certificate');
            })
            ->orderBy('id')
            ->chunk(200, function ($shops): void {
                DB::table('shop_tin_documents')->insert($shops->map(fn ($shop): array => [
                    'shop_id' => $shop->id,
                    'business_module' => BusinessModule::RETAIL,
                    'tax_identification_number' => $shop->tax_identification_number,
                    'tin_expire_date' => $shop->tin_expire_date,
                    'tin_certificate' => $shop->tin_certificate,
                    'tin_certificate_storage_type' => $shop->tin_certificate_storage_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all());
            });
    }
};
