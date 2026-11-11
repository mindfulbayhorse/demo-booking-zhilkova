<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table) {

                $table->unique(['starts_at', 'ends_at', 'room_id'], 'unique_booking');

            });

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bookings')) {

            if (Schema::hasIndex('bookings', ['starts_at', 'ends_at', 'room_id'], 'unique')) {

                Schema::table('bookings', function (Blueprint $table) {
                    $table->dropIndex('unique_booking');
                });

            }
        }
    }
};
