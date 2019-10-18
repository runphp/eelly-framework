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

namespace Shadon\Mvc\Collection;

/**
 * @author    hehui<hehui@eelly.net>
 */
class Manager extends \Phalcon\Mvc\Collection\Manager
{
    public function afterServiceResolve(): void
    {
        $di = $this->getDI();
        $mongodbConfig = $di->getModuleConfig()['mongodb'];
        foreach ($mongodbConfig as $key => $value) {
            $di->setShared('mongo_'.$key, [
                'className' => \MongoDB\Client::class,
                'arguments' => [
                    [
                        'type'  => 'parameter',
                        'value' => $value['uri'],
                    ],
                    [
                        'type'  => 'parameter',
                        'value' => $value['uriOptions']->toArray(),
                    ],
                    [
                        'type'  => 'parameter',
                        'value' => $value['driverOptions']->toArray(),
                    ],
                ],
            ]);
        }
    }
}
