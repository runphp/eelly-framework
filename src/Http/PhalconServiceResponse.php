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

namespace Shadon\Http;

use Phalcon\Http\Response as HttpResponse;
use Shadon\Application\ApplicationConst;

/**
 * Class PhalconServiceResponse.
 */
class PhalconServiceResponse extends HttpResponse
{
    public function __construct($content = null, $code = null, $status = null)
    {
        parent::__construct($content, $code, $status);
        $this->setHeader('Access-Control-Allow-Origin', '*');
        $this->setHeader('Server', ApplicationConst::APP_NAME.'/'.ApplicationConst::APP_VERSION);
        $this->setStatusCode(200);
    }

    /**
     * {@inheritdoc}
     */
    public function setJsonContent($content, $jsonOptions = 0, $depth = 512): HttpResponse
    {
        $this->setContentType('application/json', 'UTF-8');
        $json = json_encode($content, $jsonOptions, $depth);
        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->setStatusCode(500);
            parent::setJsonContent(['error' => 'json_encode error: '.json_last_error_msg()]);
        } else {
            $this->setContent($json);
        }

        return $this;
    }
}
