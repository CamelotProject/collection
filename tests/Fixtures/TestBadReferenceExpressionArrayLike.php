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

class TestBadReferenceExpressionArrayLike extends TestArrayLike
{
    public function &offsetGet($offset)
    {
        // Bad: Only variable references should be returned by reference
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }
}
