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

namespace Shadon\Helper;

use DI;
use Illuminate\Config\Repository;
use Shadon\Context\ContextInterface;

require __DIR__.'/path.php';

/**
 * @return bool
 */
function isCli()
{
    return \PHP_SAPI === 'cli';
}

/**
 * @param string $rootPath
 *
 * @throws \Exception
 */
function definedAPPConst(string $rootPath): void
{
    if (\defined('APP')) {
        return;
    }
    chdir($rootPath);
    if (!file_exists('.env')) {
        file_put_contents('.env', preg_replace(
                '/^APP_KEY=/m',
                'APP_KEY='.base64_encode(random_bytes(32)),
                file_get_contents('.env.example'))
        );
    }
    $dotenv = \Dotenv\Dotenv::create($rootPath);
    $dotenv->load();
    \define('APP', [
        'rootPath'   => $rootPath,
        'env'        => getenv('APP_ENV'),
        'key'        => getenv('APP_KEY'),
        'namespace'  => getenv('NS'),
        'rootPath'   => $rootPath,
        'serverName' => 'Shadon',
        'version'    => '2.0',
    ]);
}

/**
 * @param array $definitions
 *
 * @return ContextInterface
 */
function createContext(array $definitions = []): ContextInterface
{
    $isCli = isCli();
    $containerBuilder = new DI\ContainerBuilder();
    $containerBuilder->enableCompilation(realpath('var'), $isCli ? 'CompiledContainerConsole' : 'CompiledContainerFpm');
    $containerBuilder->writeProxiesToFile(true, realpath($isCli ? 'var/cache/console' : 'var/cache/fpm'));
    $containerBuilder->useAutowiring(true);
    $containerBuilder->useAnnotations(true);
    $config = (require realpath('var/config').($isCli ? '/console.php' : '/fpm.php')) + (require realpath('var/config/'.APP['env']).'/config.php');
    $definitions += $config['definitions'];
    unset($config['definitions']);
    $definitions += [
        // config
        'config' => new Repository($config),
    ];
    $containerBuilder->addDefinitions($definitions);

    $di = $containerBuilder->build();

    return $di->get(ContextInterface::class);
}
