<?php

namespace App\Config;


use App\Config\Diff\Diff;
use App\Config\Diff\Type;
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
     * @var array
     */
    private $oldData = [];

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
     * @param string $key
     *
     * @return array|null
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getFromStorageSpecificKey(string $key): ?array
    {
        $query = 'SELECT `data` FROM `config` WHERE `key` = ? LIMIT 1';
        if (($data = $this->db->getValue($query, [$key])) !== false) {
            return $data !== null ? json_decode($data, true) : null;
        }

        return null;
    }

    /**
     * Zapisuje konfigurację w bazie
     *
     * @param string $key
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function saveToStorage(string $key): void
    {
        if (array_key_exists($key, $this->oldData) && $this->oldData[$key] === $this->data[$key]) {
            return;
        }

        $copyDataFromStorage = $dataFromStorage = $this->getFromStorageSpecificKey($key);
        if (!is_array($this->oldData[$key]) && !is_array($this->data[$key])) {
            $dataFromStorage = $this->data[$key];
        } elseif (!empty($diffs = (new Diff($this->oldData[$key], $this->data[$key]))->get())) {
            foreach ($diffs as $diff) {
                if (in_array($diff->getType(), [Type::ADD, Type::UPDATE])) {
                    (new ArrayOperation($dataFromStorage, $diff->getPath()))->set($diff->getNewValue());
                } else {
                    if (null !== ($path = $diff->getPath())) {
                        (new ArrayOperation($dataFromStorage, $path))->remove();
                    } else {
                        $dataFromStorage = null;
                    }
                }
            }
        }

        $query = 'INSERT INTO `config` (`key`, `data`) VALUES (:key, :data) ON DUPLICATE KEY UPDATE `data` = :data';
        $parameters = [
            'key' => $key,
            'data' => json_encode($dataFromStorage)
        ];

        $this->db->rawQuery($query, $parameters);
        $this->updateChangelog($key, $copyDataFromStorage, $dataFromStorage);
    }

    /**
     * @param string $key
     * @param mixed  $oldValues
     * @param mixed  $newValues
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateChangelog(string $key, $oldValues, $newValues): void
    {
        if (empty($diffs = (new Diff($oldValues, $newValues))->get())) {
            return;
        }

        $query = 'INSERT INTO `config_changelog` (`operation_type`, `key`, `path`, `old_value`, `new_value`)
                VALUES (:operationType, :key, :path, :oldValue, :newValue)';

        foreach ($diffs as $diff) {
            $type = $diff->getType();
            $parameters = [
                'operationType' => $type,
                'key' => $key,
                'path' => $diff->getPath(),
            ];

            $oldValue = null !== ($oldValue = $diff->getOldValue()) ? var_export($oldValue, true) : null;
            $newValue = null !== ($newValue = $diff->getNewValue()) ? var_export($newValue, true) : null;

            if ($type === Type::ADD) {
                $parameters['oldValue'] = null;
                $parameters['newValue'] = $newValue;
            } elseif ($type === Type::UPDATE) {
                $parameters['oldValue'] = $oldValue;
                $parameters['newValue'] = $newValue;
            } else {
                $parameters['oldValue'] = $oldValue;
                $parameters['newValue'] = null;
            }

            $this->db->rawQuery($query, $parameters);
        }
    }

    /**
     * Usuwa konfigurację z bazy
     *
     * @param string $key
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeFromStorage(string $key): void
    {
        $this->db->query('DELETE FROM `config` WHERE `key` = ? LIMIT 1', [$key]);
    }

    /**
     * Metoda będąca aliasem dla remove
     *
     * @param string $key
     *
     * @throws \Doctrine\DBAL\DBALException
     * @see Configurator::remove()
     */
    public function __unset(string $key): void
    {
        $this->remove($key);
    }

    /**
     * @param string $key
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function remove(string $key): void
    {
        $this->removeFromStorage($key);
        unset($this->data[$key], $this->oldData[$key]);
    }

    /**
     * Pobiera konfigurację z właściwości, a w przypadku jej braku sprawdza, czy jest konfiguracja w bazie
     *
     * @param string $key
     * @param bool   $fromStorage
     *
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function get(string $key, bool $fromStorage = false)
    {
        if ($fromStorage || !array_key_exists($key, $this->data)) {
            $this->oldData[$key] = $this->data[$key] = $this->getFromStorageSpecificKey($key);
        }

        return $this->data[$key];
    }

    /**
     * Metoda będąca aliasem dla get
     *
     * @param string $key
     *
     * @return mixed
     * @throws \Doctrine\DBAL\DBALException
     *
     * @see Configurator::get()
     */
    public function __get(string $key)
    {
        return $this->get($key);
    }

    /**
     * Zapisuje konfigurację w bazie oraz właściwości
     *
     * @param string $key
     * @param mixed  $data
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function set(string $key, $data): void
    {
        $this->get($key);
        $this->data[$key] = $data;
        $this->saveToStorage($key);
        $this->get($key, true);
    }

    /**
     * @param string $key
     * @param mixed  $data
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function update(string $key, $data)
    {
        $currentData = $this->get($key);
        if (is_array($currentData) && is_array($data)) {
            $data = array_replace_recursive($currentData, $data);
        }

        $this->set($key, $data);
    }

    /**
     * Metoda będąca aliasem dla set
     *
     * @param string $key
     * @param mixed  $data
     *
     * @throws \Doctrine\DBAL\DBALException
     *
     * @see Configurator::set()
     */
    public function __set(string $key, $data): void
    {
        $this->set($key, $data);
    }
}
