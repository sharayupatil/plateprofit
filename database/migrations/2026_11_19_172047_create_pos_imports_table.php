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
        Schema::create('pos_imports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('file_hash', 64);

            $table->string('status')->default('pending');

            $table->unsignedInteger('rows_processed')->default(0);
            $table->unsignedInteger('rows_successful')->default(0);
            $table->unsignedInteger('rows_failed')->default(0);

            $table->text('failure_reason')->nullable();

            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['restaurant_id', 'file_hash'],
                'pos_imports_restaurant_hash_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_imports');
    }
};
