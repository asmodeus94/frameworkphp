<?php

namespace User;


use App\AbstractController;
use App\Helper\RouteHelper;
use App\Response\View;
use App\Security\Csrf;
use User\UserRepository\Statuses;
use View\Alerts;
use View\Alerts\Alert;

class UserController extends AbstractController
{
    /**
     * @var Csrf
     */
    private $csrf;

    public function __construct(Csrf $csrf)
    {
        $this->csrf = $csrf;
    }

    public function index(array $user, int $statusCode)
    {
        $variables = [
            'title' => 'User creator',
            'user' => $user,
            'alert' => $this->getAlerts($statusCode),
        ];

        return $this->response(new View('user/index.twig', $variables));
    }

    /**
     * @param int|null $statusCode
     *
     * @return Alert[]
     */
    private function getAlerts(?int $statusCode): array
    {
        $alerts = new Alerts($statusCode);
        $alerts->add(\View\Alerts\Type::SUCCESS, [Statuses::SUCCESS => 'Dodałeś użytkownika']);

        $messages = [
            Statuses::ERROR_EMPTY_FIELDS => 'Nie wszystkie pola zostały uzupełnione',
            Statuses::ERROR_PASSWORDS_MISMATCH => 'Podane hasła różnią się od siebie',
            Statuses::ERROR_PASSWORD_LENGTH => 'Nieprawidłowa długośc hasła',
            Statuses::ERROR_PASSWORD_WHITESPACES => 'Hasło nie może zawierać białych znaków',
            Statuses::ERROR_PASSWORD_WEAK => 'Hasło nie jest wystarczająco silne',
            Statuses::ERROR_EMAIL_INVALID => 'Nieprawidłowy email',
            Statuses::ERROR_LOGIN_ILLEGAL_CHARACTERS => 'Login powinien składać się ze znaków z alfabetu łacinskiego, cyfr lub znaków _ oraz -',
            Statuses::ERROR_LOGIN_LENGTH => 'Nieprawidłowa długość loginu',
            Statuses::ERROR_NICK_ILLEGAL_CHARACTERS => 'Nick powinien składać się ze znaków z alfabetu łacinskiego, cyfr lub znaków _ oraz -',
            Statuses::ERROR_NICK_LENGTH => 'Nieprawidłowa długość nicku',
            Statuses::ERROR_LOGIN_ALREADY_EXISTS => 'Użytkownik o takim loginie już istnieje',
            Statuses::ERROR_NICK_ALREADY_EXISTS => 'Użytkownik o takim nicku już istnieje',
        ];

        $alerts->add(\View\Alerts\Type::ERROR, $messages);

        return $alerts->make();
    }

    public function addUser(array $user, UserRepository $userManagement)
    {
        $this->csrf->checkToken();
        $user = $userManagement->add($user) !== null ? [] : $user;

        unset($user['password'], $user['password2']);

        return $this->redirect(RouteHelper::path('user-index', [], ['user' => $user, 'statusCode' => $userManagement->getStatus()]));
    }
}
