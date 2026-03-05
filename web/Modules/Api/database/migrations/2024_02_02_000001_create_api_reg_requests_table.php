<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('api_reg_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email', 255)->nullable()->comment('Email');
            $table->text('password')->nullable()->comment('Password hash');
            $table->tinyInteger('status')->default(1)->comment('Status');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('api_reg_requests');
    }
};
