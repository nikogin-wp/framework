<?php

namespace Nikogin\Framework\Abstracts;

use Nikogin\Framework\Traits\DB;

abstract class Repository
{
    use DB;

    protected string $table;

    public function __construct(protected string $tableName)
    {
        $this->table = $this->table($this->tableName);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the table name with prefix.
     */
    protected function table(string $tableName): string
    {
        return $this->db()->prefix . $tableName;
    }

    /**
     * Insert a record into the table.
     */
    public function insert(array $data): bool|int
    {
        $result = $this->db()->insert($this->table, $data);

        if ($result === false) {
            return false;
        }

        return (int)  $this->db()->insert_id;
    }

    /**
     * Update a record in the table.
     */
    public function update(array $data, array $where): bool|int
    {
        return $this->db()->update($this->table, $data, $where);
    }

    /**
     * Delete a record from the table.
     */
    public function delete(string $condition, array $values = []): bool|int
    {
        $sql = "DELETE FROM {$this->table} WHERE {$condition}";
        if (!empty($values)) {
            $sql = $this->db()->prepare($sql, $values);
        }
        return $this->db()->query($sql);
    }

    /**
     * Get all records from the table, optionally ordered.
     */
    public function getAll(?string $orderBy = null): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        return $this->db()->get_results($sql);
    }

    /**
     * Get records with WHERE and custom SELECT columns.
     */
    public function getAllWhere(array $where = null, array $select = null): ?array
    {
        $columns = '*';
        if ($select) {
            $columns = implode(', ', array_map(fn($col) => "`{$col}`", $select));
        }

        $conditions = [];
        $values = [];
        if ($where) {
            foreach ($where as $key => $value) {
                if (stripos($key, 'LIKE') !== false) {
                    $col = str_replace(' LIKE', '', $key);
                    $conditions[] = "`{$col}` LIKE %s";
                    $values[]     = $value;
                } else {
                    $conditions[] = "`{$key}` = %s";
                    $values[]     = $value;
                }
            }
        }

        $sql = "SELECT {$columns} FROM {$this->table}";
        if ($conditions) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
            $sql = $this->db()->prepare($sql, $values);
        }

        return $this->db()->get_results($sql);
    }

    /**
     * Get a single record by conditions.
     */
    public function getOne(array $where): ?object
    {
        $conditions = [];
        $values     = [];
        foreach ($where as $col => $val) {
            $conditions[] = "`{$col}` = %s";
            $values[]     = $val;
        }
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $conditions) . " LIMIT 1";
        return $this->db()->get_row($this->db()->prepare($sql, $values));
    }

    public function paginateWhere(
        int $page = 1,
        int $perPage = 10,
        array $where = [],
        string $searchColumn = '',
        string $searchTerm = '',
        string $orderBy = 'id DESC'
    ): array {


        $offset = ($page - 1) * $perPage;
        $whereConditions = [];
        $params = [];

        foreach ($where as $key => $value) {
            $whereConditions[] = "`{$key}` = %s";
            $params[] = $value;
        }

        if (!empty($searchColumn) && !empty($searchTerm)) {
            $whereConditions[] = "`{$searchColumn}` LIKE %s";
            $params[] = '%' . $this->db()->esc_like($searchTerm) . '%';
        }

        $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

        $sql = "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->table} {$whereClause}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $perPage;
        $params[] = $offset;

        $prepared = $this->db()->prepare($sql, ...$params);
        $results  = $this->db()->get_results($prepared);
        $total    = (int) $this->db()->get_var("SELECT FOUND_ROWS()");

        $totalPages = (int) ceil($total / $perPage);

        return [
            'data'         => $results,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $totalPages,
            'total_pages'  => $totalPages,
        ];
    }
}
