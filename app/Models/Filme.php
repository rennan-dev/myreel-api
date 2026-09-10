<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Filme extends Media {
    
    protected static function booted() {
        //ao buscar, trás apenas filmes
        static::addGlobalScope('filme', function (Builder $builder) {
            $builder->where('type', 'filme');
        });

        //ao criar, injeta o tipo automaticamente
        static::creating(function ($model) {
            $model->type = 'filme';
        });
    }
}