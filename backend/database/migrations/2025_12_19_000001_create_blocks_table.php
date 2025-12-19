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
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();

            // Unique key used by frontend to request this block (e.g., "homepage.hero")
            $table->string('key')->unique()->index();

            // Block type: must be one of the allowed types (hero, richText, image, cta, featureGrid, html)
            $table->string('type')->index();

            // Publication status
            $table->enum('status', ['draft', 'published'])->default('draft')->index();
            $table->timestamp('published_at')->nullable();

            // Block-specific data payload (varies by type)
            $table->json('data');

            // Visibility control (null=public, "auth", "role:admin", etc.)
            $table->string('visibility')->nullable()->index();

            // Lock flag: prevents editing unless user has blocks.manage_locked permission
            $table->boolean('locked')->default(false);

            // Audit trail
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
