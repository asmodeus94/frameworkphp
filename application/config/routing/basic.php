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
        'path' => 'dashboard/{str}[-d][/{book:slug}][/page/{n:number}]',
        'class' => \Dashboard\DashboardController::class,
        'method' => 'test',
        'allowedHttpMethods' => ['GET']
    ],
];
