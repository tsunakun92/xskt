<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('ip_address', 50)->nullable()->comment('IP address');
            $table->string('country', 200)->nullable()->comment('Country');
            $table->unsignedBigInteger('user_id')->nullable()->comment('Id of user (users.id)');
            $table->text('method')->nullable()->comment('Method');
            $table->text('content')->nullable()->comment('Content');
            $table->text('response')->nullable()->comment('Response');
            $table->tinyInteger('status')->default(1)->comment('Status');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by');
            $table->timestamp('responsed_date')->nullable()->comment('Time response');
            $table->timestamps();

            $table->index('user_id', 'api_request_logs_user_id_index');
            $table->index('status', 'api_request_logs_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('api_request_logs');
    }
};
