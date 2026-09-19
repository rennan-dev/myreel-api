<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'rating' => 'nullable|numeric|min:1|max:5',
            'image' => 'nullable|file|image|mimes:jpeg,jpg,png,webp,gif|max:4096',
            'description' => 'nullable|string',

            'cover_x' => 'nullable|numeric|between:-100,100',
            'cover_y' => 'nullable|numeric|between:-100,100',
            'cover_scale' => 'nullable|numeric|between:1,5',

            'release_date' => 'nullable|date',
    
            'status' => 'nullable|in:nao_assisti,assistindo,assistido',
        ];
    }

    protected function prepareForValidation(): void
    {
        // multipart (PUT via _method) chega com tudo como string: normaliza vazios
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
        if ($this->filled('status')) {
            $merged['status'] = $this->input('status');
        }

        $this->merge($merged);

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
