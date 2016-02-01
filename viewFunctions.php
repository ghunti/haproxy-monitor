<?php

use Ghunti\HaproxyPHP\ServerStats;
use \DateTime;

function buildDataForView($haproxyData)
{
    $viewData = [];
    foreach ($haproxyData as $serverStats) {
        $serverStats = new ServerStats($serverStats);
        $proxyName = $serverStats->getProxyName();
        $viewData[$proxyName][] = $serverStats;
    }
    return $viewData;
}

function getElapsedTime($seconds)
{
    $now = new DateTime();
    $past = clone $now;
    $past->sub(DateInterval::createFromDateString($seconds . ' seconds'));

    $diff = $now->diff($past);
    return $diff->format('%H:%Ih');
}

function getServerStatusColor(ServerStats $serverStats)
{
    if (!$serverStats->isListener()) {
        return 'active';
    }

    switch (true) {
        case $serverStats->isUp():
            return 'success';

        case $serverStats->isDown():
            return 'danger';

        case $serverStats->isMaint():
            return 'warning';

        case $serverStats->isDrain():
            return 'info';
    }
}

function getWeightLabelColor(ServerStats $serverStats)
{
    if ($serverStats->getWeight() <= 0 || $serverStats->getWeight() > 100) {
        return 'info';
    }

    if ($serverStats->getWeight() >= 80) {
        return 'success';
    } elseif ($serverStats->getWeight() >= 50) {
        return 'warning';
    }
    return 'danger';
}

function getStatusLabelColor(ServerStats $serverStats)
{
    switch ($serverStats->getStatus()) {
        case 'DOWN':
            return 'danger';

        case 'UP':
            return 'success';

        case 'MAINT':
            return 'warning';

        default:
            return 'info';
    }
}

function isPost()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}
