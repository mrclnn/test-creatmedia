<?php

include_once 'autoloader.php';

use lib\Logger;

$logger = new Logger();
try {

    $logger->log('worker started');

    if(!isset($argv[1])) throw new InvalidArgumentException('Not provided args for worker');

    $args = json_decode($argv[1], true);
    $workerClass = $args['worker'] ?? null;
    if(!class_exists($workerClass)) throw new InvalidArgumentException("worker class $workerClass not found");
    if(!isset($args['data'])) throw new InvalidArgumentException('worker arguments provided without data');

    $data = $args['data'];
    $worker = new $workerClass($data);
    if(!method_exists($worker, 'execute')) throw new RuntimeException("worker class $workerClass not contains execute method");

    $worker->execute();

} catch (Throwable $e){
    $logger->log("{$e->getMessage()} in file {$e->getFile()} at line {$e->getLine()}");
}
