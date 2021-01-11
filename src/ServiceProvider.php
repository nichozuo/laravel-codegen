<?php


namespace Nichozuo\LaravelCodegen;


use Nichozuo\LaravelCodegen\Commands\DumpTable\DumpTableCommand;
use Nichozuo\LaravelCodegen\Commands\GenFiles\GenControllerCommand;
use Nichozuo\LaravelCodegen\Commands\GenFiles\GenModelCommand;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected $defer = false;

    public function register()
    {
        $this->commands([
            DumpTableCommand::class,
            GenModelCommand::class,
            GenControllerCommand::class
        ]);
    }
}