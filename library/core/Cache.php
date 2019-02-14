<?php
/**
 * File: Cache.php
 * Author: 大眼猫
 * Functionality: Cache class for redis
 * Date: 2013-5-8
 */

abstract class Cache {

    public static function __callStatic($method, $params){
        $redis = Pool::pop(Pool::TYPE_REDIS);
        if($redis){
            try{
                return call_user_func_array([$redis, $method], $params);
            }catch(Exception $e){
                Pool::destroy(Pool::TYPE_REDIS);
                Pool::getInstance(Pool::TYPE_REDIS);
            }
        }else{
            Pool::destroy(Pool::TYPE_REDIS);
            Pool::getInstance(Pool::TYPE_REDIS);
        }
    }

}