<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Jogo extends Media
{
    protected static function booted()
    {
        // ao buscar, traz apenas jogos
        static::addGlobalScope('jogo', function (Builder $builder) {
            $builder->where('type', 'jogo');
        });

        // ao criar, injeta o tipo automaticamente
        static::creating(function ($model) {
            $model->type = 'jogo';
        });
    }
}
