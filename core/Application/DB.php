<?php

namespace Core\Application;
use PDO;
use PDOException;

class DB
{
    private static string $DB_CONNECTION;
    private static string $DB_HOST;
    private static string $DB_PORT;
    private static string $DB_DATABASE;
    private static string $DB_USERNAME;
    private static string $DB_PASSWORD;
    private static PDO $db;
    public static function init()
    {
        self::$DB_CONNECTION = $_ENV['DB_CONNECTION'];
        self::$DB_HOST = $_ENV['DB_HOST'];
        self::$DB_PORT = $_ENV['DB_PORT'];
        self::$DB_DATABASE = $_ENV['DB_DATABASE'];
        self::$DB_USERNAME = $_ENV['DB_USERNAME'];
        self::$DB_PASSWORD = $_ENV['DB_PASSWORD'];

        if (self::$DB_CONNECTION != 'mysql') {
            echo '
        Sorry, only <b>mysql</b> is implemented as DB_CONNECTION.
      ';

            exit();
        }

        self::connect_mysql_pdo();
    }

    private static function connect_mysql_pdo()
    {
        $host = self::$DB_HOST . ':' . self::$DB_PORT;
        $db_name = self::$DB_DATABASE;

        try {
            self::$db = new PDO("mysql:host=$host;dbname=$db_name", self::$DB_USERNAME, self::$DB_PASSWORD, [
                PDO::ATTR_PERSISTENT => true,
            ]);
        } catch (PDOException $e) {
            // dd(
            //   "PDO CONNECTION ERROR",
            //   [
            //     "code" => $e->getCode(),
            //     "message" => $e->getMessage(),
            //     "trace" => $e->getTrace(),
            //   ],
            // );
        }
    }

    public static function query(string $query = '', array $params = [], int $fetch_mode = PDO::FETCH_ASSOC)
    {
        if ($query === '') {
            return null;
        }
        if (!isset(self::$db)) {
            return null;
        }

        $prepared_statement = self::$db->prepare($query);
        $prepared_statement->execute($params);

        return $prepared_statement->fetchAll($fetch_mode);
    }
}
