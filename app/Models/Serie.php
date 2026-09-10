<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Serie extends Media {
    
    protected static function booted() {
        static::addGlobalScope('serie', function (Builder $builder) {
            $builder->where('type', 'serie');
        });

        static::creating(function ($model) {
            $model->type = 'serie';
        });
    }
}