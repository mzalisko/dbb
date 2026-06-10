<?php

namespace Nwdb\WpFeed\Publisher;

use RuntimeException;

/**
 * Заглушка під майбутній R2/MinIO-драйвер: потребує league/flysystem-aws-s3-v3.
 * Зараз кидає виняток, щоб помилкова конфігурація виявлялась одразу.
 */
class S3FeedPublisher implements FeedPublisher
{
    public function publish(string $path, string $bytes): string
    {
        throw new RuntimeException('S3 publisher is not implemented yet. Use NWDB_PUBLISHER=local.');
    }

    public function remove(string $path): void
    {
        throw new RuntimeException('S3 publisher is not implemented yet. Use NWDB_PUBLISHER=local.');
    }

    public function exists(string $path): bool
    {
        throw new RuntimeException('S3 publisher is not implemented yet. Use NWDB_PUBLISHER=local.');
    }
}
