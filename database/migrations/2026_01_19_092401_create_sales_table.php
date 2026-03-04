<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('location_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('total', 10, 2)->default(0);

            $table->string('status')->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->decimal('cash_given', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();

            $table->timestamps();

            $table->index(['location_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });

    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
