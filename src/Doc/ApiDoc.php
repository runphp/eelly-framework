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

namespace Eelly\Doc;

use Eelly\Doc\Adapter\ApiDocumentShow;
use Eelly\Doc\Adapter\HomeDocumentShow;
use Eelly\Doc\Adapter\ModuleDocumentShow;
use Eelly\Doc\Adapter\ServiceDocumentShow;
use Eelly\Exception\RequestException;
use Eelly\Mvc\Controller;

class ApiDoc extends Controller
{
    /**
     * @param string $module
     * @param string $class
     * @param string $method
     */
    public function display(string $module, string $class, string $method): void
    {
        $request = $this->request;
        //dd($module, $class,$method, $request->getURI());
        while (true) {
            if ('/' == $request->getURI()) {
                $documentShow = new HomeDocumentShow();
                break;
            }
            if (false !== strpos($class, 'Logic\\IndexLogic')) {
                $moduleClass = ucfirst($module).'\Module';
                if (class_exists($moduleClass)) {
                    $documentShow = new ModuleDocumentShow($moduleClass);
                    break;
                }
            }
            if (class_exists($class)) {
                if ('index' == $method) {
                    $documentShow = new ServiceDocumentShow($class);
                    break;
                }
                if (method_exists($class, $method)) {
                    $documentShow = new ApiDocumentShow($class, $method);
                    break;
                }
            }

            throw new RequestException(404, null, $this->request, $this->response);
        }
        $this->response->setContentType('text/html', 'utf-8');
        $documentShow->setDI($this->getDI());
        $documentShow->display();
    }
}
