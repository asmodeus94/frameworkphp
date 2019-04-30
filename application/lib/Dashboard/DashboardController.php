<?php

namespace Dashboard;


use App\ControllerAbstract;
use DB\DB;

class DashboardController extends ControllerAbstract
{
    public function __construct(DB $db)
    {
    }

    public function index(array $get, ?DB $db = null)
    {
        echo 'index';
    }

    public function test(?array $get, array $post)
    {
        echo 'test';
    }
}
