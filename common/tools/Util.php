<?php
/**
 * 通用处理类
 * Enter description here ...
 * @author sam
 * @authors wudean(bj) 乔迁标识
 *
 */
namespace common\tools;
use yii;

class Util
{
    //获取客户端IP
    public static function getIp()
    {
        $ip = '';
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_FROM', 'REMOTE_ADDR') as $v) {
            if (isset($_SERVER[$v])) {
                if (!preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $_SERVER[$v])) continue;
                $ip = $_SERVER[$v];
            }
        }
        return $ip;
    }

    //验证邮箱
    public static function checkEmail($email)
    {
        if ($email == '请输入您的常用邮箱')
            return false;
        if (preg_match("/^[0-9a-zA-Z]+(?:[\.\_\-][a-z0-9\-]+)*@[a-zA-Z0-9]+(?:[-.][a-zA-Z0-9]+)*\.[a-zA-Z]+$/i", $email)) {
            return true;
        } else {
            return false;
        }
    }

    //昵称是否存在特殊字符
    public static function isExistsSpecialChar($nick)
    {
        $string = $nick;
        $re = "/[\?#%^&*@$!`~\/\s\.\",，()\\\<>+=]/isu";
        $m = preg_match($re, $string);
        return $m;
    }

    /**
     * 批量删除数组中的key和值
     * @static
     * @param $arr  处理的数组  注意这里是引用传递
     * @param $keys <array(k1,k2,k3,kn....)>    删除的键列表
     * @return  void
     */
    public static function batRemoveArrKeys(&$arr, $keys)
    {
        foreach ($keys as $k) {
            if (isset($arr[$k]) || $arr[$k] === null) {
                unset($arr[$k]);
            }
        }
    }

    //检查信息是否有效（即：是否包括禁止词）
    public static function isValidChkText($text, $type = 2)
    {
        $client = \ThriftClient::getInstance('WidgetService');
        $client->type = $type;
        $client->data = $text;
        $res = $client->valiData();
        if ($res == -1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 图片转换为oss获取图片
     * @param string $img 图片url
     * @param number $h 图片高度，默认400
     * @param number $w 图片宽度，默认400
     * @return string
     * @Ps 此处由武德安（北京技术）乔迁，原内容有调整
     */
    public static function convertImgae($img, $h = 400, $w = 400)
    {
        $goods_img = strpos($img, 'http') === false ? 'https://img.mianshui365.com' . $img : $img;
//        $goods_img .= "@{$h}h_{$w}w_95q_1wh";
        $goods_img = str_replace("cdn.mianshui365.com", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn2.mianshui365.com", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn2.mianshui365.net", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn.mianshui365.net", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("images.mianshui365.com", "img.mianshui365.com", $goods_img);
        if (strpos($goods_img, "@") === false) {
            $goods_img .= "@" . $h . "h_" . $w . "w_95q_1wh";
        }
        return $goods_img;
    }

    /**
     *
     * 加密id成字符串，用于保护id不被人发现规律
     * @param int $id
     * @return false | string
     */
    public static function encryptId2Str($id)
    {
        if (self::unsignedInt($id)) {
            return false;
        }
        $id_1 = rand(pow(10, strlen($id) - 1), pow(10, strlen($id)));
        $id_2 = $id_1 + $id;
        $dash = chr(rand(0x6b, 0x7a));
        return self::num2char($id_1) . $dash . self::num2char($id_2);
    }

    public static function unsignedInt($str)
    {
        return (self::int($str) && $str > 0);
    }

    /**
     * 验证是否是整数。
     * 1、支持正、负整数
     * 2、支持字数串表示法。如：'-200'、'125'。
     * 原由：php的is_int方法只能判断数值型变量，纯数字的字符串认为不是整数。但实际应用时，纯数字的字符串需要被当成整数来处理。
     * @param mixed $str
     * @return boolean
     */
    public static function int($str)
    {
        if (!is_scalar($str)) return false;
        # 修正bug。否则当$str === true时，该函数返回true：问题出在preg_match函数。
        if (is_bool($str)) {
            return false;
        }
        return (boolean)preg_match('#^\-?\d+$#', $str);
    }

    /**
     *
     * 数字转换成对应的字符
     * @param int $num
     * @return string
     */
    private static function num2char($num)
    {
        $str = '';
        $num = (string)$num;
        for ($i = 0; $i < strlen($num); $i++) {
            $str .= chr($num[$i] + 97);
        }
        return $str;
    }

    /**
     * 确保是传入变量一定是数组
     */
    public static function checkArray($arr)
    {
        if (is_array($arr) == false)
            return array();
        return $arr;
    }

    /*
     *确保窜入变量一定是字符串
     */
    public static function checkString($str)
    {
        if (is_string($str) == false)
            return '';
        return $str;
    }

    /*
     * 查询子串是否出现过
     */
    public static function isStringsInStr($str,$arr){
        if(is_string($str) == false || is_array($arr) == false)
            return false;
    }

    /*
     * 打印变量
     */
    public static function dump_arg($arg)
    {
        if (is_array($arg))
            var_dump($arg);
        else
            echo $arg;
    }

    /*
     *  设置缓存
     */
    public static function setCache($key, $data, $cachetime = 3600)
    {
        if (defined('DISABLE_CACHE'))
            return false;
        return Yii::$app->cache->set($key, $data, $cachetime);
    }

    /*
     * 获取缓存
     */
    public static function getCache($key)
    {
        if (defined('DISABLE_CACHE'))
            return false;
        $re = Yii::$app->cache->get($key);
        return $re;
    }

    /*
     * 提取数组元素中指定 key 的元素,并返回数组
     * $arr : 从此数组中获取元素
     * $$itemkey  数组中一个元素的key
     * $isQuotation  查找到的元素是否是字符串,true 返回字符串;false 返回原内容
     */
    public static function arr_allSubItems($arr, $itemkey, $isQuotation = false)
    {
        if (is_array($arr) == false)
            return false;
        $allItems = array();
        foreach ($arr as $onerow) {
            if ($onerow[$itemkey]) {
                $tmp = trim($onerow[$itemkey]);
                if ($isQuotation)
                    $allItems[] = "'" . $tmp. "'";
                else
                    $allItems[] = $tmp;
            }
        }
        return $allItems;
    }

    /*
    * 将数组的中的元素组按照间字符组成字符串
    */
    public static function arr_subItemToArray($arr, $itemkey, $glue, $isQuotation = false)
    {
        if (is_array($arr) == false || $glue == null)
            return false;
        $allItems = self::arr_allSubItems($arr, $itemkey, $isQuotation);
        $allItems = array_unique($allItems);
        if (count($allItems))
            return implode($glue, $allItems);
        return '';
    }

    /*
    * 将数组的中的元素组按照间字符组成用于SQL查询的字符串
    */
    public static function arr_subItemToSql($arr, $itemkey)
    {
        $str = self::arr_subItemToArray($arr, $itemkey, ',', true);
        if ($str)
            return '(' . $str . ')';
        return '';
    }

    /*
     *  从数组中随机取几个数据
     */


    public static function randomItems($arr, $count)
    {
        if (is_array($arr) == false)
            return array();
        shuffle($arr);
        $re = array_slice($arr, 0, $count);
        return $re;
    }


    /*
    *
    */
    public static function arr_sortItemByRule($arrRule, $arr, $itemkey)
    {
        if (is_array($arr) == false || $itemkey == null || is_array($arrRule) == false)
            return false;
    }


    /*
    * 安全的截获数组
    */
    public static function arr_slice($arr, $start, $length): array
    {
        if (!is_array($arr))
            return array();
        $re = array_slice($arr, $start, $length);
        return $re;
    }

    /*
     * 合并两个数组
     */
    public static function arr_merge($arr1, $arr2)
    {
        $arr1 = self::checkArray($arr1);
        $arr2 = self::checkArray($arr2);
        return array_merge($arr1 . $arr2);
    }

    /*
     * 合并一组数组
     */
    public static function arr_mergeFromArr($arr)
    {
        $re = array();
        foreach ($arr as $item)
            $re = array_merge($re, self::checkArray($item));
        return $re;
    }

    /*
     *  获取数组的长度
     */
    public static function arr_count($arr)
    {
        if (is_array($arr) == false)
            return 0;
        return count($arr);
    }

    /*
     * 安全的递归获取数组中的某个元素
     * $arr : 从此元素获取数据
     * $strksys : 由 "." 连接的key 字符串
     * 例如: a['goodsinfo']['brand']['name'] 将写成   arr_getItem(a,'goodsinfo.brand.name')
     */
    public static function arr_getItem($arr, $strkeys)
    {
        if (is_array($arr) == false || is_string($strkeys) == false)
            return null;
        $ar = explode(".", $strkeys);
        $lastItem = $arr;
        foreach ($ar as $item) {
            if (is_array($lastItem) == false)
                break;
            $lastItem = $lastItem[$item];
        }
        return $lastItem;
    }


    /*
     * 将数组重新构造为,key -> value 键值对,其中key 是元素的某个属性,value 是元素值,便于排序查询
     * $arrkey: 作为新数组的key ,比如下例子中的 'barcode'
     * $arrToRemap: 待中心排序的素组
     * $isRemoveRepeat: 是否覆盖同元素
     * 例如数组 a =
     * {
     *      [{'name':'a','barcode':'1'}]
     *      [{'name':'b','barcode':'2'}]
     *      [{'name':'c','barcode':'3'}]
     *      ......
     *  }
     *
     *  调用 arr_remapArrayBykey(['name','barocde'],a)  此函数将会将以上数组转换为
     * {
     *      '1':{'name':'a','barcode':'1'}
     *      'a':{'name':'a','barcode':'1'}
     *      '2':{'name':'b','barcode':'2'}
     *      'b':{'name':'b','barcode':'2'}
     *      '3':{'name':'c','barcode':'3'}
     *      'c':{'name':'c','barcode':'3'}
     * }
     */
    public static function arr_remapArrayBykey($arrkey, $arrToRemap, $isRemoveRepeat = false)
    {
        $arrRetuern = [];
        foreach (self::checkArray($arrToRemap) as $item) {
            foreach (self::checkArray($arrkey) as $key) {
                $value = $item[$key];
                if (empty($value))
                    continue;
                if (empty($arrRetuern[$value]))
                    $arrRetuern[$value] = [];
                else {
                    if ($isRemoveRepeat && in_array($item, $arrRetuern[$value]))
                        continue;
                }
                array_push($arrRetuern[$value], $item);
            }
        }
        return $arrRetuern;
    }

    /*
     * 获取元素某个下标的值,避免越界
     */
    public static function arr_itemByIndex($arr, $index)
    {
        if (is_array($arr) == false || $index < 0 || $index >= count($arr))
            return array();
        return $arr[$index];
    }

    /*
    *  向数组添加一个元素
    */
    public static function arr_push($arr, $item)
    {
        $arr = self::checkArray($arr);
        array_push($arr, $item);
        return $arr;
    }

    /*
    * 去掉字符左右空格后,比较两个字符
    */
    public static function isStrEqualWithTrim($str1, $str2)
    {
        if (is_string($str1) == false || is_string($str2) == false)
            return false;
        if (trim($str1) == trim($str2))
            return true;
        return false;
    }

    /*
    * 在数组中查找指定键值对的元素
    */
    public static function arr_searchItem($sources, $comparekey, $comparevalue)
    {
        if (count($sources) == 0 || $comparekey == null || $comparevalue == null)
            return null;
        foreach ($sources as $item) {
            if ($item[$comparekey] == $comparevalue)
                return $item;
        }
        return null;
    }

    /*
     * 获取数组中的某个元素
     */
    public static function arr_getItemBykey($arr, $key)
    {
        if (is_array($arr) == false || empty($key) || array_key_exists($key, $arr) == false)
            return null;
        return $arr[$key];
    }

    /*
    * 是否是本机地址
    */
    public static function islocalAddr()
    {
        $addr = $_SERVER['REMOTE_ADDR'];
        if (($addr == '127.0.0.1') || ($addr == 'localhost')) {
            return true;
        }
        return false;
    }

    /*
    * 替换数组的中的某些元素为一个元素
    * $rul is Array('[a,b,c,v,e]' => 'c');表示将a,b,c,d,e替换为c
    */
    public static function replaceStr($str, $rul)
    {
        foreach ($rul as $key => $value) {
            $ar = $value;
            if (!empty($ar) && is_array($ar)) {//是数组,表示多值替换
                foreach ($ar as $item2) {
                    $str = str_replace($item2, $key, $str);
                }
            } else {//是一个单一元素
                $str = str_replace($value, $key, $str);
            }
        }
        return $str;
    }

    /*
     * 判断商品ID是否合法
     */
    public static function isValidGoodID($goodid)
    {
        if (empty($goodid) || is_numeric($goodid) == false)
            return false;
        return true;
    }

    /*
     *判断请求是否是手机端
     */
    public static function isMobile()
    {
        $is_mobile = (IS_MOBILE_USER OR strstr($_SERVER['HTTP_HOST'], 'mitem')) ? true : false;    // mobile:1、 PC:0
        return $is_mobile;
    }

    /*
     *数组过滤,去掉空元素
     */
    public static function arr_filter($arr)
    {
        if (is_array($arr) == false || count($arr) == 0)
            return array();
        $ar = array_filter($arr);
        $ar = array_merge($ar);
        return $ar;
    }


    /*
     * 删除数组里的元素
     */
    public static function arr_del($arr, $rules)
    {
        foreach ($arr as &$item) {
            foreach ($rules as $key => $ru) {
                $value = $item[$key];
                if (is_array($ru)) {
                    foreach ($ru as $r) {
                        if ($value == $r)
                            $item == null;
                    }
                } else if ($value == $r) {
                    $item == null;
                }
            }
        }
        $ar = self::arr_filter($arr);
        return $ar;
    }


    /*
     * 接口正确,构造返回正确格式的json串
     */
    public static function interfaceReturnError($errcode, $msg, $data = null)
    {
        if ($errcode == 0)
            $errcode = -1;
        $re['status'] = $errcode;
        $re['msg'] = $msg;
        $re['data'] = $data == null ? array() : $data;
        return json_encode($re);
    }

    /*
     *接口错误,构造返回错误的json串
     */
    public static function interfaceReturnOK($data = null)
    {
        $re['status'] = 0;
        $re['msg'] = '';
        $re['$data'] = $data == null ? array() : $data;
        return json_encode($re);
    }

    /*
     * 运行一个SQL语句
     */
    public static function getSQLCommand($sql)
    {
        $res = Yii::$app->db->createCommand($sql);
        return $res;
    }

    /*
     * 检测数组里的一些元素是否为空
     */
    public static function checkArrayItemNotEmpty($arr, $arrkeys)
    {
        foreach ($arrkeys as $item) {
            if (empty($item))
                continue;
            if (key_exists($item, $arr) == false)
                return false;
            if (empty($arr[$item]))
                return false;
        }
        return true;
    }

    /*
     * 赋值变量,如果新值不为空,则将新值赋值给value
     */
    public static function setValueIfNotEmpty(&$value, $newvalue){
        if($value == null || is_string($newvalue) == false )
            return;
        if (isset($newvalue))
            $value = trim($newvalue);
    }

    public static function generateRandomDecimal($length = 16, $decimalPlaces = 2): string
    {
        $numbers = '0123456789';
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $numbers[rand(0, strlen($numbers) - 1)];

            if ($i === $length - $decimalPlaces - 1) {
                $randomString .= '.'; // 在特定位置插入小数点
            }
        }

        return $randomString;
    }
}


