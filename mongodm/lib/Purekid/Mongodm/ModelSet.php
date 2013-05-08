<?php 

namespace Purekid\Mongodm;

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
	
	public function __construct($models)
	{
		
		if(empty($models)) return array();
		
		$items = array();
		
		foreach($models as $model){
			$id = (string) $model->getId(); 	
			$items[$id] = $model;
		}
		$this->_items = $items;		
		
		$this->_count = count($items);
		
	}
	
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
	
	public function add($item)
	{
		
		if($item && $item instanceof \Purekid\Mongodm\Model){
			$id = (string) $item->getId();
			$this->_items[$id] = $item;
			
		}else if(is_array($item)){
			foreach($item as $obj){
				if($obj instanceof \Purekid\Mongodm\Model){
					$this->add($obj);
				}
			}
		}else if($item instanceof self){
			$this->add($item->toArray());
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