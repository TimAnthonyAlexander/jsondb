<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use src\entity\Example\CatEntity;
use src\entity\Example\DogEntity;

class PerformanceTest extends TestCase
{
    public function testCatPerformance(): void
    {
        $start = microtime(true);
        $timeToTest = 2; // Seconds
        $iterations = 0;

        $temp = [];

        $cat = CatEntity::create();

        while (microtime(true) - $start < $timeToTest) {
            $cat = CatEntity::create(uniqid(true, true));
            $cat->name = 'Felix';
            $cat->breed = 'Persian';
            $cat->color = 'Black';
            $cat->owner = 'John Doe';
            $cat->save()->wait();
            $temp[] = $cat->id;
            $iterations++;
        }

        $cat->deleteCertain(...$temp);

        self::assertGreaterThan(0, $iterations);
    }

    public function testDogPerformance(): void
    {
        $start = microtime(true);
        $timeToTest = 2; // Seconds
        $iterations = 0;

        $temp = [];

        $dog = DogEntity::create();

        while (microtime(true) - $start < $timeToTest) {
            $dog = DogEntity::create(uniqid(true, true));
            $dog->name = 'Fido';
            $dog->breed = 'Poodle';
            $dog->color = 'White';
            $dog->owner = 'John Doe';
            $dog->save()->wait();
            $temp[] = $dog->id;
            $iterations++;
        }

        $dog->deleteCertain(...$temp);

        self::assertGreaterThan(0, $iterations);
    }
}
