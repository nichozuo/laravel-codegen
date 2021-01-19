<?php


namespace Nichozuo\LaravelCodegen\Helper;


use Doctrine\DBAL\Exception;

class GenHelper
{
    /**
     * @param string $table
     * @return string
     */
    public static function genTableString(string $table): string
    {
        return "protected \$table = '{$table}';";
    }

    /**
     * @param string $table
     * @return string
     * @throws Exception
     */
    public static function genFillableString(string $table): string
    {
        $t1 = '';
        $columns = array_keys(DbalHelper::listTableColumns($table, true));
        $fillable = implode("', '", $columns);
        $t1 .= "protected \$fillable = ['{$fillable}'];" . PHP_EOL;
        return $t1;
    }

    /**
     * @param string $table
     * @return string
     * @throws Exception
     */
    public static function genRequestValidateString(string $table): string
    {
        $columns = DbalHelper::listTableColumns($table, true);
        $t1 = '';
        foreach ($columns as $item) {
            $name = $item->getName();
            $required = DbalHelper::getRequired($item);
            $type = DbalHelper::getType($item);
            $t1 .= "'{$name}' => '{$required}|{$type}'," . PHP_EOL;
        }
        return $t1;
    }

    /**
     * @param string $table
     * @return string
     * @throws Exception
     */
    public static function genInsertString(string $table): string
    {
        $columns = DbalHelper::listTableColumns($table, true);
        $t1 = '';
        foreach ($columns as $item) {
            $name = $item->getName();
            $t1 .= "'{$name}' => ''," . PHP_EOL;
        }
        return $t1;
    }

    /**
     * @param string $table
     * @return string
     * @throws Exception
     */
    public static function genAnnotationString(string $table): string
    {
        $columns = DbalHelper::listTableColumns($table, true);
        $t1 = '';
        foreach ($columns as $item) {
            $name = $item->getName();
            $required = DbalHelper::getRequired($item);
            $type = DbalHelper::getType($item);
            $comment = $item->getComment();
            $t1 .= "* @params {$name},{$required}|{$type},{$comment}" . PHP_EOL;
        }
        return $t1;
    }
}