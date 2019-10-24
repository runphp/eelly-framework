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

use Symfony\Component\HttpFoundation\JsonResponse;

class HandleJsonResponse
{
    /**
     * @var JsonResponse
     */
    private $response;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var string
     */
    private $requestId;

    /**
     * @var int
     */
    private $tpl;

    /**
     * HandleJsonResponse constructor.
     *
     * @param JsonResponse $response
     * @param $data
     */
    public function __construct(JsonResponse $response, $data, string $requestId, int $tpl)
    {
        $this->response = $response;
        $this->data = $data;
        $this->requestId = $requestId;
        $this->tpl = $tpl;
    }

    /**
     * @return JsonResponse
     */
    public function getResponse(): JsonResponse
    {
        return $this->response;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param JsonResponse $response
     */
    public function setResponse(JsonResponse $response): void
    {
        $this->response = $response;
    }

    /**
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }

    /**
     * @return int
     */
    public function getTpl(): int
    {
        return $this->tpl;
    }
}
