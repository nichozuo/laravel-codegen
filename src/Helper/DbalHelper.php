<?php


namespace Nichozuo\LaravelCodegen\Helper;


use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Illuminate\Support\Facades\DB;

class DbalHelper
{
    /**
     * @return AbstractSchemaManager
     */
    private static function SM(): AbstractSchemaManager
    {
        return DB::connection()->getDoctrineSchemaManager();
    }

    /**
     * @throws Exception
     */
    public static function register()
    {
        self::SM()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * @param $table
     * @param $comment
     */
    public static function comment($table, $comment)
    {
        DB::statement("ALTER TABLE `{$table}` comment '{$comment}'");
    }

    /**
     * @param string $table
     * @return bool
     * @throws Exception
     */
    public static function hasSoftDelete(string $table): bool
    {
        $columns = array_keys(self::listTableColumns($table));
        return in_array('deleted_at', $columns);
    }

    /**
     * @return Table[]
     * @throws Exception
     */
    public static function listTables(): array
    {
        return self::SM()->listTables();
    }

    /**
     * @return array
     * @throws Exception
     */
    public static function listTableNames(): array
    {
        return self::SM()->listTableNames();
    }

    /**
     * @param $table
     * @param $skipCommonFields
     * @return array
     * @throws Exception
     */
    public static function listTableColumns($table, $skipCommonFields = false): array
    {
        $columns = self::SM()->listTableColumns($table);
        if (!$skipCommonFields)
            return $columns;

        $columnKeys = array_keys($columns);
        $skipColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
        foreach ($columnKeys as $columnKey)
            if (in_array($columnKey, $skipColumns))
                unset($columns[$columnKey]);

        return $columns;
    }

    /**
     * @param $column
     * @return string
     */
    public static function getRequired($column): string
    {
        return ($column->getNotNull()) ? 'required' : 'nullable';
    }

    /**
     * @param $column
     * @return string
     */
    public static function getType($column): string
    {
        $type = $column->getType()->getName();
        switch ($type) {
            case 'float':
            case 'double':
            case 'decimal':
                return 'numeric';
            case 'bigint':
            case 'int':
            case 'integer':
            case 'tinyint':
            case 'smallint':
                return 'integer';
            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'date';
            case 'boolean':
                return 'boolean';
            case 'string':
            case 'text':
            case 'varchar':
            case 'enum':
                return 'string';
            case 'array':
                return 'array';
            case 'json':
                return 'json';
            case 'geometry':
                return 'geometry';
            default:
                return 'null';
        }
    }
}