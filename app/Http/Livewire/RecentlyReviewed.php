<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;

class RecentlyReviewed extends Component
{
    public array $recentlyReviewed = [];

    public function loadRecentlyReviewed() {

        $before = Carbon::now()->subMonths(2)->timestamp;
        $current = Carbon::now()->timestamp;

        $recentlyReviewedUnformatted =  Cache::remember('popular-games', 7, function () use ($before, $current){
            return Http::withHeaders(config('services.igdb'))
                ->withBody("
                fields name, cover.url, first_release_date, platforms.abbreviation, rating, total_rating_count, summary;
                where platforms = (48, 49, 130, 6)
                & (first_release_date >= {$before}
                & first_release_date < {$current})
                & rating >= 5
                & total_rating_count >= 5;
                sort total_rating_count desc;
                sort rating desc;
                limit 3;
                ", 'application/octet-stream')
                ->post('https://api.igdb.com/v4/games')
                ->json();
        });

        $this->recentlyReviewed = $this->formatForView($recentlyReviewedUnformatted);
    }

    public function render()
    {
        return view('livewire.recently-reviewed');
    }

    private function formatForView($recentlyReviewedUnformatted): array
    {
        return collect($recentlyReviewedUnformatted)->map(function ($game) {
            return collect($game)->merge([
                'coverImageUrl' => Str::replaceFirst('thumb', 'cover_big', $game['cover']['url']),
                'rating' => round($game['rating']).'%',
                'platforms' => collect($game['platforms'])->pluck('abbreviation')->implode(', ')
            ]);
        })->toArray();
    }
}
