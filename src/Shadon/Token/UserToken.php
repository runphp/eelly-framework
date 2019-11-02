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
use Shadon\Exception\UnauthorizedException;
use Shadon\Exception\UnsupportedException;
use Shadon\Token\Data\DataInterface;
use Shadon\Token\Data\Guest;
use Shadon\Token\Data\User;
use Shadon\Token\Storage\StorageInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class UserToken.
 *
 * @author hehui<runphp@qq.com>
 */
class UserToken implements TokenInterface
{
    /**
     * @var StorageInterface
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

    public function __construct(StorageInterface $storage, Key $key, Request $request)
    {
        $this->storage = $storage;
        $this->key = $key;
        $this->request = $request;
    }

    public function setToken(?string $tokenId = null, DataInterface $data): DataInterface
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

    public function getToken(?string $tokenId = null): DataInterface
    {
        if (null === $tokenId) {
            $tokenId = $this->request->headers->get('Authorization');
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

    private function requesInfo(?DataInterface $data = null): DataInterface
    {
        if (null === $data) {
            $data = new Guest();
        }
        $data->ip = $this->request->getClientIp();
        $data->userAgent = $this->request->headers->get('user-agent');

        return $data;
    }
}
