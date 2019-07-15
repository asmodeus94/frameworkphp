<?php

namespace Dashboard;


use App\AbstractController;
use App\Config\Configurator;
use App\DB;
use App\Helper\RouteHelper;
use App\Response\View;
use App\Security\Csrf;

class DashboardController extends AbstractController
{
    /**
     * @var \App\DB
     */
    private $db;

    private $csrf;

    public function __construct(\App\DB $db, Csrf $csrf)
    {
        $this->db = $db;
        $this->csrf = $csrf;
    }

    public function index()
    {
        return $this->redirect(RouteHelper::path('dashboard-test2', ['title' => 'smthing']));
    }

    public function test(string $title, Configurator $configurator, DB $db)
    {
        $this->csrf->checkToken();

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
