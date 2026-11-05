<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin user (loaded from .env)
        $adminEmail = env('ADMIN_EMAIL');
        $adminPassword = env('ADMIN_PASSWORD');
        $adminName = env('ADMIN_NAME', 'Admin');

        if (empty($adminEmail) || empty($adminPassword)) {
            throw new \RuntimeException('ADMIN_EMAIL and ADMIN_PASSWORD must be set in .env before running DatabaseSeeder.');
        }

        $admin = User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name'     => $adminName,
                'password' => Hash::make($adminPassword),
            ]
        );

        // 2. Categories
        $categories = [
            [
                'name'        => 'Backend Engineering',
                'slug'        => 'backend-engineering',
                'description' => 'Deep dives into modern backend patterns, concurrency, API design, and scalable infrastructure.',
            ],
            [
                'name'        => 'System Architecture',
                'slug'        => 'system-architecture',
                'description' => 'Exploring resilient system design, distributed data models, caching patterns, and trade-offs.',
            ],
            [
                'name'        => 'Practical Guides',
                'slug'        => 'practical-guides',
                'description' => 'Hands-on tutorials, debugging case studies, and real-world implementation notes.',
            ],
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[$cat['slug']] = Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 3. Tags
        $tagsData = [
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'PHP', 'slug' => 'php'],
            ['name' => 'PostgreSQL', 'slug' => 'postgresql'],
            ['name' => 'Docker', 'slug' => 'docker'],
            ['name' => 'Vue.js', 'slug' => 'vuejs'],
            ['name' => 'Architecture', 'slug' => 'architecture'],
        ];

        $tagModels = [];
        foreach ($tagsData as $t) {
            $tagModels[$t['slug']] = Tag::firstOrCreate(['slug' => $t['slug']], $t);
        }

        // 4. Technologies
        $technologiesData = [
            ['name' => 'Laravel', 'slug' => 'laravel'],
            ['name' => 'Vue 3', 'slug' => 'vue-3'],
            ['name' => 'Nuxt 3', 'slug' => 'nuxt-3'],
            ['name' => 'PostgreSQL', 'slug' => 'postgresql'],
            ['name' => 'Redis', 'slug' => 'redis'],
            ['name' => 'Docker', 'slug' => 'docker'],
            ['name' => 'TypeScript', 'slug' => 'typescript'],
        ];

        $techModels = [];
        foreach ($technologiesData as $tech) {
            $techModels[$tech['slug']] = Technology::firstOrCreate(['slug' => $tech['slug']], $tech);
        }

        // 5. Sample Published Posts
        $post1 = Post::firstOrCreate(
            ['slug' => 'designing-authentication-and-session-invalidation-in-laravel'],
            [
                'author_id'       => $admin->id,
                'category_id'     => $categoryModels['backend-engineering']->id,
                'title'           => 'Designing Authentication and Session Invalidation in Laravel',
                'excerpt'         => 'A practical guide to implementing robust authentication, token lifecycle management, and immediate cross-device session invalidation in modern Laravel applications.',
                'body'            => <<<'MARKDOWN'
## Introduction

Authentication in distributed web architectures often treats session termination as a simple local cookie deletion. In security-sensitive environments, however, user logout must immediately invalidate active sessions across all connected devices and edge caches.

In this article, we examine how Laravel handles stateful and stateless authentication with Sanctum, how tokens can be efficiently verified, and how to build a reliable invalidation workflow.

### The Problem with Soft Invalidation

When building APIs consumed by Single Page Applications (SPAs), many developers store access tokens in `localStorage` or `sessionStorage` and implement logout by simply wiping the client-side store:

```javascript
// A common anti-pattern
function logout() {
  localStorage.removeItem('auth_token');
  window.location.href = '/login';
}
```

If the token itself remains valid on the server, any intercepted token can continue making authorized requests until its natural expiration.

### Enforcing Server-Side Revocation

With Laravel Sanctum, tokens are backed by the `personal_access_tokens` database table. Server-side revocation ensures the token is deleted from storage immediately:

```php
public function logout(Request $request): JsonResponse
{
    // Revoke the specific token that authenticated the current request
    $request->user()->currentAccessToken()?->delete();

    return response()->json([
        'message' => 'Token revoked successfully',
    ]);
}
```

If a user suspects compromised credentials, an "Invalidate all sessions" action can wipe all personal access tokens simultaneously:

```php
public function revokeAll(Request $request): JsonResponse
{
    $request->user()->tokens()->delete();

    return response()->json([
        'message' => 'All active sessions have been terminated.',
    ]);
}
```

### High-Throughput Token Validation with Redis

For high-traffic APIs, database queries on every authenticated request introduce latency. A lightweight Redis cache layer can store active token hashes with an automated TTL:

```php
// Check Redis first, fallback to PostgreSQL
$cacheKey = "auth_token:" . hash('sha256', $plainTextToken);
$userId = Redis::get($cacheKey);

if (! $userId) {
    // Database lookup and re-populate Redis
}
```

## Conclusion

A security-first authentication workflow combines server-enforced token revocation, rate-limited login endpoints, and fast caching strategies. In the next post, we will explore database indexing benchmarks for large token stores.
MARKDOWN,
                'status'          => 'published',
                'is_featured'     => true,
                'reading_time'    => 4,
                'views_count'     => 140,
                'published_at'    => now()->subDays(3),
                'seo_title'       => 'Authentication and Session Invalidation in Laravel',
                'seo_description' => 'Learn how to implement bulletproof session invalidation and token management in Laravel Sanctum.',
            ]
        );
        $post1->tags()->sync([
            $tagModels['laravel']->id,
            $tagModels['php']->id,
            $tagModels['architecture']->id,
        ]);

        $post2 = Post::firstOrCreate(
            ['slug' => 'database-indexing-what-it-changes-and-how-to-measure-it'],
            [
                'author_id'       => $admin->id,
                'category_id'     => $categoryModels['system-architecture']->id,
                'title'           => 'Database Indexing: What It Changes and How to Measure It',
                'excerpt'         => 'Understanding B-tree index mechanics in PostgreSQL, analyzing execution plans with EXPLAIN ANALYZE, and avoiding premature over-indexing traps.',
                'body'            => <<<'MARKDOWN'
## The Cost of Sequential Scans

When a table grows past several hundred thousand rows, queries without appropriate index support degrade rapidly. Let us analyze what happens behind the scenes during query execution.

### Analyzing Query Plans

PostgreSQL provides `EXPLAIN (ANALYZE, BUFFERS)` to inspect query planning and execution statistics:

```sql
EXPLAIN (ANALYZE, BUFFERS)
SELECT * FROM posts 
WHERE status = 'published' 
  AND published_at <= NOW()
ORDER BY published_at DESC 
LIMIT 10;
```

Without a composite index on `(status, published_at)`, the database engine must execute a **Seq Scan** across the entire table, loading blocks from disk into buffer memory and filtering row by row.

### Constructing Composite Indexes

Compound indexes must follow query access patterns. For filtering by status and sorting by publication date:

```sql
CREATE INDEX idx_posts_status_published_at 
ON posts (status, published_at DESC);
```

After adding this index, the query planner transitions to an **Index Scan**:

```text
Limit (cost=0.28..12.45 rows=10 width=850) (actual time=0.035..0.048 rows=10 loops=1)
  -> Index Scan using idx_posts_status_published_at on posts
```

Execution time drops from milliseconds to fractions of a millisecond.

### When NOT to Index

Every index introduces overhead on `INSERT`, `UPDATE`, and `DELETE` operations. Keep indexes focused on high-cardinality filters and common sorting patterns.
MARKDOWN,
                'status'          => 'published',
                'is_featured'     => true,
                'reading_time'    => 3,
                'views_count'     => 227,
                'published_at'    => now()->subDay(),
                'seo_title'       => 'PostgreSQL Indexing: Measurement and Strategy',
                'seo_description' => 'Explore PostgreSQL B-tree index performance and query execution plan analysis.',
            ]
        );
        $post2->tags()->sync([
            $tagModels['postgresql']->id,
            $tagModels['architecture']->id,
        ]);

        // 6. Sample Projects
        $proj1 = Project::firstOrCreate(
            ['slug' => 'developer-blog-platform'],
            [
                'title'           => 'Personal Developer Publication Platform',
                'summary'         => 'A bespoke, editorial-grade publishing platform and portfolio built with a headless Laravel 11 API and Nuxt 3 frontend.',
                'body'            => <<<'MARKDOWN'
### Overview

Built to provide a high-performance, distraction-free reading experience for technical articles while maintaining a private, secure content-management system.

### Key Architecture Decisions

- **Headless Separation:** Decoupled backend REST API in Laravel with a lightweight Nuxt 3 client for optimized rendering and rapid edge caching.
- **Strict Typing:** TypeScript throughout the frontend layer ensuring high code reliability and seamless API contract binding.
- **Relational Data Integrity:** Normalized PostgreSQL schema with foreign key cascades, soft deletes, and revision history tracking.
- **Dockerized Environment:** Multi-container orchestrations with Nginx, PHP-FPM 8.4, PostgreSQL 16, Redis 7, and Node.js.
MARKDOWN,
                'status'          => 'published',
                'role'            => 'Lead Architect & Developer',
                'started_at'      => '2026-01-01',
                'completed_at'    => '2026-11-04',
                'repository_url'  => 'https://github.com/sherzodkudratov/blog-app',
                'demo_url'        => 'https://blog.dev',
                'is_featured'     => true,
                'display_order'   => 0,
                'views_count'     => 95,
                'seo_title'       => 'Developer Blog Platform Project Case Study',
                'seo_description' => 'Case study of the developer publication platform architecture and implementation.',
            ]
        );
        $proj1->technologies()->sync([
            $techModels['laravel']->id,
            $techModels['nuxt-3']->id,
            $techModels['postgresql']->id,
            $techModels['docker']->id,
        ]);

        // 7. Settings (only insert if key does not exist yet, never overwrite admin changes)
        $settings = [
            'site_title'            => 'Engineering notes on resilient backends, databases, and scalable systems.',
            'site_subtitle'         => 'Software Engineer · Backend & Systems',
            'site_description'      => 'Deep-dive breakdowns on PostgreSQL indexing, distributed caching, Laravel architecture, and real-world production trade-offs.',
            'author_name'           => $adminName,
            'author_email'          => '',
            'author_bio'            => 'Software engineer specializing in resilient backend architectures, distributed services, and developer tooling.',
            'author_about_extended' => 'I build backend services and web applications with a focus on simplicity, predictable performance, and maintainable architecture. I favor clear modular boundaries before premature distributed complexity, and rely on query analysis and automated tests to keep production systems dependable.',
            'author_connect_text'   => 'Open to engineering discussions, technical collaborations, and backend architecture consulting.',
            'author_github'         => '',
            'author_linkedin'       => '',
            'author_telegram'       => '',
        ];

        foreach ($settings as $key => $val) {
            Setting::firstOrCreate(['key' => $key], ['value' => $val]);
        }
    }
}
