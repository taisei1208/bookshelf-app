<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_genre_index_displays_genres(): void
    {
        $user = User::factory()->create();

        $firstGenre = Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $secondGenre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.index'));

        $response->assertOk();
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
        $response->assertSee($firstGenre->name);
        $response->assertSee($secondGenre->name);
    }

    public function test_authenticated_user_can_view_genre_create_screen(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('genres.create'));

        $response->assertOk();
        $response->assertViewIs('genres.create');
    }

    public function test_authenticated_user_can_store_genre(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'ファンタジー',
            ]);

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'success',
            'ジャンルを登録しました。'
        );

        $this->assertDatabaseHas('genres', [
            'name' => 'ファンタジー',
        ]);
    }

    public function test_store_rejects_invalid_genre(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), [
                'name' => null,
            ]);

        $response->assertRedirect(
            route('genres.create')
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('genres', [
            'name' => '',
        ]);
    }

    public function test_store_rejects_duplicate_genre_name(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('genres.create'))
            ->post(route('genres.store'), [
                'name' => 'ファンタジー',
            ]);

        $response->assertRedirect(
            route('genres.create')
        );

        $response->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }

    public function test_genre_detail_displays_related_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ファンタジー',
        ]);

        $book = Book::factory()->create([
            'title' => 'ジャンルに紐づく書籍',
        ]);

        $genre->books()->attach($book->id);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');

        $response->assertViewHas('genre');
        $response->assertViewHas('books');
        $response->assertSee($genre->name);
        $response->assertSee($book->title);
    }

    public function test_authenticated_user_can_view_genre_edit_screen(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '変更前ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('genres.edit', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.edit');
        $response->assertViewHas('genre');
        $response->assertSee($genre->name);
    }

    public function test_authenticated_user_can_update_genre(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => '変更前ジャンル',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '変更後ジャンル',
            ]);

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'success',
            'ジャンルを更新しました。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '変更後ジャンル',
        ]);

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
            'name' => '変更前ジャンル',
        ]);
    }

    public function test_authenticated_user_can_delete_genre_without_books(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'success',
            'ジャンルを削除しました。'
        );

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_genre_with_books_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $genre->books()->attach($book->id);

        $response = $this
            ->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(
            route('genres.index')
        );

        $response->assertSessionHas(
            'error',
            'このジャンルに紐づく書籍があるため削除できません。'
        );

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }
}
