<?php

use App\Route\Rule;

return [
    'hello-world' => (new Rule())
        ->setClass(\Dashboard\DashboardController::class)
        ->setPaths([
            'dashboard/{unique:word}/page/{page:number}[/add/{name:slug}]{multiParams}',
            '',
        ])
        ->setMethod('index'),
    'test2' => (new Rule())
        ->setPath('dashboard/title/{title:slug}[/{book:slug}][/page/{page:number}]{multiParams}')
        ->setClass(\Dashboard\DashboardController::class)
        ->setMethod('test')
        ->setAllowedHttpMethods(['GET']),
    'testJson' => new Rule('test/json', \Dashboard\DashboardController::class, 'json'),
    'cliDbWrite' => (new Rule('cli/prepare', \Dashboard\DashboardController::class, 'prepare'))
        ->allowedCli(),
    'dbDatesList' => new Rule('db/dates', \Dashboard\DashboardController::class, 'dbList'),
    'dbDateDownload' => new Rule('db/date/download', \Dashboard\DashboardController::class, 'dbDateDownload'),
    'dbSaveInFile' => new Rule('db/date/saveInFile', \Dashboard\DashboardController::class, 'dbSaveInFile'),
    'dbSaveInFileNonBlocking' => new Rule('db/date/saveInFileNonBlocking', \Dashboard\DashboardController::class, 'dbSaveInFileNonBlocking'),
    'cliDbDump' => (new Rule('db/cliDbDump', \Dashboard\DashboardController::class, 'cliDbDump'))->allowedCli(),
    'videoStream' => new Rule('video/stream', \Dashboard\DashboardController::class, 'videoStream'),
    'sqlStream' => new Rule('text/stream', \Dashboard\DashboardController::class, 'sqlStream'),
];
