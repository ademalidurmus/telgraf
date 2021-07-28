<?php

declare(strict_types=1);

namespace AAD\Telgraf\Services;

use AAD\Telgraf\Stores;
use Swoole\Http\Request;
use Swoole\Websocket\Server;
use Respect\Validation\Validator as v;

/**
 * Class Authorization
 * @package AAD\Telgraf\Services
 */
class Authorization
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var mixed
     */
    private $config;

    /**
     * Authorization constructor.
     * @param Server|null $server
     * @param array $stores
     */
    public function __construct(?Server $server, array $stores)
    {
        $this->server = $server;
        $this->config = $stores[Stores::CONFIG];
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function check(Request $request): bool
    {
        return v::attribute('server', v::key('path_info', v::identical('/app-' . $this->getAuthToken())))->validate($request);
    }

    /**
     * @return string
     */
    public function getAuthToken(): string
    {
        $token = sprintf(
            '%s-%s',
            $this->config['app_secret'],
            $this->config['bot_token']
        );

        return hash('sha256', $token);
    }
}
