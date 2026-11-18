<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_kyc_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_kyc_profiles', 'verification_mode')) {
                $table->string('verification_mode', 20)->nullable()->after('status'); // online, manual, hybrid
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'pan_dob')) {
                $table->string('pan_dob', 20)->nullable()->after('pan_holder_name');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'pan_verified_at')) {
                $table->timestamp('pan_verified_at')->nullable()->after('pan_verification_status');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'pan_verification_ref')) {
                $table->string('pan_verification_ref', 100)->nullable()->after('pan_verified_at');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'pan_verification_meta')) {
                $table->json('pan_verification_meta')->nullable()->after('pan_verification_ref');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'gst_verification_status')) {
                $table->string('gst_verification_status', 30)->nullable()->after('gst_number');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'gst_verified_at')) {
                $table->timestamp('gst_verified_at')->nullable()->after('gst_verification_status');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'gst_verification_ref')) {
                $table->string('gst_verification_ref', 100)->nullable()->after('gst_verified_at');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'gst_verification_meta')) {
                $table->json('gst_verification_meta')->nullable()->after('gst_verification_ref');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'bank_verified_at')) {
                $table->timestamp('bank_verified_at')->nullable()->after('bank_verification_status');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'bank_verification_ref')) {
                $table->string('bank_verification_ref', 100)->nullable()->after('bank_verified_at');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'bank_verification_meta')) {
                $table->json('bank_verification_meta')->nullable()->after('bank_verification_ref');
            }
            if (!Schema::hasColumn('vendor_kyc_profiles', 'online_consent_at')) {
                $table->timestamp('online_consent_at')->nullable()->after('correction_note');
            }
        });

        Schema::table('vendor_kyc_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_kyc_documents', 'verification_method')) {
                $table->string('verification_method', 20)->default('manual')->after('status'); // manual, online
            }
            if (!Schema::hasColumn('vendor_kyc_documents', 'verification_provider')) {
                $table->string('verification_provider', 50)->nullable()->after('verification_method');
            }
            if (!Schema::hasColumn('vendor_kyc_documents', 'verification_reference')) {
                $table->string('verification_reference', 100)->nullable()->after('verification_provider');
            }
            if (!Schema::hasColumn('vendor_kyc_documents', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_reference');
            }
            if (!Schema::hasColumn('vendor_kyc_documents', 'verification_meta')) {
                $table->json('verification_meta')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_kyc_profiles', function (Blueprint $table) {
            $cols = ['verification_mode','pan_dob','pan_verified_at','pan_verification_ref','pan_verification_meta','gst_verification_status','gst_verified_at','gst_verification_ref','gst_verification_meta','bank_verified_at','bank_verification_ref','bank_verification_meta','online_consent_at'];
            foreach ($cols as $col) { if (Schema::hasColumn('vendor_kyc_profiles', $col)) $table->dropColumn($col); }
        });
        Schema::table('vendor_kyc_documents', function (Blueprint $table) {
            $cols = ['verification_method','verification_provider','verification_reference','verified_at','verification_meta'];
            foreach ($cols as $col) { if (Schema::hasColumn('vendor_kyc_documents', $col)) $table->dropColumn($col); }
        });
    }
};
