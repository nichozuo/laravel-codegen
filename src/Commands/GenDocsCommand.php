<?php


namespace Nichozuo\LaravelCodegen\Commands;


use DocBlockReader\Reader;
use Exception;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Nichozuo\LaravelCodegen\Helper\DbalHelper;
use Nichozuo\LaravelCodegen\Helper\StubHelper;
use ReflectionMethod;

class GenDocsCommand extends BaseCommand
{
    protected $name = 'gd';
    protected $description = 'Generate docs md files';

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->warn('生成Api文档');
        $this->genApiFiles();
        $this->warn('生成DB文档');
        $this->genDbFiles();
    }

    /**
     * @throws Exception
     */
    private function genApiFiles()
    {
        foreach (Route::getRoutes() as $route) {
            if (!Str::startsWith($route->uri, 'api/'))
                continue;

            list($controllerClass, $moduleName, $controllerName, $actionName) = $this->getInfoFromRoute($route);

            $method = new ReflectionMethod($controllerClass, $actionName);
            if (!($method->isPublic() && !$method->isConstructor()))
                continue;

            $reader = new Reader($controllerClass, $actionName);
            $data = $reader->getParameters();
            $data['title'] = isset($data['title']) ? $data['title'] : $actionName;
            $data['intro'] = isset($data['intro']) ? ' > ' . $data['intro'] : '';
            $data['url'] = $route->uri;
            $data['method'] = $route->methods[0];
            $data['params'] = $this->getParams($data, 'params');
            $data['response'] = isset($data['response']) ? json_encode($data['response'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';
            $data['responseParams'] = $this->getParams($data, 'responseParams');

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
            $filePath = $this->laravel['path'] . "/../storage/app/docs/2-modules/{$moduleName}/{$controllerName}/{$actionName}.md";
            $this->line($filePath);
            StubHelper::save($filePath, $stubContent, true);
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function genDbFiles()
    {
        DbalHelper::register();
        $tables = DbalHelper::listTables();
        foreach ($tables as $table) {
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
            $filePath = $this->laravel['path'] . "/../storage/app/docs/3-database/{$data['tableName']}.md";
            $this->line($filePath);
            StubHelper::save($filePath, $stubContent, true);
        }
    }

    /**
     * @param $route
     * @return array
     */
    private function getInfoFromRoute($route): array
    {
        $t1 = explode('@', $route->action['controller']);
        $controllerClass = $t1[0];
        $actionName = $t1[1];

        $t2 = explode('\\', $t1[0]);
        $moduleName = $t2[3];
        $controllerName = $t2[4];
        return array($controllerClass, $moduleName, $controllerName, $actionName);
    }

    /**
     * @param $data
     * @param $key
     * @return string
     */
    private function getParams($data, $key): string
    {
        if (!isset($data[$key]))
            return '';
        $t1 = '';

        if (!is_array($data[$key]))
            return '|' . str_replace(',', '|', $data[$key]) . '|' . PHP_EOL;

        foreach ($data[$key] as $item) {
            $t1 .= '|' . str_replace(',', '|', $item) . '|' . PHP_EOL;
        }

        return $t1;
    }
}