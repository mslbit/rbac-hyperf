<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Hyperf\Contract\ConfigInterface;
use Maiscraft\Crypto\CryptoManager;
use Maiscraft\Rbac\AuthManager;
use Maiscraft\Rbac\CasbinRbacEngine;
use Maiscraft\Rbac\Contract\MenuProviderInterface;
use Maiscraft\Rbac\Contract\RbacEngineInterface;
use Psr\Container\ContainerInterface;

/**
 * Hyperf DI 配置提供者
 * 只做配置读取 + 服务注册，零业务逻辑
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                AuthManager::class => static function (ContainerInterface $container) {
                    $config = $container->get(ConfigInterface::class);
                    $rbacConfig = $config->get('rbac', []);

                    $manager = new AuthManager($container, $rbacConfig);

                    // 注册字符串别名驱动
                    $manager->extend('jwt', static function (ContainerInterface $c, array $cfg, $provider) {
                        return new JwtGuard($provider, $cfg);
                    });

                    $manager->extendProvider('database', static function (ContainerInterface $c, array $cfg) {
                        $crypto = $c->has(CryptoManager::class) ? $c->get(CryptoManager::class) : null;
                        return new DatabaseUserProvider(
                            $c->get(\Hyperf\Database\ConnectionResolverInterface::class),
                            $crypto,
                            $cfg
                        );
                    });

                    return $manager;
                },
                MenuProviderInterface::class => static function (ContainerInterface $container) {
                    return new DatabaseMenuProvider(
                        $container->get(\Hyperf\Database\ConnectionResolverInterface::class)
                    );
                },
                CasbinDatabaseAdapter::class => static function (ContainerInterface $container) {
                    return new CasbinDatabaseAdapter(
                        $container->get(\Hyperf\Database\ConnectionResolverInterface::class)
                    );
                },
                RbacEngineInterface::class => static function (ContainerInterface $container) {
                    return new CasbinRbacEngine(
                        $container->get(CasbinDatabaseAdapter::class),
                        $container->get(MenuProviderInterface::class)
                    );
                },
            ],
            'publish' => [
                [
                    'id' => 'rbac-config',
                    'description' => 'RBAC configuration file.',
                    'source' => __DIR__ . '/../publish/rbac.php',
                    'destination' => BASE_PATH . '/config/autoload/rbac.php',
                ],
            ],
        ];
    }
}
