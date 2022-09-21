<?php

namespace JsonDB;


use JsonException;

class JsonDB
{
    public function __construct(private string $table)
    {
    }

    /**
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

    /**
     * @throws JsonException
     */
    public function add(string $idCol, string $idVal, array $data): void
    {
        $data[$idCol] = $idVal;

        $content   = $this->load();
        $content[] = $data;
        $this->save($content);
    }

    /**
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
     * @throws JsonException
     */
    public function deleteAll(): void
    {
        $this->save([]);
    }

    /**
     * @throws JsonException
     */
    public function get(string $idCol, string $idVal): array
    {
        $content = $this->load();
        foreach ($content as $row) {
            if ($row[$idCol] === $idVal) {
                return $row;
            }
        }
        return [];
    }

    /**
     * @throws JsonException
     */
    public function getContent(): array
    {
        return $this->load();
    }

    /**
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

    /**
     * @throws JsonException
     */
    private function load(): array
    {
        $filename = __DIR__ . '/../../tables/' . $this->table . '.json';
        if (!file_exists($filename)) {
            file_put_contents($filename, '[]');
        }

        $content = file_get_contents($filename);

        if ($content === false) {
            return [];
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    public function selectWhere(
        array $data = ['name' => '%something%', 'age' => '5%'],
        bool $allowLike = false,
        bool $sortBy = true,
        string $sortByColumn = 'id',
        string $sortByType = 'string',
        bool $descending = true,
    ): array {
        $content = $this->getContent();

        $result = [];

        foreach ($content as $row) {
            $addThisRow = true;
            foreach ($data as $column => $value) {
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
                    0       => str_contains($row[$column], $value),
                    1       => str_ends_with($row[$column], $value),
                    2       => str_starts_with($row[$column], $value),
                    default => $row[$column] === $value,
                };

                if (!$match) {
                    $addThisRow = false;
                    break;
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

    /**
     * @throws JsonException
     */
    private function save(array $content): void
    {
        $filename = __DIR__ . '/../../tables/' . $this->table . '.json';

        file_put_contents($filename, json_encode($content, JSON_THROW_ON_ERROR));
    }
}
