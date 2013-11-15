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

    /**
     * Data to unset
     */
    protected $unsetData = array();

	protected $ignoreData = array();

	/**
	* Cache for references data
	*/
	private $_cache = array();

	private $_connection = null;

    /**
     * If $_isEmbed = true , this model can't save to database alone.
     */
    private $_isEmbed = false;

    /**
     * Id for offline model (such as embed model)
     */
    private $_tempId = null;
	
	public function __construct( $data = array())
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
        if(isset($data['_id']) && $data['_id'] instanceof \MongoId){
            $this->exists = true;
        }else{
            $this->initAttrs();
        }
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
		}else if(isset($this->_tempId)){
            return $this->_tempId;
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

        if($this->_isEmbed){
            return false;
        }

        $this->_processReferencesChanged();
        $this->_processEmbedsChanged();

		/* if no changes then do nothing */

		if ($this->exists and empty($this->dirtyData) and empty($this->unsetData)) return true;

		$this->__preSave();
		
		if ($this->exists)
		{
			$this->__preUpdate();
            $updateQuery = array();
            if(!empty($this->dirtyData)){
                $updateQuery['$set'] = $this->dirtyData;
            };

            if(!empty($this->unsetData)){
                $updateQuery['$unset'] = $this->unsetData;
            }
			$success = $this->_connection->update($this->collectionName(), array('_id' => $this->getId()), $updateQuery , $options);
			$this->__postUpdate();
		}
		else
		{
			$this->__preInsert();
			$insert = $this->_connection->insert($this->collectionName(), $this->cleanData, $options);
			$success = !is_null($this->cleanData['_id'] = $insert['_id']);
			if($success){
				$this->exists = true ;
				$this->__postInsert();
			}
			
		}
	
		$this->dirtyData = array();
        $this->unsetData = array();
		$this->__postSave();
		
		return $success;
		
	}
	
	/**
	 * Export datas to array
	 *
	 * @return array
	 */
	public function toArray($ignore = array('_type'))
	{
        if(!empty($ignore)){
            $ignores = array();
            foreach($ignore as $val){
                $ignores[$val] = 1;
            }
            $ignore = $ignores;
        }

		return array_diff_key($this->cleanData,$ignore);
	}
	
	/**
	 * Determine exist in the database
	 *
	 * @return integer
	 */
	public function exist(){
		return $this->exists;
	}

    public function __call($func,$args){
        if($func == 'unset'){
            call_user_func_array( array($this,"_unset") , $args);

        }
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


    public function setIsEmbed($is_embed){

        $this->_isEmbed = $is_embed;
        if($is_embed){
            unset($this->_connection);
            unset($this->exists);
        }

    }

    public function getIsEmbed(){

        return $this->_isEmbed ;

    }

    public function setTempId( $tempId ){
        $this->_tempId = $tempId;
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
			$type_defined = array('mixed','reference','references','embed','embeds','integer','int','string','double','timestamp','boolean','array','object');
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
     *  Update the 'references' attribute for model's instance when that 'references' data  has changed.
     */
    private function _processReferencesChanged(){

        $cache = $this->_cache;
        $attrs = $this->getAttrs();
        foreach($cache as $key => $item){
            if(!isset($attrs[$key])) continue;
            $attr = $attrs[$key];
            if($attr['type'] == 'references'){
                if( $item instanceof Collection && $this->cleanData[$key] !== $item->makeRef()){
                    $this->__set($key,$item);
                }
            }
        }

    }

    /**
     *  Update the 'embeds' attribute for model's instance when that 'embeds' data  has changed.
     */
    private function _processEmbedsChanged(){

        $cache = $this->_cache;
        $attrs = $this->getAttrs();
        foreach($cache as $key => $item){
            if(!isset($attrs[$key])) continue;
            $attr = $attrs[$key];
            if($attr['type'] == 'embed'){
                if( $item instanceof Model && $this->cleanData[$key] !== $item->toArray()){
                    $this->__set($key,$item);
                }
            }else if($attr['type'] == 'embeds'){
                if( $item instanceof Collection && $this->cleanData[$key] !== $item->toEmbedsArray()){
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
     *  Tthe attribute of $key is a Embed
     *
     */
    private function setEmbed($key,$value)
    {
        $attrs = $this->getAttrs();
        $cache = &$this->_cache;
        $embed = $attrs[$key];
        $model = $embed['model'];
        $type = $embed['type'];

        if($type == "embed"){
            $model = $embed['model'];
            $type = $embed['type'];
            if($value instanceof $model){
                $value->setIsEmbed(true);
                $return  = $value->toArray(['_type','_id']);
            }else if($value == null){
                $return = null;
            }else{
                throw new \Exception ("{$key} is not instance of '$model'");
            }

        }else if($type == "embeds"){
            $arr = array();
            if(is_array($value)){
                foreach($value as $item){
                    if(! ( $item instanceof Model ) ) continue;
                    $item->setIsEmbed(true);
                    $arr[] = $item;
                }
                $value = Collection::make($arr);
            }

            if($value instanceof Collection){
                $return = $value->toEmbedsArray();
            }else if($value == null){
                $return = null;
            }else{
                throw new \Exception ("{$key} is not instance of '$model'");
            }
        }

        $cache[$key] = $value;

        return $return;
    }

    private function loadEmbed($key)
    {
        $attrs = $this->getAttrs();
        $embed = $attrs[$key];
        $cache = &$this->_cache;

        if(isset($this->cleanData[$key])){
            $value = $this->cleanData[$key];
        }else{
            $value = null;
        }

        $model = $embed['model'];
        $type = $embed['type'];
        if( isset($cache[$key]) ){
            $obj = &$cache[$key];
            return $obj;
        }else{
            if(class_exists($model)){
                if($type == "embed"){
                    if($value){
                        $data = $value;
                        $object = new $model($data);
                        $object->setIsEmbed(true);
                        $cache[$key] = $object;
                        return $object;
                    }
                    return null;
                }else if($type == "embeds"){
                    $res = array();
                    if(!empty($value)){
                        foreach($value as $item){
                            $data = $item;
                            $record = new $model($data);
                            $record->setIsEmbed(true);
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
     * unset a attribute
     * @param $key
     */
    private function _unset( $key ){
        if(is_array($key)){
            foreach($key as $item){
                $this->_unset($item);
            }
        }else{
            if(strpos($key,".") !== false){
                throw new \Exception('The key to unset can\'t contains "." ');
            }

            if(isset($this->cleanData[$key] )){
                unset($this->cleanData[$key]);
            }

            if(isset($this->dirtyData[$key] )){
                unset($this->dirtyData[$key]);
            }

            $this->unsetData[$key] = 1;
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
        $value = NULL;
	    if( isset($attrs[$key]) && isset($attrs[$key]['type']) ){
            if($attrs[$key]['type'] == 'reference' or $attrs[$key]['type'] == 'references' ){
                $value = $this->loadRef($key);
                return $value;
            }else if($attrs[$key]['type'] == 'embed' or $attrs[$key]['type'] == 'embeds' ){
                $value = $this->loadEmbed($key);
                return $value;
            }

		}

		if (array_key_exists($key, $this->cleanData))
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

		if(isset($attrs[$key]) && isset($attrs[$key]['type']) ){
            if($attrs[$key]['type'] == 'reference' or $attrs[$key]['type'] == 'references' ){
                $value = $this->setRef($key,$value);
            }else if($attrs[$key]['type'] == 'embed' or $attrs[$key]['type'] == 'embeds' ){
                $value = $this->setEmbed($key,$value);
            }
		}
		
		$value = $this->parseValue($key,$value);

		if(isset($this->cleanData[$key]) && $this->cleanData[$key] === $value){
			
		}else if($value){
			$this->cleanData[$key] = $value;
			$this->dirtyData[$key] = $value;
		}
		
	}

}
