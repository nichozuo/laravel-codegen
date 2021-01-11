<?php


namespace Nichozuo\LaravelCodegen\Helper;


use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaHelper
{
    private $database = '';
    private $builder;
    private $table = '';

    public function __construct($table)
    {
        $this->database = config('database.default');
        $this->builder = Schema::connection($this->database);
        $this->table = $table;
    }

    /**
     * @return array
     */
    public function getColumnsArray(): array
    {
        $columns = $this->builder->getColumnListing($this->table);
        return array_diff($columns, ['id', 'created_at', 'updated_at', 'deleted_at']);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getColumnsInfo(): array
    {
        $columnsList = DB::select("show columns from " . $this->table);
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
            case 'tinyint':
            case 'smallint':
                return 'integer';
            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'date';
            case 'boolean':
                return 'boolean';
            case 'text':
            case 'varchar':
            case 'enum':
                return 'string';
            case 'json':
                return 'array';
            case 'geometry':
                return 'geometry';
            default:
                throw new Exception('unknown type: ' . $Type);
        }
    }
}