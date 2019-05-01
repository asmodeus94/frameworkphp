<?php

use App\Autowiring\AutowiringFactoryInterface;

class DB implements AutowiringFactoryInterface
{
    /**
     * @var DB
     */
    private static $instance = null;

    /**
     * @var \PDO
     */
    private $pdo;

    private function __construct()
    {
        $data = require CONFIG . 'dbConfig.php';

        $options = [
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'' . $data['charset'] . '\''
        ];

        $dsn = $data['type'] . ':host=' . $data['host'] . ';dbname=' . $data['name'] . ';charset=' . $data['charset'];

        try {
            $this->pdo = new \PDO($dsn, $data['user'], $data['password'], $options);
        } catch (\PDOException $e) {
        }
    }

    public static function getInstance(): AutowiringFactoryInterface
    {
        if (!isset(self::$instance)) {
            self::$instance = new DB();
        }

        return self::$instance;
    }

    /**
     * @param string $query
     *
     * @return array
     */
    public function query(string $query): array
    {
        $stmt = $this->pdo->query($query);
        return $stmt->fetchAll();
    }
}
