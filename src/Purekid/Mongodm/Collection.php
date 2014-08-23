<?php

/**
 * This file is part of the Mongodm package.
 *
 * (c) Michael Gan <gc1108960@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @category Mongodm
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @license  https://github.com/purekid/mongodm/blob/master/LICENSE.md MIT Licence
 * @link     https://github.com/purekid/mongodm
 */

namespace Purekid\Mongodm;


/**
 * Collection
 *
 * @category Mongodm
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @license  https://github.com/purekid/mongodm/blob/master/LICENSE.md MIT Licence
 * @link     https://github.com/purekid/mongodm
 */
class Collection  implements \IteratorAggregate, \ArrayAccess, \Countable
{

    private $_items = array();

    /**
     * Make a collection from a arrry of Model
     *
     * @param array $models models to add to the collection
     */
    public function __construct($models = array())
    {

        $items = array();

        $i = 0;
        foreach ($models as $model) {
            if (! ($model instanceof Model)) continue;
            if ($model->exists()) {
                $id = (string) $model->getId();
            } elseif ($model->isEmbed()) {
                $id = $i++;
                $model->setTempId($id);
            }

            $items[$id] = $model;
        }

        $this->_items = $items;

    }

    /**
     * Add a model item or model array or ModelSet to this set
     *
     * @param mixed $items model item or arry or ModelSet to add
     *
     * @return $this
     */
    public function add($items)
    {
        if ($items && $items instanceof \Purekid\Mongodm\Model) {
            $id = (string) $items->getId();
            $this->_items[$id] = $items;
        } elseif (is_array($items)) {
            foreach ($items as $obj) {
                if ($obj instanceof \Purekid\Mongodm\Model) {
                    $this->add($obj);
                }
            }
        } elseif ($items instanceof self) {
            $this->add($items->toArray());
        }

        return $this;

    }

    /**
     * Get item by numeric index or MongoId
     *
     * @param int $index model to get
     *
     * @return \Purekid\Mongodm\Model
     */
    public function get($index = 0)
    {

        if (is_int($index)) {
            if ($index + 1 > $this->count()) {
                return null;
            } else {
                return current(array_slice($this->_items, $index, 1));
            }
        } else {

            if ($index instanceof \MongoId) {
                $index = (string) $index;
            }

            if ($this->has($index)) {
                return $this->_items[$index];
            }
        }

        return null;
    }

    /**
     * Remove a record from the collection
     *
     * @param int|\MongoID|Model $param model to remove
     *
     * @return boolean
     */
    public function remove($param)
    {
        if ($param instanceof Model) {
            $param = $param->getId();
        }

        $item = $this->get($param);
        if ($item) {
            $id = (string) $item->getId();
            if ($this->_items[$id]) {
                unset($this->_items[$id]);
            }
        }

        return $this;

    }

    /**
     * Slice the underlying collection array.
     *
     * @param int  $offset       offset to slice
     * @param int  $length       length
     * @param bool $preserveKeys preserve keys
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function slice($offset, $length = null, $preserveKeys = false)
    {
        return new static(array_slice($this->_items, $offset, $length, $preserveKeys));
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit limit
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function take($limit = null)
    {
        if ($limit < 0) return $this->slice($limit, abs($limit));
        return $this->slice(0, $limit);
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->_items);
    }

    /**
     * Determine if a record exists in the collection
     *
     * @param int|\MongoID|object $param param
     *
     * @return boolean
     */
    public function has($param)
    {

        if ($param instanceof \MongoId) {
            $id = (string) $param;
        } elseif ($param instanceof Model) {
            $id = (string) $param->getId();
        } elseif (is_string($param)) {
            $id = $param;
        }
        if ( isset($id) && isset($this->_items[$id]) ) {
            return true;
        }

        return false;

    }

