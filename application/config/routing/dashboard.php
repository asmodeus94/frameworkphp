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
    'testCli' => (new Rule('cli/{name}', \Dashboard\DashboardController::class, 'dbTest'))
        ->setAllowedHttpMethods([])
        ->allowedCli(true),
];
