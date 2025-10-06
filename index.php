<?php

// веб сервер настроен так что все запросы приходят на index.php (см. .htaccess)

include_once 'lib/autoloader.php';

use Controllers\UploadUsersController;
use Errors\ApiError;
use Errors\UnknownRouteError;
use lib\Logger;

$logger = new Logger();

try {

    $routeName = $_SERVER['REQUEST_URI'];
    $response = [
        'success' => false,
        'error' => null,
        'message' => null
    ];
    switch ($routeName) {
        case UploadUsersController::ROUTE_NAME:
            (new UploadUsersController())->handle();
            $response['message'] = 'Users successfully uploaded';
            break;

//        case '/api/another-route.php':
//            // другие роуты когда возникнет необходимость
//            break;

        default:
            throw new UnknownRouteError("Unknown route $routeName");

    }

    http_response_code(200);
    $response['success'] = true;
    echo json_encode($response);

} catch (ApiError $e) {
    // пишем логи если нужно + кастомная обработка при необходимости
    http_response_code(400);
    // т.к. наследуемся от кастомной ApiError уверены что сообщения об ошибках френдли для юзера апи и отдаем как есть
    $response['error'] = $e->getMessage();
    $logger->log("{$e->getMessage()} in file {$e->getFile()} at line {$e->getLine()}");
    echo json_encode($response);

} catch (Throwable $e) {
    // пишем логи если нужно + кастомная обработка при необходимости
    http_response_code(500);
    // $e->getMessage() отправляем в логи, а юзеру api нельзя знать что произошло на сервере
    $response['error'] = 'Unknown error';
    $logger->log("{$e->getMessage()} in file {$e->getFile()} at line {$e->getLine()}");
    echo json_encode($response);

}




