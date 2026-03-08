<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('results', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('draw_id')->comment('Draw ID');
            $table->string('prize_code', 20)->comment('Prize code');
            $table->tinyInteger('index_in_prize')->default(0)->comment('Index position within prize');
            $table->string('number', 10)->comment('Result number');
            $table->boolean('confirmed_by_rule')->default(false)->comment('Confirmed by rule');
            $table->timestamps();
            $table->tinyInteger('status')->default(1)->comment('Status');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by');

            // Foreign key
            $table->foreign('draw_id')->references('id')->on('draws')->onDelete('cascade');

            // Indexes
            $table->index('draw_id');
            $table->index('prize_code');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('results');
    }
};
