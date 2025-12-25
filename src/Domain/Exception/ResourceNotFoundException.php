<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class ResourceNotFoundException extends DomainException
{
    public const string DEFAULT_MESSAGE = 'Resource not found.';

    public const string RESOURCE_MESSAGE = 'The %s resource not found.';

    public function __construct(protected $message = self::DEFAULT_MESSAGE, private readonly ?string $resourceName = null)
    {
        if (!is_null($resourceName)) {
            $this->message = $this->getResourceMessage();
        }

        parent::__construct(message: $message);
    }

    public function getResourceMessage(): string
    {
        if (is_null($this->resourceName)) {
            return self::DEFAULT_MESSAGE;
        }

        return sprintf(self::RESOURCE_MESSAGE, $this->resourceName);
    }
}
