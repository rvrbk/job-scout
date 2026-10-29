<?php

namespace App\Policies;

use App\Models\LedgerEntry;
use App\Models\User;

class LedgerEntryPolicy
{
    public function update(User $user, LedgerEntry $ledgerEntry): bool
    {
        return $user->id === $ledgerEntry->user_id;
    }

    public function delete(User $user, LedgerEntry $ledgerEntry): bool
    {
        return $user->id === $ledgerEntry->user_id;
    }
}
