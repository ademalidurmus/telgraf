<?php

namespace AAD\Telgraf\Services;

use AAD\Telgraf\Services;
use AAD\Telgraf\Stores;
use Swoole\Websocket\Server;
use Swoole\WebSocket\Frame;
use Swoole\Table;
use Ramsey\Uuid\Uuid;

/**
 * Class Message
 * @package AAD\Telgraf\Services
 */
class Message
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var Table
     */
    private $messages;

    /**
     * Message constructor.
     * @param Server $server
     * @param array $stores
     */
    public function __construct(Server $server, array $stores)
    {
        $this->server = $server;
        $this->messages = $stores[Stores::MESSAGES];
    }

    /**
     * @param Frame $frame
     * @return $this
     * @todo redirect to assigned agent or message pooling
     *
     */
    public function create(Frame $frame): self
    {
        $data = json_decode($frame->data, true);

        $agentsId = Services::connection()->getAgentsId($frame->fd);
        $data = [
            'id' => Uuid::uuid6()->toString(),
            'agents_id' => $agentsId,
            'connections_id' => $frame->fd,
            'content' => $data['content'] ?? '',
            'attributes' => json_encode([
                'name' => $data['attributes']['name'] ?? '',
            ]),
        ];
        if (!$agentsId) {
            $this->messages->set($data['id'], $data);
        } else {
            Services::telegram()->sendMessage($agentsId, self::prepareMessage($data), ['parse_mode' => 'MarkdownV2']);
        }
        Services::log()->debug("message received", ['id' => $frame->fd, 'data' => $data, 'data_raw' => $frame->data, 'opcode' => $frame->opcode, 'finish' => $frame->finish]);
        return $this;
    }

    /**
     * @param int $connectionsId
     * @param int $agentsId
     * @return $this
     * @todo sorted send...
     *
     */
    public function pushPendingMessages(int $connectionsId, int $agentsId): self
    {
        Services::log()->debug("pending message check started");

        foreach ($this->messages as $data) {
            if ($data['connections_id'] == $connectionsId) {
                Services::telegram()->sendMessage($agentsId, self::prepareMessage($data), ['parse_mode' => 'MarkdownV2']);
                Services::log()->debug("pending message sent to agent", $data);
                $this->delete($data['id']);
            }
        }

        Services::log()->debug("pending message check finished");

        return $this;
    }

    /**
     * @param string $id
     * @return $this
     */
    public function delete(string $id): self
    {
        $this->messages->del($id);
        Services::log()->debug("message deleted", ['id' => $id]);
        return $this;
    }

    /**
     * @param array $data
     * @return string
     * @todo use message entity
     * @link https://core.telegram.org/bots/api#messageentity
     *
     */
    public static function prepareMessage(array $data): string
    {
        $data['attributes'] = json_decode($data['attributes'], true);
        return "{$data['attributes']['name']}:*\n{$data['content']}";
    }
}
