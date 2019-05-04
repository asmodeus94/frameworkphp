<?php
return [
    'hello-world' => [
        'path' => [
            'dashboard/{unique:word}/page/{page:number}[/add/{name:slug}]{multiParams}',
            ''
        ],
        'class' => \Dashboard\DashboardController::class,
        'method' => \App\Route::DEFAULT_METHOD,
        'allowedHttpMethods' => ['POST', 'GET']
    ],
    'test2' => [
        'path' => 'dashboard/{title:slug}[-d][/{book:slug}][/page/{page:number}]',
        'class' => \Dashboard\DashboardController::class,
        'method' => 'test',
        'allowedHttpMethods' => ['GET']
    ],
];
