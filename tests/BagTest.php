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

use ArrayObject;
use Camelot\Collection\Bag;
use Camelot\Collection\MutableBag;
use PHPUnit\Framework\TestCase;

class BagTest extends TestCase
{
    /** @var string|Bag class used for static creation methods and instance of assertions */
    protected $cls = Bag::class;

    protected function createBag($items = [])
    {
        return new Bag($items);
    }

    // region Creation / Unwrapping Methods

    public function testOf(): void
    {
        /** @var Bag $cls */
        $cls = $this->cls;
        $bag = $cls::of('red', 'blue', []);

        $this->assertSame(['red', 'blue', []], $bag->toArray());
    }

    public function provideFromAndToArray()
    {
        return [
            'bag'         => [$this->createBag(['foo' => 'bar'])],
            'traversable' => [new ArrayObject(['foo' => 'bar'])],
            'null'        => [null, []],
            'stdClass'    => [json_decode(json_encode(['foo' => 'bar']))],
            'array'       => [['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider provideFromAndToArray
     *
     * @param array $output
     */
    public function testFromAndToArray($input, $output = ['foo' => 'bar']): void
    {
        $cls = $this->cls;
        $actual = $cls::from($input)->toArray();
        $this->assertSame($output, $actual);
    }

    public function testFromRecursive(): void
    {
        $a = [
            'foo'       => 'bar',
            'items'     => new ArrayObject(['hello' => 'world']),
            'std class' => json_decode(json_encode([
                'why use' => 'these',
            ])),
        ];

        $cls = $this->cls;
        $bag = $cls::fromRecursive($a);

        $bagArr = $bag->toArray();
        $this->assertEquals('bar', $bagArr['foo']);

        $this->assertInstanceOf($cls, $bagArr['items']);
        /** @var Bag $items */
        $items = $bagArr['items'];
        $this->assertEquals(['hello' => 'world'], $items->toArray());

        $this->assertInstanceOf($cls, $bagArr['std class']);
        /** @var Bag $stdClass */
        $stdClass = $bagArr['std class'];
        $this->assertEquals(['why use' => 'these'], $stdClass->toArray());
    }

    public function testToArrayRecursive(): void
    {
        $bag = $this->createBag([
            'foo'    => 'bar',
            'colors' => $this->createBag(['red', 'blue']),
            'array'  => ['hello', 'world'],
        ]);
        $expected = [
            'foo'    => 'bar',
            'colors' => ['red', 'blue'],
            'array'  => ['hello', 'world'],
        ];

        $arr = $bag->toArrayRecursive();

        $this->assertEquals($expected, $arr);
    }

    public function testCombine(): void
    {
        $cls = $this->cls;
        $actual = $cls::combine(['red', 'green'], ['bad', 'good'])->toArray();
        $expected = [
            'red'   => 'bad',
            'green' => 'good',
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testCombineEmpty(): void
    {
        $cls = $this->cls;
        $actual = $cls::combine([], [])->toArray();

        $this->assertEquals([], $actual);
    }

    public function testCombineDifferentSizes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $cls = $this->cls;
        $cls::combine(['derp'], ['wait', 'wut']);
    }

    // endregion

    // region Methods returning a single value

    public function testHas(): void
    {
        $bag = $this->createBag(['foo' => 'bar', 'null' => null]);

        $this->assertTrue($bag->has('foo'));
        $this->assertTrue($bag->has('null'));

        $this->assertFalse($bag->has('derp'));
    }

    public function testHasPath(): void
    {
        $bag = $this->createBag([
            'items' => new ArrayObject([
                'foo' => 'bar',
            ]),
        ]);

        $this->assertTrue($bag->hasPath('items/foo'));

        $this->assertFalse($bag->hasPath('items/derp'));
        $this->assertFalse($bag->hasPath('derp'));
    }

    public function testHasItem(): void
    {
        $foo = new ArrayObject();
        $bag = $this->createBag([
            'foo'   => 'bar',
            'items' => $foo,
        ]);

        $this->assertTrue($bag->hasItem('bar'));
        $this->assertTrue($bag->hasItem($foo));

        $this->assertFalse($bag->hasItem('derp'));
    }

    public function testGet(): void
    {
        $bag = $this->createBag([
            'foo'  => 'bar',
            'null' => null,
        ]);

        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertNull($bag->get('null', 'default'));
        $this->assertEquals('default', $bag->get('derp', 'default'));
    }

    public function testGetPath(): void
    {
        $bag = $this->createBag([
            'items' => new ArrayObject([
                'foo' => 'bar',
            ]),
        ]);

        $this->assertEquals('bar', $bag->getPath('items/foo'));

        $this->assertNull($bag->getPath('derp/derp'));
        $this->assertEquals('default', $bag->getPath('derp/derp', 'default'));
    }

    public function testCount(): void
    {
        $bag = $this->createBag(['foo', 'bar']);

        $this->assertCount(2, $bag);
    }

    public function testEmpty(): void
    {
        $bag = $this->createBag(['foo', 'bar']);
        $this->assertFalse($bag->isEmpty());

        $empty = $this->createBag();
        $this->assertTrue($empty->isEmpty());
    }

    public function testFirst(): void
    {
        $bag = $this->createBag(['first', 'second']);
        $this->assertEquals('first', $bag->first());

        $empty = $this->createBag();
        $this->assertNull($empty->first());
    }

    public function testLast(): void
    {
        $bag = $this->createBag(['first', 'second']);
        $this->assertEquals('second', $bag->last());

        $empty = $this->createBag();
        $this->assertNull($empty->last());
    }

    public function testJoin(): void
    {
        $bag = $this->createBag(['first', 'second', 'third']);
        $this->assertEquals('first, second, third', $bag->join(', '));

        $empty = $this->createBag();
        $this->assertEquals('', $empty->join(', '));
    }

    public function testSum(): void
    {
        $bag = $this->createBag([3, 4]);
        $this->assertEquals(7, $bag->sum());

        $empty = $this->createBag();
        $this->assertEquals(0, $empty->sum());

        $dumb = $this->createBag(['wut']);
        $this->assertEquals(0, $dumb->sum());
    }

    public function testProduct(): void
    {
        $bag = $this->createBag([3, 4]);
        $this->assertEquals(12, $bag->product());

        $empty = $this->createBag();
        $this->assertEquals(1, $empty->product());

        $dumb = $this->createBag(['wut']);
        $this->assertEquals(0, $dumb->product());
    }

    /**
     * @dataProvider \Camelot\Collection\Tests\ArrTest::provideIsIndexed
     *
     * @param array $data
     * @param bool  $isIndexed
     */
    public function testIsAssociativeAndIndexed($data, $isIndexed): void
    {
        $bag = $this->createBag($data);

        $this->assertEquals($isIndexed, $bag->isIndexed());
        $this->assertEquals(!$isIndexed, $bag->isAssociative());
    }

    public function provideIndexOf()
    {
        return [
            //  0  1  2  3  4  5
            // [a, b, c, a, b, c]
            // [expected index, item to find, starting index]
            'first item, starting default'     => [0, 'a'],
            'first item, starting first index' => [0, 'a', 0],
            'first item, starting last index'  => [null, 'a', 5],
            'first item, starting max'         => [null, 'a', 100],
            'first item, starting min'         => [0, 'a', -100],
            'first item, starting mid neg'     => [3, 'a', -4],
            'first item, starting mid neg @'   => [3, 'a', -2],
            'first item, starting mid pos'     => [3, 'a', 1],
            'first item, starting mid pos @'   => [3, 'a', 3],

            'last item, starting default'     => [2, 'c'],
            'last item, starting first index' => [2, 'c', 0],
            'last item, starting last index'  => [5, 'c', 5],
            'last item, starting max'         => [5, 'c', 100],
            'last item, starting min'         => [2, 'c', -100],
            'last item, starting mid neg'     => [2, 'c', -4],
            'last item, starting mid neg @'   => [2, 'c', -3],
            'last item, starting mid pos'     => [2, 'c', 1],
            'last item, starting mid pos @'   => [2, 'c', 2],

            'empty, starting default' => [null, '', 0, []],
            'empty, starting pos'     => [null, '', 2, []],
            'empty, starting neg'     => [null, '', -2, []],

            'associative' => ['foo', 'bar', 0, ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider provideIndexOf
     *
     * @param $expectedIndex
     * @param $item
     * @param $fromIndex
     * @param $items
     */
    public function testIndexOf($expectedIndex, $item, $fromIndex = 0, $items = null): void
    {
        $bag = $this->createBag($items !== null ? $items : ['a', 'b', 'c', 'a', 'b', 'c']);

        $this->assertSame($expectedIndex, $bag->indexOf($item, $fromIndex));
    }

    public function provideLastIndexOf()
    {
        return [
            //  0  1  2  3  4  5
            // [a, b, c, a, b, c]
            // [expected index, item to find, starting index]
            'last item, starting default'     => [5, 'c'],
            'last item, starting first index' => [null, 'c', 0],
            'last item, starting last index'  => [5, 'c', 5],
            'last item, starting max'         => [5, 'c', 100],
            'last item, starting min'         => [null, 'c', -100],
            'last item, starting mid neg'     => [2, 'c', -1],
            'last item, starting mid neg @'   => [2, 'c', -3],
            'last item, starting mid pos'     => [2, 'c', 3],
            'last item, starting mid pos @'   => [2, 'c', 2],

            'first item, starting default'     => [3, 'a'],
            'first item, starting first index' => [0, 'a', 0],
            'first item, starting last index'  => [3, 'a', 5],
            'first item, starting max'         => [3, 'a', 100],
            'first item, starting min'         => [0, 'a', -100],
            'first item, starting mid neg'     => [3, 'a', -1],
            'first item, starting mid neg @'   => [3, 'a', -2],
            'first item, starting mid pos'     => [3, 'a', 4],
            'first item, starting mid pos @'   => [3, 'a', 3],

            'empty, starting default' => [null, '', null, []],
            'empty, starting pos'     => [null, '', 2, []],
            'empty, starting neg'     => [null, '', -2, []],

            'associative' => ['foo', 'bar', null, ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider provideLastIndexOf
     *
     * @param $expectedIndex
     * @param $item
     * @param $fromIndex
     * @param $items
     */
    public function testLastIndexOf($expectedIndex, $item, $fromIndex = null, $items = null): void
    {
        $bag = $this->createBag($items !== null ? $items : ['a', 'b', 'c', 'a', 'b', 'c']);

        $this->assertSame($expectedIndex, $bag->lastIndexOf($item, $fromIndex));
    }

    public function testFind(): void
    {
        [$bag, $matchBs, $b1, $b2] = $this->findSetup();

        $this->assertSame($b1, $bag->find($matchBs));
        $this->assertSame($b2, $bag->find($matchBs, 3));
        $this->assertSame($b2, $bag->find($matchBs, -2));
        $this->assertNull($bag->find($matchBs, 5));
    }

    public function testFindLast(): void
    {
        [$bag, $matchBs, $b1, $b2] = $this->findSetup();

        $this->assertSame($b2, $bag->findLast($matchBs));
        $this->assertSame($b1, $bag->findLast($matchBs, 3));
        $this->assertSame($b1, $bag->findLast($matchBs, -2));
        $this->assertNull($bag->findLast($matchBs, 0));
    }

    public function testFindKey(): void
    {
        [$bag, $matchBs] = $this->findSetup();

        $this->assertSame(1, $bag->findKey($matchBs));
        $this->assertSame(4, $bag->findKey($matchBs, 3));
        $this->assertSame(4, $bag->findKey($matchBs, -3));
        $this->assertNull($bag->findKey($matchBs, 5));
    }

    public function testFindLastKey(): void
    {
        [$bag, $matchBs] = $this->findSetup();

        $this->assertSame(4, $bag->findLastKey($matchBs));
        $this->assertSame(1, $bag->findLastKey($matchBs, 3));
        $this->assertSame(1, $bag->findLastKey($matchBs, -3));
        $this->assertNull($bag->findKey($matchBs, 5));
    }

    protected function findSetup()
    {
        $bag = $this->createBag([
            $a1 = (object) ['name' => 'a'],
            $b1 = (object) ['name' => 'b'],
            $c1 = (object) ['name' => 'c'],
            $a2 = clone $a1,
            $b2 = clone $b1,
            $c2 = clone $c1,
        ]);

        $matchBs = function ($item) {
            return $item->name === 'b';
        };

        return [$bag, $matchBs, $b1, $b2];
    }

    public function testRandomValue(): void
    {
        $bag = $this->createBag(['red']);

        $this->assertSame('red', $bag->randomValue());
    }

    public function testRandomValueEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $bag = $this->createBag([]);

        $bag->randomValue();
    }

    public function testRandomKey(): void
    {
        $bag = $this->createBag(['red']);

        $this->assertSame(0, $bag->randomKey());
    }

    public function testRandomKeyEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $bag = $this->createBag([]);

        $bag->randomKey();
    }

    // endregion

    // region Methods returning a new bag

    public function testCall(): void
    {
        $bag = $this->createBag(['red', 'blue']);

        $result = $bag->call(
            function (array $colors, $arg1) {
                $colors[] = 'green';
                $colors[] = $arg1;

                return $colors;
            },
            'black'
        );

        $this->assertBagResult(['red', 'blue', 'green', 'black'], $bag, $result);
    }

    public function testMutable(): void
    {
        $bag = $this->createBag(['foo' => 'bar']);

        $mutable = $bag->mutable();

        $this->assertNotSame($bag, $mutable);
        $this->assertInstanceOf(MutableBag::class, $mutable);
        $this->assertEquals(['foo' => 'bar'], $mutable->toArray());
    }

    public function testImmutable(): void
    {
        $bag = $this->createBag(['foo', 'bar']);

        $immutable = $bag->immutable();

        $this->assertNotSame($bag, $immutable);
        $this->assertInstanceOf(Bag::class, $immutable);
        $this->assertEquals(['foo', 'bar'], $immutable->toArray());
    }

    public function testKeys(): void
    {
        $bag = $this->createBag(['foo' => 'bar', 'hello' => 'world']);

        $keys = $bag->keys();

        $this->assertBagResult(['foo', 'hello'], $bag, $keys);
    }

    public function testValues(): void
    {
        $bag = $this->createBag(['foo' => 'bar', 'hello' => 'world']);

        $values = $bag->values();

        $this->assertBagResult(['bar', 'world'], $bag, $values);
    }

    public function testMap(): void
    {
        $bag = $this->createBag(['foo' => 'bar', 'hello' => 'world']);

        $actual = $bag->map(function ($key, $item) {
            return $key . '.' . $item;
        });

        $this->assertBagResult(['foo' => 'foo.bar', 'hello' => 'hello.world'], $bag, $actual);
    }

    public function testMapKeys(): void
    {
        $bag = $this->createBag(['foo' => 'bar', 'hello' => 'world']);

        $actual = $bag->mapKeys(function ($key, $item) {
            return $key . '.' . $item;
        });

        $this->assertBagResult(['foo.bar' => 'bar', 'hello.world' => 'world'], $bag, $actual);
    }

    public function testFilter(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'hello', 'world']);

        $actual = $bag->filter(function ($key, $item) {
            return $item !== 'bar' && $key !== 2;
        });

        $this->assertBagResult([0 => 'foo', 3 => 'world'], $bag, $actual);
    }

    public function testReject(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'hello', 'world']);

        $actual = $bag->reject(function ($key, $item) {
            return $item !== 'bar' && $key !== 2;
        });

        $this->assertBagResult([1 => 'bar', 2 => 'hello'], $bag, $actual);
    }

    public function testClean(): void
    {
        $bag = $this->createBag([null, '', 'foo', false, 0, true, [], ['bar']]);

        $actual = $bag->clean();

        $this->assertBagResult([2 => 'foo', 5 => true, 7 => ['bar']], $bag, $actual);
    }

    public function testReplace(): void
    {
        $bag = $this->createBag(['foo' => 'bar']);

        $actual = $bag->replace(['foo' => 'baz', 'hello' => 'world']);

        $this->assertBagResult(['foo' => 'baz', 'hello' => 'world'], $bag, $actual);
    }

    /**
     * @dataProvider \Camelot\Collection\Tests\ArrTest::provideReplaceRecursive
     *
     * @param array $array1
     * @param array $array2
     * @param array $expected
     */
    public function testReplaceRecursive($array1, $array2, $expected): void
    {
        $cls = $this->cls;
        $bag = $cls::from($array1);

        $actual = $bag->replaceRecursive($array2);

        $this->assertBagResult($expected, $bag, $actual);
    }

    public function testDefaults(): void
    {
        $bag = $this->createBag(['foo' => 'bar']);

        $actual = $bag->defaults(['foo' => 'baz', 'hello' => 'world']);

        $this->assertBagResult(['foo' => 'bar', 'hello' => 'world'], $bag, $actual);
    }

    /**
     * @dataProvider \Camelot\Collection\Tests\ArrTest::provideReplaceRecursive
     *
     * @param array $array1
     * @param array $array2
     * @param array $expected
     */
    public function testDefaultsRecursive($array1, $array2, $expected): void
    {
        $cls = $this->cls;
        $bag = $cls::from($array2);

        $actual = $bag->defaultsRecursive($array1);

        $this->assertBagResult($expected, $bag, $actual);
    }

    public function testMerge(): void
    {
        $bag = $this->createBag(['foo', 'bar']);

        $actual = $bag->merge(['hello', 'world']);

        $this->assertBagResult(['foo', 'bar', 'hello', 'world'], $bag, $actual);
    }

    public function provideSlice()
    {
        return [
            [0,  null, false, ['foo', 'bar', 'hello', 'world']],
            [1,  null, false,        ['bar', 'hello', 'world']],
            [1,     2, false,        ['bar', 'hello']],
            [-2, null, false,               ['hello', 'world']],
            [1,    -1, false,        ['bar', 'hello']],
            [-2,   -1, false,               ['hello']],
            [1,     2,  true, [1 => 'bar', 2 => 'hello']],
        ];
    }

    /**
     * @dataProvider provideSlice
     *
     * @param int      $offset
     * @param int|null $length
     * @param bool     $preserveKeys
     * @param array    $expected
     */
    public function testSlice($offset, $length, $preserveKeys, $expected): void
    {
        $bag = $this->createBag(['foo', 'bar', 'hello', 'world']);

        $actual = $bag->slice($offset, $length, $preserveKeys);

        $this->assertBagResult($expected, $bag, $actual);
    }

    public function testPartition(): void
    {
        $bag = $this->createBag(['foo' => 'bar', 'hello' => 'world']);

        $actual = $bag->partition(function ($key, $item) {
            return strpos($item, 'a') !== false;
        });

        $this->assertIsArray($actual);
        $this->assertCount(2, $actual);

        [$trueBag, $falseBag] = $actual;
        $this->assertBagResult(['foo' => 'bar'], $bag, $trueBag);
        $this->assertBagResult(['hello' => 'world'], $bag, $falseBag);
    }

    public function testColumn(): void
    {
        $bag = $this->createBag([
            ['id' => 'foo', 'value' => 'bar'],
            ['id' => 'hello', 'value' => 'world'],
        ]);

        $actual = $bag->column('id');
        $this->assertBagResult(['foo', 'hello'], $bag, $actual);

        $actual = $bag->column('value', 'id');
        $this->assertBagResult(['foo' => 'bar', 'hello' => 'world'], $bag, $actual);
    }

    public function testFlip(): void
    {
        $bag = $this->createBag(['foo' => 'bar', 'hello' => 'world', 'second' => 'world']);

        $actual = $bag->flip();

        $this->assertBagResult(['bar' => 'foo', 'world' => 'second'], $bag, $actual);
    }

    public function testFlipEmpty(): void
    {
        $bag = $this->createBag([]);

        $actual = $bag->flip();

        $this->assertBagResult([], $bag, $actual);
    }

    public function testFlipObjects(): void
    {
        $this->expectException(\LogicException::class);

        $this->createBag([new \stdClass()])->flip();
    }

    public function testReduce(): void
    {
        $bag = $this->createBag([1, 2, 3, 4]);

        $product = $bag->reduce(
            function ($carry, $item) {
                return $carry * $item;
            },
            1
        );

        $this->assertEquals(24, $product);
    }

    public function testUnique(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'foo', 3, '3', '3a', '3']);
        $actual = $bag->unique();
        $this->assertBagResult(['foo', 'bar', 3, '3', '3a'], $bag, $actual);

        $first = $this->createBag();
        $second = $this->createBag();
        $bag = $this->createBag([$first, $second, $first]);
        $actual = $bag->unique();
        $this->assertBagResult([$first, $second], $bag, $actual);
    }

    public function testChunk(): void
    {
        $bag = $this->createBag(['a', 'b', 'c', 'd', 'e']);

        $chunked = $bag->chunk(2);

        $this->assertInstanceOf($this->cls, $chunked);
        $this->assertNotSame($bag, $chunked);
        $this->assertCount(3, $chunked);

        $this->assertBagResult(['a', 'b'], $bag, $chunked->get(0));
        $this->assertBagResult(['c', 'd'], $bag, $chunked->get(1));
        $this->assertBagResult(['e'], $bag, $chunked->get(2));
    }

    public function testChunkPreserveKeys(): void
    {
        $bag = $this->createBag(['a', 'b', 'c', 'd', 'e']);

        $chunked = $bag->chunk(2, true);

        $this->assertInstanceOf($this->cls, $chunked);
        $this->assertNotSame($bag, $chunked);
        $this->assertCount(3, $chunked);

        $this->assertBagResult(['a', 'b'], $bag, $chunked->get(0));
        $this->assertBagResult([2 => 'c', 3 => 'd'], $bag, $chunked->get(1));
        $this->assertBagResult([4 => 'e'], $bag, $chunked->get(2));
    }

    public function testPadRight(): void
    {
        $bag = $this->createBag([1, 2]);

        $padded = $bag->pad(4, null);

        $this->assertBagResult([1, 2, null, null], $bag, $padded);
    }

    public function testPadLeft(): void
    {
        $bag = $this->createBag([1, 2]);

        $padded = $bag->pad(-4, null);

        $this->assertBagResult([null, null, 1, 2], $bag, $padded);
    }

    public function testPadNone(): void
    {
        $bag = $this->createBag([1, 2]);

        $padded = $bag->pad(2, null);

        $this->assertBagResult([1, 2], $bag, $padded);
    }

    public function testCountValues(): void
    {
        $bag = $this->createBag(
            [
                'red',
                'red',
                'blue',
            ]
        );

        $actual = $bag->countValues();
        $this->assertBagResult(['red' => 2, 'blue' => 1], $bag, $actual);
    }

    public function testFlatten(): void
    {
        $bag = $this->createBag([[1, 2], [[3]], 4]);

        $result = $bag->flatten();

        $this->assertBagResult([1, 2, [3], 4], $bag, $result);
    }

    // endregion

    // region Comparison Methods

    public function testPick(): void
    {
        $bag = $this->createBag(['a' => 'red', 'b' => 'blue', 'c' => 'green']);

        $actual = $bag->pick('a', 'c');
        $this->assertBagResult(['a' => 'red', 'c' => 'green'], $bag, $actual);

        $actual = $bag->pick(['a', 'c']);
        $this->assertBagResult(['a' => 'red', 'c' => 'green'], $bag, $actual);

        $actual = $bag->pick($this->createBag(['a', 'c']));
        $this->assertBagResult(['a' => 'red', 'c' => 'green'], $bag, $actual);
    }

    public function testOmit(): void
    {
        $bag = $this->createBag(['a' => 'red', 'b' => 'blue', 'c' => 'green']);

        $actual = $bag->omit('a', 'c');
        $this->assertBagResult(['b' => 'blue'], $bag, $actual);

        $actual = $bag->omit(['a', 'c']);
        $this->assertBagResult(['b' => 'blue'], $bag, $actual);

        $actual = $bag->omit($this->createBag(['a', 'c']));
        $this->assertBagResult(['b' => 'blue'], $bag, $actual);
    }

    public function testDiff(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'baz']);

        $actual = $bag->diff(['bar']);

        $this->assertBagResult([0 => 'foo', 2 => 'baz'], $bag, $actual);
    }

    public function testDiffComparator(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'baz']);

