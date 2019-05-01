<?php

use App\Autowiring\AutowiringFactoryInterface;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;

class DB implements AutowiringFactoryInterface
{
    /**
     * @var DB
     */
    private static $instance = null;

    /**
     * @var Connection
     */
    private $connection;

    private function __construct()
    {
        $connectionParams = require CONFIG . 'dbConfig.php';

        try {
            $this->connection = DriverManager::getConnection($connectionParams);
        } catch (DBALException $e) {
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
     * @param mixed $parameter
     *
     * @return int
     */
    private function detectTypeOfParameter($parameter): int
    {
        if (is_null($parameter)) {
            return ParameterType::NULL;
        } elseif (is_numeric($parameter) && intval($parameter) == $parameter) {
            return ParameterType::INTEGER;
        } else {
            return ParameterType::STRING;
        }
    }

    /**
     * @param DriverStatement $stmt
     * @param array|null      $parameters
     */
    private function bindValues(DriverStatement $stmt, ?array $parameters): void
    {
        if (empty($parameters)) {
            return;
        }

        $numericKeys = is_numeric(array_keys($parameters)[0]);

        foreach ($parameters as $key => $parameter) {
            $key = !$numericKeys ? ':' . $key : $key + 1;
            $stmt->bindValue($key, $parameter, $this->detectTypeOfParameter($parameter));
        }
    }

    /**
     * @param string $query
     * @param array  $parameters
     *
     * @return array
     */
    private function replaceAsteriskWithQuestionMarks(string $query, array $parameters): array
    {
        $newParameters = [];
        foreach ($parameters as $parameter) {
            if (!is_array($parameter)) {
                $newParameters[] = $parameter;
                continue;
            }

            $query = preg_replace('/\(\*\)/', '(' . self::makeQuestionMarks(count($parameter)) . ')', $query, 1);
            $newParameters = array_merge($newParameters, $parameter);
        }

        return [$query, $newParameters];
    }

    /**
     * @param string     $query
     * @param array|null $parameters
     *
     * @return DriverStatement
     * @throws DBALException
     */
    private function makeStatement(string $query, ?array $parameters): DriverStatement
    {
        if (!empty($parameters) && preg_match_all('/\(\*\)/', $query, $matches)) {
            list($query, $parameters) = $this->replaceAsteriskWithQuestionMarks($query, $parameters);
        }

        $stmt = $this->connection->prepare($query);
        $this->bindValues($stmt, $parameters);

        return $stmt;
    }

    /**
     * @param string     $query
     * @param array|null $parameters
     *
     * @return bool
     * @throws DBALException
     */
    private function execute(string $query, ?array $parameters): bool
    {
        $stmt = $this->makeStatement($query, $parameters);

        return $stmt->execute();
    }

    /**
     * @param int $number
     *
     * @return string
     */
    public static function makeQuestionMarks(int $number): string
    {
        return str_repeat('?,', $number - 1) . '?';
    }

    /**
     * @param string     $query
     * @param array|null $parameters
     *
     * @return bool
     * @throws DBALException
     */
    public function query(string $query, ?array $parameters = null): bool
    {
        return $this->execute($query, $parameters);
    }

    /**
     * @param string     $query
     * @param array|null $parameters
     *
     * @return mixed[]|false
     * @throws DBALException
     */
    public function getRows(string $query, ?array $parameters = null)
    {
        $stmt = $this->makeStatement($query, $parameters);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->fetchAll();
    }

    /**
     * @param string $query
     * @param array  $parameters
     *
     * @return false|mixed
     * @throws DBALException
     */
    public function getValue(string $query, ?array $parameters = null)
    {
        $stmt = $this->makeStatement($query, $parameters);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->fetchColumn();
    }

    /**
     * @param string     $query
     * @param array|null $parameters
     *
     * @return mixed
     * @throws DBALException
     */
    public function getRow(string $query, ?array $parameters = null)
    {
        $stmt = $this->makeStatement($query, $parameters);

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->fetch();
    }

    /**
     * @param string     $query
     * @param array|null $parameters
     *
     * @return array|false
     * @throws DBALException
     */
    public function getColumn(string $query, ?array $parameters = null)
    {
        if (empty($records = $this->getRows($query, $parameters))) {
            return false;
        }

        $columnName = array_keys($records[0])[0];

        return array_column($records, $columnName);
    }

    /**
     *
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * @throws ConnectionException
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * @throws ConnectionException
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->isTransactionActive();
    }
}
