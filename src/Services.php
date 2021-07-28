<?php

declare(strict_types=1);

namespace AAD\Telgraf;

use AAD\Telgraf\Services\Agent;
use AAD\Telgraf\Services\Authorization;
use AAD\Telgraf\Services\Connection;
use AAD\Telgraf\Services\Log;
use AAD\Telgraf\Services\Message;
use AAD\Telgraf\Services\Telegram;
use Swoole\Websocket\Server;
use Respect\Validation\Validator as v;

/**
 * Class Services
 * @package AAD\Telgraf
 */
class Services
{
    const LOG = 'log';
    const AGENT = 'agent';
    const MESSAGE = 'message';
    const TELEGRAM = 'telegram';
    const CONNECTION = 'connection';
    const AUTHORIZATION = 'authorization';

    /**
     * @var array
     */
    private static $services = [];

    /**
     * @param Server $server
     * @param array $store
     * @return array
     */
    public static function build(Server $server, array $store): array
    {
        self::$services = [
            self::LOG => new Log($store),
            self::AGENT => new Agent($server, $store),
            self::MESSAGE => new Message($server, $store),
            self::TELEGRAM => new Telegram($server, $store),
            self::CONNECTION => new Connection($server, $store),
            self::AUTHORIZATION => new Authorization($server, $store),
        ];

        return self::$services;
    }

    /**
     * @return Log
     */
    public static function log(): Log
    {
        return self::$services[self::LOG];
    }

    /**
     * @return Agent
     */
    public static function agent(): Agent
    {
        return self::$services[self::AGENT];
    }

    /**
     * @return Message
     */
    public static function message(): Message
    {
        return self::$services[self::MESSAGE];
    }

    /**
     * @return Telegram
     */
    public static function telegram(): Telegram
    {
        return self::$services[self::TELEGRAM];
    }

    /**
     * @return Connection
     */
    public static function connection(): Connection
    {
        return self::$services[self::CONNECTION];
    }

    /**
     * @return Authorization
     */
    public static function authorization(): Authorization
    {
        return self::$services[self::AUTHORIZATION];
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public static function get(string $name)
    {
        if (v::key($name)->validate(self::$services)) {
            return self::$services[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param $service
     * @return mixed
     */
    public static function add(string $name, $service)
    {
        return self::$services[$name] = $service;
    }
}
