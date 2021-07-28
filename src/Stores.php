<?php

declare(strict_types=1);

namespace AAD\Telgraf;

use Swoole\Table;
use Respect\Validation\Validator as v;

/**
 * Class Stores
 * @package AAD\Telgraf
 */
class Stores
{
    const ENV_FILE_PATH = __DIR__ . '/../.env';

    const AGENTS = 'agents';
    const MESSAGES = 'messages';
    const CONNECTIONS = 'connections';
    const CONFIG = 'config';

    /**
     * @var array
     */
    private static $stores = [];

    /**
     * @return array
     */
    public static function build(): array
    {
        self::$stores = [
            self::AGENTS => self::_agents(),
            self::MESSAGES => self::_messages(),
            self::CONNECTIONS => self::_connections(),
            self::CONFIG => self::_config(),
        ];

        return self::$stores;
    }

    /**
     * @return Table
     */
    public static function messages(): Table
    {
        return self::$stores[self::MESSAGES];
    }

    /**
     * @return Table
     */
    public static function connections(): Table
    {
        return self::$stores[self::CONNECTIONS];
    }

    /**
     * @return Table
     */
    public static function agents(): Table
    {
        return self::$stores[self::AGENTS];
    }

    /**
     * @return array
     */
    public static function config(): array
    {
        return self::$stores[self::CONFIG];
    }

    /**
     * @param string $key
     * @param string $value
     * @return bool
     * @todo putenv persistent store problem
     *
     */
    public static function modifyConfig(string $key, string $value): bool
    {
        if (!v::key($key)->validate(self::$stores[self::CONFIG])) {
            return false;
        }

        $envKey = strtoupper($key);
        $isFileUpdated = null;
        $oldValue = self::$stores[self::CONFIG][$key] ?? null;
        self::$stores[self::CONFIG][$key] = $value;

        if ($isFileExists = file_exists(self::ENV_FILE_PATH)) {
            $modifiedLineValue = "$envKey=$value\n";
            $lines = file(self::ENV_FILE_PATH);

            foreach ($lines as $index => &$line) {
                if (v::startsWith("$envKey=")->validate($line)) {
                    $line = $modifiedLineValue;
                    putenv($modifiedLineValue);
                }
            }

            $isFileUpdated = file_put_contents(self::ENV_FILE_PATH, implode('', $lines)) != false;

            if (!$isFileUpdated) {
                Services::log()->error(".env file could not updated", ['file' => self::ENV_FILE_PATH]);
            } else {
                Services::log()->info(".env file updated", ['key' => $envKey, 'old' => $oldValue, 'new' => $value]);
            }
        }

        return self::$stores[self::CONFIG][$key] === $value && $isFileExists && $isFileUpdated;
    }

    /**
     * @return Table
     */
    private static function _messages(): Table
    {
        $messages = new Table(1024);
        $messages->column('id', Table::TYPE_STRING, 36);
        $messages->column('agents_id', Table::TYPE_INT, 4);
        $messages->column('connections_id', Table::TYPE_INT, 4);
        $messages->column('content', Table::TYPE_STRING, 1204);
        $messages->column('attributes', Table::TYPE_STRING, 1204);
        $messages->create();

        return $messages;
    }

    /**
     * @return Table
     */
    private static function _connections(): Table
    {
        $connections = new Table(1024);
        $connections->column('id', Table::TYPE_INT, 4);
        $connections->column('agents_id', Table::TYPE_INT);
        $connections->create();

        return $connections;
    }

    /**
     * @return Table
     */
    private static function _agents(): Table
    {
        $agents = new Table(1024);
        $agents->column('id', Table::TYPE_INT);
        $agents->column('connections_id', Table::TYPE_INT, 4);
        $agents->create();

        return $agents;
    }

    /**
     * @return array
     */
    private static function _config(): array
    {
        return [
            'srv_host' => getenv('SRV_HOST'),
            'srv_hostname' => getenv('SRV_HOSTNAME'),
            'srv_port' => intval(getenv('SRV_PORT')),
            'srv_protocol' => getenv('SRV_PROTOCOL'),
            'log_file' => getenv('LOG_FILE'),
            'log_level' => strtoupper(getenv('LOG_LEVEL')),
            'bot_token' => getenv('BOT_TOKEN'),
            'app_secret' => getenv('APP_SECRET'),
            'app_chat_ids' => getenv('APP_CHAT_IDS'),
        ];
    }
}
