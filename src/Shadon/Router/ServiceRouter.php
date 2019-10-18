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

use Phalcon\Text;

/**
 * @author hehui<hehui@eelly.net>
 */
class ServiceRouter extends Router
{
    public function __construct(bool $defaultRoutes = false)
    {
        parent::__construct($defaultRoutes);
        $this->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);
    }

    public function getControllerName()
    {
        return Text::uncamelize(parent::getControllerName());
    }
}
