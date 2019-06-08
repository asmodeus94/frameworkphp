<?php

namespace Dashboard;


use App\AbstractController;
use App\Config\Configurator;
use App\DB;
use App\Helper\RouteHelper;
use App\Request;
use App\Response\View;

class DashboardController extends AbstractController
{
    /**
     * @var \App\DB
     */
    private $db;

    public function __construct(\App\DB $db)
    {
        $this->db = $db;
    }

    public function index()
    {
        return $this->redirect(RouteHelper::path('basic-test2', ['title' => 'smthing']));
    }

    public function test(string $title, Configurator $configurator, DB $db)
    {
        var_dump($db->getRows('SELECT * FROM `users`'));
        $params = [
            'title' => $title,
            'multiParams' => [
                's' => 'd',
            ]
        ];

        $query = [
            'page' => 5
        ];

        return $this->response(new View('dashboard/test.twig', ['title' => $title, 'params' => $params, 'query' => $query]));
    }

    public function dbTest(string $name)
    {
        return $this->response(['content' => $name]);
    }
}
