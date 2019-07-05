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
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return User
     */
    public function setId(int $id): User
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     *
     * @return User
     */
    public function setLogin(string $login): User
    {
        $this->login = $login;
        return $this;
    }

    /**
     * @return string
     */
    public function getNick(): string
    {
        return $this->nick;
    }

    /**
     * @param string $nick
     *
     * @return User
     */
    public function setNick(string $nick): User
    {
        $this->nick = $nick;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastSessionId(): ?string
    {
        return $this->lastSessionId;
    }

    /**
     * @param string $lastSessionId
     *
     * @return User
     */
    public function setLastSessionId(string $lastSessionId): User
    {
        $this->lastSessionId = $lastSessionId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getLastActivity(): ?string
    {
        return $this->lastActivity;
    }

    /**
     * @param string $lastActivity
     *
     * @return User
     */
    public function setLastActivity(string $lastActivity): User
    {
        $this->lastActivity = $lastActivity;
        return $this;
    }
}
