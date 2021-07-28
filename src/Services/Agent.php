<?php

namespace AAD\Telgraf\Services;

use AAD\Telgraf\Exceptions\ConflictException;
use AAD\Telgraf\Services;
use AAD\Telgraf\Stores;
use Swoole\Websocket\Server;
use Swoole\Table;

/**
 * Class Agent
 * @package AAD\Telgraf\Services
 */
class Agent
{
    /**
     * @var Server
     */
    private $server;

    /**
     * @var Table
     */
    private $agents;

    /**
     * Agent constructor.
     * @param Server $server
     * @param array $stores
     */
    public function __construct(Server $server, array $stores)
    {
        $this->server = $server;
        $this->agents = $stores[Stores::AGENTS];
    }

    /**
     * @param int $id
     * @return $this
     * @throws ConflictException
     */
    public function register(int $id): self
    {
        if ($this->agents->exist($id)) {
            Services::telegram()->sendMessage($id, 'you are already in session');
            throw new ConflictException("agent already registered");
        }

        $data = [
            'id' => $id
        ];

        $this->agents->set($id, $data);
        Services::log()->debug("agent registered", $data);
        Services::telegram()->sendMessage($id, 'session started');
        return $this;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function destroy(int $id): self
    {
        $this->unassign($id);
        $this->agents->del($id);
        Services::log()->debug("agent destroyed", ['id' => $id]);
        Services::telegram()->sendMessage($id, 'session stopped');
        return $this;
    }

    /**
     * @param int $id
     * @return array|null
     */
    public function get(int $id): ?array
    {
        if (!$this->agents->exist($id)) {
            Services::log()->debug("agent does not exist", ['id' => $id]);
            return null;
        }

        return $this->agents->get($id);
    }

    /**
     * @param int $id
     * @param int $connectionsId
     * @return bool
     */
    public function assign(int $id, int $connectionsId): bool
    {
        if (!Services::connection()->assign($connectionsId, $id)) {
            return false;
        }

        $data = [
            'id' => $id,
            'connections_id' => $connectionsId,
        ];

        $this->agents->set($id, $data);
        Services::log()->debug("agent assigned", $data);
        Services::telegram()->sendMessage($id, "connection#$connectionsId started");

        Services::message()->pushPendingMessages($connectionsId, $id);
        return true;
    }

    /**
     * @param int $id
     * @param bool $withConnection
     * @return bool
     */
    public function unassign(int $id, bool $withConnection = true): bool
    {
        if (!$this->agents->exist($id)) {
            Services::log()->debug("agent does not exist", ['id' => $id]);
            return false;
        }

        if ($withConnection) {
            $connectionsId = $this->getConnectionsId($id);
        }

        $data = [
            'id' => $id,
            'connections_id' => 0,
        ];

        $this->agents->set($id, $data);
        Services::log()->debug("agent unassigned", $data);

        if ($withConnection && $connectionsId) {
            Services::log()->debug("connection id found for unassign", ['id' => $connectionsId]);
            Services::connection()->unassign($connectionsId, false);
        }
        Services::telegram()->sendMessage($id, "connection closed");

        return true;
    }

    /**
     * @param int $id
     * @return int|null
     */
    public function getConnectionsId(int $id): ?int
    {
        $connectionsId = $this->agents->get($id, 'connections_id');

        if ($connectionsId > 0 && Services::connection()->get($connectionsId)) {
            return $connectionsId;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getWaitingAgentsId(): ?int
    {
        Services::log()->debug("lookup waiting agents id");

        foreach ($this->agents as $agent) {
            if ($agent['connections_id'] === 0) {
                Services::log()->debug("waiting agent found", ['id' => $agent['id']]);
                return $agent['id'];
            }
        }

        return null;
    }
}
