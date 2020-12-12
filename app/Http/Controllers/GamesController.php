<?php

namespace App\Http\Controllers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class GamesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index(): View
    {
        return view('index');
    }

    /**
     * Display the specified game.
     *
     * @param $slug
     * @return View
     */
    public function show($slug): View
    {
        $game = Http::withHeaders(config('services.igdb'))
            ->withBody("
                fields name, cover.url, first_release_date, platforms.abbreviation, rating,
                slug, involved_companies.company.name, genres.name, aggregated_rating, summary, websites.*, videos.*, screenshots.*, similar_games.cover.url, similar_games.name, similar_games.rating,similar_games.platforms.abbreviation, similar_games.slug;
                where slug = \"{$slug}\";
            ", 'application/octet-stream')
            ->post('https://api.igdb.com/v4/games')
            ->json();

        abort_if(!$game, 404);

        dump($this->formatForView($game[0]));

        return view('show', [
            'game' => $this->formatForView($game[0])
        ]);
    }

    private function formatForView($game): Collection
    {
        return collect($game)->merge([
            'coverImageUrl' => Str::replaceFirst('thumb', 'cover_big', $game['cover']['url']),
            'genres' => collect($game['genres'])->pluck('name')->implode(', '),
            'involvedCompanies' => $game['involved_companies'][0]['company']['name'],
            'platforms' => collect($game['platforms'])->pluck('abbreviation')->implode(', '),
            'memberScore' => isset($game['rating']) ? round($game['rating']).'%' : null,
            'criticScore' => isset($game['aggregated_rating']) ? round($game['aggregated_rating']).'%' : null,
            'trailerVideo' => array_key_exists('videos', $game) ? "https://www.youtube.com/watch?v={$game['videos'][0]['video_id']}" : null,
            'screenshots' => collect($game['screenshots'])->map(function ($screenshot) {
                return [
                    'big' => Str::replaceFirst('thumb', 'screenshot_big', $screenshot['url']),
                    'huge' => Str::replaceFirst('thumb', 'screenshot_huge', $screenshot['url']),
                ];
            })->take(9),
            'similarGames' => collect($game['similar_games'])->map(function ($game) {
                return collect($game)->merge([
                    'coverImageUrl' => array_key_exists('cover', $game)
                        ? Str::replaceFirst('thumb', 'cover_big', $game['cover']['url'])
                        : 'https://via.placeholder.com/264x352',
                    'rating' => isset($game['rating']) ? round($game['rating']).'%' : null,
                    'platforms' => array_key_exists('platforms', $game)
                        ? collect($game['platforms'])->pluck('abbreviation')->implode(', ')
                        : null,
                ]);
            })->take(6),
            'social' => [
                'website' => collect($game['websites'])->first(),
                'facebook' => collect($game['websites'])->filter(fn ($website) => Str::contains($website['url'], 'facebook'))->first(),
                'twitter' => collect($game['websites'])->filter(fn ($website) => Str::contains($website['url'], 'twitter'))->first(),
                'instagram' => collect($game['websites'])->filter(fn ($website) => Str::contains($website['url'], 'instagram'))->first(),
            ]
        ]);
    }
}
