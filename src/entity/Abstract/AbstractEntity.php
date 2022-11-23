<?php

/*
 * Copyright (c) 2022 5ever.
 */

namespace entity\Base;

use backend\module\ProfilerService;
use Exception;
use JsonException;
use RuntimeException;
use src\Client;
use src\JSDB;
use src\JsonDB;
use src\Promise;

abstract class AbstractEntity
{
    protected JSDB $jsdb;

    /**
     * @param string $id
     * @param string|null $tableName
     * @throws Exception
     */
    public function __construct(public readonly string $id, string $tableName = null)
    {
        $tableName  ??= self::getEntityName();
        $this->jsdb = new JSDB($tableName, $id);
        $this->load();
    }

    /**
     * @return JsonDB
     */
    private static function createJsonDB(): JsonDB
    {
        return new JsonDB(self::getEntityName());
    }

    /**
     * @return string
     */
    public static function getEntityName(): string
    {
        $exploded         = explode('\\', static::class);
        $className        = end($exploded);
        $singleEntityName = mb_strtolower(substr($className, 0, -6));
        $singleEntityName = rtrim($singleEntityName, 's');
        return $singleEntityName . 's';
    }

    /**
     * @param array $data
     * @param int $page
     * @param int $perpage
     * @return array
     */
    private static function paginate(array $data, int $page = 1, int $perpage = 3): array
    {
        $page    = max($page, 1);
        $perpage = max($perpage, 1);
        $offset  = ($page - 1) * $perpage;
        return array_slice($data, $offset, $perpage);
    }

    /**
     * @return array
     * @throws JsonException
     */
    public static function getAll(): array
    {
        return (new static(''))->jsdb->readMerged();
    }

    /**
     * @param int $page
     * @param int $perpage
     * @param array $where
     * @param bool $sortBy
     * @param string $sortByColumn
     * @param string $sortByType
     * @param bool $descending
     * @param bool $allowLike
     * @param bool $and
     * @return array
     * @throws JsonException
     */
    public static function getPage(
        int $page = 1,
        int $perpage = 3,
        array $where = [],
        bool $sortBy = true,
        string $sortByColumn = 'id',
        string $sortByType = 'string',
        bool $descending = true,
        bool $allowLike = false,
        bool $and = true,
    ): array {
        return self::paginate(self::createJsonDB()->selectWhere($where, $allowLike, $sortBy, $sortByColumn, $sortByType, $descending, $and), $page, $perpage);
    }

    /**
     * @param array $where
     * @param bool $sortBy
     * @param string $sortByColumn
     * @param string $sortByType
     * @param bool $descending
     * @param bool $allowLike
     * @param bool $and
     * @return array
     * @throws JsonException
     */
    public static function getOne(
        array $where = [],
        bool $sortBy = true,
        string $sortByColumn = 'id',
        string $sortByType = 'string',
        bool $descending = true,
        bool $allowLike = false,
        bool $and = true,
    ): array {
        return self::getPage(1, 1, $where, $sortBy, $sortByColumn, $sortByType, $descending, $allowLike, $and)[0] ?? [];
    }

    /**
     * @param int $perpage
     * @param array $where
     * @param bool $allowLike
     * @return float
     */
    public static function getPageCount(
        int $perpage = 3,
        array $where = [],
        bool $allowLike = false,
    ): float {
        return ceil(count(self::createJsonDB()->selectWhere($where, $allowLike)) / $perpage);
    }

    public static function create(string $identifier = null): static
    {
        return new static($identifier ?? 'null');
    }

    public function save(): void
    {
        $this->jsdb->update($this->toArray());
    }

        /**
     * @return void
     * @throws JsonException
     */
    public function delete(): void
    {
        $this->jsdb->delete('id', $this->id);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->jsdb->exists('id', $this->id);
    }

    /**
     * @throws \Exception
     */
    protected function load(): void
    {
        $this->jsdb->loadFromDatabase();
        $content = $this->jsdb->getData();

        if ($content === []) {
            return;
        }

        foreach ($content as $column => $data) {
            if (!property_exists($this, $column)) {
                continue;
            }
            if ($column === 'id') {
                continue;
            }
            $this->$column = $data;
        }
    }

    /**
     * @return void
     */
    public function deleteAll(): void
    {
        $this->jsdb->deleteAll();
    }

    public function deleteCertain(string ...$ids): void
    {
        // Foreach create a new JsonDB instance and delete the data
        foreach ($ids as $id) {
            $jsdb = new JSDB(self::getEntityName(), $id);
            $jsdb->delete();
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $properties = get_object_vars($this);
        unset($properties['jsonDB']);
        return $properties;
    }
}
