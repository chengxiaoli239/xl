<?php
namespace backend\service;

use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\models\WxFriends;
use backend\models\WxMsgTypes;
use common\general\helpers\Curl;
use common\tools\Tool_Common;

class WxService {

	public static function getMillisecond() {
		list($t1, $t2) = explode(' ', microtime());
		return $t2 . ceil(($t1 * 1000));
	}

	private static $appid = 'wx782c26e4c19acffb';
    private static $uuid = '';

    /**
     * 获取唯一的uuid用于生成二维码
     * @return $uuid
     */
	public static function get_uuid() {
		$url = 'https://login.weixin.qq.com/jslogin';
		$url .= '?appid=' . self::$appid;
		$url .= '&fun=new';
		$url .= '&lang=zh_CN';
		$url .= '&_=' . time();

		$content = self::curlPost($url);
        //也可以使用正则匹配
		$content = explode(';', $content);

		$content_uuid = explode('"', $content[1]);

		$uuid       = $content_uuid[1];
		self::$uuid = $uuid;
		return $uuid;
	}

    /**
     * 生成二维码
     * @param $uuid
     * @return img
     */
    public static function qrcode($uuid='') {
        if(empty($uuid)){
            $uuid  = WxService::get_uuid();
        }
        $url = 'https://login.weixin.qq.com/qrcode/' . $uuid . '?t=webwx';
        //$img = "<img class='img' src=" . $url . "/>";
        //return $img;
        return $url;
    }

    /**
     * 二、1.微信扫描
     * @param $uuid
     * @return array code 408:未扫描;201:扫描未登录;200:登录成功; icon:用户头像
     */
    public static function isLogin($uuid) {
        # https://login.wx2.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid=gcUh-c3f5w==&tip=0&r=-1061690180&_=1611674387678
        //$url = 'https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&r=-' . ~time() . '&uuid=' . $uuid . '&tip=0&_=' . self::getMillisecond();
        $url = 'https://login.wx2.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid=' . $uuid . '&tip=0&r=-' . ~time() . '&_=' . self::getMillisecond();
		$content = self::curlPost($url);
		preg_match('/\d+/', $content, $match);
		$code = $match[0];
		preg_match('/([\'"])([^\'"\.]*?)\1/', $content, $icon);

		$user_icon = !empty($icon) ? $icon[2] : [];
		if ($user_icon) {
			$data = [
				'code' => $code,
				'icon' => $user_icon
			];
		} else {
			$data['code'] = $code;
		}
		$strArr = [];
		if(strpos($content, 'redirect_uri') !== false){
		    $strArr = explode('"', $content);
		    $data['redirect_uri'] = $strArr[1];
        }
		Tool_Common::log('/wx/isLogin', 'INFO', '二、1.微信扫描', ['code'=>$code, 'content'=>$content, 'strArr'=>$strArr]);
        //echo json_encode($data);//改之前
		return $data;
	}

