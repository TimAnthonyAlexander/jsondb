<?php

namespace src;

use JsonException;
use RuntimeException;

class JSDB
{
    private array $data = [];

    //////// SINGLE

    /**
     * @param string $table
     * @param string|null $id
     */
    public function __construct(
        public string $table,
        public ?string $id = null,
    ) {
    }

    /**
     * @throws JsonException
     */
    public function loadFromDatabase(): void
    {
        if ($this->id === null) {
            return;
        }

        $file = self::createFileName($this->table ?? '', $this->id);

        if (!file_exists($file)) {
            $this->data = [];
            return;
        }

        $this->data = json_decode(file_get_contents($file) ?: '[]', true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string $table
     * @param string $id
     * @return string
     */
    public static function createFileName(
        string $table,
        string $id,
    ): string {
        $table = str_replace('/', '', $table);
        $id    = str_replace('/', '', $id);

        $baseDir = __DIR__ . '/../jsdb/';

        if (!is_dir($baseDir) && (!mkdir($baseDir) && !is_dir($baseDir))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $baseDir));
        }

        $file = $baseDir . $table . '/' . $id . '.json';

        if (!is_dir($baseDir . $table) && (!mkdir($baseDir . $table) && !is_dir($baseDir . $table))) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $baseDir . $table));
        }

        return $file;
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function save(): void
    {
        if ($this->id === null) {
            return;
        }

        if (!isset($GLOBALS['saves'])) {
            $GLOBALS['saves'] = [];
        }

        $GLOBALS['saves'][] = $this->table;

        $file = self::createFileName($this->table, $this->id);
        file_put_contents($file, json_encode($this->data, JSON_THROW_ON_ERROR));
    }

    /**
     * @param array $data
     * @return void
     * @throws JsonException
     */
    public function update(array $data): void
    {
        $this->data = $data;
        $this->save();
    }

    /**
     * @return void
     */
    public function delete(): void
    {
        if ($this->id === null) {
            return;
        }

        $file = self::createFileName($this->table, $this->id);
        unlink($file);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        if ($this->id === null) {
            return false;
        }

        $file = self::createFileName($this->table, $this->id);
        return file_exists($file);
    }

    ///
    //////// MERGED
    ///

    /**
     * @throws JsonException
     */
    private function mergeData(): array
    {
        // Read all files in the table folder
        $baseDir   = __DIR__ . '/../jsdb/' . $this->table . '/';
        $fileNames = array_diff(scandir($baseDir) ?: [], ['..', '.', 'merged.json', 'merged.json.lock', '.gitignore']);

        $data = [];

        foreach ($fileNames as $fileName) {
            $path     = $baseDir . $fileName;
            $fileData = json_decode(file_get_contents($path) ?: '[]', true, 512, JSON_THROW_ON_ERROR);
            if ($fileData === []) {
                continue;
            }

            if (!isset($fileData['id'])) {
                throw new RuntimeException('Missing id in file ' . $path . ' for table ' . $this->table);
            }
            $data[$fileData['id']] = $fileData;
        }

        return $data;
    }

    /**
     * @return array
     */
    public static function getTableFolders(): array
    {
        $baseDir = __DIR__ . '/../jsdb/';
        $folders = scandir($baseDir);
        return array_diff($folders ?: [], ['.', '..', '.gitignore']);
    }

    /**
     * @throws JsonException
     */
    public function writeMerged(): void
    {
        $data = $this->mergeData();

        $lockFile = __DIR__ . '/../jsdb/' . $this->table . '/merged.json.lock';

        if (file_exists($lockFile)) {
            return;
        }

        file_put_contents($lockFile, '1');

        $mergedFile = __DIR__ . '/../jsdb/' . $this->table . '/merged.json';

        file_put_contents($mergedFile, json_encode($data, JSON_THROW_ON_ERROR));

        unlink($lockFile);
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function readMerged(): array
    {
        if (InstantCache::isset('merged_' . $this->table)) {
            return InstantCache::get('merged_' . $this->table);
        }

        while (file_exists(__DIR__ . '/../jsdb/' . $this->table . '/merged.json.lock')) {
            usleep(1);
        }

        $mergedFile = __DIR__ . '/../jsdb/' . $this->table . '/merged.json';

        if (!is_file($mergedFile)) {
            return [];
        }

        $return = json_decode(file_get_contents($mergedFile) ?: '[]', true, 512, JSON_THROW_ON_ERROR);

        InstantCache::set('merged_' . $this->table, $return);

        return $return;
    }

    /**
     * @return void
     */
    public function deleteAll(): void
    {
        $baseDir   = __DIR__ . '/../jsdb/' . $this->table . '/';
        $fileNames = array_diff(scandir($baseDir) ?: [], ['..', '.', 'merged.json', 'merged.json.lock', '.gitignore']);

        foreach ($fileNames as $fileName) {
            $path = $baseDir . $fileName;
            unlink($path);
        }
    }

    /**
     * @param array $where
     * @param bool $allowLike
     * @param bool $sortBy
     * @param string $sortByColumn
     * @param string $sortByType
     * @param bool $descending
     * @param bool $whereIsAnd
     * @param array $furtherWheres
     * @param bool $returnOnlyIds
     * @return array
     * @throws JsonException
     */
    public function selectWhere(
        array $where = [],
        bool $allowLike = false,
        bool $sortBy = true,
        string $sortByColumn = 'id',
        string $sortByType = 'string',
        bool $descending = true,
        bool $whereIsAnd = true,
        array $furtherWheres = [],
        bool $returnOnlyIds = true,
        int $page = 1,
        int $perPage = 5
    ): array {
        $md5 = md5(json_encode(func_get_args(), JSON_THROW_ON_ERROR) . $this->table ?: '');

        if (InstantCache::isset('selectWhere_' . $md5)) {
            return InstantCache::get('selectWhere_' . $md5);
        }

        $content = $this->readMerged();

        $result = [];

        $element         = 0;
        $startingElement = ($page - 1) * $perPage;
        $endingElement   = $startingElement + $perPage;

        foreach ($content as $row) {
            if ($element >= $endingElement) {
                break;
            }

            if ($element < $startingElement) {
                $element++;
                continue;
            }

            $addThisRow = $whereIsAnd || $where === [];
            foreach ($where as $column => $value) {
                $matchType = $this->getMatchType($allowLike, $value);

                $value = $this->getTrimmedValue($matchType, $value);

                if (!isset($row[$column])) {
                    $addThisRow = false;
                    break;
                }

                $match = $this->isMatch($matchType, $row[$column], $value);

                if ($whereIsAnd) {
                    if (!$match) {
                        $addThisRow = false;
                        break;
                    }
                } elseif ($match) {
                    $addThisRow = true;
                    break;
                }
            }

            foreach ($furtherWheres as $furtherWhere) {
                if (!is_array($furtherWhere)) {
                    throw new RuntimeException('furtherWhere must be an array');
                }
                foreach ($furtherWhere as $column => $value) {
                    $matchType = $this->getMatchType($allowLike, $value);

                    $value = $this->getTrimmedValue($matchType, $value);

                    $match = $this->isMatch($matchType, $row[$column], $value);

                    if (!$match) {
                        $addThisRow = false;
                        break;
                    }
                }
            }

            if ($addThisRow) {
                $element++;
                $result[] = $row;
            }
        }

        if ($sortBy) {
            $result = match ($sortByType) {
                'int'    => self::sortByInt($sortByColumn, $result, $descending),
                'date'   => self::sortByDate($sortByColumn, $result, $descending),
                'string' => self::sortByString($sortByColumn, $result, $descending),
                default  => $result,
            };
        }

        $results = $returnOnlyIds ? array_column($result, 'id') : $result;

        InstantCache::set('selectWhere_' . $md5, $results);

        return $results;
    }

    /**
     * @param bool $allowLike
     * @param mixed $value
     * @return int
     */
    private function getMatchType(bool $allowLike, mixed $value): int
    {
        return match (true) {
            !$allowLike                                                => 3,
            str_starts_with($value, '%') && str_ends_with($value, '%') => 0,
            str_starts_with($value, '%')                               => 1,
            str_ends_with($value, '%')                                 => 2,
            default                                                    => 3,
        };
    }

    /**
     * @param int $matchType
     * @param mixed $value
     * @return mixed
     */
    private function getTrimmedValue(int $matchType, mixed $value): mixed
    {
        return match ($matchType) {
            0       => substr($value, 1, -1),
            1       => substr($value, 1),
            2       => substr($value, 0, -1),
            default => $value,
        };
    }

    /**
     * @param int $matchType
     * @param string|null $row
     * @param mixed $value
     * @return bool
     */
    private function isMatch(int $matchType, ?string $row, mixed $value): bool
    {
        return match ($matchType) {
            0       => str_contains(mb_strtolower($row ?? ''), mb_strtolower($value)),
            1       => str_ends_with(mb_strtolower($row ?? ''), mb_strtolower($value)),
            2       => str_starts_with(mb_strtolower($row ?? ''), mb_strtolower($value)),
            default => mb_strtolower($row ?? '') === mb_strtolower($value),
        };
    }

    /**
     * @param string $column
     * @param array $data
     * @param bool $descending
     * @return array
     */
    public static function sortByInt(string $column, array $data, bool $descending = true): array
    {
        usort(
            $data,
            static function ($a, $b) use ($column) {
                return $a[$column] <=> $b[$column];
            }
        );
        return $descending ? array_reverse($data) : $data;
    }

    /**
     * @param string $column
     * @param array $data
     * @param bool $descending
     * @return array
     */
    public static function sortByDate(string $column, array $data, bool $descending = true): array
    {
        usort(
            $data,
            static function ($a, $b) use ($column) {
                return strtotime($a[$column]) <=> strtotime($b[$column]);
            }
        );
        return $descending ? array_reverse($data) : $data;
    }

    /**
     * @param string $column
     * @param array $data
     * @param bool $descending
     * @return array
     */
    public static function sortByString(string $column, array $data, bool $descending = true): array
    {
        usort(
            $data,
            static function ($a, $b) use ($column) {
                return strcmp($a[$column], $b[$column]);
            }
        );
        return $descending ? array_reverse($data) : $data;
    }

    /**
     * @param string $identifierColumn
     * @param string $identifierValue
     * @param array $replaceData
     * @return void
     * @throws JsonException
     */
    public function change(
        string $identifierColumn,
        string $identifierValue,
        array $replaceData,
    ): void {
        $select = $this->selectWhere(
            [$identifierColumn => $identifierValue],
            false,
            false,
        );

        foreach ($select as $id) {
            $self = new self($this->table, $id);
            $self->loadFromDatabase();
            $self->update($replaceData);
            $self->save();
        }
    }

    /**
     * @param string $identifierColumn
     * @param string $identifierValue
     * @return void
     * @throws JsonException
     */
    public function deleteByColumn(
        string $identifierColumn,
        string $identifierValue,
    ): void {
        $select = $this->selectWhere(
            [$identifierColumn => $identifierValue],
            false,
            false,
        );

        foreach ($select as $id) {
            $file = self::createFileName($this->table, $id);
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
