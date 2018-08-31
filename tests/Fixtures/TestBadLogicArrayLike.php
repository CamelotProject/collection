<?php

/*
 * This file is part of a Camelot Project package.
 *
 * (c) The Camelot Project
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

namespace Camelot\Collection\Tests\Fixtures;

class TestBadLogicArrayLike extends TestArrayLike
{
    public function &offsetGet($offset)
    {
        // Bad: value isn't assigned by reference
        $value = $this->items[$offset];

        return $value;
    }
}
