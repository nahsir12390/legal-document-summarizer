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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('file_path');
            $table->longText('summary')->nullable();
            $table->json('key_points')->nullable(); // Store as JSON for easy array conversion
            $table->string('summary_length')->default('medium'); // short, medium, detailed
            $table->integer('file_size')->nullable(); // Store file size in bytes
            $table->string('mime_type')->nullable(); // Store file type
            $table->timestamps();
            
            // Add indexes for better performance
            $table->index('created_at');
            $table->index('file_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};