<?php

declare(strict_types=1);

namespace App\Application\Port\Service;


interface LoggerInterface
{
    public function debug(string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;

    public function warning(string $message, array $context = []): void;

    public function info(string $message, array $context = []): void;
}
