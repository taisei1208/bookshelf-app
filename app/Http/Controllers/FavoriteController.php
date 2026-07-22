<?php

namespace App\Http\Controllers;

use App\Models\Book;

class FavoriteController extends Controller
{
    public function index()
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->latest('favorites.created_at')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Book $book)
    {
        $user = auth()->user();

        $isFavorite = $user->favoriteBooks()
            ->whereKey($book->id)
            ->exists();

        if ($isFavorite) {
            $user->favoriteBooks()->detach($book->id);

            return back()->with('success', 'お気に入りを解除しました。');
        }

        $user->favoriteBooks()->syncWithoutDetaching([$book->id]);

        return back()->with('success', 'お気に入りを追加しました。');
    }
}
