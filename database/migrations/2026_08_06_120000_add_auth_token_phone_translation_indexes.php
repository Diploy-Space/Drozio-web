<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive indexes — follow-up to docs/QUERY_AUDIT.md, closing the gaps left after
 * 2026_07_26_120000_add_missing_indexes_from_query_audit.php (which already covered
 * sellers.auth_token, users.referral_code/referred_by, carts, reviews, wishlists, …).
 *
 * Remaining hot-path gaps addressed here:
 *  - delivery_men.auth_token — delivery-man app auth runs on EVERY API request
 *    (DeliveryManAuth middleware: where(auth_token)->first()); table was PRIMARY-key
 *    only → full scan per request. QUERY_AUDIT #2.
 *  - users.phone — login / OTP / referral phone lookups (14 call sites); users had
 *    only email_unique + PK. QUERY_AUDIT #10.
 *  - translations(translationable_type, translationable_id, locale) — polymorphic
 *    eager-load filters on all three; the dump indexes translationable_id and locale
 *    only as SEPARATE single-column keys, so the morph lookup that fires on nearly
 *    every localized product/category render can't use one composite. Column order =
 *    Laravel's morph query order (type + id equality, then locale). QUERY_AUDIT #12.
 *
 * Additive only. Each add is guarded so re-runs / varying dumps don't fail.
 * Runs AFTER installation/backup/database.sql is imported.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('delivery_men', 'auth_token') && !$this->indexExists('delivery_men', 'idx_delivery_men_auth_token')) {
            Schema::table('delivery_men', function (Blueprint $table) {
                $table->index('auth_token', 'idx_delivery_men_auth_token');
            });
        }

        if (Schema::hasColumn('users', 'phone') && !$this->indexExists('users', 'idx_users_phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('phone', 'idx_users_phone');
            });
        }

        if (Schema::hasTable('translations') && !$this->indexExists('translations', 'idx_translations_type_id_locale')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->index(['translationable_type', 'translationable_id', 'locale'], 'idx_translations_type_id_locale');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('translations', 'idx_translations_type_id_locale')) {
            Schema::table('translations', function (Blueprint $table) {
                $table->dropIndex('idx_translations_type_id_locale');
            });
        }
        if ($this->indexExists('users', 'idx_users_phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_phone');
            });
        }
        if ($this->indexExists('delivery_men', 'idx_delivery_men_auth_token')) {
            Schema::table('delivery_men', function (Blueprint $table) {
                $table->dropIndex('idx_delivery_men_auth_token');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }
};
