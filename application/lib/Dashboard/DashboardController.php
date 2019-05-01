<?php

namespace Dashboard;


use App\ControllerAbstract;
use App\Request;

class DashboardController extends ControllerAbstract
{
    public function __construct(\DB $db)
    {
        var_dump($db->query('SELECT * FROM `users`'));
    }

    public function index(\DB $db)
    {
        echo 'index';
    }

    public function test()
    {
        echo 'test';
    }
}
