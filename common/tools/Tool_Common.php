<?php
/**
 *  工具类
 * author: lianyuanfu
 * Date: 2015-07-22
 */
namespace common\tools;
class Tool_Common
{

	const PREKEY = "CC2_PAY_";
        private static $_traceId = null;
	/**
	 * 生成支付号
	 * 
	 * @param array $orderSn  订单号（数组）
	 * @param boolean $rebuild 知否强制重新生成
	 * @return string
	 */
    public static function buildPaySn($orderSn, $rebuild = false)
    {
        $timeArr = explode(' ', microtime());
        $second = $timeArr[1];                     // 秒
        $microsecond = substr($timeArr[0], 2, 6);  // 微秒
        
        // 单个订单不需要强制刷新的走方案一
        if (!empty($orderSn) && is_array($orderSn) && count($orderSn) == 1 && strlen($orderSn[0]) == 14 && !$rebuild) {

        	$paySn = $orderSn[0] . self::makeRandNum(6);     // 位数分布：订单号14位  + 6个随机数

        } else { // 多个订单  或者银联快捷等每次都需要重新生成的走方案二

        	$paySn = substr($second, 1, 9) . $microsecond . self::makeRandNum(5);  // 位数分布：9 + 6 + 5

        }

        return $paySn;
    }
    
    /**
     * 生成固定长度的随机数
     * 
     * @param int $len
     * @return string
     */
    public static function makeRandNum($len)
    {
    	$rand = mt_rand(0, pow(10, $len) - 1);
    	return str_pad($rand, $len, '0', STR_PAD_LEFT);
    }
    
    /**
     * 从多维数组中提取相关数据
     * 
     * @param array $orderList
     * @param string $filedName
     * @return array
     */
    public static function getListFieldData($List, $fieldName)
    {
    	if (empty($List) || !is_array($List) || !self::isMultiArray($List)) {
    		return false;
    	}
    	
    	$datas = array();
    	foreach ($List as $v) {
    		if (isset($v[$fieldName])) {
    			$datas[] = $v[$fieldName];
    		}
    	}
    	return $datas;
    }

    /**
     *  生成当前支付操作对应的唯一字符串
     */
    public static function createPayKey($payType, $param, $rand = false)
    {
        if (empty($param))  return false;
        
        if ($rand) {
        	return md5(self::randStr(20) . 'create_pay_key_by_rand');
        }

        $tmpStr = '';
        ksort($param);
        foreach ($param as $id=>$money) {
            if (!empty($id) && $money >=0 ) {
                $tmpStr .= $id . '|' . $money . '||';
            }
        }
        $tmpStr = $payType . substr($tmpStr, 0, strlen($tmpStr) - 1) . 'create_pay_key';

        return md5($tmpStr);
    }
    
    /**
     * 生成随机码
     *
     * @param int    $length
     * @param string $pattern
     * @return string
     */
    public static function randStr($length, $pattern = null)
    {
    	if (! $pattern) {
    		$pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    	}
    	$key = '';
    	for ($i = 0; $i < $length; $i ++) {
    		$key .= $pattern{rand(0, (strlen($pattern) - 1))};
    	}
    	return $key;
    }

