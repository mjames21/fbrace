<?php
use Illuminate\Support\Facades\Route;
use App\Livewire\Board\Approvals;
use App\Livewire\Board\BulkUpdate;
use App\Livewire\Board\CompareCandidates;
use App\Livewire\Board\DelegateBoard;
use App\Livewire\Dashboard\HorseRace;
use App\Livewire\Manage\Alliances;
use App\Livewire\Manage\Candidates;
use App\Livewire\Manage\Delegates;
use App\Livewire\Manage\Districts;
use App\Livewire\Manage\Groups;
use App\Livewire\Manage\Regions;
use App\Livewire\Reports\StatusHistory;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DelegateBoard::class)->name('dashboard');

  Route::get('/board/delegates', DelegateBoard::class)->name('board.delegate-board');
Route::get('/board/compare', CompareCandidates::class)->name('board.compare-candidates');
Route::get('/board/approvals', Approvals::class)->name('board.approvals');
Route::get('/board/bulk-update', BulkUpdate::class)->name('board.bulk-update');

Route::get('/horse-race', HorseRace::class)->name('horse-race');

Route::get('/manage/regions', Regions::class)->name('manage.regions');
Route::get('/manage/districts', Districts::class)->name('manage.districts');
Route::get('/manage/groups', Groups::class)->name('manage.groups');
Route::get('/manage/delegates', Delegates::class)->name('manage.delegates');
Route::get('/manage/candidates', Candidates::class)->name('manage.candidates');
Route::get('/manage/alliances', Alliances::class)->name('manage.alliances');

Route::get('/reports/status-history', StatusHistory::class)->name('reports.status-history');
});