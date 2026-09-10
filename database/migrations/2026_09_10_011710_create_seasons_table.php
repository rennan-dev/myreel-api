<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->integer('season_number');
            $table->date('release_date')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('episodes_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('seasons');
    }
};
