<?php

declare(strict_types=1);

namespace App\Application\Port\Service;

interface MetricsInterface
{
    public function incrementCounter(string $name, array $labels = []): void;

    public function observeHistogram(string $name, float $value, array $labels = []): void;
}
