<?php

namespace Dashboard;


use App\AbstractController;
use App\Helper\RouteHelper;
use App\Response\View;

class DashboardController extends AbstractController
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
        return $this->redirect(RouteHelper::path('basic-test2', ['title' => 'smthing']));
    }

    public function test(string $title)
    {
        $params = [
            'title' => $title,
            'multiParams' => [
                's' => 'd'
            ]
        ];

        $query = [
            'page' => 5
        ];

        return $this->response(new View('dashboard/test.twig', ['title' => $title, 'params' => $params, 'query' => $query]));
    }
}
