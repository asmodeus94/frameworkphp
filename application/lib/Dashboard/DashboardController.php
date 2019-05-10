<?php

namespace Dashboard;


use App\Controller;
use App\Helper\RouteHelper;
use App\Response\View;

class DashboardController extends Controller
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
        return $this->response(new View('dashboard/test.twig', ['title' => $title, 'params' => ['title' => $title, 'page' => 5]]));
    }
}
