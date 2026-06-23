<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Maiscraft\Rbac\Contract\AuthenticatableInterface;
use Maiscraft\Rbac\Contract\GuardInterface;
use Maiscraft\Rbac\Contract\UserProviderInterface;
use Maiscraft\Rbac\GuardHelpers;
use Psr\Http\Message\ServerRequestInterface;

/**
 * JWT Guard
 * 从 Authorization Header 提取 JWT → 验证 → 通过 UserProvider 获取用户
 *
 * 认证流程：
 * 1. authenticate($request) 从 Header 提取 Bearer Token
 * 2. 解码 JWT 获取 subject（用户 ID）
 * 3. 通过注入的 UserProvider 获取用户实例
 * 4. 也可以从 Request Attribute 读取预解析的 user ID（中间件预处理场景）
 */
class JwtGuard implements GuardInterface
{
    use GuardHelpers;

    protected string $secret;
    protected string $algorithm;
    protected string $headerKey;
    protected string $attributeKey;

    public function __construct(
        UserProviderInterface $provider,
        array $config = []
    ) {
        $this->provider = $provider;
        $this->secret = $config['secret'] ?? '';
        $this->algorithm = $config['algorithm'] ?? 'HS256';
        $this->headerKey = $config['header_key'] ?? 'Authorization';
        $this->attributeKey = $config['attribute_key'] ?? 'auth_user_id';
    }

    public function authenticate(ServerRequestInterface $request): static
    {
        // 1. 优先从 Request Attribute 读取预解析的 user ID（中间件预处理）
        $userId = $request->getAttribute($this->attributeKey);
        if ($userId !== null) {
            $user = $this->provider->retrieveById($userId);
            $this->setUser($user);
            return $this;
        }

        // 2. 从 Authorization Header 提取 JWT
        $token = $this->extractToken($request);
        if ($token === null) {
            $this->setUser(null);
            return $this;
        }

        // 3. 解码 JWT 获取 subject
        $subject = $this->decodeToken($token);
        if ($subject === null) {
            $this->setUser(null);
            return $this;
        }

        // 4. 通过 UserProvider 获取用户
        $user = $this->provider->retrieveById($subject);
        $this->setUser($user);

        return $this;
    }

    /**
     * 生成 JWT token
     */
    public function generateToken(AuthenticatableInterface $user, array $extra = []): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => 'maiscms',
            'sub' => (string) $user->getAuthIdentifier(),
            'iat' => $now,
            'exp' => $now + 86400,
        ], $extra);

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * 尝试登录（验证凭据 + 生成 token）
     * 返回 token 或 null
     */
    public function attemptAndToken(array $credentials): ?string
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if ($user === null) {
            return null;
        }

        if (!$this->provider->validateCredentials($user, $credentials)) {
            return null;
        }

        $this->setUser($user);
        return $this->generateToken($user);
    }

    /**
     * 从请求中提取 Bearer Token
     */
    protected function extractToken(ServerRequestInterface $request): ?string
    {
        $header = $request->getHeaderLine($this->headerKey);
        if ($header === '') {
            return null;
        }

        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        return null;
    }

    /**
     * 解码 JWT token，返回 subject
     * public 以便适配层（如 GraphQLController）调用
     */
    public function decodeToken(string $token): ?string
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return $decoded->sub ?? null;
        } catch (\Throwable) {
            return null;
        }
    }
}