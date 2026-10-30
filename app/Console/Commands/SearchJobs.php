<?php

namespace App\Console\Commands;

use App\Jobs\RunJobSearch;
use App\Models\User;
use App\Services\Cv\LedgerSnapshot;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

#[Signature('jobs:search')]
#[Description('Run the job search for every user who has set search preferences')]
class SearchJobs extends Command
{
    public function handle(): int
    {
        User::whereHas('jobSearchPreference')->each(function (User $user) {
            if (! LedgerSnapshot::forUser($user)->hasFacts()) {
                return;
            }

            $lock = Cache::lock(RunJobSearch::lockKey($user), 1800);

            // A search the user started by hand is still running.
            if (! $lock->get()) {
                return;
            }

            $this->info("Searching for user {$user->id}");

            RunJobSearch::dispatchSync($user, $lock->owner());
        });

        return self::SUCCESS;
    }
}
