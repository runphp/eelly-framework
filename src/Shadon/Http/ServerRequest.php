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

namespace Shadon\Http;

use Zend\Diactoros\ServerRequest as DiactorosServerRequest;

/**
 * Class ServerRequest.
 *
 * @author hehui<runphp@qq.com>
 */
class ServerRequest extends DiactorosServerRequest
{
    /**
     * @param bool $trustForwardedHeader
     *
     * @return |null
     */
    public function getClientAddress(bool $trustForwardedHeader = true)
    {
        $server = $this->getServerParams();
        $address = null;
        /*
         * Proxies uses this IP
         */
        if ($trustForwardedHeader) {
            if (isset($server['HTTP_X_FORWARDED_FOR'])) {
                $address = $server['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($server['HTTP_CLIENT_IP'])) {
                $address = $server['HTTP_CLIENT_IP'];
            }
        }
        if (null === $address) {
            $address = $server['REMOTE_ADDR'];
        }

        if (\is_string($address)) {
            if (false !== strpos($address, ',')) {
                $address = explode(',', $address)[0];
            }
        }

        return $address;
    }
}
