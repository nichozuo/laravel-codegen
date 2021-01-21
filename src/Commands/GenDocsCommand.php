<?php


namespace Nichozuo\LaravelCodegen\Commands;


use Exception;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Nichozuo\LaravelCodegen\Helper\DbalHelper;
use Nichozuo\LaravelCodegen\Helper\GenHelper;
use Nichozuo\LaravelCodegen\Helper\StubHelper;

class GenDocsCommand extends BaseCommand
{
    protected $name = 'gd';
    protected $description = 'Generate docs md files';

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->warn('删除旧文档');
        $this->removeDir();
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
            list($moduleName, $controllerName, $actionName) = GenHelper::getInfoFromRoute($route);
            $stubContent = GenHelper::genApiMD($route);
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
            $stubContent = GenHelper::genDatabaseMD($table);
            $filePath = $this->laravel['path'] . "/../storage/app/docs/3-database/{$table->getName()}.md";
            $this->line($filePath);
            StubHelper::save($filePath, $stubContent, true);
        }
    }

    /**
     *
     */
    private function removeDir()
    {
        StubHelper::removeDir($this->laravel['path'] . "/../storage/app/docs/2-modules");
        StubHelper::removeDir($this->laravel['path'] . "/../storage/app/docs/3-database");
    }
}