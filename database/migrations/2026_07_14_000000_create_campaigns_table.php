<?php

use App\Enums\BusinessModule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('campaigns')) {
            Schema::create('campaigns', function (Blueprint $table) {
                $table->id();
                $table->string('business_module', 20)->default(BusinessModule::WHOLESALE)->index();
                $table->string('title');
                $table->string('slug')->nullable()->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->decimal('min_discount', 8, 2)->default(0);
                $table->decimal('max_discount', 8, 2)->default(0);
                $table->dateTime('start_date');
                $table->dateTime('end_date');
                $table->time('daily_start_time')->nullable();
                $table->time('daily_end_time')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('campaigns', 'business_module')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->string('business_module', 20)->default(BusinessModule::WHOLESALE)->index()->after('id');
            });
        }

        if (! Schema::hasColumn('campaigns', 'slug')) {
            Schema::table('campaigns', function (Blueprint $table): void {
                $table->string('slug')->nullable()->after('title');
                $table->unique('slug');
            });

            $this->backfillSlugs();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }

    private function backfillSlugs(): void
    {
        $taken = [];

        DB::table('campaigns')->orderBy('id')->select(['id', 'title'])->each(function (object $campaign) use (&$taken): void {
            $base = Str::slug($campaign->title) ?: 'campaign';
            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $taken, true)) {
                $slug = $base.'-'.str_pad((string) $suffix, 2, '0', STR_PAD_LEFT);
                $suffix++;
            }

            $taken[] = $slug;

            DB::table('campaigns')->where('id', $campaign->id)->update(['slug' => $slug]);
        });
    }
};
