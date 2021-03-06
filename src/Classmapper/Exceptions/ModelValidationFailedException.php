<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Classmapper\Exceptions;

final class ModelValidationFailedException extends ClassmapperException
{
    public function __construct(string $model, string $field, string $message, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(sprintf(
            'Validation of %s::%s failed. Returned: %s',
            $model,
            $field,
            $message
        ), $code, $previous);
    }
}
