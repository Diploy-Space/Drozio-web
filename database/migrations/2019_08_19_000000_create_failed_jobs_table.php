<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recreates the canonical failed_jobs migration file, which was missing from the
 * repo even though the migrations table records it as run and the seeded dump
 * already ships the table.
 *
 * Filename matches the recorded batch-1 entry
 * (2019_08_19_000000_create_failed_jobs_table) so `php artisan migrate` treats it
 * as already applied on seeded databases and only creates the table on a truly
 * fresh one. The schema mirrors the dump exactly (no `uuid` column — the
 * config/queue.php `failed` provider is the plain `database` driver, which does
 * not need it). The hasTable guard is belt-and-braces for environments where the
 * table exists but the migration record was lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('failed_jobs')) {
            return;
        }

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
