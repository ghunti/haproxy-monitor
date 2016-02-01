<?php
include_once '../vendor/autoload.php';

use Dotenv\Dotenv;
use Ghunti\HaproxyPHP\Haproxy;
use \Exception;

include_once '../viewFunctions.php';

const ACTION_PARAM = 'action';
const BACKEND_PARAM = 'backend';
const SERVER_PARAM = 'server';
const WEIGHT_PARAM = 'weight';

const CHANGE_WEIGHT_ACTION = 'changeWeight';
const SERVER_READY_ACTION = 'setStateReady';
const SERVER_DRAIN_ACTION = 'setStateDrain';
const SERVER_MAINT_ACTION = 'setStateMaint';
const AGENT_DISABLE_ACTION = 'disableAgentCheck';
const AGENT_ENABLE_ACTION = 'enableAgentCheck';
const AGENT_UP_ACTION = 'setAgentUp';
const AGENT_DOWN_ACTION = 'setAgentDown';
const HEALTH_DISABLE_ACTION = 'disableHealthCheck';
const HEALTH_ENABLE_ACTION = 'enableHealthCheck';
const HEALTH_UP_ACTION = 'setHealthUp';
const HEALTH_STOPPING_ACTION = 'setHealthStopping';
const HEALTH_DOWN_ACTION = 'setHealthDown';

$dotenv = new Dotenv(dirname(__DIR__));
$dotenv->load();

$haproxy = new Haproxy(env('SOCKET_PATH'), env('READ_ONLY'));

if (isPost() && !env('READ_ONLY')) {
    if (!isset($_POST[ACTION_PARAM])) {
        throw new Exception('Invalid action');
    }
    switch ($_POST[ACTION_PARAM]) {
        case CHANGE_WEIGHT_ACTION:
            if (!isset($_POST[WEIGHT_PARAM]) || !is_numeric($_POST[WEIGHT_PARAM])) {
                throw new Exception('Invalid weight');
            }
            $weight = (int) $_POST[WEIGHT_PARAM];
            $haproxy->setServerWeight($weight, $_POST[BACKEND_PARAM], $_POST[SERVER_PARAM]);
            break;

        case SERVER_READY_ACTION:
        case SERVER_MAINT_ACTION:
        case SERVER_DRAIN_ACTION:
        case AGENT_DISABLE_ACTION:
        case AGENT_ENABLE_ACTION:
        case HEALTH_DISABLE_ACTION:
        case HEALTH_ENABLE_ACTION:
        case HEALTH_UP_ACTION:
        case HEALTH_STOPPING_ACTION:
        case HEALTH_DOWN_ACTION:
        case AGENT_UP_ACTION:
        case AGENT_DOWN_ACTION:
            $haproxy->$_POST[ACTION_PARAM]($_POST[BACKEND_PARAM], $_POST[SERVER_PARAM]);
            break;

        default:
            throw new Exception('Invalid action');
    }
    //Avoid duplicate requests after a command has been executed
    header('Location: /');
}

$stats = $haproxy->getStats();
$viewData = buildDataForView($stats);

