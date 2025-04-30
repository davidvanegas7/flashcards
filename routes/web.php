<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DeckController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::guard('web')->check()) {
        return redirect('/documents');
    }
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('decks', [DeckController::class, 'index'])->name('decks');
    Route::get('deck', [DeckController::class, 'create'])->name('decks.create');
    Route::post('decks', [DeckController::class, 'store'])->name('decks.store');
    Route::get('decks/{deck}', [DeckController::class, 'show'])->name('decks.show');
    Route::get('decks/{deck}/edit', [DeckController::class, 'edit'])->name('decks.edit');
    Route::put('decks/{deck}', [DeckController::class, 'update'])->name('decks.update');
    Route::delete('decks/{deck}', [DeckController::class, 'destroy'])->name('decks.destroy');

    Route::get('decks/{deck}/cards/create', [CardController::class, 'create'])->name('cards.create');
    Route::post('decks/cards', [CardController::class, 'store'])->name('cards.store');
    Route::get('decks/{deck}/cards/{card}/edit', [CardController::class, 'edit'])->name('cards.edit');
    Route::put('decks/{deck}/cards/{card}', [CardController::class, 'update'])->name('cards.update');
    Route::delete('decks/{deck}/cards/{card}', [CardController::class, 'destroy'])->name('cards.destroy');
    Route::post('decks/cards/generateAI', [CardController::class, 'generateCardsUsingAI'])->name('cards.generateAI');
    Route::post('decks/cards/generateResponseAI', [CardController::class, 'generateResponseUsingAI'])->name('cards.generateResponseAI');

    Route::get('decks/{deck}/expanded-cards', [CardController::class, 'playExpandedCards'])->name('play.expanded-cards');

    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/status', [DocumentController::class, 'checkStatus'])->name('documents.status');
});

require __DIR__.'/auth.php';
