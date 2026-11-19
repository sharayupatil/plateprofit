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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('external_id')->nullable();
            $table->string('name');
            $table->string('category')->nullable();

            $table->timestamps();

            $table->unique(
                ['restaurant_id', 'name'],
                'products_restaurant_name_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
