<?php

namespace Nwdb\WpFeed;

/**
 * Крипто-ядро dead-drop конвертів: XChaCha20-Poly1305 (AEAD, per-site ключ)
 * + Ed25519 (один центральний підписний ключ). Encrypt-then-sign: плагін
 * спочатку верифікує підпис над усім blob-ом, потім дешифрує.
 *
 * Формат конверта (binary):
 *   magic      4   "WPF1"
 *   version    1   0x01
 *   flags      1   0x00 (зарезервовано)
 *   nonce      24  XChaCha20-Poly1305 nonce
 *   ct_len     4   uint32 LE, довжина ciphertext
 *   ciphertext ..  AEAD(payload, AD = header)
 *   signature  64  Ed25519 над усім, крім самого підпису
 *
 * Associated data AEAD = увесь заголовок (magic..ct_len), що прив'язує
 * заголовок до ciphertext навіть без перевірки підпису.
 *
 * Клас stateless і не залежить від Laravel — вся ключова сировина
 * передається як raw bytes.
 */
final class Cipher
{
    public const MAGIC = 'WPF1';
    public const VERSION = 1;

    private const FLAGS = 0;
    private const NONCE_BYTES = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES; // 24
    private const KEY_BYTES = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES;    // 32
    private const TAG_BYTES = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_ABYTES;      // 16
    private const SIGN_BYTES = SODIUM_CRYPTO_SIGN_BYTES;                              // 64
    private const HEADER_BYTES = 4 + 1 + 1 + self::NONCE_BYTES + 4;                   // 34

    /** Новий 32-байтний симетричний ключ (raw bytes). */
    public static function generateSymmetricKey(): string
    {
        return random_bytes(self::KEY_BYTES);
    }

    /** Нова підписна пара Ed25519: ['public' => 32b, 'secret' => 64b] (raw bytes). */
    public static function generateSigningKeypair(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'public' => sodium_crypto_sign_publickey($pair),
            'secret' => sodium_crypto_sign_secretkey($pair),
        ];
    }

    /** Зашифрувати та підписати payload у повний конверт (raw binary string). */
    public function seal(string $plaintext, string $symKey, string $signSecret): string
    {
        if (strlen($symKey) !== self::KEY_BYTES || strlen($signSecret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw CipherException::invalidKey();
        }

        $nonce = random_bytes(self::NONCE_BYTES);
        $ctLen = strlen($plaintext) + self::TAG_BYTES;

        $header = self::MAGIC
            . chr(self::VERSION)
            . chr(self::FLAGS)
            . $nonce
            . pack('V', $ctLen);

        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
            $plaintext, $header, $nonce, $symKey
        );

        $signature = sodium_crypto_sign_detached($header . $ciphertext, $signSecret);

        sodium_memzero($plaintext);

        return $header . $ciphertext . $signature;
    }

    /** Перевірити підпис, потім дешифрувати. Будь-який збій → CipherException. */
    public function open(string $envelope, string $symKey, string $signPublic): string
    {
        if (strlen($symKey) !== self::KEY_BYTES || strlen($signPublic) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw CipherException::invalidKey();
        }

        if (! $this->verify($envelope, $signPublic)) {
            throw CipherException::invalidEnvelope();
        }

        $nonce = substr($envelope, 6, self::NONCE_BYTES);
        $ctLen = unpack('V', substr($envelope, self::HEADER_BYTES - 4, 4))[1];
        $header = substr($envelope, 0, self::HEADER_BYTES);
        $ciphertext = substr($envelope, self::HEADER_BYTES, $ctLen);

        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
            $ciphertext, $header, $nonce, $symKey
        );

        if ($plaintext === false) {
            throw CipherException::invalidEnvelope();
        }

        return $plaintext;
    }

    /** Перевірка цілісності конверта і підпису без дешифрування. */
    public function verify(string $envelope, string $signPublic): bool
    {
        $minLen = self::HEADER_BYTES + self::TAG_BYTES + self::SIGN_BYTES;
        if (strlen($envelope) < $minLen) {
            return false;
        }

        if (substr($envelope, 0, 4) !== self::MAGIC || ord($envelope[4]) !== self::VERSION) {
            return false;
        }

        $ctLen = unpack('V', substr($envelope, self::HEADER_BYTES - 4, 4))[1];
        if (strlen($envelope) !== self::HEADER_BYTES + $ctLen + self::SIGN_BYTES) {
            return false;
        }

        $signed = substr($envelope, 0, self::HEADER_BYTES + $ctLen);
        $signature = substr($envelope, -self::SIGN_BYTES);

        return sodium_crypto_sign_verify_detached($signature, $signed, $signPublic);
    }
}
