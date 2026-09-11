<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest {
    
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'type' => 'required|in:filme,serie,anime',
            'name' => 'required|string|max:255',
            'rating' => 'nullable|numeric|min:1|max:5',
            // capa: apenas upload do dispositivo (nada de URL)
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
            'description' => 'nullable|string',

            // Datas só existem no nível da mídia para FILME.
            // Série/anime: release_date vive na temporada e watched vive no episódio,
            // então esses campos são excluídos da validação para serie/anime.
            'release_date' => 'exclude_if:type,serie|exclude_if:type,anime|nullable|date|required_if:type,filme',
            'is_watched' => 'exclude_if:type,serie|exclude_if:type,anime|nullable|boolean',
            'watched_at' => 'exclude_if:type,serie|exclude_if:type,anime|nullable|date|required_if:is_watched,true|required_if:is_watched,1',
        ];
    }

    protected function prepareForValidation(): void {
        // multipart/form-data chega com tudo como string: normaliza vazios e booleanos
        $this->merge([
            'rating' => $this->filled('rating') ? $this->input('rating') : null,
            'description' => $this->filled('description') ? $this->input('description') : null,
            'release_date' => $this->filled('release_date') ? $this->input('release_date') : null,
            'watched_at' => $this->filled('watched_at') ? $this->input('watched_at') : null,
            'is_watched' => $this->boolean('is_watched'),
        ]);

        // sem arquivo: garante null (URL externa não é mais aceita)
        if (!$this->hasFile('image')) {
            $this->merge(['image' => null]);
        }
    }
}
