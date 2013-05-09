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
abstract class Model 
{
	public static $config = 'default';
	public static $use_timestamps = false;
	public $references = array();
	public $vars = array();
	public $dirtyData = array();
	public $cleanData = array();
	public $ignoreData = array();
	public $exists = false;
	
	private $_connection = null;
	
	
	public function __construct($data = array())
	{
		if (is_null($this->_connection))
		{
			if(isset($this::$config)){
				$config = $this::$config;
			}else{
				$config = self::config;
			}
			$this->_connection = MongoDB::instance($config);
		}
 		$this->update($data);
	}
	
	/**
	 * Update data by a array
	 * @param array $cleanData
	 * @return MongoId Object
	 */
	public function update($cleanData)
	{
		foreach($cleanData as $key => $value){
			$this->$key = $value;
		}
		return true;
	}
	
	/**
	 * get MongoId of this record
	 *
	 * @return MongoId Object
	 */
	public function getId()
	{
		if(isset($this->cleanData['_id'])){
			return new \MongoId($this->cleanData['_id']);
		}
		return null;
	}

	/**
	 * Delete this record
	 *
	 * @param  array $options
	 * @return boolean 
	 */
	public function delete($options = array())
	{
		$this->__beforeDelete();
		
		if($this->exists){
			$deleted =  $this->_connection->remove($this->collectionName(), array("_id" => $this->getId() ), $options);
			if($deleted){
				$this->exists = false;
			}
		}
		return true;
	}

	/**
	 * Set an index for collection
	 *
	 * @param  $keys
	 * @return void
	 */
	public function set_index($keys)
	{
		return $this->_connection->ensure_index($this->collectionName(), $keys);
	}
	
	public function save($options = array())
	{
	
		$this->__beforeSave();
		
		if ($this->exists and empty($this->dirtyData)) return true;
	
		if($this->use_timestamps)
		{
			$this->timestamp();
		}
		if ($this->exists)
		{
			$success = $this->_connection->update($this->collectionName(), array('_id' => $this->getId()), array('$set' => $this->dirtyData), $options);
		}
		else
		{
			$insert = $this->_connection->insert($this->collectionName(), $this->cleanData, $options);
	
			$success = !is_null($this->cleanData['$id'] = $insert['_id']);
		}
	
		$this->exists = true ;
		$this->dirtyData = array();
	
		return $success;
		
	}
	
	static function count($params = array())
	{
	
		$count = self::connection()->count(self::collectionName(),$params);
		return $count;
	
	}
	
	/**
	 * Export datas to array
	 *
	 * @return array
	 */
	public function toArray()
	{
		
		return $this->cleanData;
		
	}
	
	/**
	 * Find a record by MongoId
	 * @param mixed $id 
	 * @return Model
	 */
	static function id($id)
	{
		
		if($id){
			$id = new \MongoId($id);
		}else{
			return null;
		}
		return self::one( array( "_id" => $id ));
	
	}
	
	/**
	 * Find a record
	 *
	 * @param  array $params
	 * @param  array $fields
	 * @return Model
	 */
	static function one($params = array(),$fields = array())
	{
		$result =  self::connection()->find_one(static::$collection, $params , $fields);
		if($result){
			return  Hydrator::hydrate(get_called_class(), $result ,"one");
		}
	
		return null;
	}
	
	/**
	 * Find records
	 *
	 * @param  array $params
	 * @param  array $sort
	 * @param  array $fields
	 * @param  int $limit
	 * @param  int $skip
	 * @return ModelSet Object
	 */
	static function find($params = array(), $sort = array(), $fields = array() , $limit = null , $skip = null)
	{
	
		$results =  self::connection()->find(static::$collection, $params, $fields);
	
		$count = $results->count();
	
		if ( ! is_null($limit))
		{
			$results->limit($limit);
		}
	
		if( !  is_null($skip))
		{
			$results->skip($skip);
		}
	
		if ( ! empty($sort))
		{
			$results->sort($sort);
		}
	
		return Hydrator::hydrate(get_called_class(), $results);
	
	}
	
