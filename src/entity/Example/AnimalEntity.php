<?php

namespace src\entity\Example;

use entity\Base\AbstractEntity;
use entity\Base\BaseEntity;

abstract class AnimalEntity extends AbstractEntity
{
    public string $name;
    public string $owner;
    protected int $age = 0;

    /**
     * @return int
     */
    public function getAge(): int
    {
        return $this->age;
    }

    public function increaseAge(
        bool $saveImmediately = true
    ): void {
        $this->age++;

        if ($saveImmediately) {
            $this->save();
        }
    }
}
