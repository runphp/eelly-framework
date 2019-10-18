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

namespace Shadon\Events\Listener;

use League\OAuth2\Server\Exception\OAuthServerException;
use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use Phalcon\Text;
use Shadon\Application\ApplicationConst;

/**
 * 访问控制.
 *
 * @author hehui<hehui@eelly.net>
 */
class AclListener extends AbstractListener
{
    /**
     * 仅内部可调用的服务
     *
     * @var string
     */
    private const INTERNAL_ANNOTATION = 'internal';

    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher): void
    {
        // Parse the annotations in the method currently executed
        $annotations = $this->annotations->getMethod(
            $dispatcher->getControllerClass(),
            $dispatcher->getActiveMethod()
        );
        if ($annotations->has(self::INTERNAL_ANNOTATION)) {
            if (!Text::startsWith(ApplicationConst::$oauth['oauth_client_id'], self::INTERNAL_ANNOTATION)) {
                /* @var \Phalcon\Http\Request $request */
                $request = $this->getDI()->getShared('request');

                throw OAuthServerException::invalidScope($request->getURI());
            }
        }
    }
}
