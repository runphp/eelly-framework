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

use GuzzleHttp\Psr7\ServerRequest;
use Phalcon\Http\Request as HttpRequest;
use swoole_http_request as SwooleHttpRequest;

class SwoolePhalconRequest extends HttpRequest
{
    /**
     * @var SwooleHttpRequest
     */
    private $swooleHttpRequest;

    public function initialWithSwooleHttpRequest(SwooleHttpRequest $swooleHttpRequest): void
    {
        $headers = [];
        foreach ($swooleHttpRequest->header as $key => $value) {
            $headers['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
        }
        $_SERVER = array_change_key_case(array_merge($swooleHttpRequest->server, $headers), CASE_UPPER);
        $_GET = $swooleHttpRequest->get ?: [];
        $_POST = $swooleHttpRequest->post ?: [];
        $_COOKIE = $swooleHttpRequest->cookie ?: [];
        $_FILES = $swooleHttpRequest->files ?: [];
        $this->swooleHttpRequest = $swooleHttpRequest;
    }

    /**
     * @return SwooleHttpRequest
     */
    public function getSwooleHttpRequest()
    {
        return $this->swooleHttpRequest;
    }

    /**
     * @return array|mixed
     */
    public function getRouteParams(): array
    {
        if (0 === strpos($this->getHeader('Content-Type'), 'application/json')) {
            $json = $this->swooleHttpRequest->rawContent();

            try {
                $params = \GuzzleHttp\json_decode($json, true);
            } catch (InvalidArgumentException $e) {
                throw new RequestException(400, $e->getMessage(), $this, $this->getDI()->getShared('response'));
            }
        } else {
            $params = $this->getPost();
        }
        $uploadFiles = ServerRequest::normalizeFiles($_FILES);
        $params = (array) $params;
        $params = array_replace_recursive($params, $uploadFiles);
        $this->sortNestedArrayAssoc($params);

        return $params;
    }

    private function sortNestedArrayAssoc($arr): bool
    {
        if (!\is_array($arr)) {
            return false;
        }
        ksort($arr);
        foreach ($arr as $key => $value) {
            $this->sortNestedArrayAssoc($arr[$key]);
        }

        return true;
    }
}
