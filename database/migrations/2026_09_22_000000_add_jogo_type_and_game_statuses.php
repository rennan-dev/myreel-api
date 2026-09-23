<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona o tipo "jogo" e os status de jogos
     * (nao_joguei / jogando / zerei / platinado).
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->enum('type', ['filme', 'serie', 'anime', 'jogo'])->change();
            $table->enum('status', [
                'nao_assisti', 'assistindo', 'assistido',
                'nao_joguei', 'jogando', 'zerei', 'platinado',
            ])->default('nao_assisti')->change();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->enum('type', ['filme', 'serie', 'anime'])->change();
            $table->enum('status', ['nao_assisti', 'assistindo', 'assistido'])
                ->default('nao_assisti')
                ->change();
        });
    }
};
