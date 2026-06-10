<?php

namespace Nwdb\WpFeed\Publisher;

use Illuminate\Support\Facades\Storage;

/**
 * Пише конверти на диск deaddrop — локальну директорію, яку
 * rsync/mount доставляє на нейтральний статик-хост (Caddy) за Tailscale.
 */
class LocalPathPublisher implements FeedPublisher
{
    public function __construct(private readonly string $disk = 'deaddrop')
    {
    }

    public function publish(string $path, string $bytes): string
    {
        Storage::disk($this->disk)->put($path, $bytes);

        return $path;
    }

    public function remove(string $path): void
    {
        Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }
}
