<?php

namespace User;


use App\DB;
use App\Hydrator;

class UserRepository
{
    /**
     * @var DB
     */
    private $db;

    /**
     * @var Hydrator
     */
    private $hydrator;

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

    /**
     * @param array $user
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function add($user)
    {
        $user = $this->fromArray($user);
        $this->saveInDb($user);
    }

    /**
     * @param User $user
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function saveInDb(User $user)
    {
        $query = 'INSERT INTO `users` (`login`, `nick`, `email`, `password`, `role`) VALUES (:login, :nick, :email, :password, \'user\')';
        $this->db->query($query, $this->hydrator->extract($user));
        $user->setId($this->db->lastInsertId());
    }
}
