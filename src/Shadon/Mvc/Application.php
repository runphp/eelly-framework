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

use Phalcon\DiInterface;
use Phalcon\Mvc\Application as MvcApplication;

/**
 * Class Application.
 *
 * @author hehui<hehui@eelly.net>
 */
class Application extends MvcApplication
{
    public function __construct(DiInterface $di = null)
    {
        parent::__construct($di);
        $this->useImplicitView(false);
    }

    /**
     * Is implicit view.
     *
     * @return bool
     */
    public function isImplicitView()
    {
        return $this->_implicitView;
    }
}
