<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Hyperf\Database\ConnectionResolverInterface;
use Maiscraft\Crypto\CryptoManager;
use Maiscraft\Rbac\Contract\AuthenticatableInterface;
use Maiscraft\Rbac\Contract\UserProviderInterface;
use Maiscraft\Rbac\GenericUser;

/**
 * 数据库 UserProvider
 * 使用 Hyperf Query Builder 从数据库获取用户
 * 密码验证委托给 CryptoManager（支持 Bcrypt/Argon2/Sodium）
 */
class DatabaseUserProvider implements UserProviderInterface
{
    protected string $table;
    protected string $connection;

    public function __construct(
        protected ConnectionResolverInterface $resolver,
        protected ?CryptoManager $crypto = null,
        array $config = []
    ) {
        $this->table = $config['table'] ?? 'admin_users';
        $this->connection = $config['connection'] ?? 'default';
    }

    public function retrieveById(int|string $identifier): ?AuthenticatableInterface
    {
        $user = $this->query()
            ->where('id', (int) $identifier)
            ->where('status', 1)
            ->first();

        return $user ? $this->toGenericUser($user) : null;
    }

    public function retrieveByCredentials(array $credentials): ?AuthenticatableInterface
    {
        $query = $this->query()->where('status', 1);

        foreach ($credentials as $key => $value) {
            if (strtolower($key) === 'password') {
                continue;
            }
            $query->where($key, $value);
        }

        $user = $query->first();

        return $user ? $this->toGenericUser($user) : null;
    }

    public function validateCredentials(AuthenticatableInterface $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? '';
        if ($plain === '') {
            return false;
        }

        // 优先使用 CryptoManager（支持多哈希驱动）
        if ($this->crypto !== null) {
            return $this->crypto->verify($plain, $user->getAuthPassword());
        }

        // 回退到原生 password_verify（兼容无 CryptoManager 场景）
        return password_verify($plain, $user->getAuthPassword());
    }

    protected function query()
    {
        return $this->resolver->connection($this->connection)->table($this->table);
    }

    protected function toGenericUser(object $row): GenericUser
    {
        return new GenericUser((array) $row);
    }
}
