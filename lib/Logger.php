<?php

namespace lib;

use config\App;

class Logger
{
    private string $logDir;
    private string $filePath;

    public function __construct()
    {
        $this->logDir = __DIR__ . '/../storage/logs';

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0766, true);
        }

        $this->filePath = $this->logDir . '/' . date('Y-m-d') . '.log';
    }

    public function log(string $message): void
    {
        $logLine = date('H:i:s') . ' - ' . $message . PHP_EOL;

        $r = file_put_contents($this->filePath, $logLine, FILE_APPEND);
    }
}