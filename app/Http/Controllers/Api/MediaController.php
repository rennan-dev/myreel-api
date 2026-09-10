<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreMediaRequest;
use App\Models\Filme;
use App\Models\Serie;
use App\Models\Anime;
use App\Models\Media;

class MediaController extends Controller {

    public function store(StoreMediaRequest $request) {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

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

    public function filmes() {
        return response()->json(Filme::where('user_id', auth()->id())->get());
    }

    public function storeSeason(Request $request, Media $media) {
        if($media->user_id !== auth()->id()) abort(403);

        $data = $request->validate([
            'season_number' => 'required|integer',
            'release_date' => 'nullable|date',
        ]);

        $season = $media->seasons()->create($data);
        return response()->json($season, 201);
    }

    public function storeEpisode(Request $request, Season $season) {
        if($season->media->user_id !== auth()->id()) abort(403);

        $data = $request->validate([
            'episode_number' => 'required|integer',
            'is_watched' => 'boolean',
            'watched_at' => 'nullable|date',
        ]);

        $episode = $season->episodes()->create($data);
        return response()->json($episode, 201);
    }
}
