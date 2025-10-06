<?php

namespace lib;

use config\DBConfig;
use PDO;

class DB
{
    const MAX_INSERT_CHUNK_SIZE = 500;
    private PDO $db;
    public function __construct()
    {
        $config = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            DBConfig::HOST,
            DBConfig::DATABASE
        );
        $this->db = new PDO($config, DBConfig::USER, DBConfig::PASSWORD);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getDB(): PDO
    {
        return $this->db;
    }

    /**
     * @param string $table
     * @param array $columns
     * @param array $rows
     * @return void
     */
    public function massInsert(string $table, array $columns, array $rows): bool
    {

        // $placeholders - это строка вида (?,?,?) где количество ? совпадает с количеством $columns
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $values = [];
        $chunks = [];

        foreach ($rows as $row) {
            $chunks[] = $placeholders;
            foreach ($row as $value) {
                $values[] = $value;
            }
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES %s",
            $table,
            implode(',', $columns),
            implode(',', $chunks)
        );

        $stmt = $this->getDB()->prepare($sql);
        return $stmt->execute($values);
    }
}