    /**
     * 获得用户的真实IP地址
     *
     * @return  string
     */
    public static function getUserIp()
    {
        global $HTTP_SERVER_VARS;
        if (isset($HTTP_SERVER_VARS)) {
            if (isset($HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"])) {
                $ip = $HTTP_SERVER_VARS["HTTP_X_FORWARDED_FOR"];
            } elseif (isset($HTTP_SERVER_VARS["HTTP_CLIENT_IP"])) {
                $ip = $HTTP_SERVER_VARS["HTTP_CLIENT_IP"];
            } else {
                $ip = $HTTP_SERVER_VARS["REMOTE_ADDR"];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else {
                $ip = getenv('REMOTE_ADDR');
            }
        }
        $arr = explode(',', $ip);
        $merCustomIp = !empty($arr[0]) ? trim($arr[0]) : '14.17.85.32';

        //IP去除空格
        if (stripos($merCustomIp, '.') !== false) {
            $tmpArr = explode('.', $merCustomIp);
            $tmpArr = array_map('trim', $tmpArr);
            if (!empty($tmpArr)) $merCustomIp = implode('.', $tmpArr);
        }
        return $merCustomIp;
    }
	
	/**
     * 取得IP
     *
     * @return IP
     */
    public static function realIp ()
    {
        if (isset($_SERVER['HTTP_CDN_SRC_IP']) && $_SERVER['HTTP_CDN_SRC_IP']!='unknown') {
            $realip =$_SERVER["HTTP_CDN_SRC_IP"];
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');
        }
    
        $realip = explode(',', $realip);
        return $realip[0];
    }

    /**
     * 转换字符串为指定字符集
     *
     * @param string $input
     * @param string $_output_charset
     * @param string $_input_charset
     *
     * @return string $output
     */
    public static function setCharset($input,$_output_charset ,$_input_charset)
    {
        $output = "";
        if (!isset($_output_charset) ) $_output_charset  = $_input_charset;
        if ($_input_charset == $_output_charset || $input ==null ) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input,$_output_charset,$_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset,$_output_charset,$input);
        } else {
            die("sorry, you have no libs support for charset change.");
        }
        return $output;
    }

    /**
     * @param string $str
     * @return bool
     */
    public static function isUtf8($str)
    {
        if (empty($str)) return false;
        if (preg_match("/^([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}/",$str) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){1}$/",$str) == true || preg_match("/([".chr(228)."-".chr(233)."]{1}[".chr(128)."-".chr(191)."]{1}[".chr(128)."-".chr(191)."]{1}){2,}/",$str) == true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *  模拟提交数据
     */
    public static function doHttpRequest($url, $param=array(), $method = 'post', $timeOut = 5, $conTimeOut = 3, $sslVerify = true, $useAgent = null)
    {
        $returnResult = array('status'=>0, 'content'=>'');
        $url = trim($url);
        $requestData = http_build_query($param);   //待提交数据

        $ch=curl_init();
        if ($useAgent) {
        	curl_setopt($ch, CURLOPT_USERAGENT, $useAgent);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0) ;
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $conTimeOut);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        switch(strtolower($method)){
            case 'get':
                curl_setopt($ch,CURLOPT_URL,$url . '?' . $requestData);
                break;
            case 'post':
            default:
                curl_setopt($ch,CURLOPT_URL,$url);
                curl_setopt($ch,CURLOPT_POST, 1 );
                curl_setopt($ch,CURLOPT_POSTFIELDS, $requestData);
                break;
        }
        if (!$sslVerify) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $result=curl_exec($ch);
        if (curl_errno($ch)) {
            $returnResult['content'] = curl_error($ch);
        } else {
            $returnResult['status'] = 1;
            $returnResult['content'] = $result;
        }
        curl_close($ch);
        return $returnResult;
    }
    
    /**
     * curl提交 - 加重试
     */
    public static function httpRequest($url, $param=array(), $method = 'post', $timeOut = 5, $conTimeOut = 3, $sslVerify = true, $useAgent = null, $retry = 1)
    {
    	$result = self::doHttpRequest($url, $param, $method, $timeOut, $conTimeOut, $sslVerify, $useAgent);
    	if ($retry >= 1 && empty($result['status'])) {
    		$i = 1;
    		while ($i <= $retry) {
    			$i ++;
    			$result = self::doHttpRequest($url, $param, $method, $timeOut, $conTimeOut, $sslVerify, $useAgent);
    			if (!empty($result['status'])) {
    				return $result;
    			}
    		}
    		
    		return $result;
    	} else {
    		return $result;
    	}
    }

