<?php

namespace Controllers;

use config\App;
use Errors\BadRequest;
use lib\Logger;
use Services\Mailing\MailingModel;
use Services\Mailing\MailingSession;
use Services\Mailing\MailingWorker;

class MakeMailingController
{

    private Logger $logger;
    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * @throws BadRequest
     */
    public function handle()
    {

        //todo проверяем не стартанули ли уже сессию и запрещаем в таком случае стартовать заново
        // при повторном пинге сюда можем проверять жив ли процесс обработки

        //id конкретно рассылки. например - рассылка новогодней акции 2026
        $mailingId = $_POST['mailing_id'];
        //имя конкретнной рассылки. например - ne2026-15.10.2025, т.е. рассылка о новогодней акции в середине октября.
        // могут быть еще рассылки той же новогодней акции но уже ближе к декабрю и т.п
        $mailingName = $_POST['mailing_name'];

        $this->logger->log("Received new request for mailing: $mailingId, $mailingName");
        // проверяем существует ли запрашиваемая рассылка "новогодняя акция 2026"
        $mailing = MailingModel::getMailingById($mailingId);
        if(!$mailing) {
            throw new BadRequest("Mailing with name $mailingName not found. You can check existing mailings by api method ...");
        }

        // проверяем не запущена ли уже запрашиваемая рассылка от 15.10.2025
        $mailingSession = MailingSession::getSessionByName($mailingName);

        //todo если запущена в статусе finished то не проверяем ничего просто возвращаем
        // если запущена в статусе processing или not started проверяем наличие процесса

        if($mailingSession) {
            $status = $mailingSession->getStatus();
            if($status === MailingSession::STATUS_FINISHED){
                throw new BadRequest("Mailing session $mailingName by mailing id $mailingId already existed. Status = $status");
            } else {
                if($this->checkIsAlreadyRunning()){
                   throw new BadRequest("Mailing session $mailingName by mailing id $mailingId already working. Status = $status");
                }
            }

        }

        $mailingSession = MailingSession::createNewSession($mailingName, $mailingId);
        $this->logger->log("started session: {$mailingSession->getName()}, id: {$mailingSession->getId()}");

        $args = ['data' => ['mailing_session_id' => $mailingSession->getId()], 'worker' => MailingWorker::class];

        $worker = App::WORKER_PATH;
        $cmd = "php $worker " . escapeshellarg(json_encode($args)) . " > /dev/null 2>&1 &";
        exec($cmd);
        $this->logger->log("worker command: $cmd executed");

    }


    private function checkIsAlreadyRunning(): bool
    {
        $command = escapeshellarg('php '.App::WORKER_PATH);
        $processes = shell_exec("ps aux | grep $command | grep -v grep");
        return (bool)$processes;
    }
}