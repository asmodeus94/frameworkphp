<?php

namespace Dashboard;


use App\ControllerAbstract;
use App\Helper\Url;
use App\Request;

class DashboardController extends ControllerAbstract
{
    public function __construct(\DB $db)
    {
    }

    public function index(\DB $db)
    {
        echo 'index';
    }

    public function test(array $get, \DB $db)
    {
        echo 'test';
    }
}
