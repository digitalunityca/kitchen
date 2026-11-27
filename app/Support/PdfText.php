<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Text for the Dompdf print sheets: the body font (Helvetica) only has the
 * Windows-1252 characters, so anything else (→, ≠, ≤…) is wrapped in DejaVu
 * Sans, which has them, instead of printing "?".
 */
final class PdfText
{
    private const OUTSIDE_HELVETICA = '/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{007E}\x{00A0}-\x{00FF}€‚ƒ„…†‡ˆ‰Š‹ŒŽ‘’“”•–—˜™š›œžŸ]+/u';

    public static function html(string $text): string
    {
        return (string) preg_replace_callback(self::OUTSIDE_HELVETICA, fn (array $m): string => '<span style="font-family: \'DejaVu Sans\'">'.$m[0].'</span>', e($text));
    }
}
