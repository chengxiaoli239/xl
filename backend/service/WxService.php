<?php
namespace backend\service;

use backend\models\WxFriends;
use backend\models\WxMsgTypes;
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
    public static function qrcode($uuid) {
        $url = 'https://login.weixin.qq.com/qrcode/' . $uuid . '?t=webwx';
        $img = "<img class='img' src=" . $url . "/>";
        return $img;
    }

    /**
     * 扫描登录
     * @param $uuid
     * @param string $icon
     * @return array code 408:未扫描;201:扫描未登录;200:登录成功; icon:用户头像
     */
    public static function login($uuid, $icon = 'true') {
        //$url = 'https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=' . $icon . '&r=' . ~time() . '&uuid=' . $uuid . '&tip=0&_=' . getMillisecond();
        $url = 'https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=' . $icon .'&r=' . ~time() . '&uuid=' . $uuid . '&tip=0&_=' . time();
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
        //echo json_encode($data);//改之前
		return $data;
	}

    /**
     * 登录成功回调
     * @param $uuid
     * @return array $callback
     */
	public static function get_uri($uid, $uuid) {
		$url         = 'https://login.weixin.qq.com/cgi-bin/mmwebwx-bin/login?uuid=' . $uuid . '&tip=0&_=e' . time();
		$content     = self::curlPost($url);
		$content     = explode(';', $content);
		$content_uri = explode('"', $content[1]);
		$uri         = $content_uri[1];

		preg_match("~^https:?(//([^/?#]*))?~", $uri, $match);
		$https_header             = $match[0];
		//$_SESSION['https_header'] = $https_header; //补这一句
		$post_url_header          = $https_header . "/cgi-bin/mmwebwx-bin";

		$new_uri = explode('scan', $uri);
		$uri     = $new_uri[0] . 'fun=new&scan=' . time();
		$getXML  = self::curlPost($uri);

		$XML = simplexml_load_string($getXML);

		$callback = [
			'post_url_header' => $post_url_header,
            'https_header' => $https_header,
			'Ret'             => (array) $XML
		];
		$logArr = ['callback'=>$callback, 'url'=>$url, 'content'=>$content, 'https_header'=>$https_header];
		Tool_Common::log('get_uri', 'INFO', '登录成功回调', $logArr);
		return (array)$callback;
	}

    /**
     * 获取post数据
     * @param array $callback
     * @return array $post
     */
	public static function post_self($callback, &$https_header) {
		//$post   = new \stdClass();
        $post   = [];
		$Ret    = $callback['Ret'];
		$status = $Ret['ret'];
		if ($status == '1203') {
			self::error('未知错误,请2小时后重试');
		}
		if ($status == '0') {
			$post['BaseRequest'] = [
				'Uin'      => $Ret['wxuin'],
				'Sid'      => $Ret['wxsid'],
				'Skey'     => $Ret['skey'],
				'DeviceID' => 'e' . rand(10000000, 99999999) . rand(1000000, 9999999)
			];

			$post['skey'] = $Ret['skey'];

			$post['pass_ticket'] = $Ret['pass_ticket'];

			$post['sid'] = $Ret['wxsid'];

			$post['uin'] = $Ret['wxuin'];
            $https_header = $callback['https_header'];
		}
        return (array)$post;
	}

    /**
     * 初始化
     * @param $post
     * @return json $json
     */
	public static function wxinit($post, $https_header) {
		$url = $https_header . '/cgi-bin/mmwebwx-bin/webwxinit?pass_ticket='.$post['pass_ticket'].'&skey='.$post['skey'] . '&r=' . time();

		$post = [
			'BaseRequest' => $post['BaseRequest']
		];
		$json = self::curlPost($url, $post);

		return $json;
	}

    /**
     * 获取MsgId
     * @param $post
     * @param $json
     * @param $post_url_header
     * @return array $data
     */
	public static function wxstatusnotify($post, $json, $post_url_header) {
		$init = json_decode($json, true);

		$User = $init['User'];
		$url  = $post_url_header.'/webwxstatusnotify?lang=zh_CN&pass_ticket='.$post['pass_ticket'];

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
	public static function webwxgetcontact($post, $post_url_header) {
		$url = $post_url_header.'/webwxgetcontact?pass_ticket='.$post['pass_ticket'].'&seq=0&skey='.$post['skey'].'&r=' . time();

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
			if ($key == 1) {
				$SyncKey_value = $value['Key'] . '_' . $value[ 'Val'];
			} else {
				$SyncKey_value .= '|' . $value['Key'] . '_' . $value['Val'];
			}
		}

		$header = [
			'0' => 'https://webpush.wx2.qq.com',
			'1' => 'https://webpush.wx.qq.com'
		];

		foreach ($header as $key => $value) {
			$url = $value . "/cgi-bin/mmwebwx-bin/synccheck?r=" . getMillisecond() . "&skey=" . urlencode($post['skey']) . "&sid=" . $post['sid']."&deviceid=" . $post['BaseRequest']['DeviceID'] . "&uin=" . $post['uin'] . "&synckey=" . urlencode($SyncKey_value) . "&_=" . self::getMillisecond();
			$data[] = self::curlPost($url);
		}

		foreach ($data as $k => $val) {
			$rule = '/window.synccheck={retcode:"(\d+)",selector:"(\d+)"}/';

			preg_match($rule, $data[$k], $match);

			if ($match[1] == '0') {
				$retcode  = $match[1];
				$selector = $match[2];
			}
		}

		$status = [
			'ret' => $retcode,
			'sel' => $selector
		];

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
	public static function webwxsendmsg($post, $fromUser, $post_url_header, $to, $word ) {
        //header("Content-Type: application/json; charset=UTF-8");
		//header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");
		$url = $post_url_header . '/webwxsendmsg?pass_ticket='.$post['pass_ticket'];

        //$clientMsgId = getMillisecond() * 1000 + rand(1000, 9999);//原方法
		$clientMsgId = time() * 1000 + rand(1000, 9999); //原方法
		//$init        = json_decode($initInfo, true);
		//$User        = $init['User'];
		$params      = [
			'BaseRequest' => $post['BaseRequest'],
			'Msg'         => [
				"Type"         => 1,
				"Content"      => $word,
				//"FromUserName" => $User['UserName'],
                "FromUserName" => $fromUser['UserName'],
				"ToUserName"   => $to,
				"LocalID"      => $clientMsgId,
				"ClientMsgId"  => $clientMsgId
			],
			'Scene'       => 0
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

    /**
     * @desc 同步好友数据
     * @param $uid
     * @param $uuid
     * @return array
     */
    public static function syncFriendsData($uid, $uuid){
        self::$uuid = $uuid;
        $get = \Yii::$app->request->get();
        if(!$get['test']) $loginInfo = WxService::login($uuid);
        if ($loginInfo['code'] == 200 OR $get['test']) {
            //获取登录成功回调
            $callback = WxService::get_uri($uid, $uuid);
            //获取post数据
            $post = WxService::post_self($callback, $https_header);
            //初始化数据json格式
            $initInfo = WxService::wxinit($post, $https_header);
            //p(['initInfo'=>$initInfo],0);

            //获取MsgId,参数post，初始化数据initInfo
            //$msgInfo = WxService::wxstatusnotify($post,$initInfo,$callback['post_url_header']);
            //获取联系人
            $contactInfo = WxService::webwxgetcontact($post, $callback[ 'post_url_header']);
            //p(['contactInfo'=>json_decode($contactInfo, true)]);
            $contacts = json_decode($contactInfo, true);
            foreach ($contacts['MemberList'] as $info){
                $setData = $info;
                $setData['uid'] = $uid;
                $setData['MemberList'] = json_encode($info['MemberList'], 320);
                if(!$WxFriends = WxFriends::findOne(['NickName'=>$info['NickName'], 'uid'=>$uid])){
                    $WxFriends = new WxFriends();
                    $setData['created_at'] = time();
                }
                $setData['updated_at'] = time();

                $WxFriends->setAttributes($setData);
                $rst = $WxFriends->save();
                //p($WxFriends->getFirstErrors(),0);
                //p($WxFriends->attributes, 0);
            }

            //查询的数据放入缓存
            $m = \Yii::$app->cache;
            $mkey = self::getWxMcKey($uid);
            $WxInfo  = [];
            //session_start();
            $WxInfo['callback_post_url_header'] = $callback['post_url_header'];
            $WxInfo['post']                     = $post;
            //$WxInfo['initInfo']                 = $initInfo;
            $initData = json_decode($initInfo, true);
            $WxInfo['fromUser']                 = $initData['User'];
            $WxInfo['contactInfo']              = $contactInfo;
            $m->set($mkey, $WxInfo, 7*24*3600);

            //p($WxInfo);
            $logArr = ['mkey'=>$mkey, 'WxInfo'=>$WxInfo, 'rst'=>$rst, 'loginInfo'=>$loginInfo];
            Tool_Common::log('setWxInfo', 'INFO', '设置微信缓存', $logArr);
            //print_r($_SESSION['callback_post_url_header']);die;
            //header("Location: wx.php?cmd=send"); exit;
        }
        //print_r($loginInfo);
        //print_r('登陆失败');

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

                    $post_url    = $WxInfo['callback_post_url_header'];
                    $post        = (array)$WxInfo['post'];
                    //$initInfo    = $WxInfo['initInfo'];
                    $fromUser    = $WxInfo['fromUser'];
                    $contactInfo = $WxInfo['contactInfo'];
                    if (!empty($word)) {
                        //$sendRst = self::webwxsendmsg($post, $initInfo, $post_url, $to, $word);
                        $sendRst = self::webwxsendmsg($post, $fromUser, $post_url, $to, $word);
                        //$logArr = ['sendRst'=>json_decode($sendRst, true), 'post'=>$post, 'initInfo'=>json_decode($initInfo,true), 'post_url'=>$post_url, 'to'=>$to, 'word'=>$word];
                        //p($logArr);
                        $logArr = ['WxInfo'=>$WxInfo, 'post'=>$post, 'sendRst'=>json_decode($sendRst, true), 'to'=>$to, 'word'=>$word];
                        Tool_Common::log('sendMsg', 'INFO', '微信发送信息', $logArr);
                    }
                }
            }
        }
        return ['status'=>200, 'data'=>json_decode($sendRst, true)];
    }


}
