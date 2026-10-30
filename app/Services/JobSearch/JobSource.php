<?php

namespace App\Services\JobSearch;

use App\Models\JobSearchPreference;

interface JobSource
{
    /**
     * Whether this source can run, e.g. its API credentials are configured.
     */
    public function available(): bool;

    /**
     * @return list<FoundJob>
     *
     * @throws \Throwable when the source can't be reached; the searcher skips it.
     */
    public function fetch(JobSearchPreference $preferences): array;
}
