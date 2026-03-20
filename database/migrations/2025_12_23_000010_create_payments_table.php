<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['work_order', 'sale']);
            $table->unsignedBigInteger('source_id');
            $table->bigInteger('total');
            $table->enum('metode', ['cash', 'transfer', 'qris'])->default('cash');
            $table->foreignId('kasir_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
