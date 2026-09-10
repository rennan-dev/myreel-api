<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Anime extends Media {

    protected static function booted() {
        static::addGlobalScope('anime', function (Builder $builder) {
            $builder->where('type', 'anime');
        });

        static::creating(function ($model) {
            $model->type = 'anime';
        });
    }
}