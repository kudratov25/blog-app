<?php

namespace App\Console\Commands;

use App\Services\ViewCounterService;
use Illuminate\Console\Command;

class FlushViewsCommand extends Command
{
    protected $signature = 'views:flush';

    protected $description = 'Flush buffered article and project view counts from cache into the database';

    public function handle(ViewCounterService $viewCounter): int
    {
        $result = $viewCounter->flush();

        $this->info(sprintf(
            'Flushed %d article read(s) and %d project read(s) to database at %s.',
            $result['posts_views_flushed'],
            $result['projects_views_flushed'],
            $result['flushed_at']
        ));

        return self::SUCCESS;
    }
}
