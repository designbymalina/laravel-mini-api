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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('customer_name');
            $table->string('customer_email');

            $table->dateTime('slot_start');
            $table->dateTime('slot_end');

            $table->string('status')->default('active');

            $table->timestamps();

            $table->index('slot_start');
            $table->index('status');

            $table->unique([
                'slot_start',
                'status'
            ], 'booking_slot_status_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
