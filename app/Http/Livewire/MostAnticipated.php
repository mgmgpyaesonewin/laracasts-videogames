<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Component;

class MostAnticipated extends Component
{
    public array $mostAnticipated = [];

    public function loadMostAnticipated()
    {
        $current = Carbon::now()->timestamp;
        $afterFourMonths = Carbon::now()->addMonths(4)->timestamp;

        $mostAnticipatedUnformatted = Http::withHeaders(config('services.igdb'))
            ->withBody("
                fields name, cover.url, first_release_date, platforms.abbreviation, rating, rating_count, summary;
                where platforms = (48, 49, 130, 6)
                & (first_release_date >= {$current}
                & first_release_date < {$afterFourMonths});
                sort rating desc;
                limit 4;
            ", 'application/octet-stream')
            ->post('https://api.igdb.com/v4/games')
            ->json();

        $this->mostAnticipated = $this->formatForView($mostAnticipatedUnformatted);
    }

    public function render()
    {
        return view('livewire.most-anticipated');
    }

    private function formatForView(array $mostAnticipatedUnformatted): array
    {
        return collect($mostAnticipatedUnformatted)->map(function ($game) {
            return collect($game)->merge([
                'coverImageUrl' => isset($game['cover']) ? Str::replaceFirst('thumb', 'cover_big', $game['cover']['url']) : null,
                'first_release_date' =>  Carbon::parse($game['first_release_date'])->format('M d, Y')
            ]);
        })->toArray();
    }
}
