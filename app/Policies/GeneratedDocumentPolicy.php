<?php

namespace App\Policies;

use App\Models\GeneratedDocument;
use App\Models\User;

class GeneratedDocumentPolicy
{
    public function view(User $user, GeneratedDocument $generatedDocument): bool
    {
        return $user->id === $generatedDocument->user_id;
    }

    public function update(User $user, GeneratedDocument $generatedDocument): bool
    {
        return $user->id === $generatedDocument->user_id;
    }

    public function delete(User $user, GeneratedDocument $generatedDocument): bool
    {
        return $user->id === $generatedDocument->user_id;
    }
}
