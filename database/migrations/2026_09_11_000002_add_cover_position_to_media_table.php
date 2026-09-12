<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::table('media', function (Blueprint $table) {
            // enquadramento da capa definido no editor: deslocamento (%) e zoom
            $table->decimal('cover_x', 6, 2)->default(0)->after('image');
            $table->decimal('cover_y', 6, 2)->default(0)->after('cover_x');
            $table->decimal('cover_scale', 4, 2)->default(1)->after('cover_y');
        });
    }

    public function down(): void {
        Schema::table('media', function (Blueprint $table) {
            $table->dropColumn(['cover_x', 'cover_y', 'cover_scale']);
        });
    }
};
