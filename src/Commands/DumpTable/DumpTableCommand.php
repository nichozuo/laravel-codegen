<?php


namespace Nichozuo\LaravelCodegen\Commands\DumpTable;


use Exception;
use Illuminate\Console\Command;
use Nichozuo\LaravelCodegen\Helper\SchemaHelper;

class DumpTableCommand extends Command
{
    private $helper;
    private $table;

    protected $signature = 'dt {table}';
    protected $description = 'dump the fields of the table';

    /**
     * @throws Exception
     */
    public function handle()
    {
        $this->table = $this->argument('table');
        if ($this->table == '') {
            $this->line('Please Input Table Name');
        } else {
            $this->helper = new SchemaHelper($this->table);
            // 生成 $table
            $this->line($this->getTable());
            // 生成 $fillable
            $this->line($this->getFillable());
            // 生成 $request
            $this->line($this->getRequest());
        }
    }

    /**
     * @return string
     */
    private function getTable(): string
    {
        $this->line('');
        return "protected \$table = '{$this->table}';";
    }

    /**
     * @return string
     */
    private function getFillable(): string
    {
        $columnList = $this->helper->getColumnsArray();
        $fillable = implode("', '", $columnList);
        return "protected \$fillable = ['{$fillable}'];";
    }


    /**
     * @return string
     * @throws Exception
     */
    private function getRequest(): string
    {
        $this->line('');
        $columnsInfo = $this->helper->getColumnsInfo($this->table);

        $strRequest = '';
        foreach ($columnsInfo as $item) {
            $strRequest .= "'{$item[0]}' => '$item[1]|{$item[2]}',\r\n";
        }

        $strRequest .= "\r\n";

        foreach ($columnsInfo as $item) {
            $strRequest .= "'{$item[0]}' => '',\r\n";
        }

        return $strRequest;
    }
}