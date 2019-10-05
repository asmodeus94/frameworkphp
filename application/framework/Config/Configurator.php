<?php

namespace App\Config;


use App\DB;

class Configurator
{
    /**
     * @var Configurator
     */
    private static $instance = null;

    /**
     * @var \App\DB
     */
    private $db;

    /**
     * Drzewo konfiguracji
     *
     * @var array
     */
    private $data = [];

    /**
     * Configurator constructor.
     */
    private function __construct()
    {
        $this->db = DB::getInstance();
    }

    /**
     * @return Configurator|null
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Configurator();
        }

        return self::$instance;
    }

    /**
     * Pobiera z bazy konfigurację
     *
     * @param string $name
     *
     * @return string|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getFromStorage(string $name): ?string
    {
        $query = 'SELECT `data` FROM `config` WHERE `name` = ? LIMIT 1';
        if (($data = $this->db->getValue($query, [$name])) !== false) {
            return $data;
        }

        return null;
    }

    /**
     * Zapisuje konfigurację w bazie
     *
     * @param string $name
     * @param mixed  $data
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function saveToStorage(string $name, $data): bool
    {
        $query = 'INSERT INTO `config` (`name`, `data`) VALUES (:name, :data) ON DUPLICATE KEY UPDATE `data` = :data';
        $parameters = [
            'name' => $name,
            'data' => json_encode($data)
        ];

        return $this->db->rawQuery($query, $parameters);
    }

    /**
     * Usuwa konfigurację z bazy
     *
     * @param string $name
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeFromStorage(string $name): void
    {
        $this->db->query('DELETE FROM `config` WHERE `name` = ? LIMIT 1', [$name]);
    }

    /**
     * Metoda będąca aliasem dla remove
     *
     * @param string $name
     *
     * @throws \Doctrine\DBAL\DBALException
     * @see Configurator::remove()
     */
    public function __unset(string $name): void
    {
        $this->remove($name);
    }

    /**
     * @param string $name
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function remove(string $name): void
    {
        $this->removeFromStorage($name);
        unset($this->data[$name]);
    }

    /**
     * Pobiera konfigurację z właściwości, a w przypadku jej braku sprawdza, czy jest konfiguracja w bazie
     *
     * @param string $name
     *
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function get(string $name)
    {
        if (!array_key_exists($name, $this->data)) {
            $data = $this->getFromStorage($name);
            $this->data[$name] = $data !== null ? json_decode($data, true) : null;
        }

        return $this->data[$name];
    }

    /**
     * Metoda będąca aliasem dla get
     *
     * @param string $name
     *
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     *
     * @see Configurator::get()
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Zapisuje konfigurację w bazie oraz właściwości
     *
     * @param string $name
     * @param mixed  $data
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function set(string $name, $data): void
    {
        $this->saveToStorage($name, $data);
        $this->data[$name] = $data;
    }

    /**
     * @param string $name
     * @param mixed  $data
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(string $name, $data)
    {
        $currentData = $this->getFromStorage($name);
        if (is_array($currentData) && is_array($data)) {
            $data = array_replace_recursive($currentData, $data);
        }
        $this->set($name, $data);
    }

    /**
     * Metoda będąca aliasem dla set
     *
     * @param string $name
     * @param mixed  $data
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @see Configurator::set()
     */
    public function __set(string $name, $data): void
    {
        $this->set($name, $data);
    }
}
