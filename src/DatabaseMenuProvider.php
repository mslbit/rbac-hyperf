<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Hyperf\Database\ConnectionResolverInterface;
use Maiscraft\Rbac\Contract\MenuProviderInterface;

/**
 * 从数据库读取菜单
 */
class DatabaseMenuProvider implements MenuProviderInterface
{
    private ConnectionResolverInterface $resolver;
    private string $menuTable;
    private string $roleMenuTable;

    public function __construct(
        ConnectionResolverInterface $resolver,
        string $menuTable = 'menus',
        string $roleMenuTable = 'role_menu'
    ) {
        $this->resolver = $resolver;
        $this->menuTable = $menuTable;
        $this->roleMenuTable = $roleMenuTable;
    }

    public function getAllMenus(): array
    {
        return $this->connection()
            ->table($this->menuTable)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->map(fn($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'label' => $row->label,
                'icon' => $row->icon,
                'path' => $row->path,
                'parent_id' => $row->parent_id,
                'sort' => $row->sort,
            ])
            ->values()
            ->all();
    }

    public function getMenuIdsByRoles(array $roles): array
    {
        if (empty($roles)) {
            return [];
        }

        $roleIds = $this->connection()
            ->table('roles')
            ->whereIn('name', $roles)
            ->where('status', 1)
            ->pluck('id')
            ->all();

        if (empty($roleIds)) {
            return [];
        }

        return $this->connection()
            ->table($this->roleMenuTable)
            ->whereIn('role_id', $roleIds)
            ->pluck('menu_id')
            ->unique()
            ->values()
            ->all();
    }

    private function connection()
    {
        return $this->resolver->connection();
    }
}