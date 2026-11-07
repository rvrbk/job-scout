<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Enums\DocumentStatus;
use App\Enums\DocumentStyle;
use App\Enums\DocumentTone;
use App\Enums\DocumentType;
use Database\Factories\GeneratedDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $job_posting_id
 * @property DocumentType $type
 * @property DocumentStyle $style
 * @property DocumentTone $tone
 * @property DocumentStatus $status
 * @property list<array{requirement: string, note: string}>|null $gaps
 * @property string|null $error
 * @property Carbon|null $approved_at
 */
#[Fillable(['job_posting_id', 'type', 'style', 'tone', 'status', 'gaps', 'error', 'approved_at'])]
class GeneratedDocument extends Model
{
    /** @use HasFactory<GeneratedDocumentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<JobPosting, $this>
     */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    /**
     * @return HasMany<DocumentClaim, $this>
     */
    public function claims(): HasMany
    {
        return $this->hasMany(DocumentClaim::class)->orderBy('id');
    }

    /**
     * Derive the review status from the verification state of the claims.
     * Any change to the claims revokes a previous approval.
     */
    public function refreshStatusFromClaims(): void
    {
        $statuses = $this->claims()->pluck('status');

        $this->status = match (true) {
            $statuses->contains(ClaimStatus::Pending) => DocumentStatus::Verifying,
            $statuses->contains(ClaimStatus::Unsupported) => DocumentStatus::NeedsReview,
            default => DocumentStatus::Verified,
        };

        $this->approved_at = null;
        $this->save();
    }

    /**
     * What the document is for: the job's title, or a label for a general document.
     */
    public function subject(): string
    {
        return $this->jobPosting?->title ?? __('General');
    }

    public function canBeApproved(): bool
    {
        return $this->status === DocumentStatus::Verified && $this->claims()->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'style' => DocumentStyle::class,
            'tone' => DocumentTone::class,
            'status' => DocumentStatus::class,
            'gaps' => 'array',
            'approved_at' => 'datetime',
        ];
    }
}
