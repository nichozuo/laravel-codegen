<?php


namespace Nichozuo\LaravelCodegen\Controller;


use Doctrine\DBAL\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Nichozuo\LaravelCodegen\Helper\DbalHelper;
use Nichozuo\LaravelCodegen\Helper\GenHelper;
use Nichozuo\LaravelUtils\Traits\ControllerTrait;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class HomeController
{
    use ControllerTrait;

    /**
     * @title 获取不同类型菜单
     * @params type,required|string,菜单类型，如：readme/modules/database
     * @response {"code":0,"message":"ok","data":[{"key":"\u5e8f\u8a00.md","label":"\u5e8f\u8a00","type":"readme"},{"key":"\u9519\u8bef\u4ee3\u7801.md","label":"\u9519\u8bef\u4ee3\u7801","type":"readme"}]}
     *
     * @param Request $request
     * @return array
     * @throws ReflectionException
     * @throws Exception
     */
    public function getMenu(Request $request): array
    {
        $params = $request->validate([
            'type' => 'required|string',
        ]);
        switch ($params['type']) {
            case 'readme':
                return $this->getReadmeMenu();
            case 'modules':
                return $this->getModulesMenu();
            case 'database':
                return $this->getDatabaseMenu();
            default:
                return [];
        }
    }

    /**
     * @params type,required|string,菜单类型，如：readme/modules/database
     * @params key,required|string,菜单值
     * @response {"code":0,"message":"ok","data":{"content":"# admins"}}
     *
     * @param Request $request
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function getContent(Request $request): array
    {
        $params = $request->validate([
            'type' => 'required|string',
            'key' => 'required|string',
        ]);

        switch ($params['type']) {
            case 'readme':
                return $this->getReadmeContent($params['type'], $params['key']);
            case 'modules':
                return $this->getModulesContent($params['type'], $params['key']);
            case 'database':
                return $this->getDatabaseContent($params['type'], $params['key']);
            default:
                return [];
        }
    }

    /**
     * @return array
     */
    private function getReadmeMenu(): array
    {
        $dirs = null;
        foreach (File::allFiles(resource_path('laravel-codegen/readme')) as $filePath) {
            $filePath = str_replace('.md', '', $filePath);
            $t1 = explode('/', $filePath);
            $t1 = end($t1);
            $dirs[] = [
                'key' => $t1 . '.md',
                'label' => $t1,
                //'type' => 'readme'
            ];
        }
        return $dirs;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    private function getModulesMenu(): array
    {
        $dirs = null;
        foreach (File::allFiles(app_path('Modules')) as $file) {
            $path = $file->getRelativePath();
            $pathName = $file->getRelativePathname();
            $controllerName = explode('/', $pathName);
            $controllerName = str_replace('.php', '', end($controllerName));

            if ($path == '')
                continue;
            if (!isset($dirs[$path]['label']))
                $dirs[$path] = [
                    'key' => $path,
                    'label' => $path,
                    //'type' => 'modules'
                ];
            $dirs[$path]['children'][] = [
                'key' => $path . '/' . $controllerName,
                'label' => $controllerName,
//                'type' => 'modules',
                'children' => $this->getActions($path, $controllerName)
            ];
        }
        return $dirs;
    }

    /**
     * @param string $path
     * @param string $pathName
     * @return array
     * @throws ReflectionException
     */
    private function getActions(string $path, string $pathName): array
    {
        $class = "App\\Modules\\{$path}\\{$pathName}";
        $ref = new ReflectionClass($class);
        $files = null;
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class != $class || $method->name == '__construct')
                continue;
            $files[] = [
                'key' => $class . '@' . $method->name,
                'label' => $method->name
            ];
        }
        return $files;
    }

    /**
     * @throws Exception
     */
    private function getDatabaseMenu(): array
    {
        $tables = DbalHelper::listTableNames();
        $return = null;
        foreach ($tables as $table) {
            $return[] = [
                'key' => $table,
                'label' => $table
            ];
        }
        return $return;
    }

    /**
     * @param $type
     * @param $key
     * @return array
     */
    private function getReadmeContent($type, $key): array
    {
        return [
            'content' => File::get(resource_path('laravel-codegen/' . $type . '/' . $key))
        ];
    }

    /**
     * @param $type
     * @param $key
     * @return array
     * @throws \Exception
     */
    private function getModulesContent($type, $key): array
    {
        foreach (Route::getRoutes() as $route) {
            if (!Str::startsWith($route->uri, 'api/'))
                continue;
            if ($route->getAction()['controller'] != '\\' . $key)
                continue;

            return [
                'content' => GenHelper::genApiMD($route)
            ];
        }
        return [];
    }

    /**
     * @param $type
     * @param $key
     * @return array
     * @throws Exception
     */
    private function getDatabaseContent($type, $key): array
    {
        DbalHelper::register();
        $tables = DbalHelper::listTables();
        foreach ($tables as $table) {
            if ($table->getName() != $key)
                continue;
            return [
                'content' => GenHelper::genDatabaseMD($table)
            ];
        }
        return [];
    }
}
