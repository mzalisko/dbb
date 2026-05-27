<?php

/**
 * Ukrainian word form by number.
 * 1 → $one (сайт, група), 2-4 → $few (сайти, групи), 5+ → $many (сайтів, груп).
 */
function ua_word(int $n, string $one, string $few, string $many): string
{
    $mod100 = abs($n) % 100;
    $mod10  = abs($n) % 10;

    if ($mod100 >= 11 && $mod100 <= 19) return $many;
    if ($mod10 === 1)                    return $one;
    if ($mod10 >= 2 && $mod10 <= 4)     return $few;

    return $many;
}
