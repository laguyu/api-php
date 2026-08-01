<?php

namespace App\Core;

class Logger
{
    public function __construct(private string $logPath = __DIR__ . '/../../logs/app.log')
    {
    }

    public function error(string $message, array $context = []): void
    {
        $contextText = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $line = '[' . date('c') . '] ERROR ' . $message . $contextText . PHP_EOL;

        $directory = dirname($this->logPath);
        $isWritableDirectory = is_dir($directory) || @mkdir($directory, 0777, true);

        if ($isWritableDirectory && @file_put_contents($this->logPath, $line, FILE_APPEND | LOCK_EX) !== false) {
            return;
        }

        // Serverless/read-only fallback (Vercel): send logs to platform stderr.
        error_log(trim($line));
    }
}
