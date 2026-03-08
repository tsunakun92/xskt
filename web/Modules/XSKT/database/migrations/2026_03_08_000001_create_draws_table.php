<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('draws', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('region', ['MB', 'MT', 'MN'])->comment('Region: MB (Miền Bắc), MT (Miền Trung), MN (Miền Nam)');
            $table->string('province_code', 10)->nullable()->comment('Province code');
            $table->string('station_code', 10)->nullable()->comment('Station code');
            $table->date('draw_date')->comment('Draw date');

            $table->timestamp('confirmed_at')->nullable()->comment('Confirmed at timestamp');
            $table->timestamps();
            $table->tinyInteger('status')->default(1)->comment('Status');
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by');

            // Indexes
            $table->index('region');
            $table->index('province_code');
            $table->index('station_code');
            $table->index('draw_date');

            $table->index('created_at');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('draws');
    }
};
