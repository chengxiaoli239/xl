<?php
namespace common\service\jobs\robots\user;

use common\models\wechat\WechatUser;
use common\service\chat\Tool_Common;
use common\service\jobs\CommonJob;
use common\service\wechat\eyun\EYunBaseService;
use yii\helpers\Json;

class EYunUserJobs extends CommonJob {

    public static function getName($params) {
        self::$name = '微信联系人信息同步';
        return self::$name;
    }

    public function exec($params) {
        return self::handle($params);
    }

    public static function handle($params){
        $friends = $params['friends'];
        $user_id = $params['user_id'];
        $e = new EYunBaseService($user_id);
        $page = 1;
        $pageSize = 20;
        while (true){
            $offset = ($page - 1) * $pageSize;
            $wcIds = array_slice($friends, $offset, $pageSize);
            if (empty($wcIds)) {
                break;
            }
            $now_time = time();
            $response = $e->getContact($wcIds);
            Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', '微信联系人信息同步', ['wcIds'=>$wcIds, 'response'=>$response]);
            if($response['code'] == 1000 && !empty($response['data'])) {
                $datas = $response['data'];
                foreach ($datas as $data){
                    $where = ['user_id'=>$user_id, 'userName'=>$data['userName']];
                    $WechatUser = WechatUser::findOne($where);
                    $setData = [];
                    if(empty($WechatUser)){
                        $WechatUser = new WechatUser();
                        $setData = array_merge($setData, [
                            'user_id'=>$user_id,
                            'userName'=>$data['userName'],
                            'created_at' => $now_time,
                        ]);
                    }
                    $setData['updated_at'] = $now_time;
                    $setData = array_merge($setData, $data);
                    $WechatUser->setAttributes($setData, false);
                    if(!$WechatUser->save()){
                        throw_info(Json::encode($WechatUser->getErrors(), 320));
                    }
                }
            }
            sleep(2);
            $page += 1;
        }

        Tool_Common::log('/eyun/'.self::class_basename(__CLASS__), 'INFO', '微信用户消息', ['params'=>$params]);
        return '同步联系人信息成功';
    }

}
