<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ViewCounterService
{
    /**
     * Cooldown window (in seconds) before the same visitor (IP + User-Agent)
     * is counted again for the same article/project (6 hours).
     */
    private const DEDUP_TTL_SECONDS = 21600;
    private const FLUSH_INTERVAL_SECONDS = 300;

    /**
     * User-Agent fragments of crawlers, link-preview fetchers and HTTP clients.
     * Most bots never execute the client-side view beacon; this catches the rest.
     */
    private const BOT_UA_PATTERN = '/bot|crawl|spider|slurp|preview|fetch|scan|monitor|headless|lighthouse|pagespeed|curl|wget|python|axios|node|go-http|java\/|okhttp|facebookexternalhit|whatsapp|telegram|discord|embedly|bitly/i';

    public function record(string $type, int $id, Request $request): bool
    {
        if ($this->isBot($request)) {
            return false;
        }

        $visitorHash = $this->resolveVisitorHash($request);
        $seenKey = "views:seen:{$type}:{$id}:{$visitorHash}";

        // Deduplicate: only count 1 read per visitor per 6 hours
        if (Cache::has($seenKey)) {
            $this->maybeScheduleLazyFlush();
            return false;
        }

        Cache::put($seenKey, 1, self::DEDUP_TTL_SECONDS);

        $bufferKey = "views:buffer:{$type}:{$id}";
        $current = (int) Cache::get($bufferKey, 0);
        Cache::put($bufferKey, $current + 1, 86400 * 7);

        $dirtyKey = "views:dirty:{$type}";
        $dirtyIds = Cache::get($dirtyKey, []);
        if (! in_array($id, $dirtyIds, true)) {
            $dirtyIds[] = $id;
            Cache::put($dirtyKey, $dirtyIds, 86400 * 7);
        }

        $this->maybeScheduleLazyFlush();

        return true;
    }

    public function flush(): array
    {
        $flushedPosts = 0;
        $flushedProjects = 0;

        DB::transaction(function () use (&$flushedPosts, &$flushedProjects) {
            $flushedPosts = $this->flushType('post', 'posts');
            $flushedProjects = $this->flushType('project', 'projects');
        });

        Cache::put('views:last_flush_at', time(), 86400 * 7);

        return [
            'posts_views_flushed'    => $flushedPosts,
            'projects_views_flushed' => $flushedProjects,
            'flushed_at'             => now()->toISOString(),
        ];
    }

    public function totalBuffered(): int
    {
        return $this->bufferedForType('post') + $this->bufferedForType('project');
    }

    private function bufferedForType(string $type): int
    {
        $dirtyIds = Cache::get("views:dirty:{$type}", []);
        $sum = 0;
        foreach ($dirtyIds as $id) {
            $sum += (int) Cache::get("views:buffer:{$type}:{$id}", 0);
        }
        return $sum;
    }

    private function flushType(string $type, string $table): int
    {
        $dirtyKey = "views:dirty:{$type}";
        $dirtyIds = Cache::pull($dirtyKey, []);

        if (empty($dirtyIds)) {
            return 0;
        }

        $totalFlushed = 0;

        foreach ($dirtyIds as $id) {
            $bufferKey = "views:buffer:{$type}:{$id}";
            $delta = (int) Cache::pull($bufferKey, 0);

            if ($delta > 0) {
                DB::table($table)
                    ->where('id', $id)
                    ->increment('views_count', $delta);
                $totalFlushed += $delta;
            }
        }

        return $totalFlushed;
    }

    private function maybeScheduleLazyFlush(): void
    {
        $lastFlush = (int) Cache::get('views:last_flush_at', 0);

        if ($lastFlush === 0) {
            Cache::put('views:last_flush_at', time(), 86400 * 7);
            return;
        }

        if ((time() - $lastFlush) >= self::FLUSH_INTERVAL_SECONDS) {
            Cache::put('views:last_flush_at', time(), 86400 * 7);
            app()->terminating(function () {
                $this->flush();
            });
        }
    }

    public function isBot(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        return $ua === '' || preg_match(self::BOT_UA_PATTERN, $ua) === 1;
    }

    private function resolveVisitorHash(Request $request): string
    {
        $ip = $request->ip() ?: '127.0.0.1';
        $ua = substr((string) $request->userAgent(), 0, 255);
        return substr(hash('sha256', "{$ip}|{$ua}"), 0, 20);
    }
}
