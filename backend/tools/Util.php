<?php
/**
 * 通用处理类
 * Enter description here ...
 * @author sam
 * @authors wudean(bj) 乔迁标识
 *
 */
namespace item\tools;
class Util{
	//获取客户端IP
	public static function getIp(){
		$ip = '';
		foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_FROM', 'REMOTE_ADDR') as $v) {
			if (isset($_SERVER[$v])) {
				if (! preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $_SERVER[$v])) continue;
				$ip = $_SERVER[$v];
			}
		}
		return $ip;
	}
	
	//验证邮箱
	public static function checkEmail($email){
		if($email=='请输入您的常用邮箱')
			return false;
		if(preg_match("/^[0-9a-zA-Z]+(?:[\.\_\-][a-z0-9\-]+)*@[a-zA-Z0-9]+(?:[-.][a-zA-Z0-9]+)*\.[a-zA-Z]+$/i", $email)){
			return true;
		}else{
			return false;
		}
	}
	
	//昵称是否存在特殊字符
	public static function isExistsSpecialChar($nick){
		$string=$nick;
		$re="/[\?#%^&*@$!`~\/\s\.\",，()\\\<>+=]/isu";
		$m=preg_match($re,$string);
		return $m;
	}

    /**
     * 批量删除数组中的key和值
     * @static
     * @param $arr  处理的数组  注意这里是引用传递
     * @param $keys<array(k1,k2,k3,kn....)>    删除的键列表
     * @return  void
     */
    public static function  batRemoveArrKeys(&$arr,$keys){
        foreach($keys as $k){
            if(isset($arr[$k])||$arr[$k]===null){
                unset($arr[$k]);
            }
        }
    }

    //检查信息是否有效（即：是否包括禁止词）
    public static function isValidChkText($text,$type=2)
    {
        $client = \ThriftClient::getInstance('WidgetService');
        $client->type = $type;
        $client->data = $text;
        $res = $client->valiData();
        if ($res == -1) {
            return false;
        }else{
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
    public static function convertImgae($img, $h=400, $w=400) {
        $goods_img = strpos($img, 'http:') === false ? 'http://img.mianshui365.com' . $img : $img;
//        $goods_img .= "@{$h}h_{$w}w_95q_1wh";
        $goods_img = str_replace("cdn.mianshui365.com", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn2.mianshui365.com", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn2.mianshui365.net", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("cdn.mianshui365.net", "img.mianshui365.com", $goods_img);
        $goods_img = str_replace("images.mianshui365.com", "img.mianshui365.com", $goods_img);
        if(strpos($goods_img, "@") === false){
            $goods_img .= "@".$h."h_".$w."w_95q_1wh";
        }
        return $goods_img;
    }

    //计算utf8字符串的长度,例如 $a="你好",strlen($a) = 9, utf8_strlen($a) = 3
    public static function utf8_strlen($string = null) {
        // 将字符串分解为单元
        preg_match_all("/./us", $string, $match);
        // 返回单元个数
        return count($match[0]);
    }

}