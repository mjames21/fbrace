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
use App\Livewire\Manage\Guarantors;
use App\Livewire\Manage\GuarantorShow;

use App\Livewire\Manage\Categories as ManageCategories;
use App\Livewire\Manage\DelegatesCreate;

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


Route::get('/manage/guarantors', Guarantors::class)->name('manage.guarantors');
Route::get('/manage/guarantors/{guarantorId}', GuarantorShow::class)->name('manage.guarantors.show');

Route::get('/reports/status-history', StatusHistory::class)->name('reports.status-history');
Route::get('/reports/status-history/export/csv', [StatusHistoryExportController::class, 'csv'])
        ->name('reports.status-history.export.csv');

Route::get('/reports/status-history/export/pdf', [StatusHistoryExportController::class, 'pdf'])
        ->name('reports.status-history.export.pdf');

Route::get('/manage/categories', ManageCategories::class)->name('manage.categories');
Route::get('/manage/delegates/create', DelegatesCreate::class)->name('manage.delegates.create');
});