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

namespace Shadon\Application;

use Composer\Autoload\ClassLoader;
use Psr\Log\LoggerInterface;
use Shadon\Context\ContextInterface;
use Shadon\Context\FpmContext;
use Shadon\Exception\ExceptionHandler;
use Shadon\Exception\ServerException;
use function Shadon\Helper\createContext;
use function Shadon\Helper\definedAPPConst;
use Symfony\Component\Debug\ErrorHandler;

/**
 * Trait AppTrait.
 *
 * @author hehui<runphp@qq.com>
 */
trait AppTrait
{
    /**
     * @param string      $rootPath
     * @param ClassLoader $classLoader
     *
     * @throws ServerException
     *
     * @return ContextInterface
     */
    private static function createContext(string $rootPath, ClassLoader $classLoader): ContextInterface
    {
        // const
        definedAPPConst($rootPath);
        // error
        ini_set('display_errors', '0');
        $errorhandler = ErrorHandler::register();
        $exceptionHandler = ExceptionHandler::register('develop' == APP['env']);
        if (\in_array(false, APP)) {
            throw new ServerException('error runtime, check `.env`');
        }
        /* @var FpmContext $context */
        $context = createContext([ClassLoader::class => $classLoader]);
        $errorhandler->setDefaultLogger($context->get(LoggerInterface::class));
        $exceptionHandler->setContext($context);

        return $context;
    }
}
