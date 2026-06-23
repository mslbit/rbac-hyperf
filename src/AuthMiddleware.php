<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Maiscraft\Rbac\AuthManager;
use Maiscraft\Rbac\Contract\GuardInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Auth 中间件
 * 在请求阶段通过 Guard 认证用户
 * 认证结果写入 Request Attribute，后续业务可直接读取
 */
class AuthMiddleware implements MiddlewareInterface
{
    protected GuardInterface $guard;

    public function __construct(
        protected AuthManager $authManager
    ) {
        $this->guard = $authManager->guard();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 通过 Guard 认证请求
        $this->guard->authenticate($request);

        // 将认证用户 ID 写入 Request Attribute，供后续业务使用
        $userId = $this->guard->id();
        if ($userId !== null) {
            $request = $request->withAttribute('auth_user_id', $userId);
        }

        return $handler->handle($request);
    }
}