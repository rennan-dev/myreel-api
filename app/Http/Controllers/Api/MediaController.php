<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Filme;
use App\Models\Serie;
use App\Models\Anime;
use App\Models\Media;
use App\Models\Season;
use App\Models\Episode;

class MediaController extends Controller {

    public function store(StoreMediaRequest $request) {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // capa: só upload do dispositivo (URL externa removida)
        if ($request->hasFile('image')) {
            // salva em storage/app/public/covers e grava só o caminho no MySQL
            $data['image'] = $request->file('image')->store('covers', 'public');
        } else {
            $data['image'] = null;
        }

        // serie/anime não usam datas no nível da mídia:
        // release_date vive na temporada, watched vive no episódio
        if (in_array($data['type'], ['serie', 'anime'], true)) {
            $data['release_date'] = null;
            $data['is_watched'] = false;
            $data['watched_at'] = null;
        } elseif (empty($data['is_watched'])) {
            $data['is_watched'] = false;
            $data['watched_at'] = null;
        }

        $media = match ($data['type']) {
            'filme' => Filme::create($data),
            'serie' => Serie::create($data),
            'anime' => Anime::create($data),
        };

        return response()->json([
            'message' => 'Item adicionado à lista com sucesso!',
            'data' => $media
        ], 201);
    }

    public function index() {
        $listaCompleta = Media::with('seasons.episodes')->where('user_id', auth()->id())->get();
        return response()->json($listaCompleta);
    }

    public function show(Media $media) {
        if($media->user_id !== auth()->id()) abort(403);
        return response()->json($media->load('seasons.episodes'));
    }

    public function update(UpdateMediaRequest $request, Media $media) {
        if($media->user_id !== auth()->id()) abort(403);

        $data = $request->validated();
        unset($data['type']); // tipo não é editável

        // capa: se enviou arquivo novo, substitui (apaga o antigo do disco);
        // se não enviou, mantém a capa atual
        if ($request->hasFile('image')) {
            if ($media->image && !str_starts_with($media->image, 'http')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($media->image);
            }
            $data['image'] = $request->file('image')->store('covers', 'public');
        } else {
            unset($data['image']);
        }

        // serie/anime não usam datas no nível da mídia
        if (in_array($media->type, ['serie', 'anime'], true)) {
            unset($data['release_date'], $data['is_watched'], $data['watched_at']);
        } elseif (array_key_exists('is_watched', $data) && empty($data['is_watched'])) {
            $data['is_watched'] = false;
            $data['watched_at'] = null;
        }

        $media->update($data);
        return response()->json($media->fresh()->load('seasons.episodes'));
    }

    public function destroy(Media $media) {
        if($media->user_id !== auth()->id()) abort(403);

        // apaga a capa do disco (se for upload local)
        if ($media->image && !str_starts_with($media->image, 'http')) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($media->image);
        }

        $media->delete(); // seasons + episodes caem por cascadeOnDelete
        return response()->json(['message' => 'Item excluído com sucesso.']);
    }

    public function filmes() {
        return response()->json(Filme::where('user_id', auth()->id())->get());
    }

    public function storeSeason(Request $request, Media $media) {
        if($media->user_id !== auth()->id()) abort(403);
        if(!in_array($media->type, ['serie', 'anime'], true)) {
            return response()->json(['message' => 'Temporadas só existem para séries e animes.'], 422);
        }

        $data = $request->validate([
            'season_number' => 'required|integer|min:1',
            'release_date' => 'nullable|date',
        ]);

        // evita duplicar a mesma temporada
        $season = $media->seasons()->firstOrCreate(
            ['season_number' => $data['season_number']],
            ['release_date' => $data['release_date'] ?? null]
        );
        return response()->json($season, 201);
    }

    public function updateSeason(Request $request, Season $season) {
        if($season->media->user_id !== auth()->id()) abort(403);

        $data = $request->validate([
            'release_date' => 'nullable|date',
            'rating' => 'nullable|numeric|min:1|max:5',
        ]);

        $season->update($data);
        return response()->json($season->fresh());
    }

    public function destroySeason(Season $season) {
        if($season->media->user_id !== auth()->id()) abort(403);
        $season->delete(); // episódios caem por cascadeOnDelete
        return response()->json(['message' => 'Temporada excluída com sucesso.']);
    }

    public function storeEpisode(Request $request, Season $season) {
        if($season->media->user_id !== auth()->id()) abort(403);

        $data = $request->validate([
            'episode_number' => 'required|integer|min:1',
            'is_watched' => 'sometimes|boolean',
            'watched_at' => 'nullable|date',
        ]);

        if (!array_key_exists('is_watched', $data)) {
            $data['is_watched'] = false;
        }

        // evita duplicar o mesmo episódio na temporada
        $episode = $season->episodes()->firstOrCreate(
            ['episode_number' => $data['episode_number']],
            ['is_watched' => $data['is_watched'], 'watched_at' => $data['watched_at'] ?? null]
        );
        return response()->json($episode, 201);
    }

    public function updateEpisode(Request $request, Episode $episode) {
        if($episode->season->media->user_id !== auth()->id()) abort(403);

        $data = $request->validate([
            'is_watched' => 'sometimes|boolean',
            'watched_at' => 'nullable|date',
        ]);

        // marcar como assistido carimba a data de hoje; desmarcar limpa a data
        if (array_key_exists('is_watched', $data)) {
            $data['watched_at'] = $data['is_watched']
                ? ($data['watched_at'] ?? today()->toDateString())
                : null;
        }

        $episode->update($data);
        return response()->json($episode->fresh());
    }
}
