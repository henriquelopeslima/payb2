<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Metrics;

use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class MetricsController
{
    public function __construct(
        private CollectorRegistry $registry,
        private RenderTextFormat $renderer,
    ) {}

    #[Route(path: '/metrics', name: 'metrics', methods: ['GET'])]
    public function __invoke(): Response
    {
        $metrics = $this->registry->getMetricFamilySamples();
        $content = $this->renderer->render($metrics);

        return new Response(
            content: $content,
            status: Response::HTTP_OK,
            headers: ['Content-Type' => RenderTextFormat::MIME_TYPE]
        );
    }
}
