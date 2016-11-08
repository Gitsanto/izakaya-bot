<?php
/**
 * cli.php
 *
 * @copyright   Copyright (c) 2016 sonicmoov Co.,Ltd.
 * @version     $Id$
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Micro;
use LINE\LINEBot\Constant\HTTPHeader;

try {

    $di = new FactoryDefault();

    $app = new Micro($di);

    $app->post('/callback', function () use ($app) {
        $signature = $app->request->getHeader(HTTPHeader::LINE_SIGNATURE);
        if (empty($signature)) {
            return $app->response->setStatusCode(400, 'Bad Request')->sendHeaders();
        }

        $body = $app->request->getRawBody();

        Resque::setBackend('127.0.0.1:6379');

        Resque::enqueue(
            'events',
            'Izakaya\Tasks\JobWorker',
            [ 'body' => $body, 'signature' => $signature ]
        );

        return $app->response->setStatusCode(200, 'OK')->sendHeaders();
    });

    $app->notFound(function () use ($app) {
        return $app->response->setStatusCode(400, 'Not Found')->sendHeaders();
    });

    $app->handle();

} catch (\Exception $e) {

    error_log($e->getMessage());
    error_log($e->getTraceAsString());

}
