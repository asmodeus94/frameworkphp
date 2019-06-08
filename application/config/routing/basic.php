<?php
return [
    'hello-world' => [
        'path' => [
            'dashboard/{unique:word}/page/{page:number}[/add/{name:slug}]{multiParams}',
            ''
        ],
        'class' => \Dashboard\DashboardController::class,
        'method' => \App\Route\Route::DEFAULT_METHOD,
        'allowedHttpMethods' => ['POST', 'GET']
    ],
    'test2' => [
        'path' => 'dashboard/{title:slug}[/{book:slug}][/page/{page:number}]{multiParams}',
        'class' => \Dashboard\DashboardController::class,
        'method' => 'test',
        'allowedHttpMethods' => ['GET'],
    ],
    'testCli' => [
        'path' => 'cli/{name}',
        'class' => \Dashboard\DashboardController::class,
        'method' => 'dbTest',
        'allowedCli' => true,
    ],
];
