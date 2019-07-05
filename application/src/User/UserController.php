<?php

namespace User;


use App\AbstractController;
use App\Helper\RouteHelper;
use App\Response\View;

class UserController extends AbstractController
{
    public function index(array $user)
    {
        $variables = [
            'title' => 'User creator',
            'user' => $user
        ];

        return $this->response(new View('user/index.twig', $variables));
    }

    public function addUser(array $user, UserRepository $userManagement)
    {
        $userManagement->add($user);
        unset($user['password']);

        return $this->redirect(RouteHelper::path('user-index', [], ['user' => $user]));
    }
}
