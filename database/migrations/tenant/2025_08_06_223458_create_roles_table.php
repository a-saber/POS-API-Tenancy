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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('sales')->default(false);
            $table->boolean('purchase')->default(false);
            $table->boolean('users')->default(false);
            $table->boolean('roles')->default(false);
            $table->boolean('settings')->default(false);
            $table->boolean('categories')->default(false);
            $table->boolean('products')->default(false); 
            $table->boolean('units')->default(false);
            $table->boolean('branches')->default(false);
            $table->boolean('customers')->default(false);
            $table->boolean('expense_categories')->default(false);
            $table->boolean('expenses')->default(false);
            $table->boolean('purchase_return')->default(false);
            $table->boolean('sale_return')->default(false);
            $table->boolean('suppliers')->default(false);
            $table->boolean('taxes')->default(false);
            $table->boolean('discounts')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
