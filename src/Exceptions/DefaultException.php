<?php

declare(strict_types=1);

namespace AAD\Telgraf\Exceptions;

use Exception;

/**
 * Class DefaultException
 * @package AAD\Telgraf\Exceptions
 */
class DefaultException extends Exception
{
    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected $identifier;

    /**
     * @var int
     */
    protected $code;

    /**
     * DefaultException constructor.
     * @param string $message
     * @param int $identifier
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(string $message, int $identifier = 0, int $code = 500, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->message = $message;
        $this->identifier = $identifier;
        $this->code = $code;
    }

    /**
     * @return int
     */
    public function getIdentifier(): int
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . ": [$this->code]: $this->message";
    }
}
