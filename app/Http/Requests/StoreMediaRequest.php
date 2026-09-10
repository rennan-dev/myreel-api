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
            'image' => 'nullable|url', // ou 'image|mimes:jpeg,png' se for upload
            'description' => 'nullable|string',
            
            // Regras exclusivas para Filme
            'release_date' => 'required_if:type,filme|date',
            'is_watched' => 'nullable|boolean',
            'watched_at' => 'required_if:is_watched,true|date',
        ];
    }
}
