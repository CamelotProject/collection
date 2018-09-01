<?php

declare(strict_types=1);

/*
 * This file is part of a Camelot Project package.
 *
 * (c) The Camelot Project
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Camelot\Collection\Tests;

use Camelot\Collection\Arr;
use Camelot\Collection\Bag;
use Camelot\Collection\MutableBag;
use Camelot\Collection\Tests\Fixtures\TestArrayLike;
use Camelot\Collection\Tests\Fixtures\TestBadDefinitionArrayLike;
use Camelot\Collection\Tests\Fixtures\TestBadLogicArrayLike;
use Camelot\Collection\Tests\Fixtures\TestBadReferenceExpressionArrayLike;
use Camelot\Collection\Tests\Fixtures\TestColumn;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Camelot\Collection\Arr.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ArrTest extends TestCase
{
    public function provideFrom()
    {
        return [
            'array' => [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
            ],
            'bag' => [
                new Bag(['foo' => 'bar']),
                ['foo' => 'bar'],
            ],
            'traversable' => [
                new \ArrayIterator(['foo' => 'bar']),
                ['foo' => 'bar'],
            ],
            'null' => [
                null,
                [],
            ],
            'stdClass' => [
                (object) ['foo' => 'bar'],
                ['foo' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider provideFrom
     *
     * @param $input
     * @param $expected
     */
    public function testFrom($input, $expected): void
    {
        $this->assertSame($expected, Arr::from($input));
    }

    public function testFromNonIterable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected an iterable. Got: Exception');

        Arr::from(new \Exception());
    }

    public function testFromRecursive(): void
    {
        $expected = [
            'foo'    => 'bar',
            'colors' => ['red', 'blue'],
            'items'  => ['hello', 'world'],
        ];
        $input = new Bag([
            'foo'    => 'bar',
            'colors' => new Bag(['red', 'blue']),
            'items'  => (object) ['hello', 'world'],
        ]);

        $this->assertSame($expected, Arr::fromRecursive($input));
    }

    public function testColumn(): void
    {
        $data = new \ArrayIterator([
            new TestColumn('foo', 'bar'),
            new TestColumn('hello', 'world'),
            ['id' => '5', 'value' => 'asdf'],
            new \ArrayObject(['id' => '6', 'value' => 'blue']),
            ['value' => 'no key is appended'], // skipped if missing column key. Appended if missing index key.
        ]);

        $result = Arr::column($data, null);
        $this->assertEquals($data->getArrayCopy(), $result);

        $result = Arr::column($data, 'id');
        $this->assertEquals(['foo', 'hello', '5', '6'], $result);

        $result = Arr::column($data, 'value', 'id');
        $expected = [
            'foo'   => 'bar',
            'hello' => 'world',
            '5'     => 'asdf',
            '6'     => 'blue',
            7       => 'no key is appended',
        ];
        $this->assertEquals($expected, $result);
    }

    public function provideGetSetHasInvalidArgs()
    {
        return [
            'data not accessible' => [new \EmptyIterator(), 'foo'],
        ];
    }

    /**
     * @dataProvider provideGetSetHasInvalidArgs
     */
    public function testHasInvalidArgs($data, $path): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Arr::has($data, $path);
    }

    /**
     * @dataProvider provideGetSetHasInvalidArgs
     */
    public function testGetInvalidArgs($data, $path): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Arr::get($data, $path);
    }

    /**
     * @dataProvider provideGetSetHasInvalidArgs
     */
    public function testSetInvalidArgs($data, $path): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Arr::set($data, $path, 'mixed');
    }

    public function provideGetSetHas()
    {
        return [
            'array' => [
                [
                    'foo'   => 'bar',
                    'baz'   => 'remove',
                    'items' => [
                        'nested' => [
                            'hello' => 'world',
                            'bye'   => 'earth',
                        ],
                        'obj' => new \EmptyIterator(),
                    ],
                ],
            ],

            'array access' => [
                new \ArrayObject([
                    'foo'   => 'bar',
                    'baz'   => 'remove',
                    'items' => new \ArrayObject([
                        'nested' => new \ArrayObject([
                            'hello' => 'world',
                            'bye'   => 'earth',
                        ]),
                        'obj' => new \EmptyIterator(),
                    ]),
                ]),
            ],

            'user array access' => [
                new TestArrayLike([
                    'foo'   => 'bar',
                    'baz'   => 'remove',
                    'items' => new TestArrayLike([
                        'nested' => new TestArrayLike([
                            'hello' => 'world',
                            'bye'   => 'earth',
                        ]),
                        'obj' => new \EmptyIterator(),
                    ]),
                ]),
            ],

            'mixed' => [
                [
                    'foo'   => 'bar',
                    'baz'   => 'remove',
                    'items' => new \ArrayObject([
                        'nested' => [
                            'hello' => 'world',
                            'bye'   => 'earth',
                        ],
                        'obj' => new \EmptyIterator(),
                    ]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testHas($data): void
    {
        $this->assertTrue(Arr::has($data, 'foo'));
        $this->assertTrue(Arr::has($data, 'items'));
        $this->assertTrue(Arr::has($data, 'items/nested/hello'));

        $this->assertFalse(Arr::has($data, 'derp'));
        $this->assertFalse(Arr::has($data, 'items/obj/bad'));
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testGet($data): void
    {
        $this->assertEquals('bar', Arr::get($data, 'foo'));
        $this->assertEquals('world', Arr::get($data, 'items/nested/hello'));

        $this->assertEquals('default', Arr::get($data, 'derp', 'default'));
        $this->assertEquals('default', Arr::get($data, 'items/derp', 'default'));
        $this->assertEquals('default', Arr::get($data, 'derp/nope/whoops', 'default'));
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testSet($data): void
    {
        Arr::set($data, 'color', 'red');
        $this->assertEquals('red', $data['color']);

        Arr::set($data, '[]', 'first');
        $this->assertEquals('first', $data[0]);
        Arr::set($data, '[]', 'second');
        $this->assertEquals('second', $data[1]);

        Arr::set($data, 'items/nested/color', 'blue');
        $this->assertEquals('blue', $data['items']['nested']['color']);

        Arr::set($data, 'items/nested/new/point', 'bolt');
        $this->assertEquals('bolt', $data['items']['nested']['new']['point']);

        Arr::set($data, 'items/nested/list/[]', 'first');
        $this->assertEquals('first', $data['items']['nested']['list'][0]);
        Arr::set($data, 'items/nested/list/[]', 'second');
        $this->assertEquals('second', $data['items']['nested']['list'][1]);
    }

    public function testSetNestedInaccessibleObject(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot set \'a/foo\', because \'a\' is already set and not an array or an object implementing ArrayAccess.');

        $data = [
            'a' => new \EmptyIterator(),
        ];

        Arr::set($data, 'a/foo', 'bar');
    }

    public function provideSetArraysReturnedByReferenceError()
    {
        return [
            'bad definition' => [TestBadDefinitionArrayLike::class],
            'bad logic'      => [TestBadLogicArrayLike::class],
            'bad expression' => [TestBadReferenceExpressionArrayLike::class],
        ];
    }

    /**
     * Test that Arr::set can determine which objects can return arrays by reference.
     *
     * Test that Arr::set throws exception when trying to indirectly modify an ArrayAccess object.
     * This happens when one tries get a reference to an array inside an AA object.
     * Ex: A/B/C where A is an AA object. B is an array.
     *
     * @dataProvider provideSetArraysReturnedByReferenceError
     *
     * @param string $cls
     */
    public function testSetArraysReturnedByReferenceError($cls): void
    {
        $data = [
            'a' => new $cls(),
        ];

        $e = null;
        try {
            Arr::set($data, 'a/foo/bar', 'baz');
        } catch (\Exception $e) {
        } catch (\Throwable $e) {
        }

        if ($e instanceof \RuntimeException) {
            $this->assertEquals(
                "Cannot set 'a/foo/bar', because 'a' is an " . ltrim($cls, '\\') .
                ' which does not return arrays by reference from its offsetGet() method. See ' . MutableBag::class .
                ' for an example of how to do this.',
                $e->getMessage()
            );
        } else {
            $this->fail("Arr::set should've thrown a RuntimeException");
        }
    }

    /**
     * @dataProvider provideGetSetHas
     *
     * @param array|\ArrayAccess $data
     */
    public function testRemove($data): void
    {
        $this->assertSame('remove', Arr::remove($data, 'baz', 'default'));
        $this->assertSame('default', Arr::remove($data, 'baz', 'default'));

        $this->assertSame('earth', Arr::remove($data, 'items/nested/bye', 'default'));
        $this->assertSame('default', Arr::remove($data, 'items/nested/bye', 'default'));
    }

    public function testIsAccessible(): void
    {
        $this->assertTrue(Arr::isAccessible([]));
        $this->assertTrue(Arr::isAccessible(new \ArrayObject()));

        $this->assertFalse(Arr::isAccessible(new \EmptyIterator()));
    }

    public function provideIsIndexed()
    {
        return [
            'key value pairs'                  => [['key' => 'value'], false],
            'empty array'                      => [[], true],
            'list'                             => [['foo', 'bar'], true],
            'zero-indexed numeric int keys'    => [[0 => 'foo', 1 => 'bar'], true],
            'zero-indexed numeric string keys' => [['0' => 'foo', '1' => 'bar'], true],
            'non-zero-indexed keys'            => [[1 => 'foo', 2 => 'bar'], false],
            'non-sequential keys'              => [[0 => 'foo', 2 => 'bar'], false],
        ];
    }

    /**
     * @dataProvider provideIsIndexed
     *
     * @param array $array
     * @param bool  $indexed
     */
    public function testIsIndexedAndAssociative($array, $indexed): void
    {
        $this->assertEquals($indexed, Arr::isIndexed($array));
        $this->assertEquals(!$indexed, Arr::isAssociative($array));

        $traversable = new \ArrayObject($array);
        $this->assertEquals($indexed, Arr::isIndexed($traversable));
        $this->assertEquals(!$indexed, Arr::isAssociative($traversable));
    }

    public function testNonArraysAreNotIndexed(): void
    {
        $this->expectException(\TypeError::class);
        $this->assertFalse(Arr::isAssociative('derp'));
    }

    public function testNonArraysAreNotAssociative(): void
    {
        $this->expectException(\TypeError::class);
        $this->assertFalse(Arr::isAssociative('derp'));
    }

    public function testMapRecursive(): void
    {
        $arr = [
            'foo' => new \ArrayObject([
                'bar' => 'HELLO',
            ]),
        ];

        $actual = Arr::mapRecursive($arr, 'strtolower');

        $expected = [
            'foo' => [
                'bar' => 'hello',
            ],
        ];

        $this->assertSame($expected, $actual);
    }

    public function provideReplaceRecursive()
    {
        return [
            'scalar replaces scalar (no duh)'         => [
                ['a' => ['b' => 'foo']],
                ['a' => ['b' => 'bar']],
                ['a' => ['b' => 'bar']],
            ],
            'second adds to first (no duh)'           => [
                ['a' => ['b' => 'foo']],
                ['a' => ['c' => 'bar']],
                ['a' => ['b' => 'foo', 'c' => 'bar']],
            ],
            'list replaces list completely'           => [
                ['a' => ['foo', 'bar']],
                ['a' => ['baz']],
                ['a' => ['baz']],
            ],
            'null replaces scalar'                    => [
                ['a' => ['b' => 'foo']],
                ['a' => ['b' => null]],
                ['a' => ['b' => null]],
            ],
            'null ignores arrays (both types)'        => [
                ['a' => ['b' => 'foo']],
                ['a' => null],
                ['a' => ['b' => 'foo']],
            ],
            'empty list replaces arrays (both types)' => [
                ['a' => ['foo', 'bar']],
                ['a' => []],
                ['a' => []],
            ],
            'scalar replaces arrays (both types)'     => [
                ['a' => ['foo', 'bar']],
                ['a' => 'derp'],
                ['a' => 'derp'],
            ],
            'traversable'                             => [
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'foo'           => 'bar',
                        'hello'         => 'world',
                        'dont override' => new \ArrayObject(['for reals']),
                    ]),
                ]),
                new \ArrayObject([
                    'a' => new \ArrayObject([
                        'foo'           => 'baz',
                        'dont override' => null,
                    ]),
                ]),
                [
                    'a' => [
                        'foo'           => 'baz', // replaced value
                        'hello'         => 'world', // untouched pair
                        'dont override' => ['for reals'], // null didn't overwrite list
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideReplaceRecursive
     *
     * @param array $array1
     * @param array $array2
     * @param array $result
     */
    public function testReplaceRecursive($array1, $array2, $result): void
    {
        $this->assertEquals($result, Arr::replaceRecursive($array1, $array2));
    }

    public function testFlatten(): void
    {
        $result = Arr::flatten([[1, 2], [[3]], 4]);

        $this->assertSame([1, 2, [3], 4], $result);
    }

    public function testFlattenDeep(): void
    {
        $result = Arr::flatten([[1, 2], [[3]], 4], INF);

        $this->assertSame([1, 2, 3, 4], $result);
    }
}
