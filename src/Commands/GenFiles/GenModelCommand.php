<?php

namespace Nichozuo\LaravelCodegen\Commands\GenFiles;


use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class GenModelCommand extends BaseCommand
{
    protected $name = 'gm';
    protected $description = '创建 model/migrate/seed/factory 等文件';
    protected $type = 'Model';

    protected function getOptions(): array
    {
        return [
            ['all', 'a', InputOption::VALUE_NONE, '创建 model/migrate/seed/factory 等文件'],
            ['model', null, InputOption::VALUE_NONE, '创建 model/migrate/seed/factory 等文件'],
            ['factory', null, InputOption::VALUE_NONE, '创建 factory 等文件'],
            ['force', null, InputOption::VALUE_NONE, '强制创建，覆盖'],
            ['migration', 'm', InputOption::VALUE_NONE, '创建 migrate 等文件'],
            ['seed', null, InputOption::VALUE_NONE, '创建 seed 等文件'],
        ];
    }

    public function handle(): bool
    {
        if ($this->option('all')) {
            $this->input->setOption('model', true);
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
        }

        if ($this->option('model'))
            if (parent::handle() === false && !$this->option('force')) {
                return false;
            }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }
        return true;
    }

    /**
     * Create a model factory for the model.
     *
     * @return void
     */
    protected function createFactory()
    {
        $factory = Str::studly($this->getNameInput());

        $this->call('make:factory', [
            'name' => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
        ]);
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    protected function createMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->getNameInput())));

        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }

    /**
     * Create a seeder file for the model.
     *
     * @return void
     */
    protected function createSeeder()
    {
        $seeder = Str::studly(class_basename($this->argument('name')));

        $this->call('make:seed', [
            'name' => "{$seeder}TableSeeder",
        ]);
    }

    protected function getStub(): string
    {
        return __DIR__ . '/stubs/model.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Models';
    }

    protected function replaceOther(&$stub, $name): GenModelCommand
    {
        $stub = str_replace(
            ['MyTable', 'MyFillable', 'MyRelations'],
            [$this->getTableName($name), $this->getFillable($name), ''],
            $stub
        );

        return $this;
    }

    /**
     * 获取表名
     *
     * @param $name
     * @return string
     */
    private function getTableName($name): string
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);
        return Str::plural(Str::snake(class_basename($class)));
    }

    /**
     * 获取字段
     *
     * @param $name
     * @return bool|string
     */
    private function getFillable($name)
    {
        $columnList = Schema::getColumnListing($this->getTableName($name));
        $guardedList = ['id', 'created_at', 'updated_at', 'deleted_at'];

        $fillableList = '';
        foreach ($columnList as $key => $value) {
            if (!in_array($value, $guardedList)) {
                $fillableList .= "'{$value}',";
            }
        }
        $fillableList = substr($fillableList, 0, strlen($fillableList) - 1);
        return $fillableList;
    }
}
