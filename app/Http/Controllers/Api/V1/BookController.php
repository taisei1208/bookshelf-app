<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(IndexBookRequest $request)
    {
        $validated = $request->validated();
        $query = Book::with('genres');

        if ($request->filled('keyword')) {
            $keyword = $validated['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre_id')) {
            $genreId = $validated['genre_id'];
            $query->whereHas(
                'genres',
                function ($q) use ($genreId) {
                    $q->where('genres.id', $genreId);
                });
        }

        $perPage = $validated['per_page'] ?? 20;
        $books = $query->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return BookResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book = Book::create($validated);

        $book->genres()->sync($genreIds);

        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load([
            'genres',
            'reviews' => function ($query) {
                $query->with('user')->latest();
            },
        ]);

        return new BookResource($book);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBookRequest $request, Book $book)
    {
        $validated = $request->validated();
        $genreIds = $validated['genres'];
        unset($validated['genres']);

        $book->update($validated);

        $book->genres()->sync($genreIds);

        $book->load('genres');

        return new BookResource($book);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $book->delete();

        return response()->json(null, 204);
    }
}
