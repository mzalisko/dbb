<?php

namespace Nwdb\Tests\Unit;

use Nwdb\WpFeed\Cipher;
use Nwdb\WpFeed\CipherException;
use PHPUnit\Framework\TestCase;

class CipherTest extends TestCase
{
    private Cipher $cipher;
    private string $symKey;
    private array $signPair;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cipher = new Cipher();
        $this->symKey = Cipher::generateSymmetricKey();
        $this->signPair = Cipher::generateSigningKeypair();
    }

    private function seal(string $plaintext): string
    {
        return $this->cipher->seal($plaintext, $this->symKey, $this->signPair['secret']);
    }

    public function test_round_trip(): void
    {
        $payload = json_encode(['v' => 1, 'contacts' => ['phones' => [['value' => '+380501112233']]]]);

        $envelope = $this->seal($payload);
        $opened = $this->cipher->open($envelope, $this->symKey, $this->signPair['public']);

        $this->assertSame($payload, $opened);
    }

    public function test_envelope_header_format(): void
    {
        $envelope = $this->seal('{"v":1}');

        $this->assertSame(Cipher::MAGIC, substr($envelope, 0, 4));
        $this->assertSame(Cipher::VERSION, ord($envelope[4]));
        $this->assertTrue($this->cipher->verify($envelope, $this->signPair['public']));
    }

    public function test_nonce_is_unique_per_seal(): void
    {
        $a = $this->seal('same payload');
        $b = $this->seal('same payload');

        $this->assertNotSame(substr($a, 6, 24), substr($b, 6, 24));
        $this->assertNotSame($a, $b);
    }

    public function test_tampered_ciphertext_is_rejected(): void
    {
        $envelope = $this->seal('secret data');
        $pos = 40; // усередині ciphertext
        $envelope[$pos] = chr(ord($envelope[$pos]) ^ 0xFF);

        $this->assertFalse($this->cipher->verify($envelope, $this->signPair['public']));
        $this->expectException(CipherException::class);
        $this->cipher->open($envelope, $this->symKey, $this->signPair['public']);
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $envelope = $this->seal('secret data');
        $last = strlen($envelope) - 1;
        $envelope[$last] = chr(ord($envelope[$last]) ^ 0xFF);

        $this->expectException(CipherException::class);
        $this->cipher->open($envelope, $this->symKey, $this->signPair['public']);
    }

    public function test_wrong_symmetric_key_fails(): void
    {
        $envelope = $this->seal('secret data');

        $this->expectException(CipherException::class);
        $this->cipher->open($envelope, Cipher::generateSymmetricKey(), $this->signPair['public']);
    }

    public function test_wrong_public_key_fails_verification(): void
    {
        $envelope = $this->seal('secret data');
        $other = Cipher::generateSigningKeypair();

        $this->assertFalse($this->cipher->verify($envelope, $other['public']));
    }

    public function test_truncated_envelope_is_rejected(): void
    {
        $envelope = substr($this->seal('secret data'), 0, 50);

        $this->expectException(CipherException::class);
        $this->cipher->open($envelope, $this->symKey, $this->signPair['public']);
    }

    public function test_invalid_key_sizes_are_rejected(): void
    {
        $this->expectException(CipherException::class);
        $this->cipher->seal('data', 'short-key', $this->signPair['secret']);
    }
}
