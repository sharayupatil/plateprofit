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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('pos_import_id')
                ->nullable()
                ->constrained('pos_imports')
                ->nullOnDelete();

            $table->string('source')->default('csv');
            $table->string('external_order_id');

            $table->dateTime('ordered_at');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            $table->string('payment_method')->nullable();
            
            $table->timestamps();

            $table->unique(
                ['restaurant_id', 'source', 'external_order_id'],
                'orders_restaurant_source_external_unique'
            );

            $table->index(['restaurant_id', 'ordered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
