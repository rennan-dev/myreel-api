<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AnalyticsController extends Controller
{
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => 'sometimes|in:pageview',
            'path' => 'required|string|max:255',
            'referrer' => 'nullable|string|max:512',
            'visitor_id' => 'required|uuid',
            'session_id' => 'required|string|max:64',
        ]);

        $ip = (string) $request->ip();
        $geo = $this->geoForIp($ip);

        AnalyticsEvent::create([
            'visitor_id' => $data['visitor_id'],
            'session_id' => $data['session_id'],
            'event_type' => $data['event_type'] ?? 'pageview',
            'path' => $data['path'],
            'referrer' => $data['referrer'] ?? null,
            'user_id' => $request->user()?->id,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
            'ip' => $ip,
            'country' => $geo['country'],
            'city' => $geo['city'],
        ]);

        return response()->json(['message' => 'Evento registrado.'], 201);
    }

    public function summary(Request $request): JsonResponse
    {
        $days = min(max((int) $request->query('days', 30), 1), 365);
        // janela: últimos N dias INCLUINDO hoje
        $start = now()->subDays($days - 1)->startOfDay();

        $pageview = fn () => AnalyticsEvent::query()
            ->where('event_type', 'pageview')
            ->where('created_at', '>=', $start);

        $pageviews = (clone $pageview())->count();
        $uniqueVisitors = (clone $pageview())->distinct()->count('visitor_id');
        $sessions = (clone $pageview())
            ->selectRaw('COUNT(DISTINCT CONCAT(visitor_id, ":", session_id)) as c')
            ->value('c');

        $newUsers = User::where('created_at', '>=', $start)->count();
        $totalUsers = User::count();

        $pageviewsPerDay = $this->series(
            (clone $pageview())
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date'),
            $start,
            $days
        );

        $newUsersPerDay = $this->series(
            User::query()
                ->where('created_at', '>=', $start)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date'),
            $start,
            $days
        );

        $topPages = (clone $pageview())
            ->selectRaw('path, COUNT(*) as views')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $topCountries = (clone $pageview())
            ->selectRaw("COALESCE(NULLIF(country, ''), 'Desconhecido') as country, COUNT(DISTINCT visitor_id) as visitors")
            ->groupBy('country')
            ->orderByDesc('visitors')
            ->limit(10)
            ->get();

        $topCities = (clone $pageview())
            ->selectRaw("COALESCE(NULLIF(city, ''), 'Desconhecido') as city, COALESCE(NULLIF(country, ''), 'Desconhecido') as country, COUNT(DISTINCT visitor_id) as visitors")
            ->groupBy('city', 'country')
            ->orderByDesc('visitors')
            ->limit(10)
            ->get();

        $topReferrers = (clone $pageview())
            ->selectRaw("COALESCE(NULLIF(referrer, ''), 'Direto') as referrer, COUNT(*) as count")
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $browsers = $this->browserBreakdown(
            (clone $pageview())
                ->selectRaw('user_agent, COUNT(*) as count')
                ->groupBy('user_agent')
                ->orderByDesc('count')
                ->limit(25)
                ->get()
        );

        return response()->json([
            'period_days' => $days,
            'generated_at' => now()->toIso8601String(),
            'users' => [
                'total' => $totalUsers,
                'new_in_period' => $newUsers,
                'new_per_day' => $newUsersPerDay,
            ],
            'traffic' => [
                'pageviews' => $pageviews,
                'unique_visitors' => $uniqueVisitors,
                'sessions' => $sessions,
                'pageviews_per_day' => $pageviewsPerDay,
            ],
            'top_pages' => $topPages,
            'top_countries' => $topCountries,
            'top_cities' => $topCities,
            'top_referrers' => $topReferrers,
            'browsers' => $browsers,
        ]);
    }

    /**
     * Localização aproximada do IP (ip-api.com), cacheada por 30 dias.
     * IPs privados/locais não são consultados.
     */
    private function geoForIp(string $ip): array
    {
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return ['country' => null, 'city' => null];
        }

        return Cache::remember('analytics:geo:'.md5($ip), now()->addDays(30), function () use ($ip) {
            try {
                $response = Http::timeout(3)
                    ->get('http://ip-api.com/json/'.$ip, ['fields' => 'status,country,city']);

                $data = $response->json();

                if (($data['status'] ?? null) !== 'success') {
                    return ['country' => null, 'city' => null];
                }

                return [
                    'country' => $data['country'] ?? null,
                    'city' => $data['city'] ?? null,
                ];
            } catch (\Throwable) {
                return ['country' => null, 'city' => null];
            }
        });
    }

    /**
     * Preenche os dias do período com zero para as datas sem registros.
     */
    private function series($countsByDate, Carbon $start, int $days): array
    {
        $result = [];

        foreach (CarbonPeriod::create($start, $start->copy()->addDays($days - 1)) as $day) {
            $key = $day->toDateString();
            $result[] = [
                'date' => $key,
                'count' => (int) ($countsByDate[$key] ?? 0),
            ];
        }

        return $result;
    }

    private function browserBreakdown($rows): array
    {
        $buckets = [];

        foreach ($rows as $row) {
            $name = $this->browserName((string) $row->user_agent);
            $buckets[$name] = ($buckets[$name] ?? 0) + (int) $row->count;
        }

        arsort($buckets);

        return collect($buckets)
            ->map(fn ($count, $name) => ['name' => $name, 'count' => $count])
            ->values()
            ->all();
    }

    private function browserName(string $userAgent): string
    {
        if ($userAgent === '') {
            return 'Desconhecido';
        }

        return match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox') => 'Firefox',
            str_contains($userAgent, 'Chrome') || str_contains($userAgent, 'CriOS') => 'Chrome',
            str_contains($userAgent, 'Safari') => 'Safari',
            default => 'Outro',
        };
    }
}
