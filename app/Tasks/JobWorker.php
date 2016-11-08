<?php

/**
 * JobWorker.php
 *
 * @copyright   Copyright (c) 2016 sonicmoov Co.,Ltd.
 * @version     $Id$
 */
namespace Izakaya\Tasks;

use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use Phalcon\Di\Injectable;
use LINE\LINEBot\Exception\InvalidSignatureException;
use LINE\LINEBot\Exception\UnknownEventTypeException;
use LINE\LINEBot\Exception\UnknownMessageTypeException;
use LINE\LINEBot\Exception\InvalidEventRequestException;
use LINE\LINEBot\Event\FollowEvent;
use LINE\LINEBot\Event\UnfollowEvent;
use LINE\LINEBot\Event\MessageEvent\LocationMessage;
use Phalcon\Logger\Adapter\Stream as Logger;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;

class JobWorker extends Injectable
{
    /** @var \Phalcon\Logger\AdapterInterface $logger */
    private $logger;

    public function getLogger()
    {
        if ($this->logger)
            return $this->logger;

        return $this->logger = new Logger('php://stderr');
    }

    public function setup()
    {

    }

    public function perform()
    {
        /** @var \LINE\LINEBot $bot */
        $bot = $this->getDI()->get('bot');

        /** @var \Izakaya\Library\Gnavi $gnavi */
        $gnavi = $this->getDI()->get('gnavi');

        try {

            try {
                $events = $bot->parseEventRequest(
                    $this->args['body'], $this->args['signature']
                );
            } catch (InvalidSignatureException $e) {
                throw new \Exception('Invalid signature');
            } catch (UnknownEventTypeException $e) {
                throw new \Exception('Unknown event type has come');
            } catch (UnknownMessageTypeException $e) {
                throw new \Exception('Unknown message type has come');
            } catch (InvalidEventRequestException $e) {
                throw new \Exception('Invalid event request');
            }

            foreach ($events as $event) {
                if ($event instanceof FollowEvent) {
                    $r = $bot->getProfile($event->getUserId());
                    if ($r->isSucceeded()) {
                        $profile = $r->getJSONDecodedBody();
                        $response = $bot->replyText(
                            $event->getReplyToken(),
                            sprintf("%s さん、友達追加ありがとう！", $profile['displayName'])
                        );
                        $this->getLogger()->info($response->getHTTPStatus() . ': ' . $response->getRawBody());
                    }
                } elseif ($event instanceof UnfollowEvent) {

                } elseif ($event instanceof LocationMessage) {
                    $lat = $event->getLatitude();
                    $lon = $event->getLongitude();

                    $results = $gnavi->search($lat, $lon);
                    $results = $results->getJSONDecodedBody();

                    if ($results['total_hit_count'] === 0) {
                        $response = $bot->pushMessage(
                            $event->getUserId(),
                            new TextMessageBuilder('付近に居酒屋はないようです')
                        );
                        $this->getLogger()->info($response->getHTTPStatus() . ': ' . $response->getRawBody());
                    } else {
                        $columns = [];
                        foreach ($results['rest'] as $restaurant) {
                            $imageUrl = array_filter($restaurant['image_url']);
                            if (isset($imageUrl['shop_image1'])) {
                                $imageUrl = str_replace('http://', 'https://', $imageUrl['shop_image1']);
                            } elseif (isset($imageUrl['shop_image2'])) {
                                $imageUrl = str_replace('http://', 'https://', $imageUrl['shop_image2']);
                            } else {
                                $imageUrl = 'https://placehold.jp/3d4070/ffffff/1024x1024.png?text=No%20Image';
                            }

                            $shopUrl = $restaurant['url_mobile'];
                            $columns[] = new CarouselColumnTemplateBuilder(
                                $restaurant['name'],
                                $restaurant['category'],
                                $imageUrl,
                                [ new UriTemplateActionBuilder('お店の詳細を見る', $shopUrl) ]
                            );
                        }

                        $carouselTemplateBuilder = new CarouselTemplateBuilder($columns);
                        $multiMessageBuilder = new MultiMessageBuilder();

                        $response = $bot->pushMessage(
                            $event->getUserId(),
                            $multiMessageBuilder
                                ->add(new TextMessageBuilder('近くに居酒屋を発見しました！'))
                                ->add(new TemplateMessageBuilder('alt text', $carouselTemplateBuilder))
                                ->add(new TextMessageBuilder('Powered by ぐるなび'))
                        );

                        $this->getLogger()->info($response->getHTTPStatus() . ': ' . $response->getRawBody());
                    }
                } else {
                    $this->getLogger()->info('Non Location message has come');

                    $response = $bot->replyText(
                        $event->getReplyToken(),
                        '位置情報を送ってね！'
                    );

                    $this->getLogger()->info($response->getHTTPStatus() . ': ' . $response->getRawBody());
                }
            }

        } catch (\Exception $e) {

            error_log($e->getMessage());
            error_log($e->getTraceAsString());

        }
    }

    public function tearDown()
    {

    }

}