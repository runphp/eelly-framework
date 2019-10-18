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

use Eelly\SDK\Logger\Api\DingLogger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * Class EellyapiHandler.
 *
 * @author hehui<runphp@dingtalk.com>
 */
class EellyapiHandler extends AbstractProcessingHandler
{
    public function __construct($level = Logger::NOTICE, $bubble = true)
    {
        parent::__construct($level, $bubble);
    }

    /**
     * Writes the record down to the log of the implementing handler.
     *
     * @param array $record
     */
    protected function write(array $record): void
    {
        $record['datetime'] = $record['datetime']->getTimestamp();

        try {
            (new DingLogger())->monolog($record);
        } catch (\Throwable $e) {
            // ...
        }
    }
}
