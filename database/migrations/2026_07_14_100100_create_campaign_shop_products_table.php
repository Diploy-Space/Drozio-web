<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_shop_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_shop_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->decimal('discount', 8, 2)->default(0);
            $table->timestamps();

            $table->unique(['campaign_shop_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_shop_products');
    }
};
