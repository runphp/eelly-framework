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
class SortFunHelper implements Helper
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
        $buffer = '';
        $parsedArgs = $template->parseArguments($args);
        $firstArg = $context->get($parsedArgs[0]);
        $secondArg = (string) $parsedArgs[1];  //要排序的字段
        $thirdArg = (string) $parsedArgs[2] ? (string) $parsedArgs[2] : 'asc';

        if (\is_array($firstArg) && !empty($firstArg)) {
            $newData = $listData = [];
            foreach ($firstArg as $fkey => $fval) {
                if (\is_array($fval) && isset($fval[$secondArg])) {
                    $newData[$fkey] = $fval[$secondArg];
                } else {
                    $newData[$fkey] = $fval;
                }
            }
            'asc' == $thirdArg && asort($newData);
            'desc' == $thirdArg && arsort($newData);
            //重新排序数组
            if (isset($firstArg[$secondArg])) {
                $listData = $newData;
            } else {
                foreach ($newData as $nkey => $nval) {
                    if (isset($firstArg[$nkey])) {
                        $listData[$nkey] = $firstArg[$nkey];
                        $listData[$nkey][$secondArg] = $nval;
                    }
                }
            }

            //生成标签
            foreach ($listData as $lkey => $lval) {
                $lval['indexNum'] = $lkey;
                $context->push($lval);
                $template->setStopToken('else');
                $template->rewind();
                $buffer .= $template->render($context);
                $context->pop();
                $context->popSpecialVariables();
            }
        } else {
            $template->setStopToken('else');
            $template->discard();
            $template->setStopToken(false);
            $buffer = $template->render($context);
        }

        return $buffer;
    }
}
