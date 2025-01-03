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
        Schema::create('booking-service', function (Blueprint $table) {
           $table->id();
           $table->foreignId('bookings_id')->constrained()->onDelete('cascade');
           $table->foreignId('service_id')->constrained()->onDelete('cascade');
           $table->integer('jumlah')->default(1);
           $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
