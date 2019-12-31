<?php

namespace User;


class User
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $nick;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string|null
     */
    private $lastSessionId;

    /**
     * @var string|null
     */
    private $lastActivity;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * @param string $login
     *
     * @return $this
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNick()
    {
        return $this->nick;
    }

    /**
     * @param string $nick
     *
     * @return $this
     */
    public function setNick($nick): User
    {
        $this->nick = $nick;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastSessionId()
    {
        return $this->lastSessionId;
    }

    /**
     * @param string $lastSessionId
     *
     * @return $this
     */
    public function setLastSessionId($lastSessionId)
    {
        $this->lastSessionId = $lastSessionId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    /**
     * @param string $lastActivity
     *
     * @return $this
     */
    public function setLastActivity($lastActivity)
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }
}
