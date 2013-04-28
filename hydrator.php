<?php namespace Mongodm;

class Hydrator {

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
			return ModelSet::make($models);
		}else{
			$model = self::pack($class,$results);
			return $model;
		}
		
		return null;
		
	}
	
	private static function pack($class,$result){
		
		$model = new $class;
		$model->data = (array) $result;
		$model->exists = true;
		if (isset($model->data['$id']))
		{
			$id = (string) $model->data['$id'];
		
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
