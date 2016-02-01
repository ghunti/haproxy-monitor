<?php

namespace Ghunti\HaproxyPHP;

use \ArrayIterator;

class ServerStats extends ArrayIterator
{
    const PROXY_NAME_KEY = '# pxname';
    const SERVICE_NAME_KEY = 'svname';
    const DOWNTIME_KEY = 'downtime';
    const STATUS_KEY = 'status';
    const WEIGHT_KEY = 'weight';

    /**
     * Get proxy name
     * @return string The proxy name
     */
    public function getProxyName()
    {
        return $this->offsetGet(static::PROXY_NAME_KEY);
    }

    /**
     * Get service name
     * @return string The service name
     */
    public function getServiceName()
    {
        return $this->offsetGet(static::SERVICE_NAME_KEY);
    }

    /**
     * Get server status
     * @return string The server status
     */
    public function getStatus()
    {
        return $this->offsetGet(static::STATUS_KEY);
    }

    /**
     * Get server weight
     * @return string The server weight
     */
    public function getWeight()
    {
        return $this->offsetGet(static::WEIGHT_KEY);
    }

    /**
     * Check if server status is UP
     * @return boolean
     */
    public function isUp()
    {
        return $this->isStatus('UP');
    }

    /**
     * Check if server status is DOWN
     * @return boolean
     */
    public function isDown()
    {
        return $this->isStatus('DOWN');
    }

    /**
     * Check if server status is MAINT
     * @return boolean
     */
    public function isMaint()
    {
        return $this->isStatus('MAINT');
    }

    /**
     * Check if server status is DRAIN
     * @return boolean
     */
    public function isDrain()
    {
        return $this->isStatus('DRAIN');
    }

    /**
     * Check if server is a listener server
     * @return boolean
     */
    public function isListener()
    {
        return !in_array(
            $this->getServiceName(),
            ['FRONTEND', 'BACKEND']
        );
    }

    /**
     * Check if server status the provided status
     * @return boolean
     */
    protected function isStatus($status)
    {
        $serverStatus = $this->getStatus();
        return strpos($status, $serverStatus) !== false;
    }
}
