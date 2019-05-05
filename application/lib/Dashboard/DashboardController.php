<?php

namespace Dashboard;


use App\Controller;
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
        return $this->redirect('/dashboard/smthing');
    }

    public function test(string $title, array $get, ?array $cookies)
    {
        return $this->response(new View('dashboard/test.twig', ['title' => 'd']));
    }
}
