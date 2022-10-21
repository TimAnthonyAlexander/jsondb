<?php

namespace src;

use entity\Base\BaseEntity;

class Server
{
    private array $filesWorkedOn = [];

    public function work(bool $actuallyWork = true): void
    {
        while(true) {
            for ($i = 1; $i <= 10; $i++) {
                for ($j = 1; $j <= $i; $j++) {
                    $this->workOnPriority($j, $actuallyWork);
                }
            }
        }
    }

    public function showQueue(): void
    {
        $this->work(false);
    }

    private function workOnPriority(int $priority, bool $actuallyWork): void
    {
        $files = glob(__DIR__.sprintf('/../queue/%d/*.json', $priority));

        // Remove . and .. from the array
        $files = array_diff($files, ['.', '..']);

        // Work on each file depending on name (timestamp)
        // Work on the lowest/oldest timestamp first
        sort($files);

        foreach ($files as $file) {
            if (in_array($file, $this->filesWorkedOn, true)) {
                continue;
            }

            $this->filesWorkedOn[] = $file;

            $this->workOnFile($file, $actuallyWork);
            print "Working on file $file" . PHP_EOL;
        }
    }

    /**
     * @throws \JsonException
     */
    private function workOnFile(string $file, bool $actuallyWork): void
    {
        $fileContent = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        $table  = $fileContent['table'];
        $data   = $fileContent['data'];

        if ($actuallyWork) {
            $this->execute($table, $data);
            unlink($file);
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
