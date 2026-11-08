<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Services\ViewCounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewBeaconTest extends TestCase
{
    use RefreshDatabase;

    private const BROWSER_UA = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';

    private function makePost(string $status = 'published'): Post
    {
        return Post::create([
            'author_id'    => User::factory()->create()->id,
            'title'        => 'Indexing',
            'slug'         => 'indexing',
            'body'         => 'Body',
            'status'       => $status,
            'published_at' => $status === 'published' ? now()->subHour() : null,
            'reading_time' => 1,
        ]);
    }

    private function views(Post $post): int
    {
        app(ViewCounterService::class)->flush();

        return (int) $post->fresh()->views_count;
    }

    public function test_fetching_an_article_no_longer_counts_a_view(): void
    {
        $post = $this->makePost();

        $this->withHeader('User-Agent', self::BROWSER_UA)
            ->getJson('/api/v1/posts/indexing')->assertOk();

        $this->assertSame(0, $this->views($post));
    }

    public function test_browser_beacon_counts_once_per_visitor(): void
    {
        $post = $this->makePost();

        $this->withHeader('User-Agent', self::BROWSER_UA)
            ->postJson('/api/v1/posts/indexing/view')->assertNoContent();
        $this->withHeader('User-Agent', self::BROWSER_UA)
            ->postJson('/api/v1/posts/indexing/view')->assertNoContent();

        $this->assertSame(1, $this->views($post));
    }

    public function test_bots_and_empty_user_agents_are_ignored(): void
    {
        $post = $this->makePost();

        foreach ([
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'TelegramBot (like TwitterBot)',
            'curl/8.4.0',
            '',
        ] as $ua) {
            $this->withHeader('User-Agent', $ua)
                ->postJson('/api/v1/posts/indexing/view')->assertNoContent();
        }

        $this->assertSame(0, $this->views($post));
    }

    public function test_beacon_for_unpublished_post_returns_404(): void
    {
        $this->makePost('draft');

        $this->postJson('/api/v1/posts/indexing/view')->assertNotFound();
        $this->postJson('/api/v1/posts/missing/view')->assertNotFound();
    }
}
