<?php 

namespace Purekid\Mongodm;

use Purekid\Mongodm\Model;

/**
 * Mongodm - A PHP Mongodb ORM
 *
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @link     http://github.com/purekid
 */
class Collection  implements \IteratorAggregate,\ArrayAccess, \Countable 
{
	
	private $_items = array();
	private $_items_id = array();
	
	/**
	 * Make a set from a arrry of Model
	 *
	 * @param  array $models
	 */
	public function __construct($models = array())
	{
		
		$items = array();
		
		foreach($models as $model){
			if(! ($model instanceof Model)) continue;
			$id = (string) $model->getId(); 	
			$items[$id] = $model;
		}
		
		$this->_items = $items;		
		
	}
	
	/**
	 * Get item by numeric index or MongoId 
	 *
	 * @param  array $models
	 * @return Purekid\Mongodm\Model
	 */
	public function get($index = 0 )
	{
		
		if(is_int($index)){ 
			if($index + 1 > $this->count()){
				return null;
			}else{
				return	current(array_slice ($this->_items , $index , 1)) ;
			}
		}else{
		
			if($index instanceof \MongoId){
				$index = (string) $index;
			}
			
			if($this->has($index)){
				return $this->_items[$index];
			}
		}
		
		return null;
	}
	
	/**
	 * Export all items to a Array
	 * @param boolean $is_numeric_index
	 * @return array 
	 */
	public function toArray( $is_numeric_index = true)
	{
	
		$array = array();
		foreach($this->_items as $item){
			if(!$is_numeric_index){
				$id = (string) $item->getId();
				$array[$id] = $item;
			}else{
				$array[] = $item;
			}
		}
		return $array;
	
	}
	
	/**
	 * Remove a record from the collection
	 *
	 * @param int|MongoID|Model
	 * @return boolean
	 */
	public function remove($param)
	{
		if($param instanceof Model ){
			$param = $param->getId();
		}
		
		$item = $this->get($param);
		if($item){
			$id = (string) $item->getId();
			if($this->_items[$id]){
				unset($this->_items[$id]);
			}
		}
		return true;
		
	}
	
	/**
	 * Determine if a record exists in the collection
	 * 
	 * @param int|MongoID|object 
	 * @return boolean
	 */
	public function has( $param )
	{
		
		if($param instanceof \MongoId){
			$id = (string) $param;
		}else if($param instanceof Model){
			$id = (string) $param->getId() ;
		}else if(is_string($param)){
			$id = $param;
		}
		if( isset($id) && isset($this->_items[$id]) ){
			return true;
		}
		return false;
				
	}
	
	/**
	 * Make a set from a arrry of Model
	 *
	 * @param  array $models
	 * @return Purekid\Mongodm\ModelSet
	 */
	static function make($models)
	{
		
		return new self($models);	
		
	}
	
	public function first()
	{
		return current($this->_items);
	}
	
	public function last()
	{
		return array_pop($this->_items);
	}
	
	/**
	 * Add a model item or model array or ModelSet to this set
	 *
	 * @param  mixed $items
	 * @return $this
	 */
	public function add($items)
	{
		if($items && $items instanceof \Purekid\Mongodm\Model){
			$id = (string) $items->getId();
			$this->_items[$id] = $items;
		}else if(is_array($items)){
			foreach($items as $obj){
				if($obj instanceof \Purekid\Mongodm\Model){
					$this->add($obj);
				}
			}
		}else if($items instanceof self){
			$this->add($items->toArray());
		}
		return $this;
		
	}
	
	public function count()
	{
		return count($this->_items);
	}
	
	public function getIterator() 
	{
		return new \ArrayIterator($this->_items);
	}
	
	/**
	 * make a  MongoRefs array of items
	 *
	 * @param  mixed $items
	 * @return $this
	 */
	public function makeRef()
	{
	
		$data = array();
		foreach($this->_items as $item){
			$data[] = $item->makeRef();
		}
		return $data;
	
	}
	
	public function offsetExists($key) 
	{
		if(is_integer($key) && $key + 1 <= $this->count()){
			return true;
		}
		return $this->has($key);
	}

	public function offsetGet($key) 
	{
		return $this->get($key);
	}
	
	public function offsetSet($offset, $value) 
	{
		throw new \Exception('cannot change the set by using []');
	}

	public function offsetUnset($index) 
	{
		$this->remove($index);
	}

}