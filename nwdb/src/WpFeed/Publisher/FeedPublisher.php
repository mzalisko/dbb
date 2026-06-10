<?php

namespace Nwdb\WpFeed\Publisher;

interface FeedPublisher
{
    /** Записати конверт за шляхом (відносно кореня dead-drop), повертає шлях. */
    public function publish(string $path, string $bytes): string;

    public function remove(string $path): void;

    public function exists(string $path): bool;
}
