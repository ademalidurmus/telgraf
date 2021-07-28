<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use AAD\Telgraf\Stores;
use AAD\Telgraf\Services;
use AAD\Telgraf\Helpers\Http;
use Swoole\Websocket\Server;
use Swoole\WebSocket\Frame;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Timer;

$stores = Stores::build();
$config = $stores[Stores::CONFIG];

$protocol = $config['srv_protocol'];
$host = $config['srv_host'];
$hostname = $config['srv_hostname'];
$port = $config['srv_port'];

$server = new Server($host, $port);

Services::build($server, $stores);

Timer::tick(30000, function (int $timerId) {
    Services::log()->debug('timer tick is running', ['id' => $timerId]);
    Services::connection()->autoAssign();
});

$server->on('start', function () use ($protocol, $hostname, $port) {
    echo $message = sprintf('Swoole HTTP server is started at %s://%s:%s' . PHP_EOL, $protocol, $hostname, $port);
    Services::log()->info($message);
});

$server->on('open', function (Server $server, Request $request) {
    Services::connection()->open($request);
});

$server->on('request', function (Request $request, Response $response) {
    Http::requestResolver($request, $response);
});

$server->on('message', function (Server $server, Frame $frame) {
    Services::message()->create($frame);
});

$server->on('close', function (Server $server, int $client) {
    Services::connection()->close($client);
});

$server->start();
