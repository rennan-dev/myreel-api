<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:filme,serie,anime',
            'name' => 'required|string|max:255',
            'rating' => 'nullable|numeric|min:1|max:5',
            // capa: apenas upload do dispositivo (nada de URL)
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
            'description' => 'nullable|string',

            // enquadramento da capa definido no editor (posição % e zoom)
            'cover_x' => 'nullable|numeric|between:-100,100',
            'cover_y' => 'nullable|numeric|between:-100,100',
            'cover_scale' => 'nullable|numeric|between:1,5',

            'release_date' => 'nullable|date',

            // status de consumo da mídia (vale para filme, série e anime)
            'status' => 'nullable|in:nao_assisti,assistindo,assistido',
        ];
    }

    protected function prepareForValidation(): void
    {
        // multipart/form-data chega com tudo como string: normaliza vazios
        $this->merge([
            'rating' => $this->filled('rating') ? $this->input('rating') : null,
            'description' => $this->filled('description') ? $this->input('description') : null,
            'release_date' => $this->filled('release_date') ? $this->input('release_date') : null,
        ]);

        // sem arquivo: garante null (URL externa não é mais aceita)
        if (! $this->hasFile('image')) {
            $this->merge(['image' => null]);
        }

        // enquadramento da capa: remove vazios para não sobrescrever com null
        foreach (['cover_x', 'cover_y', 'cover_scale'] as $coverField) {
            if (! $this->filled($coverField)) {
                $this->request->remove($coverField);
            }
        }
    }
}
