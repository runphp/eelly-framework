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

namespace Shadon\Mvc;

/**
 * @author hehui<hehui@eelly.net>
 */
class LogicController extends Controller
{
    /**
     * @param string $repository
     * @param array  $parameters
     *
     * @return mixed
     */
    public function getRepository(string $repository, array $parameters = null)
    {
        return $this->getDI()->getShared($repository, $parameters);
    }
}
