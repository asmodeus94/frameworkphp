<?php
return [
    'hello-world' => [
        'path' => ['dashboard/{{unique:word}}/page/{{page:number}}[[/add/{{name:slug}}]]{{multiParams}}', ''],
        'class' => \Dashboard\DashboardController::class,
        'method' => \App\Route::DEFAULT_METHOD
    ],
    'test2' => [
        'path' => 'dashboard/test2[[/n/{{n:number}}]]',
        'class' => \Dashboard\DashboardController::class,
        'method' => 'test'
    ],
];