    /**
     *  mysql数据库链接
     */
    public static function mysqlConnect($config)
    {
        if (empty($config)) return false;

        $host = isset($config['host']) ? trim($config['host']) : '';
        $port = isset($config['port']) ? trim($config['port']) : '';
        $userName = isset($config['username']) ? trim($config['username']) : '';
        $password = isset($config['password']) ? trim($config['password']) : '';
        $database = isset($config['database']) ? trim($config['database']) : '';

        if (empty($host) || empty($userName)
            || empty($password) || empty($database)) {
            return false;
        }

        try {
            if (!empty($port)) {
                $connect = mysql_connect($host . ":{$port}", $userName, $password, 1, MYSQL_CLIENT_IGNORE_SPACE);
            } else {
                $connect = mysql_connect($host, $userName, $password, 1, MYSQL_CLIENT_IGNORE_SPACE);
            }
        } catch (Exception $e) {
            throw new Exception('Sorry, the server is busy now ........');
        }

        mysql_select_db($database, $connect);
        mysql_query("SET NAMES utf8", $connect);

        return $connect;
    }

    /**
     *  对象转数组
     */
    public static function objectToArray($array)
    {
        if(is_object($array)) {
            $array = (array)$array;
        }
        if(is_array($array)) {
            foreach($array as $key=>$value) {
                $array[$key] = self::objectToArray($value);
            }
        }
        return $array;
    }

    /**
     *  发送短信
     *
     *  @param string $mobile
     *  @param string $content
     *  @return mixed
     */
    public static function sendSms($mobile, $content)
    {
        $returnResult = array('status'=>0, 'content'=>'');
        if (empty($mobile) || empty($content)) {
            $returnResult['content'] = '手机号和短信内容不能为空';
            return $returnResult;
        }

        $postData = array(
            'requestType' => 'json',
            'sysId' => 'vippay',
            'value' => array(
                'type' => 'VIPPAY1',
                'event' => '1',
                'eventTime' => '60',
                'sms' => array(
                    'phoneNo' => $mobile,
                    'content' => $content,
                    'sendTime' => date('Y-m-d H:i:s'),
                    'userId' => 'pay'
                )
            )
        );
        $postData['token'] = md5($postData['sysId'] . 'vippay_7fdb35e9');
        $postData['value'] = json_encode($postData['value']);

        $url = 'https://' . DOMAIN_CHS . ':8443/message-center/sms/app/send?'.http_build_query($postData);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        $output = curl_exec($ch);
        if (curl_errno($ch)) {
            $returnResult['content'] = curl_error($ch);
        } else {
            $returnResult['status'] = 1;
            $returnResult['content'] = $output;
        }
        unset($output);
        curl_close($ch);
        
        //记录日志
        $logArr = array('mobile'=>$mobile,'content'=>$content,'result'=>$returnResult);
        Tool_Common::log('sms_record', 'INFO', '发送短信', $logArr);
        
        return $returnResult;
    }

    /**
     *  签名
     *
     *  @param array $param
     *  @param string $signKey
     *  @return string
     */
    public static function paySign($param, $signKey)
    {
        if (empty($param) || !is_array($param) || empty($signKey)) return false;
        ksort($param);
        reset($param);
        $signStr = '';
        foreach ($param as $key=>$val) {
            if ($val == '' || $key == 'token' || $key == 'api_sign' || $key == 'api_key') continue;
            $signStr .= $key . '=' . $val . '&';
        }
        $signStr = substr($signStr,0,strlen($signStr)-1);
        return md5($signStr.$signKey);
    }
    
    /**
     * 获取配置
     * 
     * @param string $section
     * @reurn array|bool
     */
    public static function getPayIni($section = null)
    {
        $iniFile = ROOT_PATH . '/application/modules/Config/pay.ini';
        if (!file_exists($iniFile)) {
            throw new Exception('Ini file not exists:' . $iniFile);
        }
        
        $iniData = include $iniFile;
        
        if (!is_array($iniData)) {
            throw new Exception('Ini data is not a array');
        }
        
        if ($section) {
            return isset($iniData[$section]) ? $iniData[$section] : null;
        }
        
        return $iniData;
    }
    
