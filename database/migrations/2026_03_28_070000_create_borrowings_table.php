<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('borrower_name');
            $table->timestamp('borrowed_at')->useCurrent();
            $table->timestamp('due_date')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('status')->default('borrowed');
            $table->string('created_by')->default('admin');
            $table->timestamps();

            $table->index(['book_id', 'status']);
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
