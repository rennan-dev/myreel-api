<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model {

    //define a tabela para garantir que as classes filhas usem a mesma tabela
    protected $table = 'media';

    // expõe `image_url` (URL pronta da capa) em todo JSON, sem quebrar o `image` legado
    protected $appends = ['image_url'];

    protected $fillable = [
        'user_id', 'type', 'name', 'rating', 'image', 'description',
        'cover_x', 'cover_y', 'cover_scale',
        'release_date', 'is_watched', 'watched_at'
    ];

    protected $casts = [
        'is_watched' => 'boolean',
        'watched_at' => 'date',
        'release_date' => 'date',
        'rating' => 'float',
        'cover_x' => 'float',
        'cover_y' => 'float',
        'cover_scale' => 'float',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function seasons(): HasMany {
        return $this->hasMany(Season::class);
    }

    /**
     * URL pública da capa para o front exibir direto no <img>.
     * - Se `image` já for http(s) (link externo antigo), devolve como está.
     * - Se for caminho do disco `public` (ex.: covers/abc.jpg), gera /storage/....
     */
    protected function imageUrl(): Attribute {
        return Attribute::make(
            get: function () {
                $raw = $this->attributes['image'] ?? null;
                if (!$raw) return null;
                if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
                    return $raw;
                }
                return Storage::disk('public')->url($raw);
            },
        );
    }
}