    /**
     * Run a map over the collection using the given Closure
     *
     * @param \Closure $callback callback
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function map(\Closure $callback)
    {

        $this->_items = array_map($callback, $this->_items);

        return $this;
    }

    /**
     * Filter the collection using the given Closure and return a new collection
     *
     * @param \Closure $callback callback
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function filter(\Closure $callback)
    {
        return new static(array_filter($this->_items, $callback));
    }

    /**
     * Sort the collection using the given Closure
     *
     * @param \Closure $callback callback
     * @param boolean $asc      asc
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function sortBy(\Closure $callback , $asc = false)
    {
        $results = array();

        foreach ($this->_items as $key => $value) {
            $results[$key] = $callback($value);
        }

        if ($asc) {
            asort($results);
        } else {
            arsort($results);
        }

        foreach (array_keys($results) as $key) {
            $results[$key] = $this->_items[$key];
        }

        $this->_items = $results;

        return $this;
    }

    /**
     * Reverse items order.
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function reverse()
    {

        $this->_items =  array_reverse($this->_items);

        return $this;

    }

    /**
     * Make a collection from a arrry of Model
     *
     * @param array $models models
     *
     * @return \Purekid\Mongodm\Collection
     */
    public static function make($models)
    {
        return new self($models);

    }

    /**
     * First item
     *
     * @return Model
     */
    public function first()
    {
        return current($this->_items);
    }

    /**
     * Last item
     *
     * @return Model
     */
    public function last()
    {
        return array_pop($this->_items);
    }

    /**
     * Execute a callback over each item.
     *
     * @param \Closure $callback callback
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function each(\Closure $callback)
    {
        array_map($callback, $this->_items);

        return $this;
    }

    /**
     * Count items
     *
     * @return int
     */
    public function count()
    {
        return count($this->_items);
    }

    /**
     * Export all items to a Array
     *
     * @param boolean $is_numeric_index is numeric index
     * @param boolean $itemToArray      item to array
     *
     * @return array
     */
    public function toArray($is_numeric_index = true ,$itemToArray = false)
    {

        $array = array();
        foreach ($this->_items as $item) {
            if (!$is_numeric_index) {
                $id = (string) $item->getId();
                if ($itemToArray) {
                    $item = $item->toArray();
                }
                $array[$id] = $item;
            } else {
                if ($itemToArray) {
                    $item = $item->toArray();
                }
                $array[] = $item;
            }
        }

        return $array;

    }

    /**
     * Export all items to a Array with embed style ( without _type,_id)
     *
     * @return array
     */
    public function toEmbedsArray()
    {

        $array = array();
        foreach ($this->_items as $item) {
            $item = $item->toArray(array('_type','_id'));
            $array[] = $item;
        }

        return $array;

    }

    /**
     * get iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_items);
    }

    /**
     * make a MongoRefs array of items
     *
     * @return \MongoDbRef[]
     */
    public function makeRef()
    {
        $data = array();
        foreach ($this->_items as $item) {
            $data[] = $item->makeRef();
        }

        return $data;
    }

    /**
     * Offset exists
     *
     * @param int|string $key index
     *
     * @return boolean
     */
    public function offsetExists($key)
    {
        if (is_integer($key) && $key + 1 <= $this->count()) {
            return true;
        }

        return $this->has($key);
    }

    /**
     * Offset get
     *
     * @param int|string $key index
     *
     * @return boolean
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Offset set
     *
     * @param mixed $offset offset
     * @param mixed $value  value
     *
     * @throws \Exception
     *
     * @return null
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception('cannot change the set by using []');
    }

    /**
     * Offset unset
     *
     * @param int $index index
     *
     * @return bool
     */
    public function offsetUnset($index)
    {
        $this->remove($index);
    }

    /**
     * Save items
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function save(){

        foreach($this->_items as $item){
            if($item->exists()){
                $item->save();
            }
        }

        return $this;

    }

    /**
     * Delete items
     *
     * @return \Purekid\Mongodm\Collection
     */
    public function delete(){

        foreach($this->_items as $key => $item){
            if($item->exists()){
                $deleted = $item->delete();
                if($deleted){
                   unset($this->_items[$key]);
                }
            }
        }

        return $this;

    }

}
