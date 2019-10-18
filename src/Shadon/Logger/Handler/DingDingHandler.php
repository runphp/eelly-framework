<?php

declare(strict_types=1);

/*
 * This file is part of eelly package.
 *
 * (c) eelly.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Shadon\Logger\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class DingDingHandler extends AbstractProcessingHandler
{
    /**
     * @var string
     */
    private $accessToken;

    public function __construct(string $accessToken, $level = Logger::NOTICE, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->accessToken = $accessToken;
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     */
    protected function write(array $record): void
    {
        $this->sendToDingDing($record['formatted']);
    }

    private function sendToDingDing($content): void
    {
        static $client;
        if (null === $client) {
            $client = new \GuzzleHttp\Client([
                'base_uri'    => 'https://oapi.dingtalk.com/robot/send',
                'query'       => ['access_token' => $this->accessToken],
                'http_errors' => false, ]
            );
        }
        $contentList = explode("\r\n", chunk_split((string) $content, 20000));
        array_pop($contentList);
        $promises = [];
        foreach ($contentList as $value) {
            $msg = [
                'msgtype' => 'text',
                'text'    => [
                    'content' => (string) $value,
                ],
            ];
            $promises[] = $client->postAsync('', ['json' => $msg]);
        }
        \GuzzleHttp\Promise\unwrap($promises);
    }
}
