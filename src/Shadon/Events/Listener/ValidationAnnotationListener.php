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

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use RuntimeException;
use Shadon\Exception\InvalidArgumentException;
use Shadon\Validation\Validation;

/**
 * validation annotation listner.
 *
 * @author hehui<hehui@eelly.net>
 */
class ValidationAnnotationListener extends AbstractListener
{
    /**
     * 注解名称.
     */
    private const ANNOTATIONS_NAME = 'Validation';

    /**
     * @param Event      $event
     * @param Dispatcher $dispatcher
     *
     * @return bool
     */
    public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
    {
        // Parse the annotations in the method currently executed
        $annotations = $this->annotations->getMethod(
            $dispatcher->getControllerClass(),
            $dispatcher->getActiveMethod()
        );
        $this->annotationsColletion = $annotations;
        if ($annotations->has(self::ANNOTATIONS_NAME)) {
            $validation = new Validation();
            foreach ($annotations->get(self::ANNOTATIONS_NAME)->getArguments() as $annotation) {
                list($field, $args) = $annotation->getArguments();
                $validatorName = '\\Shadon\\Validation\\Validator\\'.$annotation->getName();
                if (!class_exists($validatorName)) {
                    $validatorName = '\\Phalcon\\Validation\\Validator\\'.$annotation->getName();
                    if (!class_exists($validatorName)) {
                        throw new RuntimeException('Not found '.$annotation->getName().' validator');
                    }
                }
                $validation->add($field, new $validatorName($args));
            }
            $params = array_values($dispatcher->getParams());
            foreach ($validation->validate($params) as $item) {
                throw new InvalidArgumentException($item->getMessage());
            }
        }
    }
}
