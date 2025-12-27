<?php

declare(strict_types=1);

namespace App\Infrastructure\Metrics;

use App\Application\Port\Service\MetricsInterface;
use Prometheus\CollectorRegistry;

final readonly class PrometheusMetricsAdapter implements MetricsInterface
{
    private const string NAMESPACE = 'payb2';

    public function __construct(
        private CollectorRegistry $registry,
    ) {}

    public function incrementCounter(string $name, array $labels = []): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            self::NAMESPACE,
            $name,
            $name,
            array_keys($labels),
        );

        $counter->inc(array_values($labels));
    }

    public function observeHistogram(string $name, float $value, array $labels = []): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            self::NAMESPACE,
            $name,
            $name,
            array_keys($labels),
        );

        $histogram->observe($value, array_values($labels));
    }
}
