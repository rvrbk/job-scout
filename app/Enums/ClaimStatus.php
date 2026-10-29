<?php

namespace App\Enums;

enum ClaimStatus: string
{
    case Pending = 'pending';
    case Supported = 'supported';
    case Unsupported = 'unsupported';
}
