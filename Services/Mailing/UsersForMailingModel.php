<?php

namespace Services\Mailing;

use config\DBConfig;
use Iterator;
use lib\DB;
use lib\Logger;
use PDO;

class UsersForMailingModel
{
    private int $id;
    private int $number;
    private string $name;

    public function __construct(array $userFromDB)
    {
        $this->id = $userFromDB['id'];
        $this->name = $userFromDB['name'];
        $this->number = $userFromDB['number'];
    }

    /**
     * @return Iterator<self>
     */
    public static function getAllUsers(): Iterator
    {

        $db = (new DB())->getDB();

        $offset = 0;

        do {
            $stmt = $db->prepare(
                "SELECT number, name FROM users_for_mailing ORDER BY number ASC LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue(':limit', DBConfig::CHUNK_SELECT_SIZE, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                yield new self($row);
            }

            $offset += DBConfig::CHUNK_SELECT_SIZE;

        } while (!empty($rows));
    }

    /**
     * @param MailingSession $session
     * @return Iterator<self>
     */
    public static function getUsersForMailing(MailingSession $session): Iterator
    {
        $db = (new DB())->getDB();

        $offset = 0;
        $chunkSize = 500;
        $sessionId = $session->getId();

        do {
            $sql = "
                SELECT u.id, u.name, u.number
                FROM users_for_mailing AS u
                LEFT JOIN mailing_session_users AS msu
                  ON u.id = msu.user_id AND msu.session_id = :session_id
                WHERE msu.user_id IS NULL
                ORDER BY u.id ASC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':session_id', $sessionId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', DBConfig::CHUNK_SELECT_SIZE, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


            foreach ($rows as $row) {
                yield new self($row);
            }

            $offset += DBConfig::CHUNK_SELECT_SIZE;
        } while (!empty($rows));

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function getName(): ?string
    {
        return $this->name;
    }


}