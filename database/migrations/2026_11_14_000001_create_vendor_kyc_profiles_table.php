<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_kyc_profiles')) {
            return;
        }

        Schema::create('vendor_kyc_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id')->unique();
            $table->unsignedBigInteger('shop_id')->nullable()->index();
            $table->string('business_type', 50)->nullable();
            $table->string('legal_name')->nullable();
            $table->string('trade_name')->nullable();
            $table->string('registration_type', 20)->nullable();
            $table->string('gst_number', 50)->nullable();
            $table->string('pan_number', 20)->nullable();
            $table->string('pan_holder_name')->nullable();
            $table->string('pan_verification_status', 30)->nullable();
            $table->string('business_address_line1')->nullable();
            $table->string('business_address_line2')->nullable();
            $table->string('business_city', 100)->nullable();
            $table->string('business_state', 100)->nullable();
            $table->string('business_pincode', 20)->nullable();
            $table->string('business_country', 100)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_ifsc', 30)->nullable();
            $table->string('bank_verification_status', 30)->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->text('correction_note')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_kyc_profiles');
    }
};
