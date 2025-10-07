<?php

namespace Services\Mailing;

use InvalidArgumentException;
use lib\DB;
use PDO;
use PDOException;
use RuntimeException;

class MailingSession
{
    private int $id;
    private string $status;
    private string $name;
    private int $mailingId;
    const STATUS_NOT_STARTED = 'not started';
    const STATUS_PROCESSING = 'processing';
    const STATUS_FINISHED = 'finished';
    const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_PROCESSING,
        self::STATUS_FINISHED,
    ];

    public function __construct(array $mailingSessionFromDB)
    {
        $this->id = $mailingSessionFromDB['id'];
        $this->status = $mailingSessionFromDB['status'];
        $this->name = $mailingSessionFromDB['name'];
        $this->mailingId = $mailingSessionFromDB['mailing_id'];
    }

    public function setStatus(string $status)
    {
        if (!in_array($status, self::STATUSES)) {
            throw new InvalidArgumentException("Received unknown status $status");
        }

        $db = (new DB())->getDB();
        $id = $this->getId();

        $stmt = $db->prepare("UPDATE mailing_session SET status = :status WHERE id = :id");
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function createNewSession(string $name, int $mailingId): self
    {

        $db = (new DB())->getDB();
        try {
            $stmt = $db->prepare("INSERT INTO mailing_session (name, mailing_id) VALUES (:name, :mailing_id)");
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':mailing_id', $mailingId, PDO::PARAM_INT);
            $successfullyInserted = $stmt->execute();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $foundSession = self::getSessionByName($name);
                if (!$foundSession) {
                    throw new InvalidArgumentException("Unable to find session by name $name, unable to insert session by name $name");
                }
                return $foundSession;
                //todo если критично умалчивать о попытке перезаписи существующей сессии - можно выбросить исключение и обработать его выше по логике
//                throw new InvalidArgumentException("Session $name already exists");
            }
        }

        if (!$successfullyInserted) throw new RuntimeException("Unable to insert new session");
        $insertedId = $db->lastInsertId();
        $session = self::getSessionById($insertedId);
        if (!$session) throw new RuntimeException("Unable to get mailing session by id $insertedId");

        return $session;
    }

    public static function getSessionById(int $id): ?self
    {
        $db = (new DB())->getDB();
        $stmt = $db->prepare("SELECT * FROM mailing_session WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $session = $stmt->fetch() ?? null;
        if (!$session) return null;
        return new self($session);
    }

    public static function getSessionByName(string $name): ?self
    {
        $db = (new DB())->getDB();
        $stmt = $db->prepare("SELECT * FROM mailing_session WHERE name = :name");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        $session = $stmt->fetch() ?? null;
        if (!$session) return null;
        return new self($session);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMailingId(): ?int
    {
        return $this->mailingId;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}