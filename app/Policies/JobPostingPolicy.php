<?php

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    public function view(User $user, JobPosting $jobPosting): bool
    {
        return $user->id === $jobPosting->user_id;
    }

    public function update(User $user, JobPosting $jobPosting): bool
    {
        return $user->id === $jobPosting->user_id;
    }

    public function delete(User $user, JobPosting $jobPosting): bool
    {
        return $user->id === $jobPosting->user_id;
    }
}