        $actual = $bag->diff(['bar'], [$this, 'compareFirstTwoLetters']);

        $this->assertBagResult(['foo'], $bag, $actual);
    }

    public function testDiffBy(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'baz']);

        $actual = $bag->diffBy(['bar'], [$this, 'getFirstTwoLetters']);

        $this->assertBagResult(['foo'], $bag, $actual);
    }

    public function testDiffKeys(): void
    {
        $bag = $this->createBag(['foo' => 'red', 'bar' => 'blue', 'baz' => 'green']);

        $actual = $bag->diffKeys(['bar' => 'black']);

        $this->assertBagResult(['foo' => 'red', 'baz' => 'green'], $bag, $actual);
    }

    public function testDiffKeysComparator(): void
    {
        $bag = $this->createBag(['foo' => 'red', 'bar' => 'blue', 'baz' => 'green']);

        $actual = $bag->diffKeys(['bar' => 'black'], [$this, 'compareFirstTwoLetters']);

        $this->assertBagResult(['foo' => 'red'], $bag, $actual);
    }

    public function testDiffKeysBy(): void
    {
        $bag = $this->createBag(['foo' => 'red', 'bar' => 'blue', 'baz' => 'green']);

        $actual = $bag->diffKeysBy(['bar' => 'black'], [$this, 'getFirstTwoLetters']);

        $this->assertBagResult(['foo' => 'red'], $bag, $actual);
    }

    public function testIntersect(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'baz']);

        $actual = $bag->intersect(['bar', 'nope']);

        $this->assertBagResult([1 => 'bar'], $bag, $actual);
    }

    public function testIntersectComparator(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'baz']);

        $actual = $bag->intersect(['bar', 'nope'], [$this, 'compareFirstTwoLetters']);

        $this->assertBagResult([1 => 'bar', 2 => 'baz'], $bag, $actual);
    }

    public function testIntersectBy(): void
    {
        $bag = $this->createBag(['foo', 'bar', 'baz']);

        $actual = $bag->intersectBy(['bar', 'nope'], [$this, 'getFirstTwoLetters']);

        $this->assertBagResult([1 => 'bar', 2 => 'baz'], $bag, $actual);
    }

    public function testIntersectKeys(): void
    {
        $bag = $this->createBag(['foo' => 'red', 'bar' => 'blue', 'baz' => 'green']);

        $actual = $bag->intersectKeys(['bar' => 'black', 'nope' => 'red']);

        $this->assertBagResult(['bar' => 'blue'], $bag, $actual);
    }

    public function testIntersectKeysComparator(): void
    {
        $bag = $this->createBag(['foo' => 'red', 'bar' => 'blue', 'baz' => 'green']);

        $actual = $bag->intersectKeys(['bar' => 'black', 'nope' => 'red'], [$this, 'compareNormal']);

        $this->assertBagResult(['bar' => 'blue'], $bag, $actual);
    }

    public function testIntersectKeysBy(): void
    {
        $bag = $this->createBag(['foo' => 'red', 'bar' => 'blue', 'baz' => 'green']);

        $identity = function ($item) {
            return $item;
        };

        $actual = $bag->intersectKeysBy(['bar' => 'black', 'nope' => 'red'], $identity);

        $this->assertBagResult(['bar' => 'blue'], $bag, $actual);
    }

    public function compareNormal($a, $b)
    {
        if ($a === $b) {
            return 0;
        }

        return $a > $b ? 1 : -1;
    }

    public function compareFirstTwoLetters($a, $b)
    {
        $a = $this->getFirstTwoLetters($a);
        $b = $this->getFirstTwoLetters($b);

        if ($a === $b) {
            return 0;
        }

        return $a > $b ? 1 : -1;
    }

    public function getFirstTwoLetters($item)
    {
        return substr($item, 0, 2);
    }

    // endregion

    // region Sorting Methods

    public function testSortValuesAsc(): void
    {
        $bag = $this->createBag([4, 'hi' => 3, 1, 2]);

        $sorted = $bag->sort();

        $this->assertBagResult([1, 2, 3, 4], $bag, $sorted);
    }

    public function testSortValuesAscNaturalIgnoreCase(): void
    {
        $bag = $this->createBag(
            [
                'img12.png',
                'img1.png',
                'iMg2.png',
                'img10.png',
            ]
        );

        $sorted = $bag->sort(SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);

        $expected = [
            'img1.png',
            'iMg2.png',
            'img10.png',
            'img12.png',
        ];
        $this->assertBagResult($expected, $bag, $sorted);
    }

    public function testSortValuesAscPreserveKeys(): void
    {
        $bag = $this->createBag(['a' => 4, 'b' => 3, 'c' => 1, 'd' => 2]);

        $sorted = $bag->sort(SORT_ASC, SORT_REGULAR, true);

        $this->assertBagResult(['c' => 1, 'd' => 2, 'b' => 3, 'a' => 4], $bag, $sorted);
    }

    public function testSortValuesDesc(): void
    {
        $bag = $this->createBag([4, 'hi' => 3, 1, 2]);

        $sorted = $bag->sort(SORT_DESC);

        $this->assertBagResult([4, 3, 2, 1], $bag, $sorted);
    }

    public function testSortValuesDescPreserveKeys(): void
    {
        $bag = $this->createBag(['a' => 4, 'b' => 3, 'c' => 1, 'd' => 2]);

        $sorted = $bag->sort(SORT_DESC, SORT_REGULAR, true);

        $this->assertBagResult(['a' => 4, 'b' => 3, 'd' => 2, 'c' => 1], $bag, $sorted);
    }

    public function testSortValuesWithComparator(): void
    {
        $bag = $this->createBag(['blue', 'red', 'black']);

        $sorted = $bag->sortWith(
            function ($a, $b) {
                $a = $a[0];
                $b = $b[0];

                if ($a === $b) {
                    return 0;
                }

                return $a > $b ? 1 : -1;
            }
        );

        $this->assertBagResult(['blue', 'black', 'red'], $bag, $sorted);
    }

    public function testSortValuesWithComparatorPreserveKeys(): void
    {
        $bag = $this->createBag(['blue', 'red', 'black']);

        $sorted = $bag->sortWith(
            function ($a, $b) {
                $a = $a[0];
                $b = $b[0];

                if ($a === $b) {
                    return 0;
                }

                return $a > $b ? 1 : -1;
            },
            true
        );

        $this->assertBagResult([0 => 'blue', 2 => 'black', 1 => 'red'], $bag, $sorted);
    }

    public function testSortValuesByAsc(): void
    {
        $bag = $this->createBag(
            [
                ['name' => 'Bob'],
                ['name' => 'Carson'],
                ['name' => 'Alice'],
            ]
        );

        $sorted = $bag->sortBy(
            function ($item) {
                return $item['name'];
            }
        );

        $expected = [
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            ['name' => 'Carson'],
        ];

        $this->assertBagResult($expected, $bag, $sorted);
    }

    public function testSortValuesByDesc(): void
    {
        $bag = $this->createBag(
            [
                ['name' => 'Bob'],
                ['name' => 'Carson'],
                ['name' => 'Alice'],
            ]
        );

        $sorted = $bag->sortBy(
            function ($item) {
                return $item['name'];
            },
            SORT_DESC
        );

        $expected = [
            ['name' => 'Carson'],
            ['name' => 'Bob'],
            ['name' => 'Alice'],
        ];

        $this->assertBagResult($expected, $bag, $sorted);
    }

    public function testSortValuesByPreserveKeys(): void
    {
        $bag = $this->createBag(['blue', 'red', 'black']);

        $sorted = $bag->sortWith(
            function ($a, $b) {
                $a = $a[0];
                $b = $b[0];

                if ($a === $b) {
                    return 0;
                }

                return $a > $b ? 1 : -1;
            },
            true
        );

        $this->assertBagResult([0 => 'blue', 2 => 'black', 1 => 'red'], $bag, $sorted);
    }

    public function testSortKeysAsc(): void
    {
        $bag = $this->createBag(['c' => 1, 'd' => 2, 'b' => 3, 'a' => 4]);

        $sorted = $bag->sortKeys();

        $this->assertBagResult(['a' => 4, 'b' => 3, 'c' => 1, 'd' => 2], $bag, $sorted);
    }

    public function testSortKeysDesc(): void
    {
        $bag = $this->createBag(['c' => 1, 'd' => 2, 'b' => 3, 'a' => 4]);

        $sorted = $bag->sortKeys(SORT_DESC);

        $this->assertBagResult(['d' => 2, 'c' => 1, 'b' => 3, 'a' => 4], $bag, $sorted);
    }

    public function testSortKeysWithComparator(): void
    {
        $bag = $this->createBag(['blue' => 'a', 'red' => 'b', 'black' => 'c']);

        $sorted = $bag->sortKeysWith(
            function ($a, $b) {
                $a = $a[0];
                $b = $b[0];

                if ($a === $b) {
                    return 0;
                }

                return $a > $b ? 1 : -1;
            }
        );

        $this->assertBagResult(['blue' => 'a', 'black' => 'c', 'red' => 'b'], $bag, $sorted);
    }

    public function testSortKeysByAsc(): void
    {
        $bag = $this->createBag(['blue' => 'a', 'red' => 'b', 'black' => 'c']);

        $sorted = $bag->sortKeysBy(
            function ($key) {
                return $key[0];
            }
        );

        $this->assertBagResult(['blue' => 'a', 'black' => 'c', 'red' => 'b'], $bag, $sorted);
    }

    public function testSortKeysByDesc(): void
    {
        $bag = $this->createBag(['blue' => 'a', 'red' => 'b', 'black' => 'c']);

        $sorted = $bag->sortKeysBy(
            function ($key) {
                return $key[0];
            },
            SORT_DESC
        );

        $this->assertBagResult(['red' => 'b', 'blue' => 'a', 'black' => 'c'], $bag, $sorted);
    }

    public function testReverse(): void
    {
        $bag = $this->createBag(['a', 'b', 'c', 'd']);

        $actual = $bag->reverse();

        $this->assertBagResult(['d', 'c', 'b', 'a'], $bag, $actual);
    }

    public function testReversePreserveKeys(): void
    {
        $bag = $this->createBag(['a', 'b', 'c', 'd']);

        $actual = $bag->reverse(true);

        $this->assertBagResult([3 => 'd', 2 => 'c', 1 => 'b', 0 => 'a'], $bag, $actual);
    }

    public function testShuffle(): void
    {
        $bag = $this->createBag(['a', 'b', 'c', 'd']);

        $actual = $bag->shuffle();

        $this->assertInstanceOf($this->cls, $actual);
        $this->assertNotSame($bag, $actual);

        $sorted = $actual->toArray();
        sort($sorted);
        $this->assertEquals($bag->toArray(), $sorted);

        // reduce odds that shuffle is produces same order.
        for ($i = 0; $i < 10; ++$i) {
            if ($bag->toArray() !== $actual->toArray()) {
                break;
            }
            $actual = $bag->shuffle();
        }

        $this->assertNotEquals($bag->toArray(), $actual->toArray());
    }

    // endregion

    // region Internal Methods

    public function testIterator(): void
    {
        $bag = $this->createBag(['a', 'b', 'c', 'd']);

        $this->assertEquals(['a', 'b', 'c', 'd'], iterator_to_array($bag));
    }

    public function testJsonSerializable(): void
    {
        $bag = $this->createBag(['a', 'b', 'c']);

        $this->assertEquals('["a","b","c"]', json_encode($bag));
    }

    public function testOffsetExists(): void
    {
        $arr = ['foo' => 'bar', 'null' => null];
        $bag = $this->createBag($arr);

        $this->assertTrue(isset($bag['foo']));
        $this->assertTrue(isset($bag['null'])); // doesn't have PHPs stupid edge case.
        $this->assertFalse(isset($bag['derp']));

        $this->assertFalse(isset($arr['null'])); // just why PHP, why!
    }

    public function testOffsetGet(): void
    {
        $bag = $this->createBag(['foo' => 'bar']);

        $this->assertEquals('bar', $bag['foo']);
        $this->assertNull($bag['nope']);
    }

    public function after20testOffsetGetByReference(): void
    {
        if (\defined('HHVM_VERSION')) {
            $this->markTestSkipped('HHVM does not trigger indirect modification notice.');
            // we also don't know if it's being asked for by reference to throw an exception.
        }

        $bag = $this->createBag(['arr' => ['1']]);

        // Assert arrays are not able to be modified by reference.
        $errors = new \ArrayObject();
        set_error_handler(function ($type, $message) use ($errors): void {
            $errors[] = [$type, $message];
        });

        $arr = &$bag['arr'];

        restore_error_handler();

        $this->assertEquals([[E_NOTICE, 'Indirect modification of overloaded element of Camelot\Collection\Bag has no effect']], $errors->getArrayCopy());
    }

    public function after20testOffsetSet(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot modify items on an Camelot\\Collection\\Bag');

        $bag = $this->createBag();

        $bag['foo'] = 'bar';
    }

    public function after20testOffsetUnset(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot remove items from an Camelot\\Collection\\Bag');

        $bag = $this->createBag(['foo' => 'bar']);

        unset($bag['foo']);
    }

    public function testDebugInfo(): void
    {
        $bag = $this->createBag(['foo' => 'bar']);

        $this->assertEquals($bag->toArray(), $bag->__debugInfo());
        $this->assertEquals('bar', $bag->foo);
    }

    // endregion

    /**
     * Assert $actualBag is an Bag that's a different instance from $initialBag and its items equal $expected.
     *
     * @param array $expected
     * @param Bag   $initialBag
     * @param Bag   $actualBag
     */
    protected function assertBagResult($expected, $initialBag, $actualBag): void
    {
        $this->assertInstanceOf($this->cls, $actualBag);
        $this->assertNotSame($initialBag, $actualBag);
        $this->assertEquals($expected, $actualBag->toArray());
    }
}
