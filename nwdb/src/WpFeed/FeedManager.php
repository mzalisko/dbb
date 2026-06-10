<?php

namespace Nwdb\WpFeed;

use App\Models\Site;
use Nwdb\Models\FeedPublication;
use Nwdb\Models\SitePlugin;
use Nwdb\WpFeed\Publisher\FeedPublisher;

/**
 * Оркестратор життєвого циклу фіду: підключення сайту, публікація
 * конверта на dead-drop, ротація ключів/ідентичності, пауза.
 */
class FeedManager
{
    public function __construct(
        private readonly Cipher $cipher,
        private readonly PayloadBuilder $payloads,
        private readonly IdentityFactory $identities,
        private readonly FeedPublisher $publisher,
    ) {
    }

    /** Створити ідентичність білда + per-site ключ. Статус draft до першої публікації. */
    public function connect(Site $site): SitePlugin
    {
        return SitePlugin::create([
            ...$this->identities->make(),
            'site_id' => $site->id,
            'sym_key' => base64_encode(Cipher::generateSymmetricKey()),
            'status'  => SitePlugin::STATUS_DRAFT,
            'payload_version' => 1,
        ]);
    }

    /**
     * Зібрати payload → запечатати конверт → викласти на dead-drop.
     * Ідентичний payload (за contentHash) не публікується повторно.
     */
    public function publish(SitePlugin $plugin): FeedPublication
    {
        $payload = $this->payloads->build($plugin->site, $plugin->payload_version);
        $hash = $this->payloads->contentHash($payload);

        $latest = $plugin->latestPublication;
        if ($latest
            && $latest->status === FeedPublication::STATUS_PUBLISHED
            && $latest->payload_hash === $hash
            && $this->publisher->exists($plugin->feedPath())) {
            return $latest;
        }

        $publication = $plugin->publications()->create([
            'version'      => ($latest?->version ?? 0) + 1,
            'payload_hash' => $hash,
            'entry_count'  => $this->payloads->entryCount($payload),
            'disk'         => config('nwdb.deaddrop.disk'),
            'path'         => $plugin->feedPath(),
            'status'       => FeedPublication::STATUS_PENDING,
        ]);

        try {
            $envelope = $this->cipher->seal(
                $this->payloads->toCanonicalJson($payload),
                base64_decode($plugin->sym_key),
                $this->signingSecret(),
            );

            $this->publisher->publish($plugin->feedPath(), $envelope);

            $publication->update([
                'byte_size'    => strlen($envelope),
                'status'       => FeedPublication::STATUS_PUBLISHED,
                'published_at' => now(),
            ]);

            $plugin->update([
                'status'       => SitePlugin::STATUS_ENABLED,
                'connected_at' => $plugin->connected_at ?? now(),
            ]);
        } catch (\Throwable $e) {
            $publication->update([
                'status' => FeedPublication::STATUS_FAILED,
                'error'  => $e->getMessage(),
            ]);

            throw $e;
        }

        return $publication->refresh();
    }

    /**
     * Нова ідентичність + новий sym-ключ. Старий артефакт прибирається,
     * плагін треба перевстановити з нового ZIP (оновлення коду — вручну).
     */
    public function rotateKeys(SitePlugin $plugin): SitePlugin
    {
        $oldPath = $plugin->feedPath();

        $plugin->update([
            ...$this->identities->make(),
            'sym_key'         => base64_encode(Cipher::generateSymmetricKey()),
            'payload_version' => $plugin->payload_version + 1,
            'status'          => SitePlugin::STATUS_DRAFT,
            'keys_rotated_at' => now(),
        ]);

        $this->publisher->remove($oldPath);

        return $plugin->refresh();
    }

    /** Пауза: артефакт зникає з dead-drop, плагін лишається на кешованих даних. */
    public function pause(SitePlugin $plugin): void
    {
        $plugin->update(['status' => SitePlugin::STATUS_PAUSED]);
        $this->publisher->remove($plugin->feedPath());
    }

    public function resume(SitePlugin $plugin): FeedPublication
    {
        $plugin->update(['status' => SitePlugin::STATUS_ENABLED]);

        return $this->publish($plugin->refresh());
    }

    public function signingPublic(): string
    {
        $key = base64_decode((string) config('nwdb.signing.public'), true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw CipherException::invalidKey();
        }

        return $key;
    }

    private function signingSecret(): string
    {
        $key = base64_decode((string) config('nwdb.signing.secret'), true);

        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw CipherException::invalidKey();
        }

        return $key;
    }
}
