<?php

namespace Ghunti\HaproxyPHP;

use Exception;

class Haproxy
{
    const ENABLE = 'enable';
    const DISABLE = 'disable';
    const UP = 'up';
    const DOWN = 'down';

    const STATE_READY = 'ready';
    const STATE_DRAIN = 'drain';
    const STATE_MAINT = 'maint';

    const HEALTH_STOPPING = 'stopping';
    const SHOW_STATS_COMMAND = "show stat";

    protected $socketHost;
    protected $readOnly;

    public function __construct($unixSocket, $readOnly = true)
    {
        $this->socketHost = $unixSocket;
        $this->readOnly = $readOnly;
    }

    /**
     * Get haproxy stats
     *
     * @return array The haproxy stats
     */
    public function getStats()
    {
        $stats = [];
        $oldReadOnlyStatus = $this->readOnly;
        //Enable write since this command is only reading actually
        $this->readOnly = false;
        $response = $this->writeToSocket(static::SHOW_STATS_COMMAND);
        $this->readOnly = $oldReadOnlyStatus;
        $dataTable = explode(PHP_EOL, $response);
        $headers = str_getcsv(array_shift($dataTable));
        foreach ($dataTable as $row) {
            $stats[] = array_combine($headers, str_getcsv($row));
        }
        return $stats;
    }

    /**
     * Set a new proxy/service weight
     *
     * @param int    $weight      The new weight
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setServerWeight($weight, $proxyName, $serviceName)
    {
        return $this->writeToSocket($this->buildServerCommand('weight', $weight, $proxyName, $serviceName));
    }

    /**
     * Set proxy/service state to "ready"
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setStateReady($proxyName, $serviceName)
    {
        return $this->setServerState(static::STATE_READY, $proxyName, $serviceName);
    }

    /**
     * Set proxy/service state to "maint"
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setStateMaint($proxyName, $serviceName)
    {
        return $this->setServerState(static::STATE_MAINT, $proxyName, $serviceName);
    }

    /**
     * Set proxy/service state to "drain"
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setStateDrain($proxyName, $serviceName)
    {
        return $this->setServerState(static::STATE_DRAIN, $proxyName, $serviceName);
    }

    /**
     * Set proxy/service health to "up"
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setHealthUp($proxyName, $serviceName)
    {
        return $this->setServerHealth(static::UP, $proxyName, $serviceName);
    }

    /**
     * Set proxy/service health to "stopping" (nolb)
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setHealthStopping($proxyName, $serviceName)
    {
        return $this->setServerHealth(static::HEALTH_STOPPING, $proxyName, $serviceName);
    }

    /**
     * Set proxy/service health to "down"
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setHealthDown($proxyName, $serviceName)
    {
        return $this->setServerHealth(static::DOWN, $proxyName, $serviceName);
    }

    /**
     * Enable HTTP health check for proxy/service
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function enableHealthCheck($proxyName, $serviceName)
    {
        return $this->setHealthCheck(static::ENABLE, $proxyName, $serviceName);
    }

    /**
     * Disable HTTP health check for proxy/service
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function disableHealthCheck($proxyName, $serviceName)
    {
        return $this->setHealthCheck(static::DISABLE, $proxyName, $serviceName);
    }

    /**
     * Set proxy/service agent to "up"
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setAgentUp($proxyName, $serviceName)
    {
        return $this->setAgentState(static::UP, $proxyName, $serviceName);
    }

    /**
     * Set proxy/service agent to "down"
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function setAgentDown($proxyName, $serviceName)
    {
        return $this->setAgentState(static::DOWN, $proxyName, $serviceName);
    }

    /**
     * Enable agent health check for proxy/service
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function enableAgentCheck($proxyName, $serviceName)
    {
        return $this->setAgentCheck(static::ENABLE, $proxyName, $serviceName);
    }

    /**
     * Disable agent health check for proxy/service
     *
     * @param string $proxyName   Proxy name
     * @param string $serviceName Service name
     */
    public function disableAgentCheck($proxyName, $serviceName)
    {
        return $this->setAgentCheck(static::DISABLE, $proxyName, $serviceName);
    }

    protected function setServerState($state, $proxyName, $serviceName)
    {
        return $this->writeToSocket($this->buildServerCommand('state', $state, $proxyName, $serviceName));
    }

    protected function setServerHealth($state, $proxyName, $serviceName)
    {
        return $this->writeToSocket($this->buildServerCommand('health', $state, $proxyName, $serviceName));
    }

    protected function setHealthCheck($action, $proxyName, $serviceName)
    {
        $command = sprintf('%s health %s/%s', $action, $proxyName, $serviceName);
        return $this->writeToSocket($command);
    }

    protected function setAgentState($state, $proxyName, $serviceName)
    {
        return $this->writeToSocket($this->buildServerCommand('agent', $state, $proxyName, $serviceName));
    }

    protected function setAgentCheck($action, $proxyName, $serviceName)
    {
        $command = sprintf('%s agent %s/%s', $action, $proxyName, $serviceName);
        return $this->writeToSocket($command);
    }

    /**
     * Build a "set server" command to be passed to haproxy socket
     *
     * @param  string $resource    The resource to change (health, agent, state, etc)
     * @param  string $action      The action to perform on the resource
     * @param  string $proxyName   Proxy name
     * @param  string $serviceName Service name
     * @return string              The command to pass to haproxy socket
     */
    protected function buildServerCommand($resource, $action, $proxyName, $serviceName)
    {
        return sprintf('set server %s/%s %s %s', $proxyName, $serviceName, $resource, $action);
    }

    /**
     * Write $command to socket and return output (if any)
     *
     * @param  string $command The command to write to the socket
     * @return string The socket response
     */
    protected function writeToSocket($command)
    {
        if ($this->readOnly !== false) {
            throw new Exception("Can't write to socket");
        }
        $socket = socket_create(AF_UNIX, SOCK_STREAM, 1);
        if (!socket_connect($socket, $this->socketHost)) {
            throw new Exception("Can't connect to socket");
        }
        socket_write($socket, $command . PHP_EOL);

        $response = $dataReceived = '';
        //Read response from socket
        do {
            $bytesRead = socket_recv($socket, $dataReceived, 2048, MSG_WAITALL);
            if ($bytesRead === false) {
                throw new Exception(sprintf("socket_recv() failed; reason: %s", socket_strerror(socket_last_error($socket))));
            }
            $response .= $dataReceived;
            //0 bytes red, mean that data transmission has ended
        } while ($bytesRead !== 0);

        socket_close($socket);
        return trim($response);
    }
}
