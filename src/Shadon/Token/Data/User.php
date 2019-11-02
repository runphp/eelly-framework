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

namespace Shadon\Token\Data;

use Shadon\Exception\ServerException;

/**
 * Class User.
 *
 * @author hehui<runphp@qq.com>
 */
class User extends Guest
{
    public function __construct($array = [], $flags = 0)
    {
        if (!isset($array['uid'], $array['username'], $array['avatar'], $array['mobile'])) {
            throw new ServerException(sprintf('Invalid arguments `%s`', json_encode($array)));
        }
        $array['uid'] = (int) $array['uid'];
        parent::__construct($array, $flags);
    }
}
