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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')
            ->constrained()
            ->cascadeOnDelete();

            $table->string('external_id')->nullable();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->dateTime('first_order_at')->nullable();
            $table->dateTime('last_order_at')->nullable();

            $table->timestamps();

            $table->index(['restaurant_id', 'phone']);
            $table->index(['restaurant_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
