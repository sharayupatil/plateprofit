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
        Schema::create('pos_import_errors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pos_import_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('row_number')->nullable();
            $table->json('row_data')->nullable();
            $table->text('message');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_import_errors');
    }
};
