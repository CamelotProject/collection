<?php

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
use Camelot\Collection\MutableBag;

class MutableBagTest extends BagTest
{
    use BagLegacyTrait;

    /** @var string|MutableBag */
    protected $cls = MutableBag::class;

    protected function createBag($items = [])
    {
        return new MutableBag($items);
    }

    public function testRemovePath()
    {
        $bag = $this->createBag(
            [
                'items' => new ArrayObject(
                    [
                        'foo' => 'bar',
                    ]
                ),
            ]
        );

        $this->assertSame('bar', $bag->removePath('items/foo'));
        $this->assertNull($bag->removePath('items/foo'));
    }
}
