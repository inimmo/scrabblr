<?php
require_once __DIR__ . '/vendor/autoload.php';

(new DB('mysql://@127.0.0.1:3309/scrabblr'));

class DB {
    private static $conn;

    public function __construct(string $url)
    {
        self::$conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $url]);
    }

    public static function conn()
    {
        return self::$conn;
    }
}

function query($sql, $params = []) {
    $stmt = DB::conn()->prepare($sql);
    $stmt->execute($params);

    return $stmt->getIterator();
}

function scalar($sql, $params = []) {
    $result = iterator_to_array(query($sql, $params));
    return $result[0][array_keys($result[0])[0]];
}

function lastId() {
    return DB::conn()->lastInsertId();
}
