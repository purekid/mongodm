<?php 

namespace Purekid\Mongodm;

use Purekid\Mongodm\Model;

/**
 * Mongodm - A PHP Mongodb ORM
 *
 * @package  Mongodm
 * @version  1.0.0
 * @author   Michael Gan <gc1108960@gmail.com>
 * @link     http://github.com/purekid
 */
class ModelSet  implements \IteratorAggregate,\ArrayAccess, \Countable 
{
	
	private $_items = array();
	private $_items_id = array();
	
	private $_count = 0;
	
	/**
	 * Make a set from a arrry of Model
	 *
	 * @param  array $models
	 */
	public function __construct($models = array())
	{
		
		if(empty($models)) return array();
		
		$items = array();
		
		foreach($models as $model){
			if(! ($model instanceof Model)) continue;
			$id = (string) $model->getId(); 	
			$items[$id] = $model;
		}
		
		$this->_items = $items;		
		$this->_count = count($items);
		
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
			if($index + 1 > $this->_count){
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
	 *
	 * @return array 
	 */
	public function toArray()
	{
	
		$array = array();
		foreach($this->_items as $item){
			$array[] = $item;
		}
		return $array;
	
	}
	
	public function remove($index)
	{
		
		$item = $this->get($index);
		if($item){
			$id = (string) $item->getId();
			if($this->_items[$id]){
				unset($this->_items[$id]);
				$this->_count --;
			}
		}
		return true;
		
	}
	
	public function has( $model = null)
	{
		
		if(is_object($model)){
			$id = (string) $model->getId() ;
		}else if(is_string($model)){
			$id = $model;
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
		$this->_count = count($this->_items);
		return $this->_count;
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