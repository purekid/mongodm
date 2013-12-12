<?php

namespace Purekid\Mongodm;

/**
 * Mongodm - A PHP Mongodb ORM
 *
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @link     http://blog.missyi.com
 */
class Hydrator 
{

	public static function hydrate($class, $results,$type = "set")
	{
		
		if(!class_exists($class)){
			throw new \Exception("class {$class} not exists!");
		}else if($type == "set"){
			$models = array();
			foreach($results as $result){
				$model = self::pack($class,$result);
				$models[] = $model;
			}
			return Collection::make($models);
		}else{
			$model = self::pack($class,$results);
			return $model;
		}
		
		return null;
		
	}
	
	private static function pack($class,$result)
	{
		$model = new $class($result, true);
		if (isset($model->cleanData['_id']))
		{
			$id = (string) $model->cleanData['_id'];
		}
		return $model;
		
	}
	
	public static function hydrateRefs($class, $results,$type = "set")
	{
	
		if(!class_exists($class)){
			throw new \Exception("class {$class} not exists!");
		}else if($type == "set"){
			$models = array();
			foreach($results as $result){
				$model = self::pack($class,$result);
				$models[] = $model;
			}
			return self::makeSet($models);
		}else{
			$model = self::pack($class,$results);
			return $model;
		}
		return null;
		
	}
	
}
