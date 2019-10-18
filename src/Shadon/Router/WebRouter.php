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

use Phalcon\Mvc\Router\Annotations as RouterAnnotations;
use Phalcon\Text;

/**
 * @author hehui<hehui@eelly.net>
 */
class WebRouter extends RouterAnnotations
{
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

    public function getControllerName()
    {
        return Text::uncamelize(parent::getControllerName());
    }
}