?>
<html>
    <head>
        <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
        <style>
            .border-left {
                border-left: 1px solid #ddd;
            }
            .border-right {
                border-right: 1px solid #ddd;
            }
            .proxyGap {
                height: 34px;
            }
            .proxyName {
                vertical-align: middle !important;
                text-align: center;
                font-size: 30px;
            }
            td {
                vertical-align: middle !important;
            }
            td .form-inline {
                margin-bottom: 0px;
            }
            .label-as-badge {
                border-radius: 1em;
            }
        </style>
    </head>
    <body>
        <table class="table table-condensed text-center">
        <?php foreach ($viewData as $proxyName => $servers) : ?>
            <tr class="proxyGap"></tr>
            <tr>
                <th rowspan="2" colspan="3" class="border-right bg-primary proxyName"><?php echo $proxyName; ?></th>
                <th colspan="10" class="text-center border-left">Server</th>
                <th class="text-center border-left">Agent</th>
                <th colspan="3" class="text-center border-left">Queue</th>
                <th colspan="3" class="text-center border-left">Session rate</th>
                <th colspan="5" class="text-center border-left">Sessions</th>
                <th colspan="2" class="text-center border-left">Bytes</th>
                <th colspan="2" class="text-center border-left">Denied</th>
                <th colspan="3" class="text-center border-left">Errors</th>
                <th colspan="2" class="text-center border-left border-right">Warnings</th>
            </tr>
            <tr>
                <th class="text-center border-left">Status</th>
                <th class="text-center">HttpChk</th>
                <th class="text-center">Wght</th>
                <th class="text-center">LastChk</th>
                <th class="text-center">Act</th>
                <th class="text-center">Bck</th>
                <th class="text-center">Chk</th>
                <th class="text-center">Dwn</th>
                <th class="text-center">Dwntme</th>
                <th class="text-center">Thrtle</th>
                <th class="text-center border-left">LastRsp</th>
                <th class="text-center border-left">Cur</th>
                <th class="text-center">Max</th>
                <th class="text-center">Limit</th>
                <th class="text-center border-left">Cur</th>
                <th class="text-center">Max</th>
                <th class="text-center">Limit</th>
                <th class="text-center border-left">Cur</th>
                <th class="text-center">Max</th>
                <th class="text-center">Limit</th>
                <th class="text-center">Total</th>
                <th class="text-center">LbTot</th>
                <th class="text-center border-left">In</th>
                <th class="text-center">Out</th>
                <th class="text-center border-left">Req</th>
                <th class="text-center">Resp</th>
                <th class="text-center border-left">Req</th>
                <th class="text-center">Conn</th>
                <th class="text-center">Resp</th>
                <th class="text-center border-left">Retr</th>
                <th class="text-center border-right">Redis</th>
            </tr>
            <?php foreach ($servers as $serverStats) : ?>
            <tr class="<?php echo getServerStatusColor($serverStats); ?>">
            <?php if (!$serverStats->isListener()) : ?>
                <td colspan="2"></td>
            <?php else : ?>
                <td>
                <?php if (!env('READ_ONLY')) : ?>
                    <form class="form-inline" method="POST">
                        <div class="form-group">
                            <input name="<?php echo WEIGHT_PARAM; ?>" type="text" class="form-control" placeholder="Weight" maxlength="3" size="5">
                        </div>
                        <input type="hidden" name="<?php echo ACTION_PARAM; ?>" value="<?php echo CHANGE_WEIGHT_ACTION; ?>">
                        <input type="hidden" name="<?php echo BACKEND_PARAM; ?>" value="<?php echo $serverStats->getProxyName(); ?>">
                        <input type="hidden" name="<?php echo SERVER_PARAM; ?>" value="<?php echo $serverStats->getServiceName(); ?>">
                    </form>
                <?php endif; ?>
                </td>
                <td class="border-right">
                <?php if (!env('READ_ONLY')) : ?>
                    <div class="btn-group dropup">
                        <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Action <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu serverSettings" data-proxy="<?php echo $serverStats->getProxyName(); ?>" data-server="<?php echo $serverStats->getServiceName(); ?>">
                            <li><a class="serverAction" data-action="<?php echo SERVER_READY_ACTION; ?>" href="#">State to READY</a></li>
                            <li><a class="serverAction" data-action="<?php echo SERVER_DRAIN_ACTION; ?>" href="#">State to DRAIN</a></li>
                            <li><a class="serverAction" data-action="<?php echo SERVER_MAINT_ACTION; ?>" href="#">State to MAINT</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a class="serverAction" data-action="<?php echo HEALTH_DISABLE_ACTION; ?>" href="#">DISABLE Health checks</a></li>
                            <li><a class="serverAction" data-action="<?php echo HEALTH_ENABLE_ACTION; ?>" href="#">ENABLE Health checks</a></li>
                            <li><a class="serverAction" data-action="<?php echo HEALTH_UP_ACTION; ?>" href="#">Health to UP</a></li>
                            <li><a class="serverAction" data-action="<?php echo HEALTH_STOPPING_ACTION; ?>" href="#">Health to NOLB</a></li>
                            <li><a class="serverAction" data-action="<?php echo HEALTH_DOWN_ACTION; ?>" href="#">Health to DOWN</a></li>
                            <li role="separator" class="divider"></li>
                            <li><a class="serverAction" data-action="<?php echo AGENT_DISABLE_ACTION; ?>" href="#">DISABLE Agent checks</a></li>
                            <li><a class="serverAction" data-action="<?php echo AGENT_ENABLE_ACTION; ?>" href="#">ENABLE Agent checks</a></li>
                            <li><a class="serverAction" data-action="<?php echo AGENT_UP_ACTION; ?>" href="#">Agent to UP</a></li>
                            <li><a class="serverAction" data-action="<?php echo AGENT_DOWN_ACTION; ?>" href="#">Agent to DOWN</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
                </td>
            <?php endif; ?>
                <td><?php echo $serverStats->getServiceName(); ?></td>
                <td class="border-left">
                    <span class="label label-<?php echo getStatusLabelColor($serverStats); ?>"><?php echo $serverStats['status']; ?></span>
                </td>
                <td>
                    <span class="label label-info"><?php echo $serverStats['check_code']; ?></span>
                </td>
                <td>
                    <span class="label label-as-badge label-<?php echo getWeightLabelColor($serverStats); ?>"><?php echo $serverStats['weight']; ?></span>
                </td>
                <td><?php echo $serverStats['check_status']; ?></td>
                <td><?php echo $serverStats['act']; ?></td>
                <td><?php echo $serverStats['bck']; ?></td>
                <td><?php echo $serverStats['chkfail']; ?></td>
                <td><?php echo $serverStats['chkdown']; ?></td>
                <td><?php echo getElapsedTime($serverStats['downtime']); ?></td>
                <td><?php echo $serverStats['throttle']; ?></td>
                <td class="border-left"><?php echo $serverStats['last_agt']; ?></td>
                <td class="border-left"><?php echo $serverStats['qcur']; ?></td>
                <td><?php echo $serverStats['qmax']; ?></td>
                <td><?php echo $serverStats['qlimit']; ?></td>
                <td class="border-left"><?php echo $serverStats['rate']; ?></td>
                <td><?php echo $serverStats['rate_lim']; ?></td>
                <td><?php echo $serverStats['rate_max']; ?></td>
                <td class="border-left"><?php echo $serverStats['scur']; ?></td>
                <td><?php echo $serverStats['smax']; ?></td>
                <td><?php echo $serverStats['slim']; ?></td>
                <td><?php echo $serverStats['stot']; ?></td>
                <td><?php echo $serverStats['lbtot']; ?></td>
                <td class="border-left"><?php echo $serverStats['bin']; ?></td>
                <td><?php echo $serverStats['bout']; ?></td>
                <td class="border-left"><?php echo $serverStats['dreq']; ?></td>
                <td><?php echo $serverStats['dresp']; ?></td>
                <td class="border-left"><?php echo $serverStats['ereq']; ?></td>
                <td><?php echo $serverStats['econ']; ?></td>
                <td><?php echo $serverStats['eresp']; ?></td>
                <td class="border-left"><?php echo $serverStats['wretr']; ?></td>
                <td class="border-right"><?php echo $serverStats['wredis']; ?></td>
            </tr>
            <?php
endforeach; ?>
        <?php
endforeach; ?>
        </table>

        <form id="actionForm" class="form-inline hidden" method="POST">
            <input id="" type="hidden" name="<?php echo ACTION_PARAM; ?>" value="">
            <input id="" type="hidden" name="<?php echo BACKEND_PARAM; ?>" value="">
            <input id="" type="hidden" name="<?php echo SERVER_PARAM; ?>" value="">
        </form>

        <script src="/assets/js/jQuery.min.js"></script>
        <script src="/assets/js/bootstrap.min.js"></script>
        <script type="text/javascript">
            $('a.serverAction').on("click", function() {
                var action = $(this).data('action'),
                    proxy = $(this).parents('.serverSettings').data('proxy'),
                    server = $(this).parents('.serverSettings').data('server');

                $('#actionForm [name=<?php echo ACTION_PARAM; ?>]').val(action);
                $('#actionForm [name=<?php echo BACKEND_PARAM; ?>]').val(proxy);
                $('#actionForm [name=<?php echo SERVER_PARAM; ?>]').val(server);
                $('#actionForm').submit();
            });
        </script>
    </body>

</html>


