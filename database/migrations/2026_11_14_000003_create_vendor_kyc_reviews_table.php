<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_kyc_reviews')) {
            return;
        }

        Schema::create('vendor_kyc_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_kyc_profile_id')->index();
            $table->string('action', 50); // submitted,resubmitted,approved,rejected,correction_requested,under_review
            $table->string('actor_type', 30)->nullable(); // vendor,admin,system
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_kyc_reviews');
    }
};
