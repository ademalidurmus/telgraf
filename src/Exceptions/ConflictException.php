<?php

declare(strict_types=1);

namespace AAD\Telgraf\Exceptions;

use Exception;

/**
 * Class ConflictException
 * @package AAD\Telgraf\Exceptions
 */
class ConflictException extends DefaultException
{
    /**
     * ConflictException constructor.
     * @param string $message
     * @param int $identifier
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message, int $identifier = 0, int $code = 409, Exception $previous = null)
    {
        parent::__construct($message, $identifier, $code, $previous);
    }
}
