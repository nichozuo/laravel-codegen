<?php


namespace Nichozuo\LaravelCodegen;


use Nichozuo\LaravelCodegen\Commands\DumpTableCommand;
use Nichozuo\LaravelCodegen\Commands\GenDocsCommand;
use Nichozuo\LaravelCodegen\Commands\GenFilesCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = false;

    public function register()
    {
        $this->commands([
            DumpTableCommand::class,
            GenFilesCommand::class,
            GenDocsCommand::class,
        ]);
    }
}