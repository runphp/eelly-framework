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

namespace Shadon\Flash;

use Phalcon\Flash\Direct as FlashDirect;

/**
 * Class Direct.
 */
class Direct extends FlashDirect
{
    public function __construct(array $cssClasses = null)
    {
        if (null === $cssClasses) {
            $cssClasses = [
                'error'   => 'alert alert-danger',
                'success' => 'alert alert-success',
                'notice'  => 'alert alert-info',
                'warning' => 'alert alert-warning',
            ];
        }
        $this->setAutoescape(false);
        parent::__construct($cssClasses);
    }

    /**
     * @param bool $remove
     *
     * @return string
     */
    public function getOutput(bool $remove = true): string
    {
        $str = '';
        !\is_array($this->_messages) && $this->_messages = [];
        foreach ($this->_messages as $message) {
            $str .= $message;
        }
        if ($remove) {
            parent::clear();
        }

        return $str;
    }
}
