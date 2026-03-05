<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('file_masters', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->string('file_name')->comment('File name');
            $table->string('file_path')->comment('File path');
            $table->integer('file_type')->comment('File type (1:image, 2:PDF, 3:Excel, 4:Word, ...)');
            $table->unsignedBigInteger('file_size')->nullable()->comment('File size (bytes)');
            $table->string('belong_type')->comment('Belong table (e.g. hr_profiles, crm_sections, crm_room_types)');
            $table->unsignedBigInteger('belong_id')->comment('Belong ID');
            $table->string('relation_type')->nullable()->comment('Relation type (attachment, thumbnail, etc.)');
            $table->integer('display_order')->default(0)->comment('Display order for sorting');
            $table->string('alt_text', 255)->nullable()->comment('Alt text for images (SEO/accessibility)');
            $table->string('title', 255)->nullable()->comment('Image title/caption');
            $table->tinyInteger('status')->default(1)->comment('Status (1:active, 0:inactive)');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created By');
            $table->timestamps();

            $table->engine    = 'INNODB';
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';
        });

        Schema::table('file_masters', function (Blueprint $table) {
            $table->index(['belong_type', 'belong_id'], 'file_masters_belong_index');
            $table->index('relation_type');
            $table->index('status');
            $table->index('created_by');
            $table->index(['belong_type', 'belong_id', 'display_order'], 'file_masters_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('file_masters');
    }
};
