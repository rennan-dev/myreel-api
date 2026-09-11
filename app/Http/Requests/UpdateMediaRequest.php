<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'name' => 'sometimes|required|string|max:255',
            'rating' => 'nullable|numeric|min:1|max:5',
            // capa: apenas upload do dispositivo (nada de URL)
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
            'description' => 'nullable|string',

            // Datas só existem no nível da mídia para FILME.
            // Na edição o `type` vem do model (não é editável), então valida pelo valor atual.
            'release_date' => 'exclude_if:type,serie|exclude_if:type,anime|nullable|date',
            'is_watched' => 'exclude_if:type,serie|exclude_if:type,anime|nullable|boolean',
            'watched_at' => 'exclude_if:type,serie|exclude_if:type,anime|nullable|date|required_if:is_watched,true|required_if:is_watched,1',
        ];
    }

    protected function prepareForValidation(): void {
        // multipart (PUT via _method) chega com tudo como string: normaliza vazios e booleanos
        $merged = [];

        if ($this->exists('rating')) {
            $merged['rating'] = $this->filled('rating') ? $this->input('rating') : null;
        }
        if ($this->exists('description')) {
            $merged['description'] = $this->filled('description') ? $this->input('description') : null;
        }
        if ($this->exists('release_date')) {
            $merged['release_date'] = $this->filled('release_date') ? $this->input('release_date') : null;
        }
        if ($this->exists('watched_at')) {
            $merged['watched_at'] = $this->filled('watched_at') ? $this->input('watched_at') : null;
        }
        if ($this->exists('is_watched')) {
            $merged['is_watched'] = $this->boolean('is_watched');
        }

        // injeta o tipo atual para o exclude_if funcionar (tipo não é editável)
        $media = $this->route('media');
        if ($media instanceof \App\Models\Media) {
            $merged['type'] = $media->type;
        }

        $this->merge($merged);

        // sem arquivo: garante null (URL externa não é mais aceita)
        if (!$this->hasFile('image')) {
            $this->merge(['image' => null]);
        }
    }
}
