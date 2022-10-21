<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use src\entity\Example\DogEntity;

class DogTest extends TestCase
{
    public function testDog(): void
    {
        $dog = DogEntity::create(uniqid(true, true));

        $dog->name = 'Fido';
        $dog->breed = 'Poodle';
        $dog->color = 'White';
        $dog->owner = 'John Doe';
        $dog->save()->wait();

        self::assertTrue($dog->exists());
        self::assertSame(0, $dog->getAge());

        $dog->increaseAge(true);
        self::assertSame(1, $dog->getAge());

        $dog->delete();
        self::assertFalse($dog->exists());
    }
}
