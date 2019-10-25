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

namespace Shadon\Events;

use Shadon\Context\ContextInterface;

/**
 * Class BeforeResponseEvent.
 *
 * @author hehui<runphp@qq.com>
 */
class BeforeResponseEvent
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * @var ContextInterface
     */
    private $context;

    /**
     * HandleJsonResponse constructor.
     *
     * @param ContextInterface $context
     * @param mixed            $data
     */
    public function __construct(ContextInterface $context, $data)
    {
        $this->data = $data;
        $this->contex = $context;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return ContextInterface
     */
    public function getContex(): ContextInterface
    {
        return $this->contex;
    }
}
