<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SscDsYl;
use backend\models\SysPlansCodes;
use backend\models\TzSystemsAuth;
use backend\models\TzTypes;
use backend\models\UserCustomPlans;
use common\models\AdminModel;
use yii\helpers\ArrayHelper;
use  yii;

class UserSysPlansService extends BaseService {

    /**
     * @desc 预处理表单信息
     * @param $post
     * @param int $playway
     * @param $account
     * @param $lottery_type 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return bool
     */
    public static function preOpData(&$post, $user_id='', $lottery_type = DEFAULT_LOTTERY_TYPE){
        if(!$post OR !$user_id) return false;
        $tz_type = $post['UserSysPlans']['tz_type'];
        $playway = $post['UserSysPlans']['playway'];
        if(!$playway){
            $playway = BetService::getPlaywayByTzType($tz_type);
            $post['UserSysPlans']['playway'] = $playway;
        }
        $post['UserSysPlans']['lottery_type'] = $lottery_type;
        //p($post);
        //p(['tz_type'=>$tz_type, 'playway'=>$playway,'post'=>$post, 'user_id'=>$user_id]);
        $User = AdminModel::findOne($user_id);
        $post['UserSysPlans']['tz_sites'] = implode(',',$post['UserSysPlans']['tz_sites']);
        //p($post['UserSysPlans']['hz_Arr']);
        if($playway == 6) {
            $post['UserSysPlans']['hz_Arr'] = str_replace('，', ',', $post['UserSysPlans']['hz_Arr']);
        }elseif ($tz_type == 25){
            # 快选过滤
            $UserSysPlans = $post['UserSysPlans'];
            # 双重:type_2、三重:type_3、四重:type_4、双双重:type_22、两兄弟:type_2b、三兄弟:type_3b、四兄弟:type_4b
            $tmpFilter = [];
            # 1、双重
            if($UserSysPlans['type_2'] && count($UserSysPlans['type_2']) == 1){
                $tmpFilter['type_2'] = $UserSysPlans['type_2'][0];
            }
            unset($post['UserSysPlans']['type_2']);
            # 2、三重
            if($UserSysPlans['type_3'] && count($UserSysPlans['type_3']) == 1){
                $tmpFilter['type_3'] = $UserSysPlans['type_3'][0];
            }
            unset($post['UserSysPlans']['type_3']);
            # 3、四重
            if($UserSysPlans['type_4'] && count($UserSysPlans['type_4']) == 1){
                $tmpFilter['type_4'] = $UserSysPlans['type_4'][0];
            }
            unset($post['UserSysPlans']['type_4']);
            # 4、双双重
            if($UserSysPlans['type_22'] && count($UserSysPlans['type_22']) == 1){
                $tmpFilter['type_22'] = $UserSysPlans['type_22'][0];
            }
            unset($post['UserSysPlans']['type_22']);
            # 5、两兄弟
            if($UserSysPlans['type_2b'] && count($UserSysPlans['type_2b']) == 1){
                $tmpFilter['type_2b'] = $UserSysPlans['type_2b'][0];
            }
            unset($post['UserSysPlans']['type_2b']);
            # 6、三兄弟
            if($UserSysPlans['type_3b'] && count($UserSysPlans['type_3b']) == 1){
                $tmpFilter['type_3b'] = $UserSysPlans['type_3b'][0];
            }
            unset($post['UserSysPlans']['type_3b']);
            # 7、四兄弟
            if($UserSysPlans['type_4b'] && count($UserSysPlans['type_4b']) == 1){
                $tmpFilter['type_4b'] = $UserSysPlans['type_4b'][0];
            }
            unset($post['UserSysPlans']['type_4b']);
            $post['UserSysPlans']['hz_Arr'] = json_encode($tmpFilter);
            //p($post);
        }else{
            $hz_Arr = $post['UserSysPlans']['hz_Arr'];
            if(is_array($hz_Arr)) { # Array 和值打法，多个和值
                $hz_Arr = implode(',', $hz_Arr); # 和值打法
            }else{ # 四定上奖玩法 string
                $hz_Arr = str_replace('，', ',', $hz_Arr);
            }
            $post['UserSysPlans']['hz_Arr'] && $post['UserSysPlans']['hz_Arr'] = $hz_Arr;
        }
        $post['UserSysPlans']['uid'] = $user_id;
        $post['UserSysPlans']['account'] = $User->username;
        $post['UserSysPlans']['updated_at'] = time();

        if(!$post['UserSysPlans']['id']){
            $post['UserSysPlans']['created_at'] = time();
        }

        return $post;
    }

    /**
     * @description 加入三字定单双计划
     * @param string $account
     * @param int $threshold_open
     * @param int $threshold_close
     */
    public static function joinDs3DwPlans($account = 'gaozi2017', $threshold_open = 0, $threshold_close = 9, $is_simulate = 0){
        $playway = 2;
        $opData = [
            'account'=>$account,
            'playway'=>$playway,
            'playway_type'=>2,  // 投注方式：1:和值 2:单双
            'threshold_open'=>$threshold_open,
            'threshold_close'=>$threshold_close,
        ];
        $SscDsYls = SscDsYl::find()->where(['type'=>3])->all(); // 1一定2二定3三定4四定
        foreach ($SscDsYls as $SscDsYl){
            $positions = $SscDsYl->positions;
            $zhi = (string)$SscDsYl->zhi;
            $pArr = explode(',', $positions);
            $p_1 = $p_2 = $p_3 = $p_4 = '';
            foreach ($pArr as $k=>$p){
                $name = 'p_'.$p;
                $$name = $zhi[$k] + 2;  // 可变变量
            }
            $opData['hezhis'] = $zhi;
            $opData['positions'] = $positions;
            //p([$opData,$pArr, 'zhi'=>$zhi, 'p_1'=>$p_1,'p_2'=>$p_2,'p_3'=>$p_3,'p_4'=>$p_4]);
            $opData['codes'] = BaseNumService::dw4ZuHe($p_1,$p_2,$p_3,$p_4);

            $where = ['account'=>$account,'playway'=>$playway, 'hezhis'=>$zhi, 'is_simulate'=>$is_simulate]; // playway:1二定、2三定 3四定
            //p([$positions,$zhi]);
            if(!$UserCustomPlans = UserCustomPlans::findOne($where)){
                $UserCustomPlans = new UserCustomPlans();
            }

            $UserCustomPlans->setAttributes($opData);
            //p($UserCustomPlans->attributes,0);
            $UserCustomPlans->save();
        }

    }