	/**
	 * Find records
	 *
	 * @param  array $sort
	 * @param  array $fields
	 * @return ModelSet Object
	 */
	static function all( $sort = array() , $fields = array())
	{
	
		return self::find(array(),$fields,$sort);
	
	}
	
	/**
	 * Set the creation and update timestamps on the model.
	 *
	 * Uses the time() method
	 *
	 * @return void
	 */
	private function timestamp()
	{
		$this->vars['update_time'] = time();
	
		if ( ! $this->exists ) $this->vars['create_time'] = $this->vars['update_time'];
	}
	
	public static function collectionName()
	{
		$class = get_called_class();
		$collection = $class::$collection;
		return $collection;
	}
	
	private static function connection()
	{
		$class = get_called_class();
		$config = $class::$config;
		return MongoDB::instance($config);
	}
	
	/**
	 * retrieve a record by MongoRef
	 * @param mixed $ref
	 * @return Purekid\Mongodm\Model 
	 */
	public static function ref( $ref ){
		if(isset($ref['$id'])){
			if($ref['$ref'] == self::collectionName()){
				return self::id($ref['$id']);
			}
		}
		return null;
	}
	
	/**
	 * Create a Mongodb reference 
	 * @return MongoRef Object
	 */
	public function makeRef()
	{
	
		$model = get_called_class();
		$ref = \MongoDBRef::create($this->collectionName(), $this->getId(),$this->dbName());
		return $ref;
	
	}
	
	
	/****************************************************
	 *	Magic Methods
	****************************************************/
	
	/**
	 * @param  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		if (array_key_exists($key, $this->cleanData))
		{
			if(isset($this->references[$key])){
				$value = $this->loadRef($key);
			}else{
				$value = $this->cleanData[$key];
			}
			
			return $value;
		}
		elseif (array_key_exists($key, $this->ignoreData))
		{
			return $this->ignoreData[$key];
		}
	
	}
	
	public function loadRef($key)
	{
		
		$reference = $this->references[$key];
		$value = $this->cleanData[$key];
		$model = $reference['model'];
		$type = $reference['type'];
		
		if( isset($reference['record']) ){
			return $reference['record'];
		}else{
			if(class_exists($model)){
				if($type == "one"){
					if(\MongoDBRef::isRef($value)){
						$object = $model::id($value['$id']);
						$this->references[$key]['record'] = $object;
						return $object;
					}
					return null;
				}else if($type == "many"){
					$res = array();
					foreach($value as $item){
						$record = $model::id($item['$id']);
						if($record){
							$res[] = $record;
						}
					}
					$set =  ModelSet::make($res);
					$this->references[$key]['record'] = $set;
					return $set;
				}
			}
		}
		
	}
	
	public function setRef($key,$value)
	{
		$reference = $this->references[$key];
		$model = $reference['model'];
		$type = $reference['type'];
		
		if($type == "one"){
			$model = $reference['model'];
			$type = $reference['type'];
			if($value instanceof $model){
				$ref = $value->makeRef();
				$return = $ref;
			}else{
				throw new \Exception ("{$key} is not instance of '$model'");
			}
			$this->references[$key]['record'] = $value;
		}else if($type == "many"){
			$arr = array();
			if(is_array($value)){
				foreach($value as $item){
					$arr[] = $item->makeRef();
				}
				$return = $arr;
				$value = ModelSet::make($value);
			}else if($value instanceof ModelSet){
				$return = $value->makeRef();
			}else{
				throw new \Exception ("{$key} is not instance of '$model'");
			}
			$this->references[$key]['record'] = $value;
		}
		return $return;
	}
	
	/**
	 * Magic Method for setting model data.
	 */
	public function __set($key, $value)
	{
		if(isset($this->references[$key])){
			$value = $this->setRef($key,$value);
		}
				
		$this->cleanData[$key] = $value;
		$this->dirtyData[$key] = $value;
		
	}
	
	private function dbName()
	{
		
		$dbName = "unknown";
		$config = $this::$config;
		$configs = MongoDB::config($config);
		if($configs){
			$dbName = $configs['connection']['database'];
		}
		
		return $dbName;
	}
	
	protected function __beforeDelete()
	{
		
	}
	
	protected function __beforeSave()
	{
	
	}

}