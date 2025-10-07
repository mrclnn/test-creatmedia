<?php

namespace Services\Mailing;

use InvalidArgumentException;
use lib\DB;
use PDO;

class MailingModel
{
    private string $mailingName;
    private string $mailingText;
    public function __construct(array $mailingFromDB)
    {
        $this->mailingText = $mailingFromDB['mailing_text'];
        $this->mailingName = $mailingFromDB['mailing_name'];
    }

    public static function getMailingById(int $id): ?self
    {
        $db = (new DB())->getDB();
        $stmt = $db->prepare("SELECT mailing_name, mailing_text FROM mailing_list WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $mailing = $stmt->fetch();
        if(!$mailing) return null;
        return new self($mailing);
    }

    public function getMailingText(): ?string
    {
        return $this->mailingText;
    }

    public function getMailingName(): ?string
    {
        return $this->mailingName;
    }

}