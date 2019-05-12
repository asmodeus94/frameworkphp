<?php

namespace App\Config;


class Configurator
{
    /**
     * @var Configurator
     */
    private static $instance = null;

    /**
     * @var \DB
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
        $this->db = \DB::getInstance();
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
    private function setToStorage(string $name, $data): bool
    {
        $query = 'INSERT INTO `config` (`name`, `data`) VALUES (:name, CAST(:data AS JSON)) ON DUPLICATE KEY UPDATE `data` = CAST(:data AS JSON)';
        $parameters = [
            'name' => $name,
            'data' => json_encode($data, JSON_FORCE_OBJECT)
        ];

        return $this->db->query($query, $parameters);
    }

    /**
     * Usuwa konfigurację z bazy
     *
     * @param string $name
     *
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeFromStorage(string $name): bool
    {
        $query = 'DELETE FROM `config` WHERE `name` = ? LIMIT 1';

        return $this->db->query($query, [$name]);
    }

    /**
     * Usuwa konfigurację z bazy oraz z właściwości
     *
     * @param $name
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __unset($name)
    {
        if ($this->removeFromStorage($name)) {
            unset($this->data[$name]);
        }
    }

    /**
     * Pobiera konfigurację z właściwości, a w przypadku jej braku sprawdza, czy jest konfiguracja w bazie
     *
     * @param string $name
     *
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __get(string $name)
    {
        if (!array_key_exists($name, $this->data)) {
            $data = $this->getFromStorage($name);
            $this->data[$name] = $data !== null ? json_decode($data, true) : null;
        }

        return $this->data[$name];
    }

    /**
     * Zapisuje konfigurację w bazie oraz właściwości
     *
     * @param string $name
     * @param mixed  $data
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function __set(string $name, $data): void
    {
        if ($this->setToStorage($name, $data)) {
            $this->data[$name] = $data;
        }
    }
}
