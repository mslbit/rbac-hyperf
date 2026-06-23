<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Maiscraft\Rbac\Contract\GuardFactoryInterface;
use Maiscraft\Rbac\Contract\GuardInterface;
use Maiscraft\Rbac\Contract\UserProviderInterface;

/**
 * JwtGuard 工厂
 * AuthManager 通过此工厂创建 JwtGuard 实例
 * 可通过字符串别名 'jwt' 或类名 JwtGuardFactory::class 引用
 */
class JwtGuardFactory implements GuardFactoryInterface
{
    public function make(array $config, UserProviderInterface $provider): GuardInterface
    {
        return new JwtGuard($provider, $config);
    }
}