<?php
namespace common\service;

use common\service\chat\Tool_Common;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use yii\db\ActiveRecord;
use yii\helpers\Json;

class BaseService  extends ActiveRecord
{

    // 禁用状态
    const STATUS_DISABLE = 0;

    // 激活状态
    const STATUS_ACTIVE = 1;

    public static  $s = [];
    /**
     * @author
     * 获取静态选项信息
     * 如如获取状态信息
     * $mVal参数不传时获取全部数据(值=>名称)：array(
     *     1 => '正常',
     *     2 => '停用',
     *     ...
     * )
     * $mVal参数传空字符串时获取全部值: array( 1, 2, ... )
     * $mVal参数传数字时返回对应的名称,没有对应信息时返回空字符串
     * $mVal参数传名称时返回对应的数字,没有对应信息时返回-1
     * @param array $sField 关键字段
     * @param mixed $mVal
     */
    public static function getS( string $sField,  $mVal = null )
    {

        $a_static = static::$s[$sField]??[];

        if ( $mVal === null )
        {
            return $a_static;
        } elseif ( $mVal === '' )
        {
            return array_keys( $a_static );
        } elseif ( is_numeric( $mVal ) || is_string( $mVal ) )
        {
            return $a_static[$mVal] ?? '';
        } else
        {
            return array_flip( $a_static )[$mVal] ?? -1;
        }
    }
    /**
     * 用于下拉选中的时候
     * @param  string $sField 字段
     * @param  string $v      值
     * @return array
     */
    public static function getSS(string $sField,$v)
    {
        $data = self::getS($sField);
        $l_data = [];

        foreach ($data as $key => $value) {
            $l_data[] = [
              'name'=>$value,
              'value'=>$key,
              'checked'=> $v!=''&&$key==$v?true:false
            ];
        }
        return $l_data;
    }
    /**
     * [getOne description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function getOne( $id )
    {
        $data = \Yii::$app->cache->get( self::getCacheKey( $id ) );
        if( empty($data) )
        {
            $ins=self::findOne( $id );
            if( empty( $ins ) )
            {
                return false;
            }
            $data = $ins->toArray();
            \Yii::$app->cache->set( self::getCacheKey( $id ), $data, 86400  );
        }
        return $data;
    }
    /**
     * 清除缓存
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function clearOne( $id )
    {
        return \Yii::$app->cache->delete( self::getCacheKey( $id ) );
    }
    /**
     * 缓存标识
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function getCacheKey($id)
    {
        return join(":",[
            self::tableName(),
            'find',
            $id
        ]);
    }

    /**
     * 日期范围格式
     * @param  string $dateRange 2020-09-09 - 2020-09-17 || 2020-09 - 2020-10 || 2020-09-09 12:12:12 - 2020-09-17 23:59:59
     * @return [type]             [description]
     */
    public static function getRange(  $dateRange )
    {
        $dates = explode( ' - ', $dateRange );
        $startDate = trim($dates[0]);    // substr(trim($dates[0]), 0, 10);
        $endDate = trim($dates[1]); // substr(trim($dates[1]), 0, 10);

        $endDates = explode('-', $endDate);

        if (!empty($endDates) && count($endDates) == 2) {
            $endDate = date('Y-m-t', strtotime($endDate));
        }

        $endTime = strtotime( $endDate );
        if (strlen($endDate) <= 10) {
            $endTime = strtotime( $endDate ) + 86400-1;
        }

        return [
            (strtotime( $startDate )),
            $endTime
        ];
    }

    /**
     * 格式化手机，身份证***代替
     * @param  [type]  $mobile
     * @param  integer $start
     * @param  integer $length
     * @return [type]
     */
    public static function formatMobile( $mobile, $start=5, $length=4 )
    {
        $star = '*';
        $stars = [];

        for ($i=0; $i <$length ; $i++) {
           $stars[] = $star;
        }

        return substr(  $mobile,0, $start  ).join('',$stars).substr(  $mobile,$start+$length  );
    }

    /**
     * 多个价格相加
     * @param  [type] $aAmounts [description]
     * @param  bool $format 是否字符串格式化
     * @return [type]           [description]
     */
    public static function getFloatSum( $aAmounts, $format = false)
    {
        $amount = 0;
        foreach ($aAmounts as $key => $value) {
            $amount = floatval( $value )  + $amount;
        }
        $amount = round($amount, 2);

        return $format ? self::formatMoney($amount) : $amount;
    }

