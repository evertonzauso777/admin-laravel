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
        Schema::create('document_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('document_type'); // 'RG', 'CPF', etc
            $table->string('image_path');
            $table->json('ocr_response')->nullable();
            $table->enum('validation_status', ['pending', 'validated', 'failed', 'error'])->default('pending');
            $table->json('extracted_data')->nullable(); // dados extraídos do documento
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('validation_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_validations');
    }
};
