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
class CompareHelper implements Helper
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
        $firstArgs = '' === $context->get($parsedArgs[0]) ? (int) $parsedArgs[0] : (int) $context->get($parsedArgs[0]);
        $secondArgs = (string) $parsedArgs[1];
        $thirdArgs = (string) $parsedArgs[2];
        $result = $buffer = '';

        switch ($secondArgs) {
            case '>':
                $result = $firstArgs > $thirdArgs ? true : false;
                break;
            case '>=':
                $result = $firstArgs >= $thirdArgs ? true : false;
                break;
            case '<':
                $result = $firstArgs < $thirdArgs ? true : false;
                break;
            case '<=':
                $result = $firstArgs <= $thirdArgs ? true : false;
                break;
            case '==':
                $result = $firstArgs == $thirdArgs ? true : false;
                break;
            default:
                $result = false;
                break;
        }

        if ($result) {
            $template->setStopToken('else');
            $buffer = $template->render($context);
            $template->setStopToken(false);
            $template->discard($context);
        } else {
            $template->setStopToken('else');
            $template->discard($context);
            $template->setStopToken(false);
            $buffer = $template->render($context);
        }

        return $buffer;
    }
}
