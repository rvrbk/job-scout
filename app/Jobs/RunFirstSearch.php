<?php

namespace App\Jobs;

use App\Models\JobSearchRun;
use App\Models\User;
use App\Services\Cv\LedgerSnapshot;
use Illuminate\Support\Facades\Cache;

/**
 * A new subscriber should see matches soon after confirming their first facts, not at the next scheduled run.
 * Manual searches are admin-only, so this starts one search per user, the first time they are able to have one.
 */
class RunFirstSearch
{
    /**
     * Starts the user's first search after the response is sent, if they are due one. Returns whether it started.
     */
    public static function startFor(User $user): bool
    {
        if (! $user->hasAccess() || $user->jobSearchRuns()->exists() || ! LedgerSnapshot::forUser($user)->hasFacts()) {
            return false;
        }

        $lock = Cache::lock(RunJobSearch::lockKey($user), 1800);

        if (! $lock->get()) {
            return false;
        }

        RunJobSearch::dispatchAfterResponse($user, $lock->owner(), JobSearchRun::TRIGGER_MANUAL);

        return true;
    }
}
