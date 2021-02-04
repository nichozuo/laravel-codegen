<?php

use Illuminate\Support\Facades\Route;
use Nichozuo\LaravelCodegen\Controller\DocsController;
use Nichozuo\LaravelUtils\Helper\RouteHelper;

Route::prefix('/docs')->name('docs.')->group(function ($router) {
    if (config('app.debug')) {
        RouteHelper::New($router, DocsController::class);
    }
});