<?php

namespace Nwdb\WpFeed;

use RuntimeException;

/**
 * Єдиний виняток на всі збої конверта: підробка підпису, пошкоджений
 * заголовок чи невдале дешифрування дають однакове повідомлення,
 * щоб не створювати oracle для атакуючого.
 */
class CipherException extends RuntimeException
{
    public static function invalidEnvelope(): self
    {
        return new self('Invalid envelope.');
    }

    public static function invalidKey(): self
    {
        return new self('Invalid key material.');
    }
}
