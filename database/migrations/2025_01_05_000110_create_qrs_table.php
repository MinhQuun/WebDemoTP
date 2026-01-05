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
        Schema::create('qrs', function (Blueprint $table) {
            $table->id();
            $table->string('qr_code')->unique();
            $table->string('order_code')->index();
            $table->string('product_code')->index();
            $table->text('product_name');
            $table->string('machine')->nullable();
            $table->string('created_by');
            $table->foreignId('current_stage_id')
                ->nullable()
                ->constrained('stages')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrs');
    }
};
