# 快速开发说明

## 1、引用包
### laravel-utils
> 一些常用的Trait和函数
```bash
composer require nichozuo/laravel-utils -v
```
### nichozuo/laravel-codegen
> 代码生成的包，放dev里，并发布文件
```bash
composer require nichozuo/laravel-codegen --dev -v
php artisan vendor:publish --provider="Nichozuo\LaravelCodegen\ServiceProvider"
```

## 2、修改框架基础目录
### app\Exceptions\Handler.php
> 用作全局统一处理Exception。当遇到exception，会进入此方法，用json的方式返回错误响应、并记录日志
```php
class Handler extends ExceptionHandler
{
    # 增加Trait
    use ExceptionsRenderTrait;

    # 修改register函数
    public function register()
    {
        $this->renderable(function (Exception $e, $request) {
            $data = $this->renderExceptionsJson($e, $request);
            $status = $data['status'];
            unset($data['status']);
            return response()->json($data, $status);
        });
    }
}
```

### app\Http\Controllers\Controller.php
> 为controller增加一些公用的方法
```php
class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    # 增加trait
    use ControllerTrait;
}
```

### app\Http\Middleware\Authenticate.php
> 注释掉这里，让未登陆的auth不会跳转
```php
#注释掉这个方法里的内容
protected function redirectTo($request)
{
//        if (! $request->expectsJson()) {
//            return route('login');
//        }
}
```

### app\Http\Kernel.php
> 增加全局的统一json返回中间件，在控制器里return一个对象或者数组，都会自动包裹成json响应
```php
protected $middlewareGroups = [
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        ...
    ],

    'api' => [
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
        # 在api的middleware里面，增加这个中间件
        ResponseJson::class
    ],
];
```
## 3、基本设置
### config/app.php
> 配置时区和语言
```php
return [
    'timezone' => 'Asia/Shanghai',
    'locale' => 'zh_CN',
    'fallback_locale' => 'zh_CN',
    'faker_locale' => 'zh_CN',
];
```

### routes/api.php
> 配置文档系统的路由
```php
use Illuminate\Support\Facades\Route;
use Nichozuo\LaravelCodegen\Controller\HomeController;
use Nichozuo\LaravelUtils\Helper\RouteHelper;

Route::prefix('/docs')->name('docs.')->group(function ($router) {
    RouteHelper::New($router, HomeController::class);
});
```
## 4、开始开发，常用命令
### 创建 migration 文件
> 名称为'大写驼峰复数'
```bash
php artisan gf Students -m
```
> 标准的migration文件，必须包含表注释
```php
public function up()
{
    Schema::create('students', function (Blueprint $table) {
        $table->id();
        $table->string('name')->comment('名称');
        $table->timestamps();
    });
    DbalHelper::comment('students', '学生信息');
}
```
### 同步到数据库
> 根据migration文件，生成到数据库。或者删除表，重新建表，并初始化数据
```bash
php artisan migrate
php artisan migrate:refresh --seed
```

### 生成 model 文件
> 创建 model 文件，会自动生成一些内容
```bash
php artisan gf Students -d
```

> 生成的 model 文件
- relations 放关联关系
- scopes 放本模型需要用的scope封装
```php
class Students extends Model
{
    use ModelTrait, HasFactory;

    # model defines
    protected $table = 'students';
    protected $fillable = [''];

    # relations

    # scopes
}
```
### 生成 controller 文件
> 第二个参数是，模块名称，首字母大写
```bash
php artisan gf Students Admin -c
```
- 生成的controller文件
```php
namespace App\Modules\Admin;

use App\Http\Controllers\Controller;
use App\Models\Students;
use Illuminate\Http\Request;

/**
 * 暂时没有描述
 * Class StudentsController
 * @package App\Modules\Admin
 */
class StudentsController extends Controller
{
    /**
     * @intro 列表
     * @params name,nullable|string,模糊搜索：名称
     * @param Request $request
     * @return mixed
     */
    public function list(Request $request)
    {
        $params = $request->validate([
            'name' => 'nullable|string',
        ]);
        return Students::whereLikeExist('name', $params)
            ->order()
            ->paginate($this->perPage());
    }

    /**
     * @intro 添加
     * @params name,required|string,名称

     * @param Request $request
     * @return array
     */
    public function store(Request $request): array
    {
        $params = $request->validate([
            'name' => 'required|string',

        ]);
        Students::unique($params, ['name'], '名称');
        Students::create($params);
        return [];
    }

    /**
     * @intro 修改
     * @id
     * @params name,required|string,名称

     * @param Request $request
     * @return array
     */
    public function update(Request $request): array
    {
        $params = $request->validate([
            'id' => 'required|integer',
            'name' => 'required|string',

        ]);
        Students::unique($params, ['name'], '名称');
        Students::idp($params)->update($params);
        return [];
    }

    /**
     * @intro 删除
     * @id
     * @param Request $request
     * @return array
     */
    public function delete(Request $request): array
    {
        $params = $request->validate([
            'id' => 'required|integer',
        ]);
        Students::idp($params)->delete();
        return [];
    }
}

```
