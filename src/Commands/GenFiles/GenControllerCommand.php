<?php

namespace Nichozuo\LaravelCodegen\Commands\GenFiles;


use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;

class GenControllerCommand extends BaseCommand
{
    protected $name = 'gc';
    protected $description = '创建 controller 模板文件';
    protected $type = 'Controller';

    protected function getStub()
    {
        return __DIR__ . '/stubs/controller.stub';
    }

    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\Modules\\' . $this->argument('module');
    }

    protected function replaceOther(&$stub, $name)
    {
        $stub = str_replace(
            ['ModelName', 'MyFields'],
            [$this->getModelName($name, 'Controller'), $this->getFields($name)],
            $stub
        );

        return $this;
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . 'Controller.php';
    }

    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, '模型名称'],
            ['module', InputArgument::REQUIRED, '模块名称'],
        ];
    }

    private function getFields($name)
    {
        $modelName = $this->getModelName($name, 'Request');
        $tableName = Str::plural(Str::snake(class_basename($modelName)));
        $columnsInfo = TableHelper::getColumnsInfo($tableName);
        $strRequest = '';
        foreach ($columnsInfo as $item) {
            $strRequest .= "\t\t\t'{$item[0]}' => '$item[1]|{$item[2]}',\r\n";
        }
        return Str::replaceLast('\r\n', '', $strRequest);
    }
}
