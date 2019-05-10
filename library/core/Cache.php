<?php
/**
 * File: Cache.php
 * Author: 大眼猫
 * Functionality: Cache class for redis
 * Date: 2013-5-8
 */

abstract class Cache {

    public static function __callStatic($method, $params){
        $key = Pool::TYPE_REDIS;
        $redis = Pool::pop($key);
        if($redis){
            try{
                return call_user_func_array([$redis, $method], $params);
            }catch(Exception $e){
                Logger::error($e->getMessage());
                Pool::destroy($key);
                Pool::getInstance($key);
            }
        }else{
            Pool::destroy($key);
            Pool::getInstance($key);
        }
    }

}