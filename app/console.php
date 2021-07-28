<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use AAD\Telgraf\Services\Authorization;
use AAD\Telgraf\Services\Telegram;
use AAD\Telgraf\Services;
use AAD\Telgraf\Services\Log;
use AAD\Telgraf\Stores;

array_shift($argv);

if (!$argv) {
    $argv[] = 'unknown';
}

$stores = Stores::build();
$config = $stores[Stores::CONFIG];

Services::add(Services::LOG, new Log($stores));

foreach ($argv as $command) {
    $authorization = new Authorization(null, $stores);

    switch ($command) {
        case 'set_webhook':
            $url = sprintf(
                '%s/bot%s/setwebhook?url=https://%s/app-%s',
                Telegram::API_URL,
                $config['bot_token'],
                $config['srv_hostname'],
                $authorization->getAuthToken(),
            );
            $data = _run($url);
            $message = $data['description'] ?? 'unknown error occurred';
            Services::log()->info($message, $data);
            break;

        case 'delete_webhook':
            $url = sprintf(
                '%s/bot%s/setwebhook?url=',
                Telegram::API_URL,
                $config['bot_token']
            );
            $data = _run($url);
            $message = $data['description'] ?? 'unknown error occurred';
            Services::log()->info($message, $data);
            break;

        default:
            $message = "$command: unknown command\n";
            break;
    }
    echo $message;
}

function _run(string $url): array
{
    $response = file_get_contents($url);
    return json_decode($response, true) ?? [];
}
