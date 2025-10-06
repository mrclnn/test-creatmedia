<?php

namespace Controllers;

use Errors\BadFileError;
use Errors\MethodNotAllowedError;
use lib\DB;
use lib\Logger;
use RuntimeException;
use Throwable;

class UploadUsersController
{

    private Logger $logger;

    const ROUTE_NAME = '/api/upload-users.php';
    const TABLE_NAME = 'users_for_mailing';
    const ROUTE_TYPE = 'POST';

    public function __construct()
    {
        $this->logger = new Logger();
    }


    /**
     * @throws BadFileError
     * @throws MethodNotAllowedError
     */
    public function handle()
    {

        $this->validate();
        $path = $this->saveFile();
        $this->storeFileInDB($path);

    }

    private function storeFileInDB(string $path)
    {

        $usersFile = fopen($path, 'r');
        if(!$usersFile) throw new RuntimeException("Unable to read file $path");

        $usersFromFile = [];
        while ($row = fgetcsv($usersFile)) {
            $usersFromFile[] = $row;
        }

        $db = new DB();
        //todo файл может быть очень большим, мы не хотим нагружать базу одиночными insert
        // используем пакетную вставку по 500 записей (можно отконфигурировать)
        // ретраи и разбитие по чанкам логичнее абстрагировать в DB классе
        // чтобы не заниматься этим в управляющем контроллер-классе
        foreach(array_chunk($usersFromFile, DB::MAX_INSERT_CHUNK_SIZE) as $usersChunk){

            $notStoredInDB = true;
            $attempt = 1;
            $maxAttempts = 10;
            while($notStoredInDB){

                if($attempt > $maxAttempts) throw new RuntimeException("Unable to insert chunk of records, max attempts reached");

                try {
                    $success = $db->massInsert(self::TABLE_NAME, ['number', 'name'], $usersChunk);
                    if(!$success) throw new RuntimeException("Unable to insert chunk of records");
                    $notStoredInDB = false;
                } catch (Throwable $e){
                    //пишем логи
                    $this->logger->log("{$e->getMessage()} in file {$e->getFile()} at line {$e->getLine()}");
                    $attempt++;
                }
            }
        }

    }

    /**
     * @return void
     * @throws MethodNotAllowedError
     * @throws BadFileError
     */
    private function validate(): void
    {
        $this->checkMethod();
        $this->checkFile();
    }

    private function saveFile(): string
    {

        $uploadDir = __DIR__ . '/../storage/uploads'; //todo это в константу или .env куда-нибудь
        $file = $_FILES['file'];

        if (!is_dir($uploadDir)) {
            // поступаем в зависимости от обстоятельств.
            // вообще отсутствие важной папки это критичная поблема сервера, нужно выяснять причины.
            // если контроллер просто тихо создаст внезапно пропавшую папку - может стать хуже,
            // т.к. проблему сложнее станет обнаружить

//            throw new RuntimeException("Not found storage for upload: $uploadDir");

            // для простоты тестового просто создадим ненайденную папку
            mkdir($uploadDir, 0766, true);
        }

        $path = "$uploadDir/" . time() . '-' . rand(0,1000) . ' ' . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $path)) {
            // сюда логи, невозможность сохранить успешно полученный файл - довольно серьезно
            throw new RuntimeException("Unable to save file");
        }
        return $path;
    }

    /**
     * @return void
     * @throws MethodNotAllowedError
     */
    private function checkMethod(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if($requestMethod !== self::ROUTE_TYPE) {
            $errorMessage = sprintf(
                'Method %s not allowed for route %s, try %s',
                $requestMethod,
                self::ROUTE_NAME,
                self::ROUTE_TYPE
            );
            throw new MethodNotAllowedError($errorMessage);
        }
    }

    /**
     * @throws BadFileError
     */
    private function checkFile()
    {
        if (!isset($_FILES['file'])) {
            throw new BadFileError('File not found, please attach .csv file to request');
        }

        $file = $_FILES['file'];
        $size = $_FILES['file']['size'];

        $uploadMax = ini_get('upload_max_filesize');
        $postMax   = ini_get('post_max_size');
        $fileLimit = min($uploadMax, $postMax);

        // Проверяем расширение .csv
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'csv') {
            throw new BadFileError("Uploaded file has to be .scv, received: $extension");
        }

        switch ($file['error']){

            // все ок - заканчиваем проверку
            case UPLOAD_ERR_OK:
                return;
            // проблемы с размером файла
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new BadFileError("File too large, allowed: $fileLimit, received: $size");

            // проблемы с сетью и загрузкой файла
            case UPLOAD_ERR_PARTIAL:
            case UPLOAD_ERR_NO_FILE:
                throw new BadFileError("File not loaded, try again");

            // проблемы со структурой папок или правами на сервере. это серьезно, нужно писать логи, уведомлять и срочно фиксить,
            // юзеру не обязательно знать какая конкретно на сервере беда
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new RuntimeException("Unable to load file, check tmp directory");
            case UPLOAD_ERR_CANT_WRITE:
                throw new RuntimeException("Unable to load file, check permissions");
            default:
                // все серьезно, ничего не понятно, нужно логгировать и тщательно проверять что случилось в этой ветке
                throw new RuntimeException("Unknown error");
        }
    }
}