<?php

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Doctrine\DBAL\ParameterType;

class DB
{
    /**
     * @var DB
     */
    private static $instance = null;

    /**
     * @var Connection
     */
    private $connection;

    const MULTI_PARAMS_PLACEHOLDER = ':multiParams';

    private function __construct()
    {
        $this->connection = $this->createNewConnection();
    }

    /**
     * Pobiera dane konfiguracyjne bazy danych i otwiera połączenie z bazą
     *
     * @return Connection|null
     */
    private function createNewConnection(): ?Connection
    {
        $connectionParams = require CONFIG . 'dbConfig.php';

        try {
            return DriverManager::getConnection($connectionParams);
        } catch (DBALException $e) {
            // todo: logger dla klasy db
        }

        return null;
    }

    /**
     * Zwraca instancję DB
     *
     * @param string|null $id
     *
     * @return DB
     */
    public static function getInstance(?string $id = null): DB
    {
        $id = md5((string)$id);
        if (!isset(self::$instance[$id])) {
            self::$instance[$id] = new DB();
        }

        return self::$instance[$id];
    }

    /**
     * Zwraca nową instancję DB z nowym połączeniem z bazą
     *
     * @return DB
     */
    public static function getAnotherInstance(): DB
    {
        return self::getInstance(uniqid());
    }

    /**
     * Wykrywa na potrzeby bindowania typ przekazanej zmiennej
     *
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
        } elseif (is_bool($parameter)) {
            return ParameterType::BOOLEAN;
        } else {
            return ParameterType::STRING;
        }
    }

    /**
     * Binduje przekazane wartości z zapytaniem
     *
     * @param DriverStatement $stmt
     * @param array|null      $parameters
     */
    private function bindValues(DriverStatement $stmt, ?array $parameters): void
    {
        if (empty($parameters)) {
            return;
        }

        $numericKeys = is_int(array_keys($parameters)[0]);

        foreach ($parameters as $key => $parameter) {
            $key = !$numericKeys ? ':' . $key : $key + 1;
            $stmt->bindValue($key, $parameter, $this->detectTypeOfParameter($parameter));
        }
    }

    /**
     * Podmienia w zapytaniu ":multiParams" na znaki "?" rozdzielone przecinkami w liczbie zgodnej z liczbą parametrów,
     * których one dotyczą oraz modyfikuje parametry poprzez wypakowanie parametrów i utworzenie jednowymiarowej
     * tablicy
     *
     * @param string $query
     * @param array  $parameters
     *
     * @return array
     * @see DB::MULTI_PARAMS_PLACEHOLDER
     */
    private function replaceMultiParamsPlaceholderWithQuestionMarks(string $query, array $parameters): array
    {
        $newParameters = [];
        foreach ($parameters as $parameter) {
            if (!is_array($parameter)) {
                $newParameters[] = $parameter;
                continue;
            }

            $query = preg_replace(
                '/\(' . self::MULTI_PARAMS_PLACEHOLDER . '\)/',
                '(' . self::makeQuestionMarks(count($parameter)) . ')',
                $query,
                1
            );
            $newParameters = array_merge($newParameters, $parameter);
        }

        return [$query, $newParameters];
    }

    /**
     * Tworzy zapytanie poprzez zbindowanie z nim parametrów
     *
     * @param string     $query
     * @param array|null $parameters
     *
     * @return DriverStatement
     * @throws DBALException
     */
    private function makeStatement(string $query, ?array $parameters): DriverStatement
    {
        if (!empty($parameters) && preg_match_all('/\(' . self::MULTI_PARAMS_PLACEHOLDER . '\)/', $query, $matches)) {
            [$query, $parameters] = $this->replaceMultiParamsPlaceholderWithQuestionMarks($query, $parameters);
        }

        $stmt = $this->connection->prepare($query);
        $this->bindValues($stmt, $parameters);

        return $stmt;
    }

    /**
     * Tworzy znaki zapytania rozdzielone przecinkami na potrzeby bindowania parametrów
     *
     * @param int $number
     *
     * @return string
     */
    private static function makeQuestionMarks(int $number): string
    {
        return str_repeat('?,', $number - 1) . '?';
    }

    /**
     * Tworzy placeholdery ":multiParams" w nawiasach rozdzielonych przecinkami na potrzeby tworzenia zapytań INSERT
     * dodających wiele rekordów na raz
     *
     * @param int $number
     *
     * @return string
     */
    public static function makeMultiParamsInBrackets(int $number): string
    {
        $multiParams = '(' . self::MULTI_PARAMS_PLACEHOLDER . ')';
        return str_repeat($multiParams . ',', $number - 1) . $multiParams;
    }

