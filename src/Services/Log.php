<?php

declare(strict_types=1);

namespace AAD\Telgraf\Services;

use AAD\Telgraf\Stores;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use DateTimeZone;

/**
 * Class Log
 * @package AAD\Telgraf\Services
 */
class Log extends Logger
{
    /**
     * Log constructor.
     * @param array $store
     * @param string $name
     * @param array $handlers
     * @param array $processors
     * @param DateTimeZone|null $timezone
     */
    public function __construct(array $store = [], string $name = 'telgraf', array $handlers = [], array $processors = [], ?DateTimeZone $timezone = null)
    {
        parent::__construct($name, $handlers, $processors, $timezone);

        $file = $store[Stores::CONFIG]['log_file'];
        $level = $store[Stores::CONFIG]['log_level'];
        $level = Logger::getLevels()[$level] ?? Logger::WARNING;
        $this->pushHandler(new StreamHandler($file, $level));
    }
}
