<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Cv\LedgerImporter;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Reads an uploaded CV into the ledger after the response is sent, since the model call outlasts a web request.
 * Like the job search it runs in the web process; the outcome is kept in the cache for the ledger page to show.
 */
class ImportLedger
{
    use Dispatchable;

    public function __construct(public User $user, public string $cvText) {}

    public static function runningKey(User $user): string
    {
        return "ledger-import.{$user->id}.running";
    }

    public static function resultKey(User $user): string
    {
        return "ledger-import.{$user->id}.result";
    }

    public function handle(LedgerImporter $importer): void
    {
        set_time_limit(0);

        try {
            $result = $importer->import($this->user, $this->cvText);
            Cache::put(self::resultKey($this->user), $result, now()->addDay());
        } catch (Throwable $exception) {
            Log::error("Ledger import failed: {$exception->getMessage()}");
            Cache::put(self::resultKey($this->user), ['error' => __('The import failed. Nothing was added.')], now()->addDay());
        } finally {
            Cache::forget(self::runningKey($this->user));
        }
    }
}
