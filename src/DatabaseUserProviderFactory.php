<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Hyperf\Database\ConnectionResolverInterface;
use Maiscraft\Rbac\Contract\ProviderFactoryInterface;
use Maiscraft\Rbac\Contract\UserProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * DatabaseUserProvider 工厂
 * AuthManager 通过此工厂创建 DatabaseUserProvider 实例
 * 可通过字符串别名 'database' 或类名 DatabaseUserProviderFactory::class 引用
 */
class DatabaseUserProviderFactory implements ProviderFactoryInterface
{
    public function __construct(
        protected ContainerInterface $container
    ) {
    }

    public function make(array $config): UserProviderInterface
    {
        $resolver = $this->container->get(ConnectionResolverInterface::class);
        return new DatabaseUserProvider($resolver, $config);
    }
}