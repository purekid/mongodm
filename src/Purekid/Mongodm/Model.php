<?php 

namespace Purekid\Mongodm;

/**
 * Mongodm - A PHP Mongodb ORM
 *
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @link     http://github.com/purekid
 */
abstract class Model
{

	public $cleanData = array();

	/**
	 *  exists in the database
	 */
	public $exists = false;
	
	/**
	 * section choosen in the config 
	 */
	protected static $config = 'default';

	protected static $attrs = array();
	
	/**
	 * Data modified 
	 */
	protected $dirtyData = array();

	protected $ignoreData = array();

	/**
	* Cache for references data
	*/
	private $_cache = array();

	private $_connection = null;
	
	public function __construct( $data = array() )
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
		
 		$this->update($data,true);
 		$this->initAttrs();
 		$this->initTypes();
 		$this->__init();
 		
	}
	

	/**
	 * Update data by a array
	 * @param array $cleanData
	 * @return boolean
	 */
	public function update(array $cleanData,$isInit = false)
	{
		if($isInit){
			$attrs = $this->getAttrs();
			foreach($cleanData as $key => $value){
				if(($value instanceof Model) && isset($attrs[$key]) && isset($attrs[$key]['type']) 
			    	&& ( $attrs[$key]['type'] == 'reference' or $attrs[$key]['type'] == 'references' )){
					$value = $this->setRef($key,$value);
				} 
				$this->cleanData[$key] = $value;
			}
		}else{
			foreach($cleanData as $key => $value){
				$this->$key = $value;
			}
		}
	
		return true;
	}

	/**
	 * Mutate data by direct query
	 * @param array $updateQuery
	 * @return boolean
	 */
	public function mutate($updateQuery, $options = array())
	{
		if(!is_array($updateQuery)) throw new Exception('$updateQuery should be an array');
		if(!is_array($options)) throw new Exception('$options should be an array');

		$default = array(
			'w' => 1
		);
		$options = array_merge($default, $options);

		try {
			$this->_connection->update($this->collectionName(), array('_id' => $this->cleanData['_id']), $updateQuery, $options);
		}
		catch(\MongoCursorException $e) {
			return false;
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
		$this->__preDelete();
		
		if($this->exists){
			$deleted =  $this->_connection->remove($this->collectionName(), array("_id" => $this->getId() ), $options);
			if($deleted){
				$this->exists = false;
			}
		}
		$this->__postDelete();
		return true;
	}

	/**
	 * Save to database
     *
	 * @param array $options
	 * @return array
	 */
	public function save($options = array())
	{

        $this->_processReferencesChanged();

		/* if no changes then do nothing */
		if ($this->exists and empty($this->dirtyData)) return true;
	
		$this->__preSave();
		
		if ($this->exists)
		{
			$this->__preUpdate();
			$success = $this->_connection->update($this->collectionName(), array('_id' => $this->getId()), array('$set' => $this->dirtyData), $options);
			$this->__postUpdate();
		}
		else
		{
			$this->__preInsert();
			$insert = $this->_connection->insert($this->collectionName(), $this->cleanData, $options);
			$success = !is_null($this->cleanData['$id'] = $insert['_id']);
			if($success){
				$this->exists = true ;
				$this->__postInsert();
			}
			
		}
	
		$this->dirtyData = array();
		$this->__postSave();
		
		return $success;
		
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
	 * Determine exist in the database
	 *
	 * @return integer
	 */
	public function exist(){
		return $this->exists;
	}
	
	/**
	 * Create a Mongodb reference
	 * @return MongoRef Object
	 */
	public function makeRef()
	{
	
		$model = get_called_class();
		$ref = \MongoDBRef::create($this->collectionName(), $this->getId());
		return $ref;
	
	}
	
	/**
	 * Retrieve a record by MongoId
	 * @param mixed $id 
	 * @return Model
	 */
	public static function id($id)
	{
		
		if($id  && strlen($id) == 24 ){
			$id = new \MongoId($id);
		}else{
			return null;
		}
		return self::one( array( "_id" => $id ));
	
	}
	
	/**
	 * Retrieve a record
	 *
	 * @param  array $params
	 * @param  array $fields
	 * @return Model
	 */
	public static function one($params = array(),$fields = array())
	{
		$class = get_called_class();
		$types = $class::getModelTypes();
		if(count($types) > 1){
			$params['_type'] = $class::get_class_name(false);
		}
		
		$result =  self::connection()->find_one(static::$collection, $params , $fields);
		if($result){
			return  Hydrator::hydrate(get_called_class(), $result ,"one");
		}
	
		return null;
	}
	
	/**
	 * Retrieve records
	 *
	 * @param  array $params
	 * @param  array $sort
	 * @param  array $fields
	 * @param  int $limit
	 * @param  int $skip
	 * @return Collection
	 */
	public static function find($params = array(), $sort = array(), $fields = array() , $limit = null , $skip = null)
	{
	
		$class = get_called_class();
		$types = $class::getModelTypes();
		if(count($types) > 1){
			$params['_type'] = $class::get_class_name(false);
		}
		
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
	 * Retrieve all records
	 *
	 * @param  array $sort
	 * @param  array $fields
	 * @return Collection
	 */
	public static function all( $sort = array() , $fields = array())
	{
		$class = get_called_class();
		$types = $class::getModelTypes();
		$params = array();
		if(count($types) > 1){
			$params['_type'] = $class::get_class_name(false);
		}
		
		return self::find($params,$fields,$sort);
	
	}

    public static function group(array $keys, array $query, $initial = null,$reduce = null)
	{

        if(!$reduce) $reduce = new \MongoCode( 'function(doc, out){ out.object = doc }');
        if(!$initial) $initial = array('object'=>0);
        return self::connection()->group(self::collectionName(),$keys,$initial,$reduce,array('condition'=>$query));

    }

    public static function aggregate($query){

        $rows =  self::connection()->aggregate(self::collectionName(),$query);
        return $rows;

    }

	/**
	 * Count of records
	 *
	 * @param  $params
	 * @return integer
	 */
	public static function count($params = array())
	{
		$class = get_called_class();
		$types = $class::getModelTypes();
		if(count($types) > 1){
			$params['_type'] = $class::get_class_name(false);
		}
		$count = self::connection()->count(self::collectionName(),$params);
		return $count;
	
	}
	
	/**
	 * Get collection name
	 *
	 * @return string
	 */
	public static function collectionName()
	{
		$class = get_called_class();
		$collection = $class::$collection;
		return $collection;
	}
	
	/**
	 * Drop the collection
	 *
	 * @return boolean
	 */
	public static function drop(){
	
		$class = get_called_class();
		return self::connection()->drop_collection($class::collectionName());
	
	}
	
	/**
	 * Retrieve a record by MongoRef
     *
	 * @param mixed $ref
	 * @return Model 
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
	 * Returns the name of a class using get_class with the namespaces stripped.
     *
	 * @param boolean $with_namespaces
	 * @return  string  Name of class with namespaces stripped
	 */
	public static function get_class_name($with_namespaces = true)
	{
		$class_name = get_called_class();
		if($with_namespaces) return $class_name;
		$class = explode('\\',  $class_name);
		return $class[count($class) - 1];
	}
	
	/**
	 * Ensure index 
	 * @param mixed $keys
	 * @param array $options
	 * @return  boolean 
	 */
	public static function ensure_index (  $keys, $options = array())
	{
		$result =  self::connection()->ensure_index(self::collectionName(),$keys,$options);
		return $result; 
	}
	


    /**
     * Initialize the "_type" attribute for the model
     */
    private function initTypes(){

        $class = $this->get_class_name(false);
        $types = $this->getModelTypes();
        $type = $this->_type;
        if(!$type || !is_array($type)){
            $this->_type = $types;
        }else if(!in_array($class,$type)){
            $type[] = $class;
            $this->_type = $type;
        }

    }

	/**
	 * Get Mongodb connection instance
	 * @return Mongodb
	 */
	private static function connection()
	{
		$class = get_called_class();
		$config = $class::$config;
		return MongoDB::instance($config);
	}
	
	/**
	 * Get current database name
	 * @return string
	 */
	private function dbName()
	{
	
		$dbName = "default";
		$config = $this::$config;
		$configs = MongoDB::config($config);
		if($configs){
			$dbName = $configs['connection']['database'];
		}
	
		return $dbName;
	}
	
    /**
	 * Parse value with specific definition in $attrs
     *
	 * @param string $key
	 * @param mixed $value
	 * @return mixed
	 */
	private function parseValue($key,$value){
		
		$attrs = $this->getAttrs();
		if( !isset($attrs[$key]) && is_object($value)){
			if(method_exists($value, 'toArray')){
				$value = (array) $value->toArray();
			}else if(method_exists($value, 'to_array')){
				$value = (array) $value->to_array();
			}
		}
		else if( isset($attrs[$key]) && isset($attrs[$key]['type'])){
			$type = $attrs[$key]['type'];
			$type_defined = array('mixed','reference','references','integer','int','string','double','timestamp','boolean','array','object');
			if($type == "int") $type = 'integer';
			if(in_array($type, $type_defined)){
				switch($type){
					case "integer":
						$value = intval($value);
					break;
					case "string":
						$value = (string) $value;
						break;
					case "double":
						$value = floatval($value);
						break;
					case "timestamp":
						if(! ($value instanceof \MongoTimestamp)){
							$value = new \MongoTimestamp($value);
						}
						break;
					case "boolean":
						$value = (boolean) $value;
						break;
					case "object":
						if(!empty($value) && !is_object($value)){
							throw new \Exception("[$key] is not a object");
						}
						$value = (object) $value;
						break;
					case "array":
						if(!empty($value) && !is_array($value)){
							throw new \Exception("[$key] is not a array");
						}
						$value = (array) $value;
						break;
					default:
						
						break;
				}
			}else{
				throw new \Exception("type {$type} is invalid！");
			}
		}
		return $value;
	
	}

    /**
     *  Update the 'references' attr when that 'references' instance has changed.
     */
    private function _processReferencesChanged(){

        $cache = $this->_cache;
        $attrs = $this->getAttrs();
        foreach($cache as $key => $item){
            if(!isset($attrs[$key])) continue;
            $attr = $attrs[$key];
            if($attr['type'] == 'references'){
                if( $item instanceof Collection && $this->cleanData[$key] !== $item->makeRef()){
//                    $this->cleanData[$key] = $item->makeRef();
//                    $this->dirtyData[$key] = $item->makeRef();
                    $this->__set($key,$item);
                }
            }
        }

    }

	/**
	 *  If the attribute of $key is a reference ,
	 *  save the attribute into database as MongoDBRef
	 */
	private function setRef($key,$value)
	{
		$attrs = $this->getAttrs();
		$cache = &$this->_cache;
		$reference = $attrs[$key];
		$model = $reference['model'];
		$type = $reference['type'];
		// 		$return = null;
	
		if($type == "reference"){
			$model = $reference['model'];
			$type = $reference['type'];
			if($value instanceof $model){
				$ref = $value->makeRef();
				$return = $ref;
			}else if($value == null){
				$return = null;
			}else{
				throw new \Exception ("{$key} is not instance of '$model'");
			}
				
		}else if($type == "references"){
			$arr = array();
			if(is_array($value)){
				foreach($value as $item){
					if(! ( $item instanceof Model ) ) continue;
					$arr[] = $item->makeRef();
				}
				$return = $arr;
				$value = Collection::make($value);
			}else if($value instanceof Collection){
				$return = $value->makeRef();
			}else if($value == null){
				$return = null;
			}else{
				throw new \Exception ("{$key} is not instance of '$model'");
			}
		}
	
		$cache[$key] = $value;
		return $return;
	}
	
	/**
	 *  If the attribute of $key is a reference ,
	 *  load its original record from db and save to $_cache temporarily.
	 */
	private function loadRef($key)
	{
		$attrs = $this->getAttrs();
		$reference = $attrs[$key];
		$cache = &$this->_cache;
		if(isset($this->cleanData[$key])){
			$value = $this->cleanData[$key];
		}else{
			$value = null;
		}
	
		$model = $reference['model'];
		$type = $reference['type'];
		if( isset($cache[$key]) ){
            $obj = &$cache[$key];
			return $obj;
		}else{
			if(class_exists($model)){
				if($type == "reference"){
					if(\MongoDBRef::isRef($value)){
						$object = $model::id($value['$id']);
						$cache[$key] = $object;
						return $object;
					}
					return null;
				}else if($type == "references"){
					$res = array();
					if(!empty($value)){
						foreach($value as $item){
							$record = $model::id($item['$id']);
							if($record){
								$res[] = $record;
							}
						}
					}
					$set =  Collection::make($res);
					$cache[$key] = $set;
                    $obj = &$cache[$key];
                    return $obj;
				}
			}
		}
	}


    /**
     * Initialize attributes with default value
     */
    protected function initAttrs(){
        $attrs = self::getAttrs();
        foreach($attrs as $key => $attr){
            if(! isset($attr['default'])) continue;
            if( !isset($this->cleanData[$key])){
                $this->$key = $attr['default'];
            }
        }
    }

    /**
     * Get all defined attributes in $attrs ( extended by parent class )
     * @return array
     */
    protected static function getAttrs(){

        $class = get_called_class();
        $parent = get_parent_class($class);
        if($parent){
            $attrs_parent = $parent::getAttrs();
            $attrs = array_merge($attrs_parent,$class::$attrs);
        }else{
            $attrs = $class::$attrs;
        }
        if(empty($attrs)) $attrs = array();
        return $attrs;

    }

    /**
     * Get types of model,type is the class_name without namespace of Model
     * @return array
     */
    protected static function getModelTypes(){

        $class = get_called_class();
        $class_name = $class::get_class_name(false);
        $parent = get_parent_class($class);
        if($parent){
            $names_parent = $parent::getModelTypes();
            $names = array_merge($names_parent,array($class_name));
        }else{
            $names = array();
        }
        return $names;

    }


	/*********** Hooks ***********/
	
	protected function __init()
	{
		return true;
	}
	
	protected function __preSave()
	{
		return true;
	}
	
	protected function __preUpdate()
	{
		return true;
	}
	
	protected function __preInsert()
	{
		return true;
	}
	
	protected function __preDelete()
	{
		return true;
	}
	
	protected function __postSave()
	{
		return true;
	}
	
	protected function __postUpdate()
	{
		return true;
	}
	
	protected function __postInsert()
	{
		return true;
	}
	
	protected function __postDelete()
	{
		return true;
	}
	
	/*********** Magic methods ************/


	/**
	 * @param  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		$attrs = $this->getAttrs();
	    if( isset($attrs[$key]) && isset($attrs[$key]['type']) 
	    	&& ( $attrs[$key]['type'] == 'reference' or $attrs[$key]['type'] == 'references' )){
			$value = $this->loadRef($key);
			return $value;
		}
		else if (array_key_exists($key, $this->cleanData))
		{
			$value = $this->parseValue($key,$this->cleanData[$key]);
			return $value;
		}
		elseif (array_key_exists($key, $this->ignoreData))
		{
			return $this->ignoreData[$key];
		}
	
	}
	
	/**
	 * Magic Method for setting model data.
	 */
	public function __set($key, $value)
	{
		$attrs = $this->getAttrs();
		if(isset($attrs[$key]) && isset($attrs[$key]['type']) 
	    	&& ( $attrs[$key]['type'] == 'reference' or $attrs[$key]['type'] == 'references' )){
			$value = $this->setRef($key,$value);
		} 
		
		$value = $this->parseValue($key,$value);
		if(isset($this->cleanData[$key]) && $this->cleanData[$key] === $value){
			
		}else{
			$this->cleanData[$key] = $value;
			$this->dirtyData[$key] = $value;
		}
		
	}

}
