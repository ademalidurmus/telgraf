<?php

namespace AAD\Telgraf\Services;

use AAD\Telgraf\Services;
use AAD\Telgraf\Stores;
use Swoole\Websocket\Server;
use Swoole\Http\Request;
use Swoole\Table;

/**
 * Class Connection
 * @package AAD\Telgraf\Services
 */
class Connection
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var Table
     */
    private $connections;

    /**
     * Connection constructor.
     * @param Server $server
     * @param array $stores
     */
    public function __construct(Server $server, array $stores)
    {
        $this->server = $server;
        $this->connections = $stores[Stores::CONNECTIONS];
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function open(Request $request): self
    {
        $data = [
            'id' => $request->fd,
        ];

        $this->connections->set($data['id'], $data);
        Services::log()->debug("connection opened", $data);

        if ($agentsId = Services::agent()->getWaitingAgentsId()) {
            Services::agent()->assign(
                $agentsId,
                $data['id']
            );
        }

        return $this;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function close(int $id): self
    {
        $this->unassign($id, true, true);
        $this->connections->del($id);
        Services::log()->debug("connection closed", ['id' => $id]);
        return $this;
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function get(int $id): ?array
    {
        if (!$this->connections->exist($id)) {
            Services::log()->debug("connection does not exist", ['id' => $id]);
            return null;
        }
        return $this->connections->get($id);
    }

    /**
     * @param int $id
     * @param int $agentsId
     * @return bool
     */
    public function assign(int $id, int $agentsId): bool
    {
        if (!$this->connections->exist($id)) {
            Services::log()->debug("connection does not exist", ['id' => $id]);
            return false;
        }

        $data = [
            'id' => $id,
            'agents_id' => $agentsId,
        ];

        $this->connections->set($id, $data);
        Services::log()->debug("connection assigned", $data);
        $this->server->push($id, self::prepareMessage('connection assigned', 'info'));
        return true;
    }

    /**
     * @param int $id
     * @param bool $withAgent
     * @param bool $onClose
     * @return bool
     * @todo re-assign after waiting...
     *
     */
    public function unassign(int $id, bool $withAgent = true, bool $onClose = false): bool
    {
        if (!$this->connections->exist($id)) {
            Services::log()->debug("connection does not exist for unassign", ['id' => $id]);
            return false;
        }

        if ($withAgent) {
            $agentsId = $this->getAgentsId($id);
        }

        $data = [
            'id' => $id,
            'agents_id' => 0,
        ];

        $this->connections->set($id, $data);
        Services::log()->debug("connection unassigned", $data);

        if ($withAgent && $agentsId) {
            Services::log()->debug("agent id found for unassign", ['id' => $agentsId]);
            Services::agent()->unassign($agentsId, false);
        }

        if (!$onClose) {
            $this->server->push($id, self::prepareMessage('connection unassigned', 'info'));
        }

        return true;
    }

    /**
     * @param int $id
     * @return int|null
     */
    public function getAgentsId(int $id): ?int
    {
        $agentsId = $this->connections->get($id, 'agents_id');

        if ($agentsId > 0 && Services::agent()->get($agentsId)) {
            return $agentsId;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getWaitingConnectionsId(): ?int
    {
        Services::log()->debug("lookup waiting connections id");

        foreach ($this->connections as $connection) {
            if ($connection['agents_id'] === 0) {
                Services::log()->debug("waiting connection founded", ['id' => $connection['id']]);
                return $connection['id'];
            }
        }

        return null;
    }

    /**
     * @return int
     */
    public function autoAssign(): int
    {
        Services::log()->debug("connection auto assign job started");

        $assignedCount = 0;
        foreach ($this->connections as $connection) {
            if ($connection['agents_id'] === 0 && $connection['agents_id'] = Services::agent()->getWaitingAgentsId()) {
                $result = Services::agent()->assign(
                    $connection['agents_id'],
                    $connection['id']
                );
                if ($result) {
                    $assignedCount++;
                    Services::log()->debug("connection auto assigned", $connection);
                }
            }
        }

        Services::log()->debug("connection auto assign job finished", ['assigned_count' => $assignedCount]);

        return $assignedCount;
    }

    /**
     * @param string $content
     * @param string $type
     * @param array $attributes
     * @return false|string
     */
    public static function prepareMessage(string $content = '', string $type = 'message', array $attributes = [])
    {
        return json_encode([
            'type' => $type,
            'content' => $content,
            'attributes' => $attributes,
        ]);
    }
}
