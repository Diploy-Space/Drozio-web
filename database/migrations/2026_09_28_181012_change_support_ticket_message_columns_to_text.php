<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
            $table->text('reply')->nullable()->change();
        });

        Schema::table('support_ticket_convs', function (Blueprint $table) {
            $table->text('customer_message')->nullable()->change();
            $table->text('admin_message')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->change();
            $table->string('reply', 255)->nullable()->change();
        });

        Schema::table('support_ticket_convs', function (Blueprint $table) {
            $table->string('customer_message', 191)->nullable()->change();
            $table->string('admin_message', 191)->nullable()->change();
        });
    }
};
