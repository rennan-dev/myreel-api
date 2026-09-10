<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up(): void {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['filme', 'serie', 'anime']);

            //atributos base da classe Lista
            $table->string('name');
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();

            //atributos exclusivos de Filme
            $table->date('release_date')->nullable();
            $table->boolean('is_watched')->default(false);
            $table->date('watched_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('media');
    }
};
