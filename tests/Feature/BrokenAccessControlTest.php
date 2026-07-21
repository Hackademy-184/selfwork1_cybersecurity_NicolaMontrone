<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokenAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_delete_another_users_article(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $article = Article::factory()->for($owner)->create();

        $this->actingAs($attacker)
            ->delete(route('articles.destroy', $article))
            ->assertRedirect()
            ->assertSessionHas('errors', 'Not authorized');

        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }

    public function test_an_author_cannot_publish_an_article_by_tampering_with_the_request(): void
    {
        $author = User::factory()->create();
        $article = Article::factory()->for($author)->create(['published' => false]);

        $this->actingAs($author)
            ->put(route('articles.update', $article), [
                'title' => 'Updated title',
                'content' => 'Updated content',
                'published' => true,
            ])
            ->assertRedirect(route('articles.show', $article));

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'published' => false,
        ]);
    }

    public function test_an_unpublished_article_is_not_available_to_other_users(): void
    {
        $owner = User::factory()->create();
        $visitor = User::factory()->create();
        $article = Article::factory()->for($owner)->create(['published' => false]);

        $this->actingAs($visitor)
            ->get(route('articles.show', $article))
            ->assertNotFound();
    }

    public function test_a_non_admin_cannot_access_the_admin_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('profile'));
    }

    public function test_role_changes_are_not_available_via_get_requests(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($admin)
            ->get(route('admin.users.toggle', $user))
            ->assertMethodNotAllowed();
    }
}
