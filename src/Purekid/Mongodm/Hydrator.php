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
 * Mongodm - A PHP Mongodb ORM
 *
 * @category Mongodm
 * @package  Mongodm
 * @author   Michael Gan <gc1108960@gmail.com>
 * @license  https://github.com/purekid/mongodm/blob/master/LICENSE.md MIT Licence
 * @link     https://github.com/purekid/mongodm
 */
class Hydrator
{

    /**
     * Hydrate
     *
     * @param string $class   class
     * @param array  $results results
     * @param string $type    type
     *
     * @return Model|null
     */
    public static function hydrate($class, $results, $type = "collection" , $exists = false)
    {

        if (!class_exists($class)) {
            throw new \Exception("class {$class} not exists!");
        } elseif ($type == "collection") {
            $models = array();
            foreach ($results as $result) {
                $model = self::pack($class, $result , $exists);
                $models[] = $model;
            }

            return Collection::make($models);
        } else {
            $model = self::pack($class, $results , $exists);
            return $model;
        }

        return null;

    }

    /**
     * Pack
     *
     * @param string $class  class
     * @param array  $result result
     *
     * @static
     *
     * @return type
     */
    protected static function pack($class, $result, $exists = false)
    {
        $model = new $class($result, true , $exists);
        return $model;
    }

    /**
     * Hydrate ref
     *
     * @param string $class   class
     * @param array  $results results
     * @param string $type    type
     *
     * @return type
     */
    public static function hydrateRefs($class, $results,$type = "set")
    {

        if (!class_exists($class)) {
            throw new \Exception("class {$class} not exists!");
        } elseif ($type == "set") {
            $models = array();
            foreach ($results as $result) {
                $model = self::pack($class, $result);
                $models[] = $model;
            }

            return self::makeSet($models);
        } else {
            $model = self::pack($class, $results);

            return $model;
        }

        return null;

    }

}
