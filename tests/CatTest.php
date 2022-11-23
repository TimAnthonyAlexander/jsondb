<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use src\entity\Example\CatEntity;

class CatTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    public function testCat(): void
    {
        $cat = CatEntity::create(uniqid(true, true));

        $cat->name = 'Felix';
        $cat->breed = 'Persian';
        $cat->color = 'Black';
        $cat->owner = 'John Doe';
        $cat->save();

        self::assertTrue($cat->exists());
        self::assertSame(0, $cat->getAge());

        $cat->increaseAge(true);
        self::assertSame(1, $cat->getAge());

        $cat->delete();
        self::assertFalse($cat->exists());
    }
}
