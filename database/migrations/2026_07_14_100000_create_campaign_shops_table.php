<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->unsignedBigInteger('seller_id')->index();
            $table->string('status', 20)->default('pending');
            $table->text('deny_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'seller_id']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_shops');
    }
};
