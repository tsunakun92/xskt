<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void {
        Schema::create('otp_managements', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->comment('1: register, 2: forgot_password');
            $table->string('email')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('otp_code', 6);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->tinyInteger('platform')->nullable();
            $table->string('version')->nullable();
            $table->timestamps();

            $table->index(['email', 'type', 'otp_code']);
            $table->index('expires_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {
        Schema::dropIfExists('otp_managements');
    }
};
