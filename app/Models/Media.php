<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Media extends Model {
    
    //define a tabela para garantir que as classes filhas usem a mesma tabela
    protected $table = 'media';

    protected $fillable = [
        'user_id', 'type', 'name', 'rating', 'image', 'description', 'release_date', 'is_watched', 'watched_at'
    ];

    protected $casts = [
        'is_watched' => 'boolean',
        'watched_at' => 'date',
        'release_date' => 'date',
        'rating' => 'float',
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function seasons(): HasMany {
        return $this->hasMany(Season::class);
    }
}