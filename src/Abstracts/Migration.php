<?php

namespace Nikogin\Framework\Abstracts;

use Nikogin\Framework\Traits\DB;

abstract class Migration
{
    use DB;
    protected string $charsetCollate;
    protected string $prefix = 'tc_';

    public function __construct()
    {
        $this->charsetCollate = $this->db()->get_charset_collate();
    }

    abstract public function getTableName(): string;
    abstract public function getSchema(): string;

    public function getFullTableName(string $name = ""): string
    {
        if ($name)
            return $this->db()->prefix . $this->prefix . $name;
        return $this->db()->prefix . $this->prefix . $this->getTableName();
    }

    public function up(): void
    {
        $sql = $this->getSchema();
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        dbDelta($sql);
    }

    public function down(): void
    {
        $table = $this->getFullTableName();
        $this->db()->query("DROP TABLE IF EXISTS {$table}");
    }
}