<?php 

namespace Mongodm;

require_once 'mongodb.php';

class Model {

	public static $config = 'default';
	public $references = array();
	public static $use_timestamps = false;
	public $vars = array();
	public $dirtyData = array();
	public $data = array();
	public $ignoreData = array();
	public $exists = false;
	
	private $_connection = null;
	
	
	public function __construct($data = array())
	{
		if (is_null($this->_connection))
		{
			$this->_connection = MongoDB::instance(self::$config);
		}
		$this->update($data);
	}
	
	public function update($data){
		foreach($data as $key => $value){
			$this->$key = $value;
		}
		return true;
	}
	
	public function getId(){
		if(isset($this->data['_id'])){
			return new \MongoId($this->data['_id']);
		}
		return null;
	}

	/**
	 * Insert a document
	 *
	 * @param  array $insert
	 * @param  bool  $options
	 * @return MongoDB Object
	 */
	public function insert(array $insert, $options =  true)
	{
		return $this->_connection->insert(self::$collection(), $insert, $options);
	}

	/**
	 * Delete a document
	 *
	 * @param  array $criteria
	 * @return null
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
		return $this->_connection->ensure_index(self::$collection, $keys);
	}
	
	public function save($options = array()){
	
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
			$insert = $this->_connection->insert($this->collectionName(), $this->data, $options);
	
			$success = !is_null($this->data['$id'] = $insert['_id']);
		}
	
		$this->exists = true ;
		$this->dirtyData = array();
	
		return $success;
		
	}
	
	static function id($id){
		
		if($id){
			$id = new \MongoId($id);
		}else{
			return null;
		}
		return self::one( array( "_id" => $id ));
	
	}
	
	/**
	 * Find documents
	 *
	 * @param  array $query
	 * @param  array $fields
	 * @return MongoDB Object
	 */
	static function find($params = array(), $fields = array(), $sort = array() , $limit = null , $skip = null)
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
	
	static function all( $sort = array() , $fields = array()){
	
		return self::find(array(),$fields,$sort);
	
	}
	
	static function one($params = array(),$fields = array())
	{
		$result =  self::connection()->find_one(static::$collection, $params , $fields);
		if($result){
			return  Hydrator::hydrate(get_called_class(), $result ,"one");
		}
	
		return null;
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
	
	private static function collectionName(){
		$class = get_called_class();
		$collection = $class::$collection;
		return $collection;
	}
	
	private static function connection(){
		return MongoDB::instance(self::$config);
	}
	
	/**
	 * create mongodb reference data
	 * @return array()
	 */
	public function makeRef(){
	
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
		if (array_key_exists($key, $this->data))
		{
			if(isset($this->references[$key])){
				$value = $this->loadRef($key);
			}else{
				$value = $this->data[$key];
			}
			
			return $value;
		}
		elseif (array_key_exists($key, $this->ignoreData))
		{
			return $this->ignoreData[$key];
		}
	
	}
	
	public function loadRef($key){
		
		$reference = $this->references[$key];
		$value = $this->data[$key];
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
				
		$this->data[$key] = $value;
		$this->dirtyData[$key] = $value;
		
	}
	
	private function dbName(){
		$class = get_called_class();
		if(isset($class::$config)){
			$config = $class::$config;
		}else{
			$config = self::$config;
		}
		$configs = MongoDB::config($config);
		if($configs){
			$dbName = $configs['connection']['database'];
		}
		return $dbName;
	}
	
	protected function __beforeDelete(){
		
	}
	
	protected function __beforeSave(){
	
	}

}