    /**
     * @desc 二、2.获取微信新的登陆页面   # 需存储cookie 未完成
     * @param string $url
     * @param string $uid
     * @return array|bool
     */
	public static function webWxNewLoginPage($uid = '', $url = ''){
        if(strpos($url, 'http') === false){
            return false;
        }
        $m = \Yii::$app->cache;
        $mkey = WxService::buildWebWxNewLoginKey($uid);
        $url = $url . '&fun=new&version=v2&lang=zh_CN';
        //$content = self::curlPost($url);
        $content = self::postCurl($url);

        /**
         * 缓存重要的参数 数组化***
            <error>
                <ret>0</ret>
                <message></message>
                <skey>@crypt_133e5bb7_48be0fb82d0e50758696333935f518ce</skey>
                <wxsid>uOcm0aQ3hwhq3U8E</wxsid>
                <wxuin>1120382433</wxuin>
                <pass_ticket>kp7aGhTsb6lzSZkJMIX3fzKYbSfO6z6dSX8OKWiFrIeQiH4DnhwsGGpDLKqnZ5%2Fr</pass_ticket>
                <isgrayscale>1</isgrayscale>
            </error>
         */
        $m->set($mkey, $content['rstData'], 3600);

        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid]);
        $cookiesArr = $content['cookies'][1];
        $cookieDatas = []; # 存储后续发送消息cookies -- 重要
        foreach ($cookiesArr as $cookie){
            $cookieDatas[] = trim(explode(';', $cookie)[0]);
        }
        $TzSystemsUsers->cookie_wx_web = 'MM_WX_NOTIFY_STATE=1; MM_WX_SOUND_STATE=1; refreshTimes=5;'.implode(';', $cookieDatas).'';
        $TzSystemsUsers->save();
        Tool_Common::log('/wx/webWxNewLoginPage', 'INFO', '二、2.获取微信新的登陆页面', ['uid'=>$uid, 'url'=>$url, 'content'=>$content]);

        return ['status'=>200, 'msg'=>'操作成功'];
    }

    /**
     * @desc 网页确认登陆后微信返回数据
     * @param string $uid
     * @return string
     */
    public static function buildWebWxNewLoginKey($uid = ''){
        $mkey = 'webWxNewLoginPage_xml_data_0_'.$uid;

        return $mkey;
    }

    /**
     * 登录成功回调
     * @param $uuid
     * @return array $callback
     */
	public static function get_uri($uuid='') {
		$url = 'https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?uuid=' . $uuid . '&tip=0&_=e' . time();
		//$content = self::curlPost($url); # 返回： ;
        $content = 'window.code=200; window.redirect_uri="https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage?ticket=AzeYiiVyd05sBJGENEXV4Kll@qrticket_0&uuid=gcUh-c3f5w==&lang=zh_CN&scan=1611674425"';
		$content     = explode(';', $content);
		$content_uri = explode('"', $content[1]);
		$uri         = $content_uri[1];

		preg_match("~^https:?(//([^/?#]*))?~", $uri, $match);
		$https_header = $match[0];
		//$_SESSION['https_header'] = $https_header; //补这一句
		$post_url_header = $https_header . "/cgi-bin/mmwebwx-bin";

		$new_uri = explode('scan', $uri);
		$uri  = $new_uri[0] . 'fun=new&scan=' . time();
		$getXML  = self::curlPost($uri);

        $XML = WxService::xmlToArray($getXML);
		$callback = [
			'post_url_header' => $post_url_header,
            'https_header' => $https_header,
			'Ret' => (array) $XML
		];
		$logArr = ['callback'=>$callback, 'url'=>$url, 'getXML'=>$getXML, 'content'=>$content, 'https_header'=>$https_header];
		Tool_Common::log('/wx/get_uri', 'INFO', '登录成功回调', $logArr);
		return (array)$callback;
	}

    /**
     * 获取post数据
     * @desc 微信的登陆返回信息
     * @param string $uid
     * @return array|bool
     */
	public static function getWxNewLoginPostData($uid='') {
	    if(empty($uid)) return false;
	    $m = \Yii::$app->cache;
	    $mkey_post = 'getWxNewLoginPostData_'.$uid;
	    if(true or $post = $m->get($mkey_post)) return $post;

	    $mkey = WxService::buildWebWxNewLoginKey($uid);
	    $wxLoginRst = $m->get($mkey);
        $post   = [];
		$Ret    = $wxLoginRst['Ret'];
		if ($Ret['ret'] == '1203') {
            Tool_Common::log('/wx/post_self_error', 'INFO', '微信post', ['msg'=>'未知错误,请2小时后重试']);
            return false;
		}
		if ($Ret['ret'] == '0') {
			$post = [
			    'BaseRequest' => [
                    'Uin'      => $Ret['wxuin'],
                    'Sid'      => $Ret['wxsid'],
                    'Skey'     => $Ret['skey'],
                    'DeviceID' => 'e' . rand(10000000, 99999999) . rand(1000000, 9999999)
                ],
                'skey' => $Ret['skey'],
                'pass_ticket' => $Ret['pass_ticket'],
                'sid' => $Ret['wxsid'],
                'uin' => $Ret['wxuin'],
			];
		}
		$m->set($mkey_post, $post, 3600);
        Tool_Common::log('/wx/post_self', 'INFO', '微信post', ['wxLoginRst'=>$wxLoginRst, 'status'=>$Ret['ret'], 'post'=>$post]);
        return (array)$post;
	}

    /**
     * 初始化 - 返回数据有 data.SyncKey.List 里边有心跳包需要的参数
     * @param $post
     * @return json $json
     */
	public static function wxinit($post) {
		$url = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxinit?pass_ticket='.$post['pass_ticket'].'&skey='.$post['skey'] . '&r=' . time();

		$post = [
			'BaseRequest' => $post['BaseRequest']
		];
		$json = self::curlPost($url, $post);
		Tool_Common::log('/wx/wxinit', 'INFO', '微信登陆初始化', ['post'=>$post, 'json'=>$json]);

		return $json;
	}

    /**
     * 获取MsgId
     * @param $post
     * @param $json
     * @param $post_url_header
     * @return array $data
     */
	public static function wxstatusnotify($post, $json) {
		$init = json_decode($json, true);

		$User = $init['User'];
		$url  = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxstatusnotify?lang=zh_CN&pass_ticket='.$post['pass_ticket'];

		$params = [
			'BaseRequest'  => $post['BaseRequest'],
			"Code"         => 3,
			"FromUserName" => $User['UserName'],
			"ToUserName"   => $User['UserName'],
			"ClientMsgId"  => time()
		];

		$data = self::curlPost($url, $params);

		$data = json_decode($data, true);

		return $data;
	}

    /**
     * 获取联系人
     * @param $post
     * @param $post_url_header
     * @return array $data
     */
	public static function webwxgetcontact($post) {
		$url = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxgetcontact?pass_ticket='.$post['pass_ticket'].'&seq=0&skey='.$post['skey'].'&r=' . time();

		$params['BaseRequest'] = $post['BaseRequest'];

		$data = self::curlPost($url, $params);

		return $data;
	}

    /**
     * 获取当前活跃群信息
     * @param $post
     * @param $post_url_header
     * @param $group_list 从获取联系人和初始化中获取
     * @return array $data
     */
	public static function webwxbatchgetcontact($post, $post_url_header, $group_list) {
		$url = $post_url_header.'/webwxbatchgetcontact?type=ex&lang=zh_CN&r='.time().'&pass_ticket='.$post['pass_ticket'];

		$params['BaseRequest'] = $post['BaseRequest'];

		$params['Count'] = count($group_list);

		foreach ($group_list as $key => $value) {
			if ($value['MemberCount'] == 0) {
				$params['List'][] = [
					'UserName'   => $value['UserName'],
					'ChatRoomId' => ""
				];
			}
			$params['List'][] = [
				'UserName'        => $value['UserName'],
				'EncryChatRoomId' => ""
			];
		}

		$data = self::curlPost($url, $params);

		$data = json_decode($data, true);

		return $data;
	}

    /**
     * 心跳检测 0正常；1101失败／登出；2新消息；7不要耍手机了我都收不到消息了；
     * @param $post
     * @param $SyncKey 初始化方法中获取
     * @return array $status
     */
	public static function synccheck($post, $SyncKey) {
		if (!$SyncKey['List']) {
			$SyncKey = $_SESSION['json']['SyncKey'];
		}
		$SyncKey_value = '';
		foreach ($SyncKey['List'] as $key => $value) {
            $SyncKey_value .= $value['Key'] . '_' . $value[ 'Val'].'|';
		}
		$SyncKey_value = trim($SyncKey_value, '|');

        // https://webpush.wx2.qq.com/cgi-bin/mmwebwx-bin/synccheck?r=1613883269500&skey=%40crypt_133e5bb7_bfe7c7220554fcf93fb668b486488db6&sid=c35OOrVYDxQvFMKX&uin=1120382433&deviceid=e822961665627194&synckey=1_733942461%7C2_733942477%7C3_733941956%7C11_733942444%7C19_5979%7C201_1613883216%7C203_1613878857%7C206_103%7C1000_1613878833%7C1001_1613880193&_=1613875831805
        //$header = [ '0' => 'https://webpush.wx2.qq.com', '1' => 'https://webpush.wx.qq.com' ];
        $baseUrl = 'https://webpush.wx2.qq.com';
        $microtime = self::getMillisecond();
        $post_datas = [
            'r' => $microtime,
            'skey' => urlencode($post['skey']),
            'sid' => $post['sid'],
            'deviceid' => $post['BaseRequest']['DeviceID'],
            'uin' => $post['uin'],
            'synckey' => urlencode($SyncKey_value),
            '_' => $microtime,
        ];
        $url = $baseUrl . "/cgi-bin/mmwebwx-bin/synccheck?" . http_build_query($post_datas);
        $rstData = self::curlPost($url);  # window.synccheck={retcode:"0",selector:"0"}

        $rule = '/window.synccheck={retcode:"(\d+)",selector:"(\d+)"}/';
        preg_match($rule, $rstData, $match);

        if ($match[1] == '0') {
            $retcode  = $match[1];
            $selector = $match[2]; # 0无消息2有消息3异常，3目前认为是非法参数用户退出
        }
		$status = [ 'ret' => $retcode, 'sel' => $selector ];

		return $status;
	}

    /**
     * 获取最新消息
     * @param $post
     * @param $post_url_header
     * @param $SyncKey
     * @return array $data
     */
    public static function webwxsync($post, $post_url_header, $SyncKey) {
        $url = $post_url_header.'/webwxsync?sid='.$post['sid'].'&skey='.$post['skey'].'&pass_ticket=' . $post['pass_ticket'];

        $params = [
            'BaseRequest' => $post['BaseRequest'],
            'SyncKey'     => $SyncKey,
            'rr'          => ~time()
        ];
        $data = self::curlPost($url, $params);

        return $data;
    }

    /**
     * 发送消息
     * @param $post
     * @param $initInfo
     * @param $post_url_header
     * @param $to 发送人
     * @param $word
     * @return array $data
     */
	public static function webwxsendmsg($uid, $fromUser, $to, $word ) {
		$post = WxService::getWxNewLoginPostData($uid);
        $url = 'https://wx2.qq.com/cgi-bin/mmwebwx-bin/webwxsendmsg?pass_ticket='.$post['pass_ticket'];

		$clientMsgId = time() * 1000 + rand(1000, 9999); //原方法
		$params = [
			'BaseRequest' => $post['BaseRequest'],
			'Msg' => [
				"Type"         => 1,
				"Content"      => $word,
                "FromUserName" => $fromUser['UserName'],
				"ToUserName"   => $to,
				"LocalID"      => $clientMsgId,
				"ClientMsgId"  => $clientMsgId
			],
			'Scene' => 0
		];
		$data = self::sendCurlPost($url, $params, 1);

		$logArr = ['url'=>$url, 'fromUser'=>$fromUser, 'params'=>$params, 'data'=>$data];
		Tool_Common::log('webwxsendmsg', 'INFO', '发送微信消息', $logArr);

		return $data;
	}

    /**
     *退出登录
     * @param $post
     * @param $post_url_header
     * @return bool
     */
	public static function wxloginout($post, $post_url_header) {
		$url   = $post_url_header . '/webwxlogout?redirect=1&type=1&skey=' . urlencode($post['skey']);
		$param = [
			'sid' => $post['sid'],
			'uin' => $post['uin']
		];
		self::curlPost($url, $param);

		return true;
	}

	public static function curlPost($url, $data = '', $is_gbk = false, $timeout = 30, $CA = false) {
		$cacert = getcwd() . '/cacert.pem'; //CA根证书

		$SSL = substr($url, 0, 8) == "https://" ? true : false;

        //$header = 'ContentType: application/json; charset=UTF-8';
		$header[] = 'ContentType: application/json;';
		$header[] = "charset:UTF-8";
		$ch       = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 2);
		if ($SSL && $CA) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书
			curl_setopt($ch, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
		} else if ($SSL && !$CA) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']); //避免data数据过长问题
		if ($data) {
			if ($is_gbk) {
				$data = json_encode($data);
			} else {
				$data = json_encode($data);
			}

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}

	public static function sendCurlPost($url, $data = '', $is_gbk = false, $timeout = 30, $CA = false) {
		$cacert = getcwd() . '/cacert.pem'; //CA根证书

		$SSL = substr($url, 0, 8) == "https://" ? true : false;

        //$header = 'ContentType: application/json; charset=UTF-8';
		$header[] = 'ContentType: application/json;';
		$header[] = "charset:UTF-8";
		$ch       = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 2);
		if ($SSL && $CA) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书
			curl_setopt($ch, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
		} else if ($SSL && !$CA) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']); //避免data数据过长问题
		if ($data) {
			if ($is_gbk) {
				$data = urldecode(json_encode($data));
			} else {
				$data = urldecode(json_encode($data));
			}

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}

    public static  function anewarray($array, $filed = 'UserName', $keyName = 'NickName') {
        $data = [];
        if (!empty($array)) {
            foreach ($array as $key => $val) {
                if (!empty($val[$filed])) {
                    $data[$val[$keyName]] = $val[$filed];
                }
            }
            $data = array_filter($data);
        }

        return $data;
    }

    public static function buildCallbackKey($uid=''){
        $mkey = 'syncFriendsData_callback_'.$uid;
        return $mkey;
    }
    public static function buildPostSelfKey($uid=''){
        $mkey = 'syncFriendsData_post_self_'.$uid;
        return $mkey;
    }
    public static function buildInitInfoKey($uid=''){
        $mkey = 'syncFriendsData_initInfo_'.$uid;
	    return $mkey;
    }

    /**
     * @desc 同步好友数据
     * @param $uid
     * @param $uuid
     * @return array
     */
    public static function syncFriendsData($uid, $uuid){
        self::$uuid = $uuid;
        $m = \Yii::$app->cache;
        //获取post数据
        $postBase = WxService::getWxNewLoginPostData($uid);
        //初始化数据json格式
        $initInfo = WxService::wxinit($postBase);
        $mkey = WxService::buildInitInfoKey($uid);
        $m->set($mkey, $initInfo, 3600);

        //获取MsgId,参数post，初始化数据initInfo
        $msgInfo = WxService::wxstatusnotify($postBase, $initInfo);
        //获取联系人
        $contactInfo = WxService::webwxgetcontact($postBase);
        //p(['contactInfo'=>json_decode($contactInfo, true)]);
        $contacts = json_decode($contactInfo, true);
        foreach ($contacts['MemberList'] as $info){
            try{
                $setData = $info;
                $setData['uid'] = $uid;
                $setData['NickName'] = urlencode($info['NickName']);
                $setData['MemberList'] = json_encode($info['MemberList'], 320);
                if(!$WxFriends = WxFriends::findOne(['UserName'=>$info['UserName'], 'uid'=>$uid])){
                    $WxFriends = new WxFriends();
                    $setData['created_at'] = time();
                    $setData['UserName'] = $info['UserName'];
                }
                $setData['updated_at'] = time();
                $setData['AttrStatus'] = (string)$setData['AttrStatus'];
                $setData['RemarkName'] = urlencode($setData['RemarkName']);

                $url = \Yii::$app->params['WX_IMG_URL_DOMAIN'].$info['HeadImgUrl'];
                //CurlService::getCurl($url);
                $WxFriends->setAttributes($setData);
                if(!$rst = $WxFriends->save()){
                    Tool_Common::log('/wx/syncFriendsData', 'INFO', '微信好友同步', ['msg'=>$WxFriends->getErrors(), 'setData'=>$setData, 'info'=>$info]);
                }
            }catch (\ErrorException $e){
                Tool_Common::log('/wx/syncFriendsData', 'INFO', '微信好友同步', ['msg'=>$e->getMessage(), 'setData'=>$setData, 'info'=>$info]);
            }
        }

        //查询的数据放入缓存
        $m = \Yii::$app->cache;
        $mkey = self::getWxMcKey($uid);
        $WxInfo  = [
            'post' => $postBase,
            'fromUser' => json_decode($initInfo, true)['User'],
            'contactInfo' => $contactInfo
        ];
        $m->set($mkey, $WxInfo, 7*24*3600);

        $logArr = ['mkey'=>$mkey, 'WxInfo'=>$WxInfo, 'contacts'=>$contacts, 'url'=>$url];
        Tool_Common::log('/wx/setWxInfo', 'INFO', '设置微信缓存', $logArr);

        return ['status'=>200, 'contactInfo'=>$contactInfo];
    }

    /**
     * @desc 获取微信缓存key
     * @param $uid
     * @return string
     */
    public static function getWxMcKey($uid){
        $mkey = 'WX_FRIENDS_DATA_'.$uid;

        return $mkey;
    }

    /**
     * @description 更新表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function switchStatus($id, $status, $uid, $model)
    {
        $m = \Yii::$app->cache;
        $mkey = 'wx_updateSysPlansStatus_'.$id.'_'.$status;
        if($rst = $m->get($mkey)) return false;

        if($model == 'WxMsgTypes'){
            $Model = WxMsgTypes::findOne(['uid' => $uid, 'id' => $id]);
        }else{
            $Model = WxFriends::findOne(['uid' => $uid, 'id' => $id]);
        }
        $Model->status = (int)$status;

        $m->set($mkey, 1, 3);

        $rst = $Model->save(false);

        return $rst;
    }

    /**
     * @desc 发送消息
     */
    public static function sendMsg(){

        $WxMsgTypes = WxMsgTypes::findAll(['status'=>1]);
        $m = \Yii::$app->cache;
        foreach ($WxMsgTypes as $wxMsgType) {
            $uid = $wxMsgType->uid;
            $msg = $wxMsgType->msg;
            $WxFriends = WxFriends::findAll(['status'=>1]);
            //Tool_Common::log('sendMsgFriends', 'INFO', '微信发送好友', ['WxFriends'=>$WxFriends->attributes]);
            foreach ($WxFriends as $friend){
                $word = urlencode($msg);
                if($wxMsgType->is_name){
                    $word = urlencode(sprintf($msg, $friend->send_name));
                }
                $to = $friend->UserName;
                $mkey = self::getWxMcKey($friend->uid);
                if($WxInfo = $m->get($mkey)){
                    $fromUser = $WxInfo['fromUser'];
                    if (!empty($word)) {
                        $sendRst = self::webwxsendmsg($uid, $fromUser, $to, $word);
                        $logArr = ['WxInfo'=>$WxInfo, 'sendRst'=>json_decode($sendRst, true),'from'=>$fromUser, 'to'=>$to, 'word'=>$word];
                        Tool_Common::log('/wx/sendMsg', 'INFO', '微信发送信息', $logArr);
                    }
                }
            }
        }
        return ['status'=>200, 'data'=>json_decode($sendRst, true)];
    }

    /**
     * @decription post请求根据，接受传递的header头
     * @param $url
     */
    public static function postCurl($url,$post_data = [], $headers=[], $uid = 0){
        $timeout = SystemConfig::findOne(['key'=>'time_out_sec'])->value;
        if(!$timeout) $timeout = 30;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        // 设置浏览器的特定header
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);//设置超时限制，防止死循环
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);//设置超时限制，防止死循环
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 1); #

        //$poxy_addr = self::setPoxy($ch, $url, $uid); # 设置代理IP

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    # 302 redirect
        curl_setopt($ch, CURLOPT_HEADER, TRUE);    //表示需要response header

        if(!empty($post_data)){
            //设置post方式提交
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $start_time = microtime(true);
        $content = curl_exec($ch);
        $end_time = microtime(true);
        $errno = curl_errno($ch);
        //$logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$data, 'errno'=>$errno]; p($logArr);
        if($errno){
            $logArr = ['url'=>$url, 'post_data'=>$post_data, 'header'=>$headers, 'rst'=>$content, 'errno'=>$errno]; //p($logArr);
            Tool_Common::log('httpPostError','INFO','httpPost请求-1', $logArr);
        }

        # ================= xCsrf token start =====================
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($content, 0, $headerSize);

            preg_match_all("/Set\-Cookie:([^\r\n]*)/i", $headers, $matches1);
            $body = substr($content, $headerSize);
            $result['rstData'] = WxService::xmlToArray(trim($body));
            $result['cookies'] = $matches1;
        }
        # ================= xCsrf token start =====================

        return $result;
    }

    /**
     * @desc 心跳检测任务
     * @param $uid
     * @return array]
     */
    public static function syncCheckTask($uid){
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $m = \Yii::$app->cache;
        $mkey = WxService::buildWebWxNewLoginKey($uid);
        if($loginData = $m->get($mkey)){
            $syncCheckKey = WxService::buildWxSyncCheckTaskKey($uid);
            if($status = $m->get($syncCheckKey)) return ['status'=>300, 'msg'=>'有在进行的任务，请稍后...'];
            $i = 0;
            while (true){
                $m->set($syncCheckKey, 1, 60);
                $syncRst = WxService::synccheck($loginData);
                sleep(5);
                $i++;
                if($i>=20 OR $syncRst['sel'] == 3){
                    $r1 = $m->delete($syncCheckKey);
                    $r2 = $m->delete($mkey); # 删除用户登陆信息
                    Tool_Common::log('/wx/syncCheckTask_clear', 'INFO', '心跳检测异常清理', ['uid'=>$uid, 'r1'=>$r1, 'r2'=>$r2, 'syncRst'=>$syncRst]);
                    break;
                }
            }
        }
        $m->delete($syncCheckKey);
        Tool_Common::log('/wx/syncCheckTask', 'INFO', '微信心跳任务', ['uid'=>$uid, 'loginData'=>$loginData]);

        return $rst;
    }

    public static function buildWxSyncCheckTaskKey($uid=''){
        return 'buildWxSyncCheckTaskKey_'.$uid;
    }

    public static function xmlToArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }
}
