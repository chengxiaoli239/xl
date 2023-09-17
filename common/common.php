<?php

function p($data,$exit = true){
    echo '<pre>';
    print_r($data);
    $exit && exit;
}

function d($data, $exit = true){
    echo '<pre>';
    var_dump($data);
    $exit && exit;

}


function is_mobile()
{
    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
    $mobile_browser = '0';
    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
	    $mobile_browser++;
    if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
	    $mobile_browser++;
    if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
	    $mobile_browser++;
    if(isset($_SERVER['HTTP_PROFILE']))
	    $mobile_browser++;
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
    $mobile_agents = array(
		'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
		'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
		'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
		'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
		'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
		'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
		'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
		'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
		'wapr','webc','winw','winw','xda','xda-'
	);
    if(in_array($mobile_ua, $mobile_agents))
	    $mobile_browser++;
    if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
	    $mobile_browser++;
    // Pre-final check to reset everything if the user is on Windows
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
	    $mobile_browser=0;
    // But WP7 is also Windows, with a slightly different characteristic
    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
	    $mobile_browser++;
    if($mobile_browser>0)
	    return true;
    else
	    return false;
}


if (!function_exists('throw_info')) {
    function throw_info($message, $code=200, $data=[])
    {
        $exception = new \common\exceptions\InfoException($message, $code);
        $exception->setData($data);
        throw $exception;
    }
}

if (!function_exists('throw_warn')) {
    function throw_warn($message, $code=200, $data=[])
    {
        $exception = new \common\exceptions\WarnException($message, $code);
        $exception->setData($data);
        throw $exception;
    }
}


if (!function_exists('is_cli')) {
    function is_cli()
    {
        return preg_match("/cli/i", php_sapi_name()) ? 1 : 0;
    }
}

if (!function_exists('json_data')) {
    function json_data($data, $code = HTTP_CODE_OK)
    {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        \Yii::$app->response->setStatusCode($code);
        return $data;
    }
}

if (!function_exists('json_success')) {
    function json_success($data)
    {
        return json_data($data);
    }
}

if (!function_exists('json_warning')) {
    function json_warning($data)
    {
        return json_data($data, HTTP_CODE_BAD_REQUEST);
    }
}

if (!function_exists('json_error')) {
    function json_error($data)
    {
        return json_data($data, HTTP_CODE_SERVER_ERROR);
    }
}

if (!function_exists('get_session_data')) {
    function get_session_data($field, $default = '')
    {
        $sessionData = $_SERVER["session_data"] ?? [];
        return isset($sessionData[$field]) ? $sessionData[$field] : $default;
    }
}

if (!function_exists('is_json')) {
    function is_json($data)
    {
        json_decode($data);

        return (json_last_error() == JSON_ERROR_NONE);
    }
}

if (!function_exists('get_unique_id')) {
    /**
     * 获取唯一自增值
     */
    function get_unique_id()
    {
        //拿现在时间减去2021-01-01，加上100000000，确保时间戳是9位
        //拼接上6位微妙数
        //再拼接上3位随机数
        $randomCount = 3;
        $uSecondCount = 6;
        $secondCount = 9;
        $startTime = strtotime('2021-01-01');
        list($t1, $t2) = explode(' ', microtime());
        $diffTime = floatval($t2) - $startTime + pow(10, $secondCount-1);
        if ($diffTime < 0) {
            throw_info('时钟异常');
        }

        $uStr = floatval($t1*pow(10, $uSecondCount));
        $uStr = str_pad($uStr, $uSecondCount, '0', STR_PAD_LEFT);
        $timeStr = $diffTime . $uStr;
        $random = random_int(0, pow(10, $randomCount)-1);
        $randomStr = str_pad($random, $randomCount, '0', STR_PAD_LEFT);

        return intval("{$timeStr}{$randomStr}");
    }
}

if (!function_exists('export_file')) {
    function export_file($fullName, $filename)
    {
        set_time_limit(0);
        header('Content-type:application/octet-stream; charset=utf-8');
        header('Content-type: application/vnd.ms-excel');
        header( "Accept-Ranges:  bytes ");
        header( "Accept-Length: " .filesize($fullName));
        header( "Content-Disposition:attachment;filename= {$filename}");
        echo file_get_contents($fullName);
        @unlink($fullName);
        exit;
    }
}

if (!function_exists('check_is_running')) {
    function check_is_running($commandStr)
    {
        @exec("ps aux | grep {$commandStr}",$result);
        $sum  = count($result);
        if ($sum > 3) {
            var_dump('程序已在运行');exit(0);
        }
    }
}

if (!function_exists('msectime')) {
    function msectime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return $msectime;
    }
}

