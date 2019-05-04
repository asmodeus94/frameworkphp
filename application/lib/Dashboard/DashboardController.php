<?php

namespace Dashboard;


use App\ControllerAbstract;

class DashboardController extends ControllerAbstract
{
    /**
     * @var \DB
     */
    private $db;

    public function __construct(\DB $db)
    {
        $this->db = $db;
    }

    public function index()
    {
        echo 'index';
    }

    public function test()
    {
        echo 'test';
    }
}
