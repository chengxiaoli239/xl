<?php
/**
 * 工具类：快存
 * Enter description here ...
 * @author "jimmy.dong@gmail.com"
 *
 */
namespace common\tools;
use common\classes\Runtime;
class QuickCache{
	private static $prefix = "QuickCache_";
	private static $buffer;
    private static $AIRPORT_KEY = 'AIRPORT_KEY';
	/**
	 * 快速存储，返回一个随机生成的key
	 * Enter description here ...
	 * @param unknown_type $data
	 */
	public static function add($data){
		//$m = \Cache::getInstance('default');
        $m = \Yii::$app->cache;
		//$key =  uniqid();
		$key = substr(md5(json_encode($data)),4,8);
		self::$buffer[$key] = $data;
		//取消时间限制 if($m->set(self::$prefix . $key, $data, SiteCacheTime)) return $key;
		if($m->set(self::$prefix . $key, $data, SiteCacheTime)) return $key;
		else return false;
	}
	
	/**
	 * 用Key读取快存内容
	 * Enter description here ...
	 * @param unknown_type $key
	 */
	public static function get($key){
		if(!$key) return false;
		if(self::$buffer[$key]) return self::$buffer[$key];
		$m = \Cache::getInstance('default');
		return $m->get(self::$prefix . $key);
	}
	
	/**
	 * 保存当前地址
	 * _c,_a,page 自动保存，其他参数需在数组中指定
	 * @param obj $request  框架request对象 OR 参数数组
	 * @param array $arr	需要保存的参数（配合request对象）
	 * @param boolean $strict 是否保存空值的参数
	 */
	public static function saveBackUrl($request, $arr = array(), $strict = false){
		if(is_array($request)){
			$re = $request;
		}
		else{
			$re['_c'] = $request->_c;
			$re['_a'] = $request->_a;
			$re['page'] = $request->page;
			if(is_array($arr))foreach($arr as $v){
				if($strict == false && $request->$v === '')continue;
				$re[$v] = $request->$v;
			}
		}
		return self::add($re);
	}
	
	/**
	 * 快速读取保存的地址
	 * Enter description here ...
	 * @param string $key
	 * @param boolean $array_format 返回地址还是原始数组
	 */
	public static function getBackUrl($key, $array_format = false){
		if(!$key)return false;
		$t = self::get($key);
//		\Debug::log('back_url', $t);
		if($array_format) return $t;
		else return template_url_encode($t);
	}

    /**
     * @function 随机生成key  -> airport
     * @return bool|string
     */
	public static function getAirportRandKey(){
        $uid = Runtime::getUid();

        if(isset($uid)&&!empty($uid)){
            $key = $uid;
        }else{
            if(!$_COOKIE[self::$AIRPORT_KEY]){
                //$_COOKIE[self::$AIRPORT_KEY] = self::add(rand());
                $rand_key = self::add(rand());
                setcookie(self::$AIRPORT_KEY, $rand_key , time()+3600*24*30, '/', SITE_COOKIE_DOMAIN);
                $_COOKIE[self::$AIRPORT_KEY] = $rand_key;
            }
            $key = $_COOKIE[self::$AIRPORT_KEY];
        }

        return $key;
    }

}
