<?php

namespace src;

use JsonException;

class Client
{
    private string $queueFolder;

    public function __construct() {
        $this->queueFolder = __DIR__ . '/../queue';
        if (!file_exists($this->queueFolder) && !mkdir($this->queueFolder, 0777, true) && !is_dir($this->queueFolder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->queueFolder));
        }
    }

    /**
     * @throws JsonException
     */
    public function add(
        string $table,
        array $data,
        int $priority = 10
    ): Promise {
        $prioFolder = $this->queueFolder . '/' . $priority;
        if(!file_exists($prioFolder) && !mkdir($prioFolder, 0777, true) && !is_dir($prioFolder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $prioFolder));
        }

        $fileName = sprintf($prioFolder . '/%s.json', self::generateTimestamp());
        while(file_exists($fileName)) {
            $fileName = sprintf($prioFolder . '/%s.json', self::generateTimestamp());
        }

        $file = fopen($fileName, 'ab');
        fwrite($file, json_encode(
            [
                'table'  => $table,
                'data'   => $data,
            ], JSON_THROW_ON_ERROR
        ));
        fclose($file);

        return new Promise($fileName);
    }

    private static function generateTimestamp(): string
    {
        return (string) round(microtime(true) * 1000);
    }
}
