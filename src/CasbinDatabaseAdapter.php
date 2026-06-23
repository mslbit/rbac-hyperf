<?php

declare(strict_types=1);

namespace Maiscraft\RbacHyperf;

use Casbin\Persist\Adapter;
use Casbin\Persist\AdapterHelper;
use Casbin\Persist\BatchAdapter;
use Hyperf\Database\ConnectionResolverInterface;

/**
 * Casbin 数据库适配器
 * 从 Hyperf 数据库连接读写策略
 */
class CasbinDatabaseAdapter implements Adapter, BatchAdapter
{
    use AdapterHelper;

    private ConnectionResolverInterface $resolver;
    private string $table;

    public function __construct(ConnectionResolverInterface $resolver, string $table = 'casbin_rule')
    {
        $this->resolver = $resolver;
        $this->table = $table;
    }

    public function loadPolicy($model): void
    {
        $rows = $this->connection()->table($this->table)->get();

        foreach ($rows as $row) {
            $rule = [$row->ptype];
            for ($i = 0; $i < 6; $i++) {
                $col = 'v' . $i;
                if (property_exists($row, $col) && $row->$col !== null) {
                    $rule[] = $row->$col;
                }
            }
            $this->loadPolicyArray($rule, $model);
        }
    }

    public function savePolicy($model): void
    {
        $this->connection()->table($this->table)->delete();

        foreach ($model->getPolicy() as $ptype => $ast) {
            foreach ($ast as $rule) {
                $this->insertRule($ptype, $rule);
            }
        }
    }

    public function addPolicy(string $sec, string $ptype, array $rule): void
    {
        $this->insertRule($ptype, $rule);
    }

    public function removePolicy(string $sec, string $ptype, array $rule): void
    {
        $query = $this->connection()->table($this->table)->where('ptype', $ptype);

        foreach ($rule as $i => $value) {
            $query->where('v' . $i, $value);
        }

        $query->delete();
    }

    public function removeFilteredPolicy(string $sec, string $ptype, int $fieldIndex, ?string ...$fieldValues): void
    {
        $query = $this->connection()->table($this->table)->where('ptype', $ptype);

        foreach ($fieldValues as $i => $value) {
            if ($value !== null) {
                $query->where('v' . ($fieldIndex + $i), $value);
            }
        }

        $query->delete();
    }

    public function addPolicies(string $sec, string $ptype, array $rules): void
    {
        foreach ($rules as $rule) {
            $this->insertRule($ptype, $rule);
        }
    }

    public function removePolicies(string $sec, string $ptype, array $rules): void
    {
        foreach ($rules as $rule) {
            $this->removePolicy($sec, $ptype, $rule);
        }
    }

    private function connection()
    {
        return $this->resolver->connection();
    }

    private function insertRule(string $ptype, array $rule): void
    {
        $data = ['ptype' => $ptype];
        foreach ($rule as $i => $value) {
            $data['v' . $i] = $value;
        }
        $this->connection()->table($this->table)->insert($data);
    }
}