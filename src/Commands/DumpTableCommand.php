<?php


namespace Nichozuo\LaravelCodegen\Commands;


use Exception;
use Illuminate\Console\Command;
use Nichozuo\LaravelCodegen\Helper\DbalHelper;
use Nichozuo\LaravelCodegen\Helper\GenHelper;

class DumpTableCommand extends Command
{
    protected $signature = 'dt {table}';
    protected $description = 'dump the fields of the table';

    /**
     * @throws Exception
     */
    public function handle()
    {
        $table = $this->argument('table');
        DbalHelper::register();

        if ($table == '') {
            $this->line('Please Input Table Name');
        } else {
            $this->warn('生成 Table 模板');
            $this->line(GenHelper::genTableString($table));
            $this->line(GenHelper::genFillableString($table));

            $this->warn('生成 Validate 模板');
            $this->line(GenHelper::genRequestValidateString($table));

            $this->warn('生成 Insert 模板');
            $this->line(GenHelper::genInsertString($table));

            $this->warn('生成 Annotation 模板');
            $this->line(GenHelper::genAnnotationString($table));
        }
    }
}