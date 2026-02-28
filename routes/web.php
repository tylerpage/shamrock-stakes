<?php

use App\Http\Controllers\Admin\PartyController as AdminPartyController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PartyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware(['auth', 'not_blocked'])->group(function () {
    Route::get('/parties', [PartyController::class, 'index'])->name('parties.index');
    Route::get('/parties/{party}', [PartyController::class, 'show'])->name('parties.show');
    Route::get('/parties/{party}/markets/{market}/bets', [PartyController::class, 'marketBets'])->name('parties.markets.bets');
    Route::post('/parties/{party}/markets/{market}/pre-vote', [PartyController::class, 'preVote'])->name('parties.pre-vote');
    Route::get('/parties/{party}/markets/{market}/propose-resolution', [PartyController::class, 'proposeResolutionForm'])->name('parties.propose-resolution.form');
    Route::post('/parties/{party}/markets/{market}/propose-resolution', [PartyController::class, 'proposeResolution'])->name('parties.propose-resolution');
    Route::post('/parties/{party}/markets/{market}/bet', [PartyController::class, 'placeBet'])->name('parties.bet');
    Route::get('/parties/{party}/leaderboard', [PartyController::class, 'leaderboard'])->name('parties.leaderboard');
    Route::post('/push-subscription', [App\Http\Controllers\PushSubscriptionController::class, 'store'])->name('push.subscribe');

    Route::prefix('admin')->middleware('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/block', [AdminUserController::class, 'block'])->name('users.block');
        Route::post('/users/{user}/unblock', [AdminUserController::class, 'unblock'])->name('users.unblock');

        Route::get('/parties', [AdminPartyController::class, 'index'])->name('parties.index');
        Route::get('/parties/create', [AdminPartyController::class, 'create'])->name('parties.create');
        Route::post('/parties', [AdminPartyController::class, 'store'])->name('parties.store');
        Route::get('/parties/{party}', [AdminPartyController::class, 'show'])->name('parties.show');
        Route::put('/parties/{party}', [AdminPartyController::class, 'update'])->name('parties.update');
        Route::post('/parties/{party}/invite', [AdminPartyController::class, 'invite'])->name('parties.invite');
        Route::post('/parties/{party}/balance', [AdminPartyController::class, 'updateBalance'])->name('parties.balance');
        Route::get('/parties/{party}/markets/{market}/edit', [AdminPartyController::class, 'editMarket'])->name('parties.markets.edit');
        Route::put('/parties/{party}/markets/{market}', [AdminPartyController::class, 'updateMarket'])->name('parties.markets.update');
        Route::post('/parties/{party}/markets', [AdminPartyController::class, 'createMarket'])->name('parties.markets.store');
        Route::post('/parties/{party}/markets/{market}/options', [AdminPartyController::class, 'addOption'])->name('parties.markets.options.store');
        Route::put('/parties/{party}/markets/{market}/options/{market_option}', [AdminPartyController::class, 'updateOption'])->name('parties.markets.options.update');
        Route::delete('/parties/{party}/markets/{market}/options/{market_option}', [AdminPartyController::class, 'deleteOption'])->name('parties.markets.options.destroy');
        Route::post('/parties/{party}/start-pre-voting', [AdminPartyController::class, 'startPreVoting'])->name('parties.start-pre-voting');
        Route::post('/parties/{party}/start-live', [AdminPartyController::class, 'startLive'])->name('parties.start-live');
        Route::post('/parties/{party}/markets/{market}/resolve-official', [AdminPartyController::class, 'setOfficialOutcome'])->name('parties.markets.resolve-official');
        Route::post('/parties/{party}/markets/{market}/resolve-voting', [AdminPartyController::class, 'resolveVoting'])->name('parties.markets.resolve-voting');
        Route::post('/parties/{party}/resolution-proposals/{proposal}/accept', [AdminPartyController::class, 'acceptResolutionProposal'])->name('resolution-proposals.accept');
        Route::post('/parties/{party}/resolution-proposals/{proposal}/deny', [AdminPartyController::class, 'denyResolutionProposal'])->name('resolution-proposals.deny');
    });
});