    /**
     * @desc 获取配置中心配置信息
     * @param $key
     */
    public static function getCcConfig($key, $domain = 'B2C_pay.vip.com_1', $autoPre = true)
    {
    	$configCenterEntity = self::_getConfigCenterEntity();
    	
    	if ($autoPre) {
    	    $key = self::PREKEY.$key;
    	}
    	
    	try {
    		$data = $configCenterEntity->cc_getConfigValueByKey($key, $domain);
    	} catch (Exception $e) {
    		Tool_Common::log('getCcConfig_failed','ERR','获取配置中心信息失败key:'.$key,$e->getMessage());    		
    		return self::getPayIni($key);  // 针对目前只接入的pay.ini
    	}
    	return json_decode($data,true);
    }
    
    /**
     * 创建自动提交表单
     * 
     * @param string $action
     * @param array $params
     * @param string $method
     * @param string $butName
     * @param string $title
     */
    public static function createForm($action, $params, $method = 'post', $butName = '提交到银行支付', $title = null)
    {
        if (!$action) {
            throw new Exception('Parameters "action" cannot be empty');
        }
        if (empty($title)) {
            $title = '免税易购：海口美兰机场免税店官方唯一预定网站_确保正品_确保低价_线上付款';
        }
        
        $html = '<html><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $html .= '<head><title>' . $title . '</title></head>';
        $html .= '<body> Loading... ';
        $html .= '<form style="display:none" name="dataSubmit" action="' . $action . '" method="' . $method . '">';
        
        foreach($params as $key => $val) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
        }
        
        $html .= '<input type="submit" value="' . $butName . '" /></form>';
        $html .= "<script type='text/javascript'>document.forms['dataSubmit'].submit();</script>";
        $html .= '</body></html>';
        
