<?php

namespace App\Enums;

/**
 * The voice a CV or cover letter is written in. It only changes how sentences
 * are worded, never what they claim: every claim is still checked against the ledger.
 */
enum DocumentTone: string
{
    case Professional = 'professional';
    case Direct = 'direct';
    case Warm = 'warm';
    case Playful = 'playful';
    case Formal = 'formal';

    public function label(): string
    {
        return match ($this) {
            self::Professional => __('Professional'),
            self::Direct => __('Direct'),
            self::Warm => __('Warm'),
            self::Playful => __('Playful'),
            self::Formal => __('Formal'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Professional => __('Clear and polished, the safe default.'),
            self::Direct => __('Short sentences, no filler, straight to the point.'),
            self::Warm => __('Friendly and personable, still professional.'),
            self::Playful => __('Light and witty, with some personality.'),
            self::Formal => __('Traditional and reserved, for conservative employers.'),
        };
    }

    /**
     * The instruction given to the model.
     */
    public function instruction(): string
    {
        return match ($this) {
            self::Professional => 'Write in a clear, polished, professional voice.',
            self::Direct => 'Write in a direct voice: short sentences, active verbs, no filler, pleasantries or hedging. State things plainly.',
            self::Warm => 'Write in a warm, friendly and personable voice, while staying professional.',
            self::Playful => 'Write in a playful voice: light, witty and with personality, using a touch of humour or an unexpected turn of phrase. Keep it tasteful, never flippant about the work itself, and never let a joke say more than the cited facts do.',
            self::Formal => 'Write in a formal, traditional and reserved voice, with full sentences and no contractions or colloquialisms.',
        };
    }
}
