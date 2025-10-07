<?php

namespace Services\Mailing;

use InvalidArgumentException;
use lib\Cache;
use lib\Logger;
use RuntimeException;
use Throwable;

class MailingWorker
{
    private Cache $cache;
    private Logger $logger;
    private array $data;

    public function __construct(array $data)
    {
        $this->logger = new Logger();
        $this->data = $data;
    }

    public function execute()
    {
        try {

            $this->logger->log('Start handling mailing');

            $mailingSessionId = $this->data['mailing_session_id'] ?? null;
            if(!$mailingSessionId){
                throw new InvalidArgumentException("Received empty mailing_id");
            }
            $mailingSession = MailingSession::getSessionById($mailingSessionId);
            if (!($mailingSession instanceof MailingSession)) {
                throw new InvalidArgumentException("Received invalid mailing");
            }

            $status = $mailingSession->getStatus();
            if($status === MailingSession::STATUS_NOT_STARTED){
                $mailingSession->setStatus(MailingSession::STATUS_PROCESSING);
            }

            $this->logger->log("mailing id: {$mailingSession->getId()}, name: {$mailingSession->getName()}, current status: $status");

            // перед началом работы проверить кэш и если не пустой - закинуть юзеров + очистить
            // если не пустой - значит обработка прервалась. сохраняем изменения и стартуем с последнего обработанного юзера
            $this->cache = new Cache($mailingSession->getName());
            $this->rememberProcessedUsers($mailingSession);

            $mailing = MailingModel::getMailingById($mailingSession->getMailingId());
            if(!$mailing) throw new RuntimeException("Mailing with id {$mailingSession->getMailingId()} not found");

            $usersForMailing = UsersForMailingModel::getUsersForMailing($mailingSession);

            $usersProcessedCount = 0;
            foreach($usersForMailing as $user){
                $this->sendMessage($user, $mailing);
                $this->cache->add($mailingSession->getName(), $user->getId());
                if($usersProcessedCount++ > 100){
                    $this->rememberProcessedUsers($mailingSession);
                    $usersProcessedCount = 0;
                }
            }
            $this->rememberProcessedUsers($mailingSession);

            $mailingSession->setStatus(MailingSession::STATUS_FINISHED);
            $this->logger->log("successfully finished, mailing id: {$mailingSession->getId()}, name: {$mailingSession->getName()}");

        } catch (Throwable $e) {
            $this->logger->log("{$e->getMessage()} in file {$e->getFile()} at line {$e->getLine()}");
        }
    }

    private function rememberProcessedUsers(MailingSession $mailingSession)
    {
        $usersProcessed = $this->cache->get($mailingSession->getName());
        if(empty($usersProcessed)) return;
        MailingSessionUsers::addProcessedUsers($usersProcessed, $mailingSession->getId());
        $this->cache->clear();
    }

    private function sendMessage(UsersForMailingModel $user, MailingModel $mailing)
    {
        // заглушка под отправку рассылки.
        // вероятно такие методы поддерживают и пакетную отправку, но работа с этим выходит за рамки ТЗ тестового
        // симулируем обработку чтобы процесс висел дольше и можно было потестить
        $this->logger->log("send message for {$user->getName()} by mailing {$mailing->getMailingName()}");
        usleep(500000);

    }
}