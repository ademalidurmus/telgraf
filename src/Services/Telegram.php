<?php

declare(strict_types=1);

namespace AAD\Telgraf\Services;

use AAD\Telgraf\Services;
use AAD\Telgraf\Stores;
use AAD\Telgraf\Helpers\Str;
use Swoole\Websocket\Server;
use Respect\Validation\Validator as v;
use AAD\Telgraf\Exceptions\ConflictException;

/**
 * Class Telegram
 * @package AAD\Telgraf\Services
 */
class Telegram
{
    const API_URL = 'https://api.telegram.org';

    /**
     * @var Server
     */
    private $server;

    /**
     * @var mixed
     */
    private $token;

    /**
     * Telegram constructor.
     * @param Server $server
     * @param array $stores
     */
    public function __construct(Server $server, array $stores)
    {
        $this->server = $server;
        $this->token = $stores[Stores::CONFIG]['bot_token'];
    }

    /**
     * @param $chatId
     * @param $message
     * @param array $args
     * @return string|null
     */
    public function sendMessage($chatId, $message, array $args = []): ?string
    {
        if (v::arrayType()->validate($message)) {
            $message = json_encode($message);
        }

        $url = sprintf(
            '%s/bot%s/sendMessage?chat_id=%s&text=%s',
            self::API_URL,
            $this->token,
            $chatId,
            rawurlencode(Str::escapeMarkdownV2($message))
        );

        foreach ($args as $key => $value) {
            if (v::arrayType()->validate($value)) {
                $value = json_encode($value);
            }
            $url .= "&$key=" . rawurlencode((string)$value);
        }

        $response = file_get_contents($url);

        return !$response ? null : $response;
    }

    /**
     * @param array $body
     * @return array
     */
    public function messageResolver(array $body): array
    {
        $chatId = (int)$body['message']['chat']['id'] ?? '';
        $chatText = $body['message']['text'] ?? '';
        $language = $body['message']['from']['language_code'] ?? '';
        $messageId = $body['message']['message_id'] ?? '';
        $name = sprintf('%s %s.', $body['message']['from']['first_name'], $body['message']['from']['last_name'][0]);
        $source = 'message';

        $data = [
            'type' => 'message',
            'content' => $chatText,
            'attributes' => [
                'name' => $name,
            ]
        ];

        $additionalChatId = null;
        if (
            v::regex('/^(\/add\ )[0-9]{6,}$/')->validate($chatText) ||
            v::regex('/^(\/remove\ )[0-9]{6,}$/')->validate($chatText)
        ) {
            $explodedChatText = explode(' ', $chatText);
            $chatText = $explodedChatText[0];
            $additionalChatId = $explodedChatText[1];
        }

        $args = $data;
        if ($additionalChatId) {
            $args['additional_chat_id'] = $additionalChatId;
        }

        if (!$this->_accessCheck($chatId, $messageId, $args)) {
            return [];
        }

        switch ($chatText) {
            case '/start':
            case '/register':
                $this->_register($chatId);
                break;

            case '/stop':
            case '/destroy':
                Services::agent()->destroy($chatId);
                break;

            case '/close':
            case '/unassign':
                Services::agent()->unassign($chatId);
                break;

            case '/add':
                $message = 'agent chat id must be sent';
                if ($additionalChatId) {
                    $message = 'agent already added';
                    $chatIds = self::_getChatIds();

                    if (!v::in($chatIds)->validate($additionalChatId)) {
                        $message = 'agent added';
                        $chatIds[] = $additionalChatId;
                        if (!Stores::modifyConfig('app_chat_ids', implode(',', $chatIds))) {
                            $message = 'agent can not added';
                        }
                    }
                }
                $this->sendMessage($chatId, $message, ['reply_to_message_id' => $messageId]);
                break;

            case '/remove':
                $message = 'agent chat id must be sent';
                if ($additionalChatId) {
                    $message = 'agent does not exist';
                    $chatIds = self::_getChatIds();

                    if (v::in($chatIds)->validate($additionalChatId)) {
                        $message = 'agent removed';
                        $chatIds = array_diff($chatIds, [$additionalChatId]);
                        if (!Stores::modifyConfig('app_chat_ids', implode(',', $chatIds))) {
                            $message = 'agent can not removed';
                        }
                    }
                }
                $this->sendMessage($chatId, $message, ['reply_to_message_id' => $messageId]);
                break;

            default:
                $agent = Services::agent()->get($chatId);
                if (v::nullType()->validate($agent)) {
                    if (!$this->_register($chatId)) {
                        break;
                    }
                    $agent = Services::agent()->get($chatId);
                }

                $connectionsId = $agent['connections_id'] ?? null;
                if (!$connectionsId || !Services::connection()->get($connectionsId)) {
                    Services::log()->error("no connection to message publish", $data);
                    if ($messageId) {
                        $this->sendMessage($chatId, 'no connection to message publish', ['reply_to_message_id' => $messageId]);
                    }
                    break;
                }

                $this->server->push($connectionsId, json_encode($data));
                break;
        }

        return [
            'chat_id' => $chatId,
            'chat_text' => $chatText,
            'message_id' => $messageId,
            'language' => $language,
            'source' => $source,
        ];
    }

    /**
     * @return array
     */
    private static function _getChatIds(): array
    {
        return explode(',', (string)(Stores::config()['app_chat_ids'] ?? ''));
    }

    /**
     * @param $chatId
     * @param null $messageId
     * @param array $data
     * @return bool
     */
    private function _accessCheck($chatId, $messageId = null, array $data = []): bool
    {
        $chatIds = self::_getChatIds();
        if (!v::in($chatIds)->validate($chatId)) {
            Services::log()->error("permission failed", array_merge(['chat_id' => $chatId, 'chat_ids' => $chatIds], $data));
            $args = [];
            if ($messageId) {
                $args = ['reply_to_message_id' => $messageId];
            }
            $this->sendMessage($chatId, 'You cannot send message via this bot. Please contact your manager.', $args);
            return false;
        }
        return true;
    }

    /**
     * @param $chatId
     * @return bool
     */
    private function _register($chatId): bool
    {
        try {
            Services::agent()->register($chatId);
            if ($id = Services::connection()->getWaitingConnectionsId()) {
                Services::agent()->assign($chatId, $id);
            }
        } catch (ConflictException $e) {
            Services::log()->error($e->getMessage(), ['id' => $chatId]);
            return false;
        }
        return true;
    }
}