    /**
     * Wywołuje zapytanie
     *
     * @param string     $query
     * @param array|null $parameters
     *
     * @return bool
     * @throws DBALException
     */
    public function query(string $query, ?array $parameters = null): bool
    {
        return $this->makeStatement($query, $parameters)->execute();
    }

    /**
     * Wywołuje zapytanie z pominięciem analizy typów zmiennych
     *
     * @param string     $query
     * @param array|null $parameters
     *
     * @return bool
     * @throws DBALException
     */
    public function rawQuery(string $query, ?array $parameters = null): bool
    {
        return $this->connection->prepare($query)->execute($parameters);
    }

    /**
     * Pobiera pojedynczą wartość
     *
     * @param string $query
     * @param array  $parameters
     *
     * @return mixed|false
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
     * Pobiera cały wiersz
     *
     * @param string     $query
     * @param array|null $parameters
     *
     * @return mixed|false
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
     * Pobiera na raz wszystkie rekordy
     *
     * @param string     $query
     * @param array|null $parameters
     *
     * @return array|false
     * @throws DBALException
     */
    public function getRows(string $query, ?array $parameters = null)
    {
        $stmt = $this->makeStatement($query, $parameters);

        if (!$stmt->execute()) {
            return false;
        }

        return !empty($rows = $stmt->fetchAll()) ? $rows : false;
    }

    /**
     * @param string     $key
     * @param string     $query
     * @param array|null $parameters
     *
     * @return array|bool
     * @throws DBALException
     */
    public function getRowsByKey(string $key, string $query, ?array $parameters = null)
    {
        $rows = $this->getRows($query, $parameters);

        if (!$rows) {
            return false;
        }

        $keysAreUnique = count(array_unique(array_column($rows, $key))) === count($rows);

        $newRows = [];

        foreach ($rows as $index => $data) {
            if (!isset($data[$key])) {
                return false;
            }

            $rowKey = $data[$key];
            if (!isset($newRows[$rowKey])) {
                if ($keysAreUnique) {
                    $newRows[$rowKey] = $data;
                } else {
                    $newRows[$rowKey][] = $data;
                }
            } else {
                if (array_keys($newRows[$rowKey])[0] !== 0) {
                    $tmp = $newRows[$rowKey];
                    unset($newRows[$rowKey]);
                    $newRows[$rowKey][] = $tmp;
                }
                $newRows[$rowKey][] = $data;
            }

            unset($rows[$index]);
        }

        return $newRows;
    }

    /**
     * Pobiera całą kolumnę
     *
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
     * Włącza lub wyłącza buforowanie zapytań
     *
     * Uwaga! Przy wyłączonym buforowaniu można jednocześnie na danym połączeniu wykonać tylko jedno zapytanie
     *
     * @param bool $toggle
     *
     * @see DB::getAnotherInstance()
     */
    public function bufferedQuery(bool $toggle): void
    {
        /** @var PDOConnection $wrappedConnection */
        $wrappedConnection = $this->connection->getWrappedConnection();

        $isBuffered = $wrappedConnection->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);

        if ($isBuffered === $toggle) {
            return;
        }

        if ($this->connection->isConnected()) {
            $this->connection->close();
        }

        /** @var PDOConnection $wrappedConnection */
        $wrappedConnection = $this->connection->getWrappedConnection();
        $wrappedConnection->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $toggle);
        $this->connection->connect();
    }

    /**
     * Pobiera wszystkie rekordy jeden po drugim
     *
     * @param string     $query
     * @param array|null $parameters
     *
     * @return Generator
     * @throws DBALException
     *
     * @see DB::bufferedQuery()
     */
    public function getOneByOne(string $query, ?array $parameters = null)
    {
        $stmt = $this->makeStatement($query, $parameters);

        if (!$stmt->execute()) {
            return;
        }

        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }

    /**
     * Zwraca identyfikator ostatnio dodanego rekordu w przypadku gdy tabela ma kolumnę z AUTO_INCREMENT, w przeciwnym
     * razie zwraca '0'
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Rozpoczyna transakcję
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Zatwierdza transakcję
     *
     * @throws ConnectionException
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Wycofuje transakcję
     *
     * @throws ConnectionException
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Sprawdza, czy na danym połączeniu jest otwarta transakcja
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->isTransactionActive();
    }
}
