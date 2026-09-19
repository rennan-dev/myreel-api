<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Substitui a lógica de "assistido" por um status único por mídia
     * (nao_assisti / assistindo / assistido) e remove temporadas/episódios.
     */
    public function up(): void
    {
        // 1. Adiciona a nova coluna de status
        Schema::table('media', function (Blueprint $table) {
            $table->enum('status', ['nao_assisti', 'assistindo', 'assistido'])
                ->default('nao_assisti')
                ->after('release_date');
        });

        // 2. Migra os dados antigos: quem estava marcado como assistido vira 'assistido'
        DB::table('media')->update([
            'status' => DB::raw("CASE WHEN is_watched = 1 THEN 'assistido' ELSE 'nao_assisti' END"),
        ]);

        // 3. Remove as colunas antigas de "assistido"
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['is_watched', 'watched_at']);
        });

        // 4. Remove a lógica de temporadas/episódios
        Schema::dropIfExists('episodes');
        Schema::dropIfExists('seasons');
    }

    public function down(): void
    {
        // restaura as tabelas de temporadas/episódios
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            $table->integer('season_number');
            $table->date('release_date')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('episodes_count')->default(0);
            $table->timestamps();
        });

        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->integer('episode_number');
            $table->boolean('is_watched')->default(false);
            $table->date('watched_at')->nullable();
            $table->timestamps();
        });

        // restaura as colunas antigas de "assistido" a partir do status
        Schema::table('media', function (Blueprint $table) {
            $table->boolean('is_watched')->default(false)->after('release_date');
            $table->date('watched_at')->nullable()->after('is_watched');
        });

        DB::table('media')->update([
            'is_watched' => DB::raw("CASE WHEN status = 'assistido' THEN 1 ELSE 0 END"),
        ]);

        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
