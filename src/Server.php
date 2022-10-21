<?php

namespace src;

use entity\Base\BaseEntity;

class Server
{
    private array $filesWorkedOn = [];

    public function work(bool $actuallyWork = true): void
    {
        while (true) {
            if (!file_exists(__DIR__ . '/../queue')) {
                if (!mkdir($concurrentDirectory = __DIR__ . '/../queue', 0777, true) && !is_dir($concurrentDirectory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
                }
            }
            $files = glob(__DIR__ . '/../queue/*.json');

            // Remove . and .. from the array
            $files = array_diff($files, ['.', '..']);

            // Work on each file depending on name (timestamp)
            // Work on the lowest/oldest timestamp first
            sort($files);

            foreach ($files as $file) {
                if (in_array($file, $this->filesWorkedOn, true)) {
                    continue;
                }

                $fileContent = file_get_contents($file);
                while($fileContent === '') {}

                $this->filesWorkedOn[] = $file;

                $this->workOnFile($fileContent, $actuallyWork);
                unlink($file);
            }
        }
    }

    public function showQueue(): void
    {
        $this->work(false);
    }

    /**
     * @throws \JsonException
     */
    private function workOnFile(string $fileContent, bool $actuallyWork): void
    {
        $fileContent = json_decode($fileContent, true, 512, JSON_THROW_ON_ERROR);

        $table  = $fileContent['table'];
        $data   = $fileContent['data'];

        if ($actuallyWork) {
            $this->execute($table, $data);
        }
    }

    /**
     * @throws \Exception
     */
    private function execute(string $table, array $data): void
    {
        $id = $data['id'];
        unset($data['id']);

        // Create an anonymous class that extends the BaseEntity Class
        $class = new class($id, $table) extends BaseEntity {
            public function __construct(string $id, string $tableName = null)
            {
                parent::__construct($id, $tableName);
            }
        };

        // Set the data
        foreach ($data as $key => $value) {
            $class->$key = $value;
        }

        $class->save(false);
    }
}
