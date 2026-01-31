<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('status')->default('open')->index();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('cash_given', 10, 2)->nullable();
            $table->decimal('change_amount', 10, 2)->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['status', 'closed_at', 'cash_given', 'change_amount']);
        });
    }
};
