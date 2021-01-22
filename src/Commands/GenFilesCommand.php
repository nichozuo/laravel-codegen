<?php


namespace Nichozuo\LaravelCodegen\Commands;


use Exception;
use Illuminate\Database\Console\Migrations\BaseCommand;
use Illuminate\Support\Str;
use Nichozuo\LaravelCodegen\Helper\DbalHelper;
use Nichozuo\LaravelCodegen\Helper\GenHelper;
use Nichozuo\LaravelCodegen\Helper\StubHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenFilesCommand extends BaseCommand
{
    protected $name = 'gf';
    protected $description = 'Generate files of the table';

    protected function getArguments(): array
    {
        return [
            ['table', InputArgument::REQUIRED, '表名'],
            ['module', InputArgument::OPTIONAL, '模块名'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['migration', 'm', InputOption::VALUE_NONE, '创建 migration 文件'],
            ['model', 'd', InputOption::VALUE_NONE, '创建 model 文件'],
            ['factory', 'f', InputOption::VALUE_NONE, '创建 factory 文件'],
            ['seed', 's', InputOption::VALUE_NONE, '创建 seed 文件'],
            ['controller', 'c', InputOption::VALUE_NONE, '创建 controller 文件'],
            ['force', 'F', InputOption::VALUE_NONE, '强制创建并覆盖'],
        ];
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function handle()
    {
        $options = $this->options();
        $args = $this->arguments();

        $table = Str::snake($args['table']);
        $module = $args['module'];

        $this->makeMigration($table, $args, $options);
        $this->makeModel($table, $args, $options);
        $this->makeFactory($table, $args, $options);
        $this->makeSeed($table, $args, $options);
        $this->makeController($table, $args, $options);
    }

    /**
     * @param $table
     * @param $args
     * @param $options
     */
    private function makeMigration($table, $args, $options)
    {
        if ($options['migration']) {
            try {
                $this->call('make:migration', [
                    'name' => "create_{$table}_table",
                    '--create' => $table,
                    '--table' => $table,
                ]);
            } catch (Exception $ex) {
                $this->error($ex->getMessage());
            }
        }
    }

    /**
     * @param string $table
     * @param array $args
     * @param array $options
     * @throws \Doctrine\DBAL\Exception
     */
    private function makeModel(string $table, array $args, array $options)
    {
        if ($options['model']) {
            $hasSoftDelete = DbalHelper::hasSoftDelete($table);
            $stubName = $hasSoftDelete ? 'modelWithSoftDelete.stub' : 'model.stub';
            $stubContent = StubHelper::getStub($stubName);
            $stubContent = StubHelper::replace([
                '{{ModelName}}' => $args['table'],
                '{{TableString}}' => GenHelper::genTableString($table),
                '{{FillableString}}' => GenHelper::genFillableString($table),
            ], $stubContent);
            $filePath = $this->laravel['path'] . '/Models/' . $args['table'] . '.php';
            StubHelper::save($filePath, $stubContent);
        }
    }

    /**
     * @param string $table
     * @param array $args
     * @param array $options
     */
    private function makeFactory(string $table, array $args, array $options)
    {
        if ($options['factory']) {
            try {
                $this->call('make:factory', [
                    'name' => "{$args['table']}Factory"
                ]);
            } catch (Exception $ex) {
                $this->error($ex->getMessage());
            }
        }
    }

    /**
     * @param string $table
     * @param array $args
     * @param array $options
     */
    private function makeSeed(string $table, array $args, array $options)
    {
        if ($options['seed']) {
            try {
                $this->call('make:seed', [
                    'name' => "{$args['table']}TableSeeder"
                ]);
            } catch (Exception $ex) {
                $this->error($ex->getMessage());
            }
        }
    }

    /**
     * @param string $table
     * @param array $args
     * @param array $options
     * @throws \Doctrine\DBAL\Exception
     */
    private function makeController(string $table, array $args, array $options)
    {
        if ($options['controller']) {
            $hasSoftDelete = DbalHelper::hasSoftDelete($table);
            $stubName = $hasSoftDelete ? 'controllerWithSoftDelete.stub' : 'controller.stub';
            $stubContent = StubHelper::getStub($stubName);
            $stubContent = StubHelper::replace([
                '{{ModelName}}' => $args['table'],
                '{{ModuleName}}' => $args['module'],
                '{{AnnotationString}}' => GenHelper::genAnnotationString($table),
                '{{InsertString}}' => GenHelper::genRequestValidateString($table),
            ], $stubContent);
            $filePath = $this->laravel['path'] . "/Modules/{$args['module']}/{$args['table']}Controller.php";
            StubHelper::save($filePath, $stubContent);
        }
    }
}