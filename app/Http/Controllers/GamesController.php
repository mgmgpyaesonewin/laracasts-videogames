<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class GamesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
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

        return view('show', [
            'game' => $game[0]
        ]);
    }
}
