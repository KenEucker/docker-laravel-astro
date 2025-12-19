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
        Schema::create('user_notes', function (Blueprint $table) {
            $table->id();

            // Owner of the note
            $table->unsignedBigInteger('user_id');

            // Note content
            $table->string('title');
            $table->text('body'); // Markdown text

            // Status: active or archived
            $table->enum('status', ['active', 'archived'])->default('active')->index();

            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Index for efficient user queries
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notes');
    }
};
