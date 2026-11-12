<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_and_receive_token(): void
    {
        $user = User::factory()->create([
            'email'    => 'admin@blog.dev',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'admin@blog.dev',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_access_admin_dashboard(): void
    {
        $response = $this->getJson('/api/v1/admin/dashboard');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'stats' => [
                        'published_posts',
                        'draft_posts',
                        'scheduled_posts',
                        'total_projects',
                        'unread_messages',
                    ],
                    'recent_posts',
                    'recent_messages',
                ],
            ]);
    }

    public function test_public_posts_only_shows_published(): void
    {
        $user = User::factory()->create();

        // Draft
        Post::create([
            'author_id'    => $user->id,
            'title'        => 'Draft Post',
            'slug'         => 'draft-post',
            'body'         => 'Draft content',
            'status'       => 'draft',
            'reading_time' => 1,
        ]);

        // Published
        Post::create([
            'author_id'    => $user->id,
            'title'        => 'Published Post',
            'slug'         => 'published-post',
            'body'         => 'Published content',
            'status'       => 'published',
            'published_at' => now()->subHour(),
            'reading_time' => 1,
        ]);

        $response = $this->getJson('/api/v1/posts');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('published-post', $data[0]['slug']);
    }

    public function test_admin_can_create_post_with_revision(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'name' => 'Backend',
            'slug' => 'backend',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/posts', [
                'title'       => 'First Automated Article',
                'body'        => 'This is a comprehensive article about Laravel architecture.',
                'excerpt'     => 'Short summary of the article.',
                'status'      => 'published',
                'category_id' => $category->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'First Automated Article')
            ->assertJsonPath('data.slug', 'first-automated-article');

        $post = Post::where('slug', 'first-automated-article')->first();
        $this->assertNotNull($post);
        $this->assertCount(1, $post->revisions);
    }

    public function test_contact_form_submission(): void
    {
        $response = $this->postJson('/api/v1/contact', [
            'name'    => 'Jane Reader',
            'email'   => 'jane@example.com',
            'subject' => 'Question about architecture',
            'message' => 'I really enjoyed your article on session invalidation!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Thank you for reaching out! Your message has been received.');

        $this->assertDatabaseHas('contact_messages', [
            'email'  => 'jane@example.com',
            'status' => 'unread',
        ]);
    }

    public function test_public_projects_are_listed_newest_first(): void
    {
        $make = fn (string $slug, ?string $started) => Project::create([
            'title' => $slug, 'slug' => $slug, 'summary' => 's',
            'status' => 'published', 'started_at' => $started,
        ]);
        $make('old', '2024-02-01');
        $make('undated', null);
        $make('newest', '2026-11-04');
        $make('middle', '2026-01-01');

        $this->getJson('/api/v1/projects')
            ->assertOk()
            ->assertJsonPath('data.*.slug', ['newest', 'middle', 'old', 'undated']);
    }
}
