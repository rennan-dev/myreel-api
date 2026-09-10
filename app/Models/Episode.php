<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Episode extends Model {

    protected $fillable = ['season_id', 'episode_number', 'is_watched', 'watched_at'];

    protected $casts = ['is_watched' => 'boolean', 'watched_at' => 'date'];

    public function season() { return $this->belongsTo(Season::class); }
}