if (!function_exists('export_csv')) {
    function export_csv($fullName, $filename)
    {
        set_time_limit(0);
        header("Content-type:text/csv;charset=utf-8");
        header( "Content-Disposition:attachment;filename= {$filename}");
        header('Expires:0');
        header('Pragma:no-cache');
        header( "Accept-Length: " .filesize($fullName));
        echo file_get_contents($fullName);
        @unlink($fullName);
        exit;
    }
}


if (!function_exists('push_queue')) {

    /**
     * 入列
     * @param $jobClass string 类名
     * @param $params array 参数
     * @param $isRepush bool 是否重推
     * @return bool
     */
    function push_queue($jobClass, array $params, $isRepush = false)
    {
        try {
            $type = 'queue';
            $queue = \Yii::$app->queue;
            if (!empty($params['queue_fast'])) {
                $queue = \Yii::$app->queue_fast;
                $type = 'queue_fast';
            } elseif (!empty($params['queue_open'])) {
                $type = 'queue_open';
                $queue = \Yii::$app->queue_open;
            }
            \common\tools\Tool_Common::log('/queue/push-start', 'info', '入列开始1', ['params'=>$params, 'jobClass'=>$jobClass]);
            if (empty($jobClass)) {
                throw_info('队列类名不能为空');
            }
            $queueDelayTime = intval($params['queue_delay_time'] ?? 0);
            unset($params['queue_delay_time']);
            if (!$isRepush) {
                $queueId = get_unique_id();
                $log = new \common\models\QueueLog();
                $log->id = $queueId;
                $log->business_id = $params['business_id'] ?? '';
                $log->last_push_time = time();
                $log->type = $type;
                $log->delay = $queueDelayTime;
                $log->name = $jobClass::getName($params);
                $log->params = json_encode($params, 320);
                $log->job_class = $jobClass;
                $log->job_class_md5 = md5($jobClass);
                if (!$log->save()) {
                    throw_info('入列失败');
                }
            } else {
                \common\tools\Tool_Common::log('/queue/push-start', 'info', '入列开始2', ['params'=>$params, 'jobClass'=>$jobClass]);
                if (empty($params['id'])) {
                    throw_info('重推消息ID不能为空');
                }
                $queueId = $params['id'];
                $log = \common\models\QueueLog::find()->andWhere(['id'=>$params['id']])->limit(1)->one();
                if (empty($log)) {
                    throw_info('找不到消息队列:'.$params['id']);
                }
                $log->last_push_time = time();
                $log->save(false);
            }
            \common\tools\Tool_Common::log('/queue/push-start', 'info', '入列开始3-1', ['params'=>$params, 'jobClass'=>$jobClass, 'queueId'=>$queueId]);
            $job = new $jobClass($queueId);
            \common\tools\Tool_Common::log('/queue/push-start', 'info', '入列开始3-2', ['params'=>$params, 'jobClass'=>$jobClass, 'queueId'=>$queueId]);

            if ($queueDelayTime > 0 && !$isRepush) {
                \common\tools\Tool_Common::log('/queue/push-start', 'info', '入列开始4', ['params'=>$params, 'jobClass'=>$jobClass, 'queueId'=>$queueId]);
                $systemQueueId = $queue->delay($queueDelayTime)->push($job);
            } else {
                \common\tools\Tool_Common::log('/queue/push-start', 'info', '入列开始5', ['params'=>$params, 'jobClass'=>$jobClass, 'queueId'=>$queueId]);
                $systemQueueId = $queue->push($job);
            }
            $log->system_queue_id = $systemQueueId;
            $log->save(false);
            return true;
        } catch (\Exception $e) {
            \common\tools\Tool_Common::log('/queue/push', 'info', '入列异常', ['params'=>$params, 'jobClass'=>$jobClass, 'message'=>$e->getMessage().'-File-'.$e->getFile().'--line-'.$e->getLine()]);
            return false;
        }
    }
}

if (!function_exists('push_queue_fast')) {

    /**
     * 入列
     * @param $jobClass string 类名
     * @param $params array 参数
     * @param $isRepush bool 是否重推
     * @return bool
     */
    function push_queue_fast($jobClass, array $params, $isRepush = false)
    {
        $params['queue_fast'] = true;
        push_queue($jobClass, $params, $isRepush);
    }
}

if (!function_exists('push_queue_open')) {

    /**
     * 入列
     * @param $jobClass string 类名
     * @param $params array 参数
     * @param $isRepush bool 是否重推
     * @return bool
     */
    function push_queue_open($jobClass, array $params, $isRepush = false)
    {
        $params['queue_open'] = true;
        push_queue($jobClass, $params, $isRepush);
    }
}

if (!function_exists('filter_special_str')) {

    /**
     * 过滤字符串
     * @param $string
     * @return mixed
     */
    function filter_special_str($string, $specialStr = [])
    {
        $string = trim($string);
        if (empty($specialStr)) {
            $specialStr = ["\n", "\t", "\r", "\r\n"];
        }
        $string = str_replace($specialStr, '', $string);
        return $string;
    }
}


