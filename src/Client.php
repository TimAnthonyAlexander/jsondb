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
    ): Promise {
        $fileName = sprintf($this->queueFolder . '/%s.json', self::generateTimestamp());
        while(file_exists($fileName)) {
            $fileName = sprintf($this->queueFolder . '/%s.json', self::generateTimestamp());
        }

        $file = fopen($fileName, 'wb');
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
