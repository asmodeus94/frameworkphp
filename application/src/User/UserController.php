<?php

namespace User;


use App\AbstractController;
use App\Helper\BitMaskHelper;
use App\Helper\RouteHelper;
use App\Response\View;
use App\Security\Csrf;
use User\UserRepository\Statuses;
use View\Message;

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

    public function index(array $user, ?int $statusCode)
    {
        $alert = $this->getMessage($statusCode);

        $variables = [
            'title' => 'User creator',
            'user' => $user,
            'alert' => $alert,
        ];

        return $this->response(new View('user/index.twig', $variables));
    }

    private function getMessage(?int $statusCode): ?Message
    {
        if (!is_numeric($statusCode)) {
            return null;
        }

        $bitMask = new BitMaskHelper($statusCode);
        $message = new Message();

        if ($bitMask->contains(Statuses::SUCCESS)) {
            $message->setType(Message::TYPE_SUCCESS);
            $message->setContent('Dodałeś użytkownika');
        } else {
            $txt = [
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

            foreach ($txt as $errorCode => $msg) {
                if ($bitMask->contains($errorCode)) {
                    $message->setContent($msg);
                    break;
                }
            }

            $message->setType(Message::TYPE_ERROR);
        }

        return $message;
    }

    public function addUser(array $user, UserRepository $userManagement)
    {
        $this->csrf->checkToken();
        $userManagement->add($user);

        unset($user['password'], $user['password2']);

        return $this->redirect(RouteHelper::path('user-index', [], ['user' => $user, 'statusCode' => $userManagement->getStatus()]));
    }
}
