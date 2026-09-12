<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model {
    
    protected $fillable = ['media_id', 'season_number', 'title', 'release_date', 'rating', 'episodes_count'];

    protected $casts = ['rating' => 'float', 'release_date' => 'date'];

    public function episodes() { return $this->hasMany(Episode::class); }
    public function media() { return $this->belongsTo(Media::class); }
}