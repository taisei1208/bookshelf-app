<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [BookController::class, 'index'])->name('books.index');

Route::middleware('auth')->group(function () {
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::resource('/reviews', ReviewController::class)->only(['edit', 'update', 'destroy']);

    Route::resource('/books', BookController::class)->except(['index', 'show']);

    Route::resource('genres', GenreController::class);
});

Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');

// 仮：ランキング一覧
Route::get('/ranking', function () {
    return 'ランキング一覧（準備中）';
})->name('ranking.index');

// 仮：お気に入り一覧
Route::get('/favorites', function () {
    return 'お気に入り一覧（準備中）';
})->name('favorites.index');

// 仮：お気に入り登録
Route::post('/books/{book}/favorites', function (Book $book) {
    return back()->with('success', 'お気に入り処理（仮）');
})->name('favorites.toggle');

// 仮：レビューいいね登録
Route::post('/reviews/{review}/like', function (Review $review) {
    return redirect()
        ->route('books.show', $review->book)
        ->with('success', 'レビューいいね処理（仮）');
})->name('reviews.like');