        return $html;
    }

    /**
     * 日志
     *
     * @param string $file
     * @param string $priority
            EMERG       紧急:系统无法使用  数据库连不上、redis连不上等
            ALERT       警告:必须采取行动
            CRIT        关键:关键条件
            ERR         错误:错误条件
            WARN        警告:警告条件
            NOTICE      注意:正常的但重要的条件
            INFO        信息:信息消息
            DEBUG       调试:调试消息
     * @param string $title  标题
     * @param mixed  $logArr  可以是字符串、数组（推荐数组）
     * @example Tool_Common::log('file', 'DEBUG', '测试标题', '测试内容');
     * @return boolean
     */
    public static function log($file, $priority, $title, $logArr = '')
    {
        static $logSwitch = null;
        $priorities = array('DEBUG', 'INFO', 'WARN', 'ERR', 'CRIT', 'ALERT', 'EMERG');
        // 日志分隔符
        $split = ' || ';
    
        $priority = strtoupper($priority);
        if (!in_array($priority, $priorities)) {
            $priority = 'ERR';
        }
        
        // 可在后台控制开关 后台设置的为关闭的
//        if (in_array($priority, array('DEBUG', 'INFO', 'WARN'))) {
//            
//            if ($logSwitch === null) {
//                $logSwitch = self::getAdminConfig('pay_log_switch');
//            }
//
//            if (!empty($logSwitch) && !empty($logSwitch[0]) && in_array($priority, $logSwitch)) {
//                return false;
//            }
//            
//        }
    
        if (strpos($file, '/') !== false) { // 有包含路劲的
            $dir = dirname($file);
            $file = basename($file);
        } else {
            $dir = '/WORK/LOG/'.\Yii::$app->params['LOG_PATH'].'/' . date('Ymd');
        }
    
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    
        // 获取IP
        if (php_sapi_name() == 'cli') {
            $ip = '127.0.0.1';  // 脚本模式，也记录一下占个位置
        } else {
            $ip = self::getUserIp();
        }
    
        // 完整路劲
        $fullFile = $dir . '/' . $file . '.log';
    
        // 获取到上一级执行的类名::方法 或者 文件名::行号
        $arr = debug_backtrace();
        
        if (isset($arr[1])) { // 是在类的方法中执行
            if (isset($arr[1]['class'])) {
                $backtrace1 = $arr[1]['class'];
                $backtrace2 = $arr[1]['function'];
            } else {
                $backtrace1 = basename($arr[1]['file']);
                $backtrace2 = $arr[1]['line'];
            }
        } else { // 取文件名和行号
            $backtrace1 = basename($arr[0]['file']);
            $backtrace2 = $arr[0]['line'];
        }
    
        $backtrace = $backtrace1 . '::' . $backtrace2;
        // 日志内容-数组自动处理
        if (is_array($logArr)) {
            $arr = array();
            foreach ($logArr as $k => $v) {
                $v = is_array($v) ? json_encode($v, 320) : $v;
                $arr[] = is_numeric($k) ? $v : ($k . ':' . $v);
            }
            $content = implode($split, $arr);
        } else {
            $content = $logArr;
        }
        
        // 去除换行，确保每条日志是一行
        $content = preg_replace('/(\r\n)|\r|\n/', ' ', $content);
    
        // 完整日志
        $str = date('Y-m-d H:i:s') . ' [' . $priority . '] trace:' . self::getId() . ' ' . $backtrace . ' ' .  $ip . ' [' . $title . ']';

        if ($content) {
            $str .= ' == ' . $content;
        }
        
        // 以下为日志示例：
        // 2014-03-04 15:35:12 [ERR] trace:25145 Model_Payment_AlipayApp::request 192.168.52.189 [查询失败] == pay_sn:XXX || pay_type:XXX || ...

        $fp = fopen($fullFile, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, $str . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }
    
    /**
     * 设置或读取js、css版本号
     * 
     * @param string $method
     * @param string $values
     * @return array|bool
     */
    public static function staticVer($method = 'get', $value = null)
    {
        /* 返回：
        array(
            'cssVer' => '1.1',
            'jsVer'  => '1.1'
        );*/
        return true;
        $key = 'pay_static_ver';
        
        //$redis = Server_Redis::factory('pay_master');

        if ($method == 'get') {
            
            $value = $redis->get($key);
            if ($value) {
                $value = json_decode($value, true);
            }
            if (!$value) {  // 没取到的话，返回当天的
                $value = array(
                    'cssVer' => date('Ymd'),
                    'jsVer'  => date('Ymd'),
                );
            }
            return $value;
            
        } else {
            $rs = $redis->set($key, json_encode($value));
            if (!$rs) {
                return false;
            }
            return true;
        }
    }
    
    /**
     * @desc 获取配置中心实例
     * @return Ambigous <NULL, ConfigCenter>
     */
    private static  function _getConfigCenterEntity()
    {
    	static $_instance = null;
    	if($_instance == null ) {
    		require_once ROOT_PATH.'application/library/Cc/ConfigCenter.class.php';
    		$_instance = new ConfigCenter();
    	}
    	 
    	return $_instance;
    }
    
	/**
     * 获取后台配置
     *
     * @param string $section
     * @param mixed $default
     * @reurn string|bool
     */
    public static function getAdminConfig($key, $default = null)
    {
        static $storage = array();
        
        if (isset($storage[$key])) {
            return $storage[$key];
        }

        // 优先级：redis->本地文件->数据库
        do {
        	
        	$value = self::_getAdminConfigByCache($key);

        	if (false !== $value) {
        		break;
        	}

            $value = self::_getAdminConfigByfile($key);
            
            if (false !== $value) {
                break;
            }

            $value = self::_getAdminConfigByDb($key);
        } while (false);
        
        if (null === $value || false === $value) {
            $value = $default;
        }

        $storage[$key] = is_array($value) ? $value : trim($value);

        return $storage[$key];
    }
    
    private static function _getAdminConfigByCache($key)
    {
        // 缓存前缀
        $redisKeyPer = 'pay_admin_config_';
        
        try {
            //$redis = Server_Redis::factory('pay_master');
        } catch (Exception $e) {
            return false;
        }
        
       // $value = $redis->get($redisKeyPer . $key);
        if (false === $value) {
            return false;
        }
        
        return json_decode($value, true);
    }
    
    private static function _getAdminConfigByDb($key)
    {
        /* @var $adminConfigDao Model_Dao_AdminConfig */
        $adminConfigDao = Tool_Common::getDataModel('Model_Dao_AdminConfig', 'pay_slave');
        
        $value = $adminConfigDao->getValueByKey($key);

        if (false === $value) {
            return false;
        }
        
        return json_decode($value, true);
    }
    
    private static function _getAdminConfigByfile($key)
    {
        // 文件
        $configFile = '/apps/logs/pay/adminConfig.ini';
        if (!file_exists($configFile)) {
            return false;
        }
        
        $datas = file_get_contents($configFile);
        $json = json_decode($datas, true);

        return isset($json[$key]) ? $json[$key] : false;
    }

	/**
     * 获取数据模型
     * 
     * @param string $className
     * @param string $dbName  master|pay_master|pay_slave|slave
     * @return object
     */
    public static function getDataModel($className, $dbName = 'pay_master')
    {
        static $instance = array();
        
        $cacheKey = $className . '-' . $dbName;
        
        if (empty($instance[$cacheKey])) {

           $pdo = Server_Pdo::factory($dbName);
        
            $instance[$cacheKey] = new $className($pdo);
        }
        
        return $instance[$cacheKey];
    }

    /**
     * 是否pad
     * 
     * @return boolean
     */
    public static function isIpad () 
    {
		$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		if (!$userAgent) {
			return false;
		}

		return strpos(strtolower($userAgent), 'ipad') === false ? false : true;
	}
	
	/**
	 * 重置数组
	 *
	 * @param array $array
	 * @return array
	 */
	public static function resetArray($array)
	{
	    $newArr = array();
	    if (!is_array($array)) {
	        return $newArr;
	    }
	    foreach ($array as $k => $v) {
	        $newArr[] = $v;
	    }
	    return $newArr;
	}
	
	/**
	 * 是否多维数组
	 *
	 * @param mixed  $value
	 * @return boolean
	 */
	public static function isMultiArray($value)
	{
	    if (! is_array($value)) {
	        return false;
	    }
	    foreach ($value as $arr) {
	        if (is_array($arr)) {
	            return true;
	        }
	    }
	    return false;
	}

	/**
	 * 转换数组
	 *
	 * @param array $array
	 * @param string $field
	 * @return array
	 */
	public static function convertArray($array, $field = 'id')
	{
		$newArr = array();
		foreach ($array as $arr) {
			$newArr[$arr[$field]] = $arr;
		}
		return $newArr;
	}

	/**
	 * 
	 * aes加密
	 * @param $input
	 * @param $key
	 */
    public static function aesEncrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = self::_pkcs5Pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return $data;
    }
 
    private static function _pkcs5Pad ($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
    /**
     * bank转换为10位字符 前补0
     *
     * @param int $bankSn
     * @return string
     */
    public static function bankSnTo10Len($bankSn)
    {
    	if (strlen($bankSn) >= 10 || empty($bankSn)) {
    		return $bankSn;
    	}
    
    	return str_pad($bankSn, 10, '0', STR_PAD_LEFT);
    }
    
    /**
     * 格式化数字输出(对数字进行四舍五入)
     *
     * @param mixed $data 数字或包含数字的数组
     * @param int $length 小数点后保留的位数(默认保留2位)
     * @return array|float
     */
    public static function formatRoundNumber($data, $length = 2)
    {
    	if (is_array($data)) {
    		foreach ($data as $n => $number) {
    			if (is_numeric($number)) {
    				$data[$n] = round($number, $length);
    
    			} elseif (is_array($number)) {
    				$data[$n] = self::formatRoundNumber($number, $length);
    
    			} else {
    				$data[$n] = $number;
    			}
    		}
    
    	} elseif (is_numeric($data)) {
    		$data = round($data, $length);
    
    	}
    
    	return $data;
    }
    
    public static function getId() {
        if (!self::$_traceId) {
            self::$_traceId = time() . rand(10000, 99999);
        }
        return self::$_traceId;
    }

}
