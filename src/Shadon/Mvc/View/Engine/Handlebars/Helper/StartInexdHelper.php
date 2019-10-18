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

namespace Shadon\Mvc\View\Engine\Handlebars\Helper;

use Handlebars\Context;
use Handlebars\Helper;
use Handlebars\Template;

/**
 * Handlebars halper interface.
 *
 * @category  Xamin
 *
 * @author    zhangyingdi <zhangyingdi@eelly.net>
 * @copyright 2017 Authors
 **/
class StartInexdHelper implements Helper
{
    /**
     * Execute the helper.
     *
     * @param \Handlebars\Template  $template The template instance
     * @param \Handlebars\Context   $context  The current context
     * @param \Handlebars\Arguments $args     The arguments passed the the helper
     * @param string                $source   The source
     *
     * @return mixed
     */
    public function execute(Template $template, Context $context, $args, $source)
    {
        $parsedArgs = $template->parseArguments($args);
        $first = $parsedArgs[0];
        $last = (int) $parsedArgs[1];
        $first = '' === $context->get($first) ? 1 : (int) $context->get($first);

        return $first * 1 + $last;
    }
}
