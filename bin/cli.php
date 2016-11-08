<?php
/**
 * cli.php
 *
 * @copyright   Copyright (c) 2016 sonicmoov Co.,Ltd.
 * @version     $Id$
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Phalcon\Di\FactoryDefault\Cli as CliDI;
use Phalcon\Cli\Console as ConsoleApp;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use Izakaya\Library\Gnavi;

try {

    $di = new CliDI();

    $cli = new ConsoleApp($di);

    $cli->getDI()->set('bot', function () use ($cli) {
        $channelToken = 'Channel Access Token';
        $channelSecret = 'Channel Secret';

        $bot = new LINEBot(
            new CurlHTTPClient($channelToken),
            [ 'channelSecret' => $channelSecret ]
        );

        return $bot;
    });

    $cli->getDI()->set('gnavi', function () use ($cli) {
        $accessKey = 'Access Key';

        $gnavi = new Gnavi($accessKey);

        return $gnavi;
    });

    return $cli;

} catch (\Exception $e) {

    error_log($e->getMessage());
    error_log($e->getTraceAsString());

    exit(255);

}

exit(0);