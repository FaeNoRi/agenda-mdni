<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdherentController;
use App\Http\Controllers\CongeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\ObjetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MaterielController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\ChangementHoraireController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\ReglementSignatureController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\TacheController;

// tableau de bord initial (vue Blade + Offcanvas)
Route::get('/dashboard', [EvenementController::class, 'dashboard'])
     ->middleware(['auth','verified'])
     ->name('dashboard');

// endpoint AJAX pour filtrer sans recharger la page
Route::get('/dashboard/data', [EvenementController::class, 'filter'])
     ->middleware(['auth','verified'])
     ->name('dashboard.data');

Route::get('/dashboard/cards', [EvenementController::class, 'cards'])
->name('dashboard.cards')
->middleware(['auth','verified']);

Route::get('/dashboard/calendar-data', [EvenementController::class, 'calendarData'])->middleware(['auth','verified']);

Route::get('/export', function () {return view('export.index');})->name('export.form');
Route::get('/export/columns/{model}', [ExportController::class, 'getModelColumns'])->name('export.columns');
Route::post('/export', [ExportController::class, 'export'])->name('export');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::resource('users', UserController::class)->middleware(['auth']);
Route::resource('salles', SalleController::class)->middleware(['auth']);
Route::resource('objets', ObjetController::class)->middleware(['auth']);
Route::resource('materiels', MaterielController::class)->middleware(['auth']);
Route::resource('conges', CongeController::class)->middleware(['auth']);
Route::resource('changements_horaires', ChangementHoraireController::class)->middleware(['auth']);
Route::resource('adherents', AdherentController::class)->middleware(['auth']);

Route::middleware(['auth'])->group(function () {
    Route::get('/presence', [PresenceController::class, 'index'])->name('presence');

    Route::post('/presence/adherents', [PresenceController::class, 'store'])
        ->name('presence.adherents.store');

    Route::put('/presence/adherents/{adherent}', [PresenceController::class, 'update'])
        ->name('presence.adherents.update');

    Route::delete('/presence/adherents/{adherent}', [PresenceController::class, 'destroy'])
        ->name('presence.adherents.destroy');

    Route::post('/presence/reset', [PresenceController::class, 'resetAll'])
        ->name('presence.reset');

    Route::get('/presence/stats/periods', [PresenceController::class, 'listStatsPeriods'])
        ->name('presence.stats.periods');

    Route::post('/presence/stats/export', [PresenceController::class, 'exportStats'])
        ->name('presence.stats.export');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/sync/version', [SyncController::class, 'version'])->name('sync.version');
});

Route::middleware(['auth'])->get('/presence/partials', function () {
    $equipe   = \App\Models\Adherents::where('situation_adh', 'MDNI')->get();
    $presents = \App\Models\Adherents::where('isPresent', true)->where('situation_adh', '!=', 'MDNI')->get();
    $absents  = \App\Models\Adherents::where('isPresent', false)->where('situation_adh', '!=', 'MDNI')->get();

    $colors = [
        'MDNI' => 'info',
        'Étudiant' => 'success',
        'Bénévole' => 'secondary',
        'Porteur de projet' => 'warning',
        'Chef d\'entreprise' => 'danger',
        'Salarié' => 'secondary',
        'Particulier' => 'warning',
    ];

    return response()->json([
        'team'     => view('adherents.partials.team',     compact('equipe','colors'))->render(),
        'presents' => view('adherents.partials.presents', compact('presents','colors'))->render(),
        'absents'  => view('adherents.partials.absents',  compact('absents','colors'))->render(),
    ])->header('Cache-Control', 'no-store');
})->name('presence.partials');

Route::post('/adherents/{id}/presence', [PresenceController::class, 'changerPresence'])->name('adherents.changerPresence');

Route::get('/evenements/data', [EvenementController::class, 'data'])->name('evenements.data')->middleware(['auth']);
Route::get('/evenements/{evenement}/details', [EvenementController::class, 'showDetails'])->middleware(['auth']);
Route::get('/evenements/disponibilites', [EvenementController::class, 'disponibilites'])->name('evenements.disponibilites')->middleware(['auth']);
Route::get('/evenements/{evenement}/duplicate', [EvenementController::class, 'duplicate'])->name('evenements.duplicate')->middleware(['auth']);
Route::resource('evenements', EvenementController::class)->middleware(['auth']);

Route::middleware(['auth'])->group(function () {
    Route::get('/reglement/lecture', [ReglementSignatureController::class, 'showPdf'])
        ->name('reglement.lecture');

    Route::get('/reglement/adherents-2026', [ReglementSignatureController::class, 'adherentsCurrentYear'])
        ->name('reglement.adherentsCurrentYear');

    Route::post('/reglement/signature', [ReglementSignatureController::class, 'sign'])
        ->name('reglement.signature');
});

// Projets & tâches : réservé aux administrateurs tant que le module n'est pas ouvert (config/features.php)
Route::middleware(['auth', 'projets.acces'])->group(function () {
    Route::get('/projets', [ProjetController::class, 'index'])->name('projets.index');
    // /projets/create doit rester avant /projets/{projet}
    Route::get('/projets/create', [ProjetController::class, 'create'])->name('projets.create');
    Route::post('/projets', [ProjetController::class, 'store'])->name('projets.store');
    Route::get('/projets/{projet}', [ProjetController::class, 'show'])->name('projets.show');
    Route::get('/projets/{projet}/edit', [ProjetController::class, 'edit'])->name('projets.edit');
    Route::put('/projets/{projet}', [ProjetController::class, 'update'])->name('projets.update');
    Route::delete('/projets/{projet}', [ProjetController::class, 'destroy'])->name('projets.destroy');
    Route::post('/projets/{projet}/etat', [ProjetController::class, 'etat'])->name('projets.etat');
    Route::get('/taches', [TacheController::class, 'index'])->name('taches.index');
    // /taches/create doit rester avant /taches/{tache}
    Route::get('/taches/create', [TacheController::class, 'create'])->name('taches.create');
    Route::post('/taches', [TacheController::class, 'store'])->name('taches.store');
    Route::get('/taches/{tache}', [TacheController::class, 'show'])->name('taches.show');
    Route::get('/taches/{tache}/edit', [TacheController::class, 'edit'])->name('taches.edit');
    Route::put('/taches/{tache}', [TacheController::class, 'update'])->name('taches.update');
    Route::delete('/taches/{tache}', [TacheController::class, 'destroy'])->name('taches.destroy');
    Route::post('/taches/{tache}/statut', [TacheController::class, 'statut'])->name('taches.statut');
});

Route::get('/', function () {
    return redirect('/login');
});

Route::post('/user/theme-color', [App\Http\Controllers\UserThemeController::class, 'updateThemeColor'])
    ->middleware('auth')
    ->name('user.theme.color');

Route::middleware(['auth'])->post('/debug-sign-auth', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info('debug-sign-auth atteint', [
        'user' => $request->user()?->email,
        'keys' => array_keys($request->all()),
    ]);
    return response()->json(['ok' => true, 'user' => $request->user()?->email]);
});


require __DIR__.'/auth.php';
