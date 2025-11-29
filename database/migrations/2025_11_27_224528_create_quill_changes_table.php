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
        Schema::create('quill_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('quill_documents')->cascadeOnDelete();
            $table->uuid('change_uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->enum('change_type', ['insert', 'delete']);
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->json('delta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quill_changes');
    }
};
