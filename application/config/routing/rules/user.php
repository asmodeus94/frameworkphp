<?php

use App\Route\Rule;

return [
    'index' => (new Rule())
        ->setClass(\User\UserController::class)
        ->setPath('user[/{code:testPattern}]')
        ->setMethod('index'),
    'add' => (new Rule('user/add', \User\UserController::class, 'addUser'))
        ->setAllowedHttpMethods(['POST']),
];
