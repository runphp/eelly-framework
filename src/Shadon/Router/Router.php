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

namespace Shadon\Router;

use Phalcon\Mvc\Router as MvcRouter;

/**
 * @author hehui<hehui@eelly.net>
 */
class Router extends MvcRouter
{
    /**
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->_params = $params;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Phalcon\Mvc\Router::getRewriteUri()
     */
    public function getRewriteUri()
    {
        $url = $_SERVER['REQUEST_URI'];
        $urlParts = explode('?', $url);
        if (!empty($urlParts[0])) {
            return $urlParts[0];
        }

        return '/';
    }
}
