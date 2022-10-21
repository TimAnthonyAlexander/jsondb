<?php

/*
 * Copyright (c) 2022 5ever.
 */

namespace entity\Base;

use Exception;
use JsonException;
use src\JsonDB;

abstract class BaseEntity
{
    protected JsonDB $jsonDB;

    /**
     * @param string $id
     */
    public function __construct(public readonly string $id)
    {
        $tableName    = self::getEntityName();
        $this->jsonDB = new JsonDB($tableName);
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
     */
    public static function getAll(): array
    {
        return (new static(''))->getTable();
    }


    /**
     * @return array
     */
    public function getTable(): array
    {
        return $this->jsonDB->getContent();
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
        if ($this->id === 'null') {
            throw new \Exception('Empty id cannot be saved to database.');
        }

        if (method_exists($this, 'validate')) {
            if (!$this->validate()) {
                throw new \Exception('Validation failed.');
            }
        }

        try {
            $this->jsonDB->changeSelect('id', $this->id, $this->toArray());
        } catch (JsonException $e) {
            throw new \Exception($e->getMessage());
        }

        $this->load();
    }

    /**
     * @return void
     */
    public function delete(): void
    {
        $this->jsonDB->delete('id', $this->id);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->jsonDB->exists('id', $this->id);
    }

    /**
     * @throws \Exception
     */
    protected function load(): void
    {
        $this->jsonDB->clearCache();
        $content = $this->jsonDB->get('id', $this->id, true);

        if ($content === []) {
            return;
        }

        foreach ($content as $column => $data) {
            if (!property_exists($this, $column)) {
                throw new \Exception('Column ' . $column . ' does not exist in ' . static::class);
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
        $this->jsonDB->deleteAll();
    }

    public function deleteCertain(string ...$ids): void
    {
        $this->jsonDB->deleteCertain(...$ids);
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
