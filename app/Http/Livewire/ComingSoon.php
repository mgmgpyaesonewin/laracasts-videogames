<?php

namespace App\Http\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class ComingSoon extends Component
{
    public $comingSoon = [];

    public function loadComingSoon()
    {
        $current = Carbon::now()->timestamp;
        $afterFourMonths = Carbon::now()->addMonths(4)->timestamp;

        $this->comingSoon = Http::withHeaders(config('services.igdb'))
            ->withBody("
                fields name, cover.url, first_release_date, platforms.abbreviation, rating, rating_count, summary;
                where platforms = (48, 49, 130, 6)
                & (first_release_date >= {$current}
                & first_release_date < {$afterFourMonths});
                sort first_release_date desc;
                limit 4;
            ", 'application/octet-stream')
            ->post('https://api.igdb.com/v4/games')
            ->json();

    }
    public function render()
    {
        return view('livewire.coming-soon');
    }
}
