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
        Schema::create('quill_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('quill_documents')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->unsignedInteger('anchor_index');
            $table->unsignedInteger('anchor_length');
            $table->text('body');
            $table->enum('status', ['active', 'resolved', 'closed'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quill_comments');
    }
};
