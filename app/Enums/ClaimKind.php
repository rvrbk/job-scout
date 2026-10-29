<?php

namespace App\Enums;

/**
 * A claim asserts something about the candidate and must be backed by ledger facts.
 * Framing is connective text (greeting, interest in the role, closing) that may assert nothing about the candidate.
 * A skill is a skill name that must exist in the ledger.
 */
enum ClaimKind: string
{
    case Claim = 'claim';
    case Framing = 'framing';
    case Skill = 'skill';
}
