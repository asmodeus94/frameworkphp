<?php

namespace Dashboard;


use App\ControllerAbstract;
use App\Request;

class DashboardController extends ControllerAbstract
{
    public function __construct(\DB $db)
    {
    }

    public function index(\DB $db)
    {
        $parameters = [
            'id' => 1,
            'login' => 'dfsfsfsdąśąśćżł'
        ];
        var_dump($db->getRows('SELECT * FROM `users` WHERE user_id IN (:id) OR login = :login', $parameters));
        echo 'index';
    }

    public function test()
    {
        echo 'test';
    }
}
