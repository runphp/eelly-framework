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

namespace Shadon\Utils;

class DateTime
{
    /**
     * 获取当前时间.
     *
     * @param string $timezone 时区
     * @param string $format   日期格式
     *
     * @return string
     */
    public static function formatTime(string $timezone = APP['timezone'], string $format = DATE_ATOM)
    {
        $dateTime = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)));
        if (null !== $timezone) {
            $dateTime->setTimezone(new \DateTimeZone($timezone));
        }

        return $dateTime->format($format);
    }
}
