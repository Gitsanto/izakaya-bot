<?php

/**
 * Gnavi.php
 *
 * @copyright   Copyright (c) 2016 sonicmoov Co.,Ltd.
 * @version     $Id$
 */
namespace Izakaya\Library;

use LINE\LINEBot\HTTPClient\Curl;
use LINE\LINEBot\Exception\CurlExecutionException;
use LINE\LINEBot\Response;

class Gnavi
{
    /** @var string */
    private $apiEndpoint = 'http://api.gnavi.co.jp/RestSearchAPI/20150630/';

    /** @var string */
    private $accessKey;

    public function __construct($accessKey)
    {
        $this->accessKey = $accessKey;
    }

    public function search($lat, $lon)
    {
        $query = http_build_query([
            'keyid' => $this->accessKey,
            'format' => 'json',
            'hit_per_page' => 5,
            'latitude' => $lat,
            'longitude' => $lon,
            'category_s' => 'RSFST09004' // 居酒屋
        ], '', '&');

        $url = $this->apiEndpoint . '?' . $query;

        $curl = new Curl($url);

        $options = [
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_HEADER => true
        ];

        $curl->setoptArray($options);

        $result = $curl->exec();

        if ($curl->errno()) {
            throw new CurlExecutionException($curl->error());
        }

        $info = $curl->getinfo();
        $httpStatus = $info['http_code'];

        $responseHeaderSize = $info['header_size'];

        $responseHeaderStr = substr($result, 0, $responseHeaderSize);
        $responseHeaders = [];
        foreach (explode("\r\n", $responseHeaderStr) as $responseHeader) {
            $kv = explode(':', $responseHeader, 2);
            if (count($kv) === 2) {
                $responseHeaders[$kv[0]] = trim($kv[1]);
            }
        }

        $body = substr($result, $responseHeaderSize);

        return new Response($httpStatus, $body, $responseHeaders);
    }
}