    public static function formatMoney( $money )
    {
        return sprintf("%1\$.2f",$money);
    }

    /**
     * 获取某个时间戳所属月份的开始和结束日期
     * @param  int  $iTime
     * @return array
     */
    public static function monthDate($iTime)
    {
        if(empty($iTime)) {
            return '';
        }

        $iTime = self::getSubTime( $iTime );

        return [
            date('Y-m-01', $iTime),
            date('Y-m-t', $iTime)
        ];
    }

    /**
     * 格式化时间|支持毫秒级格式化
     * @param  int    $iTime
     * @return [type]
     */
    public static function formatTime( $iTime )
    {
        if( empty($iTime) )
        {
            return '';
        }
        $iTime = self::getSubTime( $iTime );
        return date("Y-m-d H:i:s",$iTime);
    }
    /**
     * 截取毫秒级时间戳
     * @return [type] [description]
     */
    public static function getSubTime( $iTime )
    {
        if( empty($iTime) )
        {
            return 0;
        }
        return substr((string)$iTime,0,10);
    }


    /**
     * 以某字段作为键名返回数据
     * @param  array $data
     * @param  string $field
     * @return array
     */
    public static function getFieldKey( $data, $field ){
       return \common\tools\UtilArray::getFieldKey( $data, $field );
    }
    /**
     * 以某字段作为键名返回数据:多个
     * @param  array $data
     * @param  string $field
     * @return array
     */
    public static function getFieldKeys($data, $field)
    {
        return \common\tools\UtilArray::getFieldKeys( $data, $field );
    }

    /**
     * 支持同时检索多个拆分成数组
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public static function getQuerySplit( $value )
    {
        $values=preg_replace('/\s+/','#',$value);
        $values=str_replace(array("\r\n", "\r", "\n",",",'，'), "#", $values);
        $values=explode('#',$values);
        return $values;
    }

    /**
     * [getChange description]
     * @return [type] [description]
     */
    public static function getChange( $beforeData, $newData, $fields )
    {
        $a_change = array();
        $a_before = array();
        $a_after = array();
        foreach ($fields as $field)
        {
           if( !empty( $beforeData[$field] ) && $beforeData[ $field ] != $newData[ $field ] )
           {
              $a_change[] = [
                'field'=>$field,
                'before'=>$newData[$field],
                'after'=>$beforeData[$field]
              ];
              $a_before[ $field ] = $newData[$field];
              $a_after[ $field ] = $beforeData[$field];
           }
        }
        return [$a_change,$a_before,$a_after];
    }

    /**
     * 获取一条记录
     * @param  string|array $where   过滤条件
     * @param  array|string|null $fields 查询字段
     * @param  boolean      $asArray 是否作为数组返回
     * @return array|ActiveRecord|null
     */
    public static function get($where, $fields = null, $asArray = true)
    {
        $query = self::find()->where($where)->limit(1);

        if ($asArray) {
            $query = $query->asArray();
        }

        if ($fields) {
            $query->select($fields);
        }

        return $query->one();
    }

    /**
     * 获取一个记录实例
     * @param  string|array $where   过滤条件
     * @return array|ActiveRecord|null
     */
    public static function getEx($where)
    {
        return self::get($where, null, false);
    }

    public static function request($url='', $params=[], $headers=[]){
        $client = new Client();
        $bHeaders = [
            'Content-Type' => 'application/json; charset=utf8',
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1'
        ];
        if(!empty($headers)){
            $headers = array_merge($headers, $bHeaders);
        }else{
            $headers = $bHeaders;
        }
        $response = $client->request('POST', $url, [
            RequestOptions::HEADERS   => $headers,
            RequestOptions::BODY => Json::encode($params),
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode != 200) {
            \Yii::error('Http请求接口出错：' . $response->getReasonPhrase());
            return false;
        }
        $body = $response->getBody()->getContents();
        $response = Json::decode($body) ?: false;

        Tool_Common::log('/request/'.__FUNCTION__, 'INFO', '接口请求', ['url'=>$url, 'params'=>$params, 'response'=>$response]);

        return $response;
    }
}
