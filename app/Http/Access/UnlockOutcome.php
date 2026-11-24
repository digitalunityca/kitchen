<?php

declare(strict_types=1);

namespace App\Http\Access;

enum UnlockOutcome
{
    case Unlocked;

    case WrongPin;

    /** The wrong PIN that used up the last allowed try. */
    case LockedOut;
}
