<?php

/*
 * Copyright (c) 2022 5ever.
 */

namespace src;

use JsonException;

class JsonDB
{
    /**
     * @param string $table
     */
    public function __construct(public readonly string $table)
    {
    }

    /**
     * @param string $idCol
     * @param string $idVal
     * @param string $column
     * @param mixed $value
     * @return void
     * @throws JsonException
     */
    public function change(string $idCol, string $idVal, string $column, mixed $value): void
    {
        $content = $this->load();

        // Search through each row of $content until $content[$idCol] = $idVal
        foreach ($content as $row) {
            if ($row[$idCol] === $idVal) {
                // Only change if the value is different
                if ($row[$column] !== $value) {
                    $row[$column] = $value;
                    $this->save($content);
                    return;
                }
                return;
            }
        }
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
     * @param string $idCol
     * @param string $idVal
     * @return bool
     * @throws JsonException
     */
    public function exists(string $idCol, string $idVal): bool
    {
        $content = $this->load();

        // Search through each row of $content until $content[$idCol] = $idVal
        foreach ($content as $row) {
            if ($row[$idCol] === $idVal) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws BackendException
     * @throws JsonException
     */
    public function changeSelect(string $idCol, string $idVal, array $data): void
    {
        $content = $this->load();

        // Search through each row of $content until $content[$idCol] = $idVal
        foreach ($content as $rowId => $row) {
            if ($row[$idCol] === $idVal) {
                // Only change if the value is different
                foreach ($data as $column => $value) {
                    if ($row[$column] !== $value) {
                        $content[$rowId][$column] = $value;
                    }
                }
                $this->save($content);
                return;
            }
        }

        $this->add($idCol, $idVal, $data);
    }

    public function add(string $idCol, string $idVal, array $data): void
    {
        $data[$idCol] = $idVal;

        $content   = $this->load();
        $content[] = $data;
        $this->save($content);
    }

    /**
     * @param string $idCol
     * @param string $idVal
     * @return void
     * @throws JsonException
     */
    public function delete(string $idCol, string $idVal): void
    {
        $content    = $this->load();
        $newContent = [];
        foreach ($content as $row) {
            if ($row[$idCol] !== $idVal) {
                $newContent[] = $row;
            }
        }
        $this->save($newContent);
    }

    /**
     * @return void
     * @throws JsonException
     */
    public function deleteAll(): void
    {
        $this->save([]);
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        InstantCache::delete('jsondb_' . $this->table);
    }

    /**
     * @param string $idCol
     * @param string $idVal
     * @param bool $useCache
     * @return array
     * @throws JsonException
     */
    public function get(string $idCol, string $idVal, bool $useCache = false): array
    {
        if ($useCache && InstantCache::isset('jsondb_' . $this->table)) {
            $content = InstantCache::get('jsondb_' . $this->table);
        } else {
            $content = $this->load();
            InstantCache::set('jsondb_' . $this->table, $content);
        }

        foreach ($content as $row) {
            if ($row[$idCol] === $idVal) {
                return $row;
            }
        }
        return [];
    }

    /**
     * @return array
     * @throws JsonException
     */
    public function getContent(): array
    {
        return $this->load();
    }

    /**
     * @param string $idCol
     * @param string $idVal
     * @param string ...$columns
     * @return array
     * @throws JsonException
     */
    public function getSelect(string $idCol, string $idVal, string ...$columns): array
    {
        $content = $this->load();
        foreach ($content as $row) {
            if ($row[$idCol] === $idVal) {
                $result = [];
                foreach ($columns as $column) {
                    $result[$column] = $row[$column] ?? throw new \Exception('Column not given for row.');
                }
                return $result;
            }
        }
        return [];
    }

    public function deleteCertain(string ...$ids): void
    {
        $content = $this->load();
        foreach ($content as $rowId => $row) {
            if (in_array($row['id'], $ids, true)) {
                unset($content[$rowId]);
            }
        }
        $this->save($content);
    }

    /**
     * @throws JsonException
     */
    private function load(): array
    {
        $dirname = __DIR__ . '/../tables/';
        if (!file_exists($dirname)) {
            if (!mkdir($dirname) && !is_dir($dirname)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
            }
        }
        $filename = __DIR__ . '/../tables/' . $this->table . '.json';
        if (!file_exists($filename)) {
            file_put_contents($filename, '[]');
        }

        $content = file_get_contents($filename);

        if ($content === false) {
            return [];
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array $where
     * @param bool $allowLike
     * @param bool $sortBy
     * @param string $sortByColumn
     * @param string $sortByType
     * @param bool $descending
     * @param bool $whereIsAnd
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
    ): array {
        $content = $this->getContent();

        $result = [];

        foreach ($content as $row) {
            $addThisRow = $whereIsAnd || $where === [];
            foreach ($where as $column => $value) {
                $matchType = match (true) {
                    !$allowLike                                                => 3,
                    str_starts_with($value, '%') && str_ends_with($value, '%') => 0,
                    str_starts_with($value, '%')                               => 1,
                    str_ends_with($value, '%')                                 => 2,
                    default                                                    => 3,
                };

                $value = match ($matchType) {
                    0       => substr($value, 1, -1),
                    1       => substr($value, 1),
                    2       => substr($value, 0, -1),
                    default => $value,
                };

                $match = match ($matchType) {
                    0       => str_contains(mb_strtolower($row[$column] ?? ''), mb_strtolower($value)),
                    1       => str_ends_with(mb_strtolower($row[$column] ?? ''), mb_strtolower($value)),
                    2       => str_starts_with(mb_strtolower($row[$column] ?? ''), mb_strtolower($value)),
                    default => mb_strtolower($row[$column] ?? '') === mb_strtolower($value),
                };

                if ($whereIsAnd) {
                    if (!$match) {
                        $addThisRow = false;
                        break;
                    }
                } else {
                    if ($match) {
                        $addThisRow = true;
                        break;
                    }
                }
            }

            if ($addThisRow) {
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

        return $result;
    }

    private function save(array $content): void
    {
        $filename = __DIR__ . '/../tables/' . $this->table . '.json';
        file_put_contents($filename, json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
        $this->clearCache();
    }
}
