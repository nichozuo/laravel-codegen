<?php


namespace Nichozuo\LaravelCodegen\Commands\GenFiles;


use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableHelper
{
    /**
     * @param string $table
     * @return array
     */
    public static function getColumnsArray(string $table): array
    {
        $t1 = Schema::getColumnListing($table);
        return array_diff($t1, ['id', 'created_at', 'updated_at', 'deleted_at']);
    }

    /**
     * @param $table
     * @return array
     * @throws Exception
     */
    public static function getColumnsInfo($table): array
    {
        $columnsList = DB::select("show columns from " . $table);
        $guardedList = ['id', 'created_at', 'updated_at', 'deleted_at'];
        $columnsInfo = [];

        foreach ($columnsList as $item) {
            if (!in_array($item->Field, $guardedList)) {
                $columnsInfo[] = [
                    $item->Field,
                    self::getRequired($item->Null, $item->Default),
                    self::getValidateType($item->Type),
                ];
            }
        }
        return $columnsInfo;
    }

    /**
     * @param $Null
     * @param $Default
     * @return string
     */
    private static function getRequired($Null, $Default): string
    {
        return ($Null === "NO" && $Default === null) ? 'required' : 'nullable';
    }

    /**
     * @param $Type
     * @return string
     * @throws Exception
     */
    private static function getValidateType($Type): string
    {
        $index = strpos($Type, '(');
        if ($index)
            $Type = substr($Type, 0, $index);

        switch ($Type) {
            case 'float':
            case 'double':
            case 'decimal':
                return 'numeric';
            case 'bigint':
            case 'int':
            case 'integer':
            case 'smallint':
                return 'integer';
            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'date';
            case 'boolean':
            case 'tinyint':
                return 'boolean';
            case 'text':
            case 'varchar':
            case 'enum':
                return 'string';
            case 'json':
                return 'array';
            default:
                throw new Exception('unknown type: ' . $Type);
        }
    }
}
