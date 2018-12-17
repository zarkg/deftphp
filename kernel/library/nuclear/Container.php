<?php

namespace nuclear;

use Countable;
use ArrayAccess;
use IteratorAggregate;

/**
 * 容器类
 *
 * @package nuclear
 * @author zarkg <admin@zarkg.com>
 */
class Container implements ArrayAccess, Countable, IteratorAggregate {

    // PSR-11
    public function get($id)
    {

    }

    public function has($id)
    {

    }

    // ArrayAccess
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

    // Countable
    public function count()
    {
        // TODO: Implement count() method.
    }

    // IteratorAggregate
    public function getIterator()
    {
        // TODO: Implement getIterator() method.
    }
}