    /**
     * @description 四字定单双计划
     */
    public static function insertSDPlans(){
        $playway = 3;
        # 投注方式：1:和值 2:单双
        $opData = [ 'playway'=>$playway, 'playway_type'=>2 ];
        $codeTypes = [
            # 所有
            //0 => ['1111', '1112', '1121', '1122', '1211', '1212', '1221', '1222', '2111', '2112', '2121', '2122', '2211', '2212', '2221', '2222' ],
            # 一单三双、一双三单
            1 => ['1112', '1121', '1211', '2111', '1222', '2122', '2212', '2221'],
            # 两双两单
            2 => ['1122', '1212', '1221', '2112', '2121', '2211'],
            # 四双四单
            3 => ['1111', '2222'],
            # 一单三双
            4 => ['1222', '2122', '2212', '2221'],
            # 一双三单
            5 => ['2111', '1211', '1121', '1112'],
        ];
        foreach ($codeTypes as $key=>$codeType){
            $setData = $opData;
            $setData['position'] = '1,2,3,4';
            $setData['status'] = 1;
            $setData['tz_type'] = $key;
            $codes = '';
            foreach ($codeType as $type){
                $codeArr = [1=>3, 2=>4, 3=>1, 4=>2];
                $p_1 = $codeArr[$type[0]];
                $p_2 = $codeArr[$type[1]];
                $p_3 = $codeArr[$type[2]];
                $p_4 = $codeArr[$type[3]];
                $codes .= BaseNumService::dw4ZuHe($p_1,$p_2,$p_3,$p_4).'@';
            }
            $setData['code'] = trim($codes, '@');
            $where = ['playway'=>$playway, 'tz_type'=>$key]; # tz_type:1二定、2三定 3四定
            if(!$SysPlansCodes = SysPlansCodes::findOne($where)){
                $SysPlansCodes = new SysPlansCodes();
                $setData['created_at'] = time();
            }
            $setData['updated_at'] = time();
            $SysPlansCodes->setAttributes($setData);
            //p($SysPlansCodes->attributes);
            $rst = $SysPlansCodes->save();
        }

        return ['status'=>200, 'msg'=>'添加四定', 'rst'=>$rst];
    }

    /**
     * @desc 添加投注类型对应表单数据
     * @param int $playway
     * @param $tz_type
     * @return array
     */
    public static function getSysPlansTypeDatas($playway = 3, $tz_type){
        $data = [];
        if($playway ==2){

        }elseif($playway ==3){
            if($tz_type<20){
                $kArr = StaticService::$kArr;
                unset($kArr[0], $kArr[1], $kArr[10], $kArr[11], $kArr[21], $kArr[22]);
                $data['kArr'] = $kArr;
            }elseif ($tz_type == 20){
                $hzArr = [];
                for ($i=1; $i<=36; $i++){
                    $hzArr[$i] = $i;
                }
                $data['hzArr'] = $hzArr;
            }elseif ($tz_type == 22){
                $SscDsYls = SscDsYl::find()->select(['id', 'positions', 'zhi'])->where(['type'=>4, 'LENGTH(zhi)'=>4])->asArray()->all();
                $hzArr = ArrayHelper::getColumn($SscDsYls, 'zhi', false);
                $tmpData = [];
                foreach ($hzArr as $zhi){
                    $tmpData[$zhi] = $zhi;
                }
                $data['hzArr'] = $tmpData;
            }
        }

        return $data;
    }

    /**
     * @desc 获取我的投注类型
     * @param $uid
     * @return array|TzTypes[]
     */
    public static function getMyTzTypes($uid){

        $tz_types_Arr = explode(',', TzSystemsAuth::find()->where(['uid'=>$uid])->one()->tz_types);

        $TzTypes = TzTypes::find()->where(['type'=>$tz_types_Arr])->asArray()->all();
        $tzTypeArr = [];
        foreach ($TzTypes as $key=>$data){
            $tzTypeArr[$key]['tz_type'] = $data['type'];
            $tzTypeArr[$key]['type_name'] = $data['type_name'];
            $tzTypeArr[$key]['playway'] = $data['playway'];
        }

        return $tzTypeArr;
    }

    /**
     * @desc 获取默认投注注数
     * @param int $playway
     * @param $tz_type
     * @return int
     */
    public static function getDefaultTzNums($tz_type){
        $playway = \backend\models\TzTypes::findOne(['type'=>$tz_type])->playway;
        $nums = 17;
        if($playway == 3){
            $nums = 6;
            if($tz_type == 20){
                $nums = 630;
            }
        }

        return $nums;
    }

}