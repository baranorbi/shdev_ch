<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'scheduled_at')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->dateTime('scheduled_at')->nullable()->after('email');
        });

        DB::statement("UPDATE bookings SET scheduled_at = CONCAT(date, ' ', hour)");

        Schema::table('bookings', function (Blueprint $table) {
            $table->unique('scheduled_at', 'bookings_scheduled_at_unique');
            $table->dropColumn(['date', 'hour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('bookings', 'scheduled_at')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->date('date')->nullable()->after('email');
            $table->time('hour')->nullable()->after('date');
        });

        DB::statement("UPDATE bookings SET date = DATE(scheduled_at), hour = TIME(scheduled_at)");

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropUnique('bookings_scheduled_at_unique');
            $table->dropColumn('scheduled_at');
        });
    }
};
