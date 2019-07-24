<?php

namespace User;


use App\DB;
use App\Helper\ArrayHelper;
use App\Helper\Traits\StatusTrait;
use App\Hydrator;
use User\UserRepository\Statuses;

class UserRepository
{
    use StatusTrait;

    /**
     * @var DB
     */
    private $db;

    /**
     * @var Hydrator
     */
    private $hydrator;

    const PASSWORD_LENGTH_MIN = 8;
    const PASSWORD_LENGTH_MAX = 64;
    const PASSWORD_MIN_NUMBER_OF_SPECIAL_CHARS = 3;
    const PASSWORD_SPECIAL_CHARS = '$&+,:;=?@#|\'<>.^*()%!-_';

    const LOGIN_MIN_LENGTH = 8;
    const LOGIN_MAX_LENGTH = 30;

    const NICK_MIN_LENGTH = 8;
    const NICK_MAX_LENGTH = 30;

    public function __construct(DB $db, Hydrator $hydrator)
    {
        $this->db = $db;
        $this->hydrator = $hydrator;
    }

    /**
     * @param array $user
     *
     * @return User
     */
    private function fromArray(array $user): User
    {
        return $this->hydrator->hydrate($user, new User());
    }

    private function checkPassword(string $password, string $password2): bool
    {
        if ($password !== $password2) {
            $this->setStatus(Statuses::ERROR_PASSWORDS_MISMATCH);

            return false;
        }

        $passwordLength = mb_strlen($password);
        if ($passwordLength < self::PASSWORD_LENGTH_MIN || $passwordLength > self::PASSWORD_LENGTH_MAX) {
            $this->appendStatus(Statuses::ERROR_PASSWORD_LENGTH);
        }

        if ((bool)preg_match('/[\s]+/', $password)) {
            $this->appendStatus(Statuses::ERROR_PASSWORD_WHITESPACES);
        }

        if ((int)preg_match_all('/[0-9]|[' . addslashes(self::PASSWORD_SPECIAL_CHARS) . ']/', $password) < self::PASSWORD_MIN_NUMBER_OF_SPECIAL_CHARS) {
            $this->appendStatus(Statuses::ERROR_PASSWORD_WEAK);
        }

        return $this->getStatus() === 0;
    }

    /**
     * @param string $email
     *
     * @return mixed
     */
    private function checkEmail(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->setStatus(Statuses::ERROR_EMAIL_INVALID);

            return false;
        }

        return true;
    }

    private function checkLogin(string $login): bool
    {
        $loginLength = strlen($login);
        if (!preg_match('/^[a-z0-9_-]+$/i', $login)) {
            $this->setStatus(Statuses::ERROR_LOGIN_ILLEGAL_CHARACTERS);
        } elseif ($loginLength < self::LOGIN_MIN_LENGTH || $loginLength > self::LOGIN_MAX_LENGTH) {
            $this->setStatus(Statuses::ERROR_LOGIN_LENGTH);
        } else {
            return true;
        }

        return false;
    }

    private function checkNick(string $nick): bool
    {
        $nickLength = strlen($nick);
        if (!preg_match('/^[a-z0-9_-]+$/i', $nick)) {
            $this->setStatus(Statuses::ERROR_NICK_ILLEGAL_CHARACTERS);
        } elseif ($nickLength < self::NICK_MIN_LENGTH || $nickLength > self::NICK_MAX_LENGTH) {
            $this->setStatus(Statuses::ERROR_NICK_LENGTH);
        } else {
            return true;
        }

        return false;
    }

    /**
     * @param User $user
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function isUserExists(User $user): bool
    {
        $parameters = $this->hydrator->extract($user);
        if ($this->db->getValue('SELECT 1 FROM `users` WHERE `login` = :login LIMIT 1', $parameters) !== false) {
            $this->setStatus(Statuses::ERROR_LOGIN_ALREADY_EXISTS);
        } elseif ($this->db->getValue('SELECT 1 FROM `users` WHERE `nick` = :nick LIMIT 1', $parameters) !== false) {
            $this->setStatus(Statuses::ERROR_NICK_ALREADY_EXISTS);
        } else {
            return false;
        }

        return true;
    }

    /**
     * @param array $user
     *
     * @return User|null
     * @throws \Doctrine\DBAL\DBALException
     */
    public function add($user): ?User
    {
        if (!ArrayHelper::areFieldsFilled($user, ['login', 'nick', 'email','password', 'password2'])) {
            $this->setStatus(Statuses::ERROR_EMPTY_FIELDS);

            return null;
        }

        if (!$this->checkPassword($user['password'], $user['password2'])) {
            return null;
        }

        $user = $this->fromArray($user);
        if (
            !$this->checkEmail($user->getEmail())
            || !$this->checkLogin($user->getLogin())
            || !$this->checkNick($user->getLogin())
            || $this->isUserExists($user)
        ) {
            return null;
        }

        $this->setStatus(Statuses::SUCCESS);

        return $this->saveInDb($user);
    }

    /**
     * @param User $user
     *
     * @return User|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function saveInDb(User $user): ?User
    {
        $query = 'INSERT INTO `users` (`login`, `nick`, `email`, `password`, `role`) VALUES (:login, :nick, :email, :password, \'user\')';
        $this->db->query($query, $this->hydrator->extract($user));
        $user->setId($this->db->lastInsertId());

        return $user;
    }
}
