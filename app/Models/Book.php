<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Book extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'published_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre')->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoritedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')
            ->withTimestamps();
    }

    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }

    /**
     * タイトルまたは著者をキーワードで部分一致検索する。
     */
    public function scopeSearchKeyword(Builder $query, ?string $keyword):Builder
    {
        if ($keyword === null || $keyword === '') {
            return $query;
        }

        return $query->where(
            function (Builder $query) use ($keyword): void
            {
                $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            }
        );
    }

    /**
     * 指定されたジャンルに紐づく書籍へ絞り込む。
     */
    public function scopeForGenre(Builder $query, ?int $genreId): Builder
    {
        if ($genreId === null) {return $query;}

        return $query->whereHas(
            'genres',
            function (Builder $query) use ($genreId): void {
                $query->where('genres.id', $genreId);
            }
        );
    }

    /**
     * 指定された条件で書籍を並び替える。
     */
    public function scopeSorted(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'oldest' => $query->oldest('created_at'),

            'title' =>$query->orderBy('title'),

            'rating' => $query->orderByRaw(
                'reviews_avg_rating IS NULL ASC'
            )
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('created_at'),

            default => $query->latest('created_at')
        };
    }
}
