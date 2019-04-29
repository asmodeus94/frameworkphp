<?php
return [
    'article' => [
        'path' => 'dashboard/{{unique:word}}/page/{{page:number}}[[/add/{{name:slug}}]]{{multiParams}}',
        'class' => \Dashboard\DashboardController::class,
        'method' => 'index'
    ],
];
