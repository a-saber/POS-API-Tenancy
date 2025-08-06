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
        Schema::table('sales', function (Blueprint $table) {
            // Add sales_return_id foreign key nullable
            $table->foreignId('sales_return_id')->nullable()->constrained('sales_returns')->restrictOnDelete();
        });

        Schema::table('purchases', function (Blueprint $table) {
            // Add purchase_return_id foreign key nullable
            $table->foreignId('purchase_return_id')->nullable()->constrained('purchase_returns')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['sales_return_id']);
            $table->dropColumn('sales_return_id');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->dropColumn('purchase_return_id');
        });

    }
};
