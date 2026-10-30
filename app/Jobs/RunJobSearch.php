<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\JobSearch\JobSearcher;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs a search after the response is sent, so the many model calls it makes can't hit the web server's timeout.
 * It runs in the web process, so no queue worker is needed.
 */
class RunJobSearch
{
    use Dispatchable;

    public function __construct(public User $user, public string $lockOwner) {}

    public static function lockKey(User $user): string
    {
        return "job-search.{$user->id}";
    }

    public function handle(JobSearcher $searcher): void
    {
        set_time_limit(0);

        try {
            $searcher->search($this->user);
        } catch (Throwable $exception) {
            Log::error("Job search failed: {$exception->getMessage()}");
        } finally {
            Cache::restoreLock(self::lockKey($this->user), $this->lockOwner)->release();
        }
    }
}
