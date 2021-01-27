<?php


namespace Nichozuo\LaravelCodegen\Controller;


use DocBlockReader\Reader;
use Doctrine\DBAL\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
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

    private $basePath;

    /**
     * HomeController constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->basePath = resource_path('laravel-codegen/readme/');
        DbalHelper::register();
    }

    /**
     * @title 获取不同类型菜单
     * @params type,required|string,菜单类型，如：readme/modules/database
     * @response {"code":0,"message":"ok","data":[{"key":"\u5e8f\u8a00.md","label":"\u5e8f\u8a00","type":"readme"},{"key":"\u9519\u8bef\u4ee3\u7801.md","label":"\u9519\u8bef\u4ee3\u7801","type":"readme"}]}
     *
     * @param Request $request
     * @return array
     * @throws Exception|ReflectionException
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
                return $this->getModulesContent($params['key']);
            case 'database':
                return $this->getDatabaseContent($params['key']);
            default:
                return [];
        }
    }

    /**
     * @return array
     */
    private function getReadmeMenu(): array
    {
        return $this->getReadmeChildrenDirs($this->basePath);
    }

    /**
     * @param $path
     * @return array
     */
    private function getReadmeChildrenDirs($path): array
    {
        $arr = [];
        foreach (File::directories($path) as $dir) {
            $t1 = explode('/', $dir);
            $name = end($t1);
            $arr[] = [
                'key' => str_replace($this->basePath, '', $dir),
                'title' => $name,
                'children' => $this->getReadmeChildrenDirs($dir)
            ];
        }
        foreach (File::files($path) as $file) {
            $name = $file->getPathname();
            $key = str_replace($this->basePath, '', $name);
            $title = str_replace('.md', '', $key);
            $title = explode('/', $title);
            $title = end($title);
            $arr[] = [
                'key' => $key,
                'title' => $title,
                'isLeaf' => true
            ];
        }
        return $arr;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    private function getModulesMenu(): array
    {
        $dirs = null;
        $baseDir = app_path('Modules/');
        foreach (File::directories($baseDir) as $dir) {
            $dir = str_replace($baseDir, '', $dir);
            $dirs[] = [
                'key' => $dir,
                'title' => $dir,
                'subTitle' => $this->getModulesSubTitle($dir),
                'children' => $this->getModulesControllers($dir)
            ];
        }
        return $dirs;
    }

    /**
     * @param string $dir
     * @return string
     */
    private function getModulesSubTitle(string $dir): string
    {
        $base = [
            'Admin' => '管理员',
            'Agent' => '代理商',
            'Company' => '公司',
            'Customer' => '客户',
            'Zhike' => '知客'
        ];
        return $base[$dir] . '模块';
    }

    /**
     * @param string $dir
     * @return array
     * @throws ReflectionException
     */
    private function getModulesControllers(string $dir): array
    {
        $dirs = [];
        foreach (File::allFiles(app_path('Modules/' . $dir)) as $file) {
            $pathName = $file->getRelativePathname();
            $controllerName = explode('/', $pathName);
            $controllerName = str_replace('.php', '', end($controllerName));
            $ref = new ReflectionClass('App\\Modules\\' . $dir . '\\' . $controllerName);
            $dirs[] = [
                'key' => $dir . '/' . $pathName,
                'title' => $controllerName,
                'subTitle' => $this->getSubTitleOfController($ref),
                'children' => $this->getModulesActions($ref)
            ];
        }
        return $dirs;
    }

    /**
     * @param $ref
     * @return string|string[]
     */
    private function getSubTitleOfController($ref)
    {
        $docs = $ref->getDocComment();
        if (!$docs)
            return '暂时没有名称';

        $t1 = explode(PHP_EOL, $docs)[1];
        return str_replace(' * ', '', $t1);
    }

    /**
     * @param $ref
     * @return array
     * @throws \Exception
     */
    private function getModulesActions(ReflectionClass $ref): array
    {
        $files = null;
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class != $ref->getName() || $method->name == '__construct')
                continue;
            $action = new Reader($ref->getName(), $method->name);
            $files[] = [
                'key' => $ref->getName() . '@' . $method->name,
                'title' => $method->name,
                'subTitle' => $action->getParameter('intro'),
                'isLeaf' => true,
            ];
        }
        return $files;
    }

    /**
     * @throws Exception
     */
    private function getDatabaseMenu(): array
    {
        $tables = DbalHelper::listTables();
        $return = null;
        foreach ($tables as $table) {
            $return[] = [
                'key' => $table->getName(),
                'title' => $table->getName(),
                'subTitle' => $table->getComment(),
                'isLeaf' => true
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
     * @param $key
     * @return array
     * @throws \Exception
     */
    private function getModulesContent($key): array
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
     * @param $key
     * @return array
     * @throws Exception
     */
    private function getDatabaseContent($key): array
    {
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
