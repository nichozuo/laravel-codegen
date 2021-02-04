<?php


namespace Nichozuo\LaravelCodegen\Helper;


use DocBlockReader\Reader;
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

    /**
     * @param $route
     * @return mixed|string|string[]
     * @throws \Exception
     */
    public static function genApiMD($route)
    {
        list($controllerClass, $moduleName, $controllerName, $actionName) = GenHelper::getInfoFromRoute($route);

        $reader = new Reader($controllerClass, $actionName);
        $data = $reader->getParameters();
        $data['title'] = isset($data['title']) ? $data['title'] : $actionName;
        $data['intro'] = isset($data['intro']) ? ' > ' . $data['intro'] : '';
        $data['url'] = $route->uri;
        $data['method'] = $route->methods[0];
        $data['params'] = self::getParams($data, 'params');
        $data['response'] = isset($data['response']) ?
            json_encode($data['response'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) :
            json_encode([
                'code' => 0,
                'message' => 'ok'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $data['responseParams'] = self::getParams($data, 'responseParams', false);

        $stubContent = StubHelper::getStub('api.md');
        $stubContent = StubHelper::replace([
            '{{title}}' => $data['title'],
            '{{intro}}' => $data['intro'],
            '{{url}}' => $data['url'],
            '{{method}}' => $data['method'],
            '{{params}}' => $data['params'],
            '{{response}}' => $data['response'],
            '{{responseParams}}' => $data['responseParams'],
        ], $stubContent);
        return $stubContent;
    }


    /**
     * @param $data
     * @param $key
     * @param bool $params
     * @return string
     */
    private static function getParams($data, $key, $params = true): string
    {
        $t1 = '';

        if (isset($data['id']) &&  $params) {
            $t1 .= '|id|是|integer|id|' . PHP_EOL;
        }

        if (!isset($data[$key]))
            return $t1;

        if (!is_array($data[$key])) {
            $item = $data[$key];
            $item = str_replace('nullable|', '- |', $item);
            $item = str_replace('required|', '是 |', $item);
            return $t1 .= '|' . str_replace(',', '|', $item) . '|' . PHP_EOL;
        }

        foreach ($data[$key] as $item) {
            $item = str_replace('nullable|', '- |', $item);
            $item = str_replace('required|', '是 |', $item);
            $t1 .= '|' . str_replace(',', '|', $item) . '|' . PHP_EOL;
        }

        return $t1;
    }

    /**
     * @param $route
     * @return array
     */
    public static function getInfoFromRoute($route): array
    {
        $t1 = explode('@', $route->action['controller']);
        $controllerClass = $t1[0];
        $actionName = $t1[1];

        $t2 = explode('\\', $t1[0]);
        $moduleName = $t2[3];
        $controllerName = $t2[4];
        return array($controllerClass, $moduleName, $controllerName, $actionName);
    }

    public static function genDatabaseMD($table)
    {
        $data['tableName'] = $table->getName();
        $data['tableComment'] = $table->getComment() ? '> ' . $table->getComment() : '';
        $data['columns'] = '';

        $columns = $table->getColumns();
        foreach ($columns as $column) {
            $data['columns'] .= '|' . implode('|', [
                    $column->getName(),
                    $column->getType()->getName(),
                    $column->getPrecision(),
                    $column->getScale(),
                    $column->getNotNull() ? '是' : ' ',
                    $column->getDefault() ? $column->getDefault() : ' ',
                    $column->getComment() ? $column->getComment() : ' ',
                ]) . '|' . PHP_EOL;
        }
        $stubContent = StubHelper::getStub('db.md');
        $stubContent = StubHelper::replace([
            '{{tableName}}' => $data['tableName'],
            '{{tableComment}}' => $data['tableComment'],
            '{{columns}}' => $data['columns'],
        ], $stubContent);
        return $stubContent;
    }
}