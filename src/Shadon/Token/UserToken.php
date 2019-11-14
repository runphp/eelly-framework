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

namespace Shadon\Token;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Psr\Http\Message\ServerRequestInterface;
use Shadon\Exception\UnauthorizedException;
use Shadon\Exception\UnsupportedException;
use Shadon\Token\Data\DataInterface;
use Shadon\Token\Data\Guest;
use Shadon\Token\Data\User;
use Shadon\Token\Storage\StorageInterface;

/**
 * Class UserToken.
 *
 * @author hehui<runphp@qq.com>
 */
class UserToken implements TokenInterface
{
    /**
     * @var ServerRequestInterface
     */
    private $storage;

    /**
     * @var Key
     */
    private $key;

    /**
     * @var Request
     */
    private $request;

    public function __construct(StorageInterface $storage, Key $key, ServerRequestInterface $request)
    {
        $this->storage = $storage;
        $this->key = $key;
        $this->request = $request;
    }

    public function setToken(DataInterface $data, string $tokenId = null): DataInterface
    {
        $this->requesInfo($data);
        if ($data instanceof User) {
            if (null === $tokenId) {
                // new token
                $tokenId = Crypto::encrypt((string) $data->uid, $this->key);
            }
            $this->storage->saveToken($tokenId, $data);
            $data->token = $tokenId;
        }

        return $data;
    }

    public function getToken(string $tokenId = null): DataInterface
    {
        if (null === $tokenId) {
            $authHeader = $this->request->getHeader('Authorization');
            if ($authHeader) {
                $tokenId = current($authHeader);
            } else {
                return $this->requesInfo();
            }
        }
        try {
            $uid = Crypto::decrypt($tokenId, $this->key);
            $data = $this->storage->fetchToken($tokenId, $uid);
            $data->token = $tokenId;
        } catch (WrongKeyOrModifiedCiphertextException | UnsupportedException | UnauthorizedException $e) {
            $data = $this->requesInfo();
        }

        return $data;
    }

    /**
     * @param array $server
     * @param bool  $trustForwardedHeader
     *
     * @return mixed|null
     */
    private function getClientAddress(array $server, bool $trustForwardedHeader = true)
    {
        $address = null;
        /*
         * Proxies uses this IP
         */
        if ($trustForwardedHeader) {
            if (isset($server['HTTP_X_FORWARDED_FOR'])) {
                $address = $server['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($server['HTTP_CLIENT_IP'])) {
                $address = $server['HTTP_CLIENT_IP'];
            }
        }
        if (null === $address) {
            $address = $server['REMOTE_ADDR'];
        }

        if (\is_string($address)) {
            if (false !== strpos($address, ',')) {
                $address = explode(',', $address)[0];
            }
        }

        return $address;
    }

    private function requesInfo(DataInterface $data = null): DataInterface
    {
        if (null === $data) {
            $data = new Guest();
        }

        $ip = $this->getClientAddress($this->request->getServerParams());
        if ($ip) {
            $data->ip = $ip;
        }
        $userAgentHeader = $this->request->getHeader('user-agent');
        if ($userAgentHeader) {
            $data->userAgent = current($userAgentHeader);
        }

        return $data;
    }
}
