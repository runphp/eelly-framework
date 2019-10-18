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

namespace Shadon\Client;

use ErrorException;
use Swoole\Client;

class TcpClient extends Client
{
    public function __construct($sockType, $syncType = SWOOLE_SOCK_SYNC, $connectionKey = '')
    {
        parent::__construct($sockType, $syncType, $connectionKey);
        $this->set([
            'open_eof_check'     => true,
            'package_eof'        => "\r\n",
            'package_max_length' => 1024 * 1024 * 2,
        ]);
    }

    /**
     * send data as json.
     *
     * @param array $data
     *
     * @return bool
     */
    public function sendJson(array $data)
    {
        return $this->send(\GuzzleHttp\json_encode($data)."\r\n");
    }

    /**
     * receive json data.
     *
     * @throws ErrorException
     *
     * @return mixed
     */
    public function recvJson()
    {
        $recvData = false;
        while (true) {
            try {
                $recvData = $this->recv();
                break;
            } catch (ErrorException $e) {
                if (0 != $this->errCode) {
                    throw $e;
                }
            }
        }
        if (false === $recvData) {
            throw new ErrorException('Server error('.$this->errCode.'))');
        }

        return \GuzzleHttp\json_decode($recvData, true);
    }
}
