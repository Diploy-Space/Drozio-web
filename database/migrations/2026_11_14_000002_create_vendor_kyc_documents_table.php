<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_kyc_documents')) {
            return;
        }

        Schema::create('vendor_kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_kyc_profile_id')->index();
            $table->string('document_type', 50)->index();
            $table->string('file_path');
            $table->string('storage', 20)->default('public');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status', 30)->default('pending'); // pending,approved,rejected
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['vendor_kyc_profile_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_kyc_documents');
    }
};
