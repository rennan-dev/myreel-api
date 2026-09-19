<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Anime;
use App\Models\Filme;
use App\Models\Media;
use App\Models\Serie;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function store(StoreMediaRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // capa: só upload do dispositivo (URL externa removida)
        if ($request->hasFile('image')) {
            // salva em storage/app/public/covers e grava só o caminho no MySQL
            $data['image'] = $request->file('image')->store('covers', 'public');
        } else {
            $data['image'] = null;
        }

        // status de consumo (Não Assisti / Assistindo / Assistido)
        $data['status'] ??= 'nao_assisti';

        $media = match ($data['type']) {
            'filme' => Filme::create($data),
            'serie' => Serie::create($data),
            'anime' => Anime::create($data),
        };

        return response()->json([
            'message' => 'Item adicionado à lista com sucesso!',
            'data' => $media,
        ], 201);
    }

    public function index()
    {
        $listaCompleta = Media::where('user_id', auth()->id())->get();

        return response()->json($listaCompleta);
    }

    public function show(Media $media)
    {
        if ($media->user_id !== auth()->id()) {
            abort(403);
        }

        return response()->json($media);
    }

    public function update(UpdateMediaRequest $request, Media $media)
    {
        if ($media->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->validated();
        unset($data['type']); // tipo não é editável

        // capa: se enviou arquivo novo, substitui (apaga o antigo do disco);
        // se não enviou, mantém a capa atual
        if ($request->hasFile('image')) {
            if ($media->image && ! str_starts_with($media->image, 'http')) {
                Storage::disk('public')->delete($media->image);
            }
            $data['image'] = $request->file('image')->store('covers', 'public');
        } else {
            unset($data['image']);
        }

        $media->update($data);

        return response()->json($media->fresh());
    }

    public function destroy(Media $media)
    {
        if ($media->user_id !== auth()->id()) {
            abort(403);
        }

        // apaga a capa do disco (se for upload local)
        if ($media->image && ! str_starts_with($media->image, 'http')) {
            Storage::disk('public')->delete($media->image);
        }

        $media->delete(); // seasons + episodes caem por cascadeOnDelete

        return response()->json(['message' => 'Item excluído com sucesso.']);
    }

    public function filmes()
    {
        return response()->json(Filme::where('user_id', auth()->id())->get());
    }
}
