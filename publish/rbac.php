<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    /*
    |--------------------------------------------------------------------------
    | 默认 Guard / Provider
    |--------------------------------------------------------------------------
    |
    | 指定默认使用的 guard 和 provider 名称
    |
    */
    'default' => [
        'guard' => 'jwt',
        'provider' => 'admin_users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard 配置
    |--------------------------------------------------------------------------
    |
    | driver: 字符串别名（extend 注册）或实现 GuardFactoryInterface 的类名
    | provider: 关联的 provider 名称
    |
    */
    'guards' => [
        'jwt' => [
            'driver' => 'jwt',
            'provider' => 'admin_users',
            'secret' => env('RBAC_JWT_SECRET', 'maiscms-secret-key-change-me'),
            'algorithm' => 'HS256',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider 配置
    |--------------------------------------------------------------------------
    |
    | driver: 字符串别名（extendProvider 注册）或实现 ProviderFactoryInterface 的类名
    | table: 用户表名（含前缀由 Hyperf DB_PREFIX 处理）
    |
    */
    'providers' => [
        'admin_users' => [
            'driver' => 'database',
            'table' => 'admin_users',
        ],
    ],
];
