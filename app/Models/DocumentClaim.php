<?php

namespace App\Models;

use App\Enums\ClaimKind;
use App\Enums\ClaimStatus;
use Database\Factories\DocumentClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $generated_document_id
 * @property int|null $ledger_entry_id
 * @property string $section
 * @property int $group
 * @property int $position
 * @property ClaimKind $kind
 * @property string $text
 * @property ClaimStatus $status
 * @property string|null $verifier_note
 */
#[Fillable(['ledger_entry_id', 'section', 'group', 'position', 'kind', 'text', 'status', 'verifier_note'])]
class DocumentClaim extends Model
{
    /** @use HasFactory<DocumentClaimFactory> */
    use HasFactory;

    public const string SECTION_SUMMARY = 'summary';

    public const string SECTION_EXPERIENCE = 'experience';

    public const string SECTION_SKILLS = 'skills';

    public const string SECTION_LETTER = 'letter';

    /**
     * @return BelongsTo<GeneratedDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(GeneratedDocument::class, 'generated_document_id');
    }

    /**
     * @return BelongsTo<LedgerEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'ledger_entry_id');
    }

    /**
     * The ledger facts this claim cites as its source.
     *
     * @return BelongsToMany<LedgerFact, $this>
     */
    public function facts(): BelongsToMany
    {
        return $this->belongsToMany(LedgerFact::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => ClaimKind::class,
            'status' => ClaimStatus::class,
        ];
    }
}
