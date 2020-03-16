<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\CodeTypes;
use backend\models\CodeTypesQuery;
use backend\models\ImportPlanCodes;
use backend\models\LotteryType;
use backend\models\SscDsYl;
use backend\models\SysPlansCodes;
use backend\models\TzSystemsAuth;
use backend\models\TzTypes;
use backend\models\UserCustomPlans;
use backend\models\UserSysPlans;
use common\models\AdminModel;
use common\service\CommonService;
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
    public static function preOpData(&$post, $user_id='', $id = ''){
        if(!$post OR !$user_id) return false;
        $tz_type = $post['UserSysPlans']['tz_type'];
        $playway = $post['UserSysPlans']['playway'];
        $plan_type = $post['UserSysPlans']['plan_type'];
        if(!$playway){
            $playway = BetService::getPlaywayByTzType($tz_type);
            $post['UserSysPlans']['playway'] = $playway;
        }
        //p(['tz_type'=>$tz_type, 'playway'=>$playway,'post'=>$post, 'user_id'=>$user_id]);

        $User = AdminModel::findOne($user_id);
        $post['UserSysPlans']['tz_sites'] = implode(',',$post['UserSysPlans']['tz_sites']);
        //p($post['UserSysPlans']['hz_Arr']);
        if($playway == 6) {
            $post['UserSysPlans']['hz_Arr'] = str_replace('，', ',', $post['UserSysPlans']['hz_Arr']);
        }elseif (in_array($tz_type, [29, 32])){ # 三定-快选 、三定快译切换
            # 三定-快选过滤
            $UserSysPlans = $post['UserSysPlans'];
            # 双重:type_2、三重:type_3、两兄弟:type_2b、三兄弟:type_3b
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
            # 8、和值
            if(isset($post['UserSysPlans']['hz']) && $post['UserSysPlans']['hz']){
                $tmpFilter['hz'] = $post['UserSysPlans']['hz'];
            }
            unset($post['UserSysPlans']['hz']);
            # 9、上奖
            if(isset($UserSysPlans['arise']) && $UserSysPlans['arise'] !== '' && ($UserSysPlans['arise'] OR $UserSysPlans['arise'] == 0)){
                $tmpFilter['arise'] = trim($UserSysPlans['arise']);
            }
            unset($post['UserSysPlans']['arise']);
            # 10、第1位
            if(isset($UserSysPlans['p1']) && $UserSysPlans['p1']){
                $tmpFilter['p1'] = $UserSysPlans['p1'];
            }
            unset($post['UserSysPlans']['p1']);
            # 11、第2位
            if(isset($UserSysPlans['p2']) && $UserSysPlans['p2']){
                $tmpFilter['p2'] = trim($UserSysPlans['p2']);
            }
            unset($post['UserSysPlans']['p2']);
            # 12、第3位
            if(isset($UserSysPlans['p3']) && $UserSysPlans['p3']){
                $tmpFilter['p3'] = trim($UserSysPlans['p3']);
            }
            unset($post['UserSysPlans']['p3']);
            # 13、第4位
            if(isset($UserSysPlans['p4']) && $UserSysPlans['p4']){
                $tmpFilter['p4'] = trim($UserSysPlans['p4']);
            }
            unset($post['UserSysPlans']['p4']);
            # 14、对数
            if($UserSysPlans['type_log'] && count($UserSysPlans['type_log']) == 1){
                $tmpFilter['type_log'] = $UserSysPlans['type_log'][0];
            }
            unset($post['UserSysPlans']['type_log']);
            # 15.1、合分位置
            if($UserSysPlans['hefen_pos'] && count($UserSysPlans['hefen_pos']) > 0){
                //$tmpFilter['hefen_pos'] = $UserSysPlans['hefen_pos'][0];
                $tmpFilter['hefen_pos'] = implode(',', $post['UserSysPlans']['hefen_pos']); # 合分位置
            }
            unset($post['UserSysPlans']['hefen_pos']);
            # 15.2、合分值
            if(isset($post['UserSysPlans']['hefen']) && $post['UserSysPlans']['hefen']){
                $tmpFilter['hefen'] = $post['UserSysPlans']['hefen']; # 合分
            }
            unset($post['UserSysPlans']['hefen']);

            # 16.1、不定位合分:位置
            if($UserSysPlans['no_fix_hefen_pos'] && count($UserSysPlans['no_fix_hefen_pos']) == 1){
                //$tmpFilter['hefen_pos'] = $UserSysPlans['hefen_pos'][0];
                $tmpFilter['no_fix_hefen_pos'] = implode(',', $post['UserSysPlans']['no_fix_hefen_pos']); # 合分位置
            }
            unset($post['UserSysPlans']['no_fix_hefen_pos']);
            # 16.2、不定位合分:值
            if(isset($post['UserSysPlans']['no_fix_hefen']) && $post['UserSysPlans']['no_fix_hefen']){
                $tmpFilter['no_fix_hefen'] = trim($post['UserSysPlans']['no_fix_hefen']); # 合分
            }
            unset($post['UserSysPlans']['no_fix_hefen']);

            # 17.1、三定含：除、取
            if(!empty($UserSysPlans['arise_in_sel']) && count($UserSysPlans['arise_in_sel']) == 1){
                $tmpFilter['arise_in_sel'] = $UserSysPlans['arise_in_sel'][0];
            }
            unset($post['UserSysPlans']['arise_in_sel']);
            # 17.2、含
            if(!empty($UserSysPlans['arise_in'])){
                $tmpFilter['arise_in'] = trim($UserSysPlans['arise_in']);
            }
            unset($post['UserSysPlans']['arise_in']);

            # 号码切换倍投
            if(!empty($UserSysPlans['code1'])){
                $UserSysPlans['code1'] = str_replace(' ', '',$UserSysPlans['code1']);
                $tmpFilter['code1'] = str_replace('，', ',', $UserSysPlans['code1']);
            }
            unset($post['UserSysPlans']['code1']);
            if(!empty($UserSysPlans['code2'])){
                $UserSysPlans['code2'] = str_replace(' ', '',$UserSysPlans['code2']);
                $tmpFilter['code2'] = str_replace('，', ',', $UserSysPlans['code2']);
            }
            unset($post['UserSysPlans']['code2']);

            //$post['UserSysPlans']['hz_Arr'] = json_encode($tmpFilter, 320);
        }elseif (in_array($tz_type, [30, 31, 33])){ # 二定-快选、五位二定、二定变换
            # 二定-快选过滤
            $UserSysPlans = $post['UserSysPlans'];
            # 双重:type_2、三重:type_3、四重:type_4、双双重:type_22、两兄弟:type_2b、三兄弟:type_3b、四兄弟:type_4b
            $tmpFilter = [];
            # 1、双重
            if($UserSysPlans['type_2'] && count($UserSysPlans['type_2']) == 1){
                $tmpFilter['type_2'] = $UserSysPlans['type_2'][0];
            }
            unset($post['UserSysPlans']['type_2']);
            # 5、两兄弟
            if($UserSysPlans['type_2b'] && count($UserSysPlans['type_2b']) == 1){
                $tmpFilter['type_2b'] = $UserSysPlans['type_2b'][0];
            }
            unset($post['UserSysPlans']['type_2b']);
            # 8、和值
            if(isset($post['UserSysPlans']['hz']) && $post['UserSysPlans']['hz']){
                $tmpFilter['hz'] = $post['UserSysPlans']['hz'];
            }
            unset($post['UserSysPlans']['hz']);
            # 9、上奖
            if(isset($UserSysPlans['arise']) && $UserSysPlans['arise'] !== '' && ($UserSysPlans['arise'] OR $UserSysPlans['arise'] == 0)){
                $tmpFilter['arise'] = trim($UserSysPlans['arise']);
            }
            unset($post['UserSysPlans']['arise']);
            # 10、第1位
            if(isset($UserSysPlans['p1']) && $UserSysPlans['p1']){
                $tmpFilter['p1'] = trim($UserSysPlans['p1']);
            }
            unset($post['UserSysPlans']['p1']);
            # 11、第2位
            if(isset($UserSysPlans['p2']) && $UserSysPlans['p2']){
                $tmpFilter['p2'] = trim($UserSysPlans['p2']);
            }
            unset($post['UserSysPlans']['p2']);
            # 12、第3位
            if(isset($UserSysPlans['p3']) && $UserSysPlans['p3']){
                $tmpFilter['p3'] = trim($UserSysPlans['p3']);
            }
            unset($post['UserSysPlans']['p3']);
            # 13、第4位
            if(isset($UserSysPlans['p4']) && $UserSysPlans['p4']){
                $tmpFilter['p4'] = trim($UserSysPlans['p4']);
            }
            unset($post['UserSysPlans']['p4']);
            # 14、第5位
            if(isset($UserSysPlans['p5']) && $UserSysPlans['p5']){
                $tmpFilter['p5'] = trim($UserSysPlans['p5']);
            }
            unset($post['UserSysPlans']['p4']);
            # 15、对数
            if($UserSysPlans['type_log'] && count($UserSysPlans['type_log']) == 1){
                $tmpFilter['type_log'] = $UserSysPlans['type_log'][0];
            }
            unset($post['UserSysPlans']['type_log']);

            # 号码切换倍投
            if(!empty($UserSysPlans['code1'])){
                $UserSysPlans['code1'] = str_replace(' ', '',$UserSysPlans['code1']);
                $tmpFilter['code1'] = str_replace('，', ',', $UserSysPlans['code1']);
            }
            unset($post['UserSysPlans']['code1']);

            if(!empty($UserSysPlans['code2'])){
                $UserSysPlans['code2'] = str_replace(' ', '',$UserSysPlans['code2']);
                $tmpFilter['code2'] = str_replace('，', ',', $UserSysPlans['code2']);
            }
            unset($post['UserSysPlans']['code2']);

            //$post['UserSysPlans']['hz_Arr'] = json_encode($tmpFilter, 320);
        }elseif ($tz_type == 25){
            # 四定-快选过滤
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
            # 8、和值
            if(isset($post['UserSysPlans']['hz']) && $post['UserSysPlans']['hz']){
                $tmpFilter['hz'] = $post['UserSysPlans']['hz'];
            }
            unset($post['UserSysPlans']['hz']);
            # 9、上奖
            if(isset($UserSysPlans['arise']) && $UserSysPlans['arise'] !== '' && ($UserSysPlans['arise'] OR $UserSysPlans['arise'] == 0)){
                $tmpFilter['arise'] = trim($UserSysPlans['arise']);
            }
            unset($post['UserSysPlans']['arise']);
            # 10、第1位
            if(isset($UserSysPlans['p1']) && $UserSysPlans['p1']){
                $tmpFilter['p1'] = $UserSysPlans['p1'];
            }
            unset($post['UserSysPlans']['p1']);
            # 11、第2位
            if(isset($UserSysPlans['p2']) && $UserSysPlans['p2']){
                $tmpFilter['p2'] = trim($UserSysPlans['p2']);
            }
            unset($post['UserSysPlans']['p2']);
            # 12、第3位
            if(isset($UserSysPlans['p3']) && $UserSysPlans['p3']){
                $tmpFilter['p3'] = trim($UserSysPlans['p3']);
            }
            unset($post['UserSysPlans']['p3']);
            # 13、第4位
            if(isset($UserSysPlans['p4']) && $UserSysPlans['p4']){
                $tmpFilter['p4'] = trim($UserSysPlans['p4']);
            }
            unset($post['UserSysPlans']['p4']);
            # 13、四单四双
            if(isset($UserSysPlans['type_4ds']) && $UserSysPlans['type_4ds'] && count($UserSysPlans['type_4ds']) == 1){
                $tmpFilter['type_4ds'] = $UserSysPlans['type_4ds'][0];
            }
            unset($post['UserSysPlans']['type_4ds']);
            # 14、对数
            if($UserSysPlans['type_log'] && count($UserSysPlans['type_log']) == 1){
                $tmpFilter['type_log'] = $UserSysPlans['type_log'][0];
            }
            unset($post['UserSysPlans']['type_log']);

            # 15、四单
            if($UserSysPlans['type_4d'] && count($UserSysPlans['type_4d']) == 1){
                $tmpFilter['type_4d'] = $UserSysPlans['type_4d'][0];
            }
            unset($post['UserSysPlans']['type_4d']);
            # 16、四双
            if($UserSysPlans['type_4s'] && count($UserSysPlans['type_4s']) == 1){
                $tmpFilter['type_4s'] = $UserSysPlans['type_4s'][0];
            }
            unset($post['UserSysPlans']['type_4s']);

            # 15.1、合分位置
            if($UserSysPlans['hefen_pos'] && count($UserSysPlans['hefen_pos']) > 0){
                //$tmpFilter['hefen_pos'] = $UserSysPlans['hefen_pos'][0];
                $tmpFilter['hefen_pos'] = implode(',', $post['UserSysPlans']['hefen_pos']); # 合分位置
            }
            unset($post['UserSysPlans']['hefen_pos']);
            # 15.2、合分值
            if(isset($post['UserSysPlans']['hefen']) && $post['UserSysPlans']['hefen']){
                $tmpFilter['hefen'] = $post['UserSysPlans']['hefen']; # 合分
            }
            unset($post['UserSysPlans']['hefen']);

            # 16.1、不定位合分:位置
            if($UserSysPlans['no_fix_hefen_pos'] && count($UserSysPlans['no_fix_hefen_pos']) == 1){
                //$tmpFilter['hefen_pos'] = $UserSysPlans['hefen_pos'][0];
                $tmpFilter['no_fix_hefen_pos'] = implode(',', $post['UserSysPlans']['no_fix_hefen_pos']); # 合分位置
            }
            unset($post['UserSysPlans']['no_fix_hefen_pos']);
            # 16.2、不定位合分:值
            if(isset($post['UserSysPlans']['no_fix_hefen']) && $post['UserSysPlans']['no_fix_hefen']){
                $tmpFilter['no_fix_hefen'] = trim($post['UserSysPlans']['no_fix_hefen']); # 合分
            }
            unset($post['UserSysPlans']['no_fix_hefen']);

            # 17.1、三定含：除、取
            if(!empty($UserSysPlans['arise_in_sel']) && count($UserSysPlans['arise_in_sel']) == 1){
                $tmpFilter['arise_in_sel'] = $UserSysPlans['arise_in_sel'][0];
            }
            unset($post['UserSysPlans']['arise_in_sel']);
            # 17.2、含
            if(!empty($UserSysPlans['arise_in'])){
                $tmpFilter['arise_in'] = trim($UserSysPlans['arise_in']);
            }
            unset($post['UserSysPlans']['arise_in']);

            //$post['UserSysPlans']['hz_Arr'] = json_encode($tmpFilter, 320);
        }elseif ($tz_type == 28){ # 系统快捷
            $tmpFilter = [];
            # 1.1、号码类型：取
            if(isset($post['UserSysPlans']['get_types']) && $post['UserSysPlans']['get_types']){
                $tmpFilter['get_types'] = $post['UserSysPlans']['get_types'];
            }
            unset($post['UserSysPlans']['get_types']);
            # 1.2、号码类型：除
            if(isset($post['UserSysPlans']['remove_types']) && $post['UserSysPlans']['remove_types']){
                $tmpFilter['remove_types'] = $post['UserSysPlans']['remove_types'];
            }
            unset($post['UserSysPlans']['remove_types']);

            # 2.1、和值：取
            if(isset($post['UserSysPlans']['get_hzs']) && $post['UserSysPlans']['get_hzs']){
                $tmpFilter['get_hzs'] = $post['UserSysPlans']['get_hzs'];
            }
            unset($post['UserSysPlans']['get_hzs']);
            # 2.2、和值：除
            if(isset($post['UserSysPlans']['remove_hzs']) && $post['UserSysPlans']['remove_hzs']){
                $tmpFilter['remove_hzs'] = $post['UserSysPlans']['remove_hzs'];
            }
            unset($post['UserSysPlans']['remove_hzs']);

            # 3.1、上奖：取
            if(isset($post['UserSysPlans']['get_arises']) && $post['UserSysPlans']['get_arises']){
                $tmpFilter['get_arises'] = $post['UserSysPlans']['get_arises'];
            }
            unset($post['UserSysPlans']['get_arises']);
            # 3.2、上奖：除
            if(isset($post['UserSysPlans']['remove_arises']) && $post['UserSysPlans']['remove_arises']){
                $tmpFilter['remove_arises'] = $post['UserSysPlans']['remove_arises'];
            }
            unset($post['UserSysPlans']['remove_arises']);
            # 15.2、合分值
            if(isset($post['UserSysPlans']['hefen']) && $post['UserSysPlans']['hefen']){
                $tmpFilter['hefen'] = $post['UserSysPlans']['hefen']; # 合分
            }
            unset($post['UserSysPlans']['hefen']);
            //$post['UserSysPlans']['hz_Arr'] = json_encode($tmpFilter, 320);
        }else{
            #
            $hz_Arr = $post['UserSysPlans']['hz_Arr'];
            if(is_array($hz_Arr)) { # Array 和值打法，多个和值
                $hz_Arr = implode(',', $hz_Arr); # 和值打法
            }else{ # 四定上奖玩法 string
                $hz_Arr = str_replace('，', ',', $hz_Arr);
                $hz_Arr = str_replace(' ', ',', $hz_Arr);
            }
            $post['UserSysPlans']['hz_Arr'] && $post['UserSysPlans']['hz_Arr'] = trim($hz_Arr);
        }

        if(!in_array($tz_type, [20, 23]) && in_array($plan_type, [2,3,4,5])){ # 翻倍计划
            $tmpFilter['singles_key'] = (isset($post['UserSysPlans']['singles_key'])) ? (int)$post['UserSysPlans']['singles_key'] : 0;
            unset($post['UserSysPlans']['singles_key']);
            if(in_array($plan_type, [4, 5])){ # 号码切换,当前组1或者组2
                $tmpFilter['status_val'] = ($post['UserSysPlans']['status_val'] == 2) ? 2 : 1;
                unset($post['UserSysPlans']['status_val']);
            }
        }

        if(!in_array($tz_type, [20, 23])){
            $post['UserSysPlans']['hz_Arr'] = json_encode($tmpFilter, 320);
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
     * @desc 保存导入方案号码
     * @param $plan_id
     * @param $codes
     * @param $uid
     */
    public static function saveImportCodesTxt($plan_id, $codes, $uid){
        $setData = [];

        $flag = false;
        if($plan_id){
            if(!$ImportPlanCodes = ImportPlanCodes::findOne(['uid'=>$uid, 'plan_id'=>$plan_id])){
                $ImportPlanCodes = new ImportPlanCodes();
                $setData = array_merge($setData, [
                    'created_at' => time(),
                    'uid' => $uid,
                    'plan_id' => $plan_id,
                ]);
            }
            $codesData = trim($codes);
            $codesData = str_replace('  ', ',', $codesData);
            $codesData = str_replace(' ', ',', $codesData);
            $codesArr = explode(',', $codesData);
            $insertCodes = [];
            foreach ($codesArr as $tmpCodes){
                $insertCodes[] = strtoupper($tmpCodes[0]).','.strtoupper($tmpCodes[1]).','.strtoupper($tmpCodes[2]).','.strtoupper($tmpCodes[3]);
            }

            $insertCodesData = implode('@', $insertCodes);

            $setData = array_merge($setData, [
                'updated_at' => time(),
                'codes' => $insertCodesData,
            ]);
            $ImportPlanCodes->setAttributes($setData);
            $flag = $ImportPlanCodes->save();
        }

        return $flag;
    }

    /**
     * @desc 导入方案号码表数据
     * @param $plan_id
     * @return string
     */
    public static function getImportCodes($plan_id){
        $data = ImportPlanCodes::findOne(['plan_id'=>$plan_id]);

        $codes = explode('@',$data->codes);

        return $codes;
    }

    /**
     * @desc 添加投注类型对应表单数据
     * @param int $playway
     * @param $tz_type
     * @return array
     */
    public static function getSysPlansTypeDatas($playway = 3, $tz_type){
        $data = [];
        if($playway ==1){
            $hzArr = [];
            for ($i = 0; $i <= 18; $i++) {
                $hzArr[$i] = $i;
            }
            $data['hzArr'] = $hzArr;
        }elseif($playway ==2){
            $hzArr = [];
            for ($i = 0; $i <= 27; $i++) {
                $hzArr[$i] = $i;
            }
            $data['hzArr'] = $hzArr;
            $data['hefen_pos'] = [1=>'',2=>'',3=>'',4=>''];
        }elseif($playway ==3){
            $data['hefen_pos'] = [1=>'',2=>'',3=>'',4=>''];
            if($tz_type<20){
                $kArr = StaticService::$kArr;
                unset($kArr[0], $kArr[1], $kArr[10], $kArr[11], $kArr[21], $kArr[22]);
                $data['kArr'] = $kArr;
            }elseif ($tz_type == 20){ # 四定和值
                $hzArr = [];
                for ($i=1; $i<=36; $i++){
                    $hzArr[$i] = $i;
                }
                $data['hzArr'] = $hzArr;
            }elseif ($tz_type == 22){ # 四字定单双
                $SscDsYls = SscDsYl::find()->select(['id', 'positions', 'zhi'])->where(['type'=>4, 'LENGTH(zhi)'=>4])->asArray()->all();
                $hzArr = ArrayHelper::getColumn($SscDsYls, 'zhi', false);
                $tmpData = [];
                foreach ($hzArr as $zhi){
                    $tmpData[$zhi] = $zhi;
                }
                $data['hzArr'] = $tmpData;
            }elseif (in_array($tz_type, [25, 28])){ # 快选
                $hzArr = [];
                for ($i=0; $i<=36; $i++){
                    $hzArr[$i] = $i;
                }
                $data['hzArr'] = $hzArr;
                if(in_array($tz_type, [28])){
                    $data['code_types'] = UserSysPlansService::getCodeTypes();
                }
            }
        }

        return $data;
    }

    /**
     * @desc 获取我的投注类型
     * @param $uid
     * @return array|TzTypes[]
     */
    public static function getMyTzTypes($uid, $lottery_type = DEFAULT_LOTTERY_TYPE){

        $m = \Yii::$app->cache;
        $mkey = CommonService::buildMyTzTypes($uid, $lottery_type);
        //if($tzTypeArr = $m->get($mkey)) return $tzTypeArr;
        $tz_types_Arr = explode(',', TzSystemsAuth::find()->where(['uid'=>$uid])->one()->tz_types);

        $TzTypes = TzTypes::find()->where(['type'=>$tz_types_Arr])->asArray()->all();
        foreach ($TzTypes as $key=>$data){
            $tzTypeArr[$key]['tz_type'] = $data['type'];
            $tzTypeArr[$key]['type_name'] = $data['type_name'];
            $tzTypeArr[$key]['playway'] = $data['playway'];
            $tzTypeArr[$key]['lottery_type'] = $lottery_type;
        }

        $m->set($mkey, $tzTypeArr, \Yii::$app->params['BASE_DATA_CACHE_TIME']);

        return $tzTypeArr;
    }

    /**
     * @desc 获取我的投注类型
     * @param $uid
     * @return array|mixed
     */
    public static function getMyLotteryTypes($uid){

        $m = \Yii::$app->cache;
        $mkey = 'getMyLotteryTypes_data_'.$uid;
        //if($typeDatas = $m->get($mkey)) return $typeDatas;
        if($uid>1){
            $where = ['uid'=>$uid];
        }else{
            $where = '1=1';
        }
        $lottery_types = explode(',', TzSystemsAuth::find()->where($where)->one()->lottery_types);

        $datas = LotteryType::find()->where(['lottery_type'=>$lottery_types])->asArray()->all();
        $tmpDatas = [];
        foreach ($datas as $key=>$data){
            $tmpDatas[$data['lottery_type']] = $data['shortName'];
        }
        $typeDatas = [];
        foreach ($lottery_types as $key=>$lottery_type){
            $typeDatas[$key]['lottery_type'] = $lottery_type;
            $typeDatas[$key]['name'] = $tmpDatas[$lottery_type];
        }

        $m->set($mkey, $typeDatas, \Yii::$app->params['BASE_DATA_CACHE_TIME']);

        return $typeDatas;
    }

    /**
     * @desc 号码类型
     * @return array
     */
    public static function getCodeTypes(){
        $m = \Yii::$app->cache;
        $mkey = 'getCodeTypes_01';

        if(!$data = $m->get($mkey)){
            $codeTypes = CodeTypes::find()->asArray()->All();
            foreach ($codeTypes as $codeType){
                $data[$codeType['type']] = $codeType['type_name'];
            }
            $m->set($mkey, $data, \Yii::$app->params['BASE_DATA_CACHE_TIME']*2);
        }

        return $data;
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

    /**
     * @desc 计划方案倍数、投注号码或者投注状态修改
     * @param int $lottery_type
     * @return array
     */
    public static function userSysPlanChange($lottery_type = DEFAULT_LOTTERY_TYPE){
        $rst = ['status'=>300, 'msg'=>'投注方案修改成功'];
        //$kjData = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->one();
        //$qihao = $kjData->qihao;
        $singleArr = [0=>1, 1=>2, 2=>3, 3=>4, 4=>5, 5=>6, 6=>7, 7=>8, 8=>9];
        $qihao = HN0898Service::getQihao($lottery_type);
        $m = \Yii::$app->cache;
        $mkey = 'userSysPlanChange_'.$lottery_type.'_'.$qihao;
        if($r = $m->get($mkey)) return ['status'=>300, 'msg'=>'倍数修改计划已经处理完成[lottery_type:'.$lottery_type.',期号:'.$qihao.']'];

        $UserSysPlans = UserSysPlans::find()->where(['tz_type'=>18, 'status'=>1, 'is_parent'=>1, 'lottery_type'=>$lottery_type])->all(); # 一字定 倍数切换方案
        foreach ($UserSysPlans as $UserSysPlan){
            if(!$UserSysPlan->children_plan_id) continue;
            $ids = explode(',', $UserSysPlan->children_plan_id);

            $yls = [];
            foreach ($ids as $id){
                $plan = UserSysPlans::findOne($id);
                $codes = BetService::getPlansAllCodesType1($plan->tz_type, $plan->buy_type, $plan->sel_same, $plan->hz_Arr, $plan->id);
                $yl = StaticService::getYlByCodes($codes, $lottery_type, $plan->tz_type);
                $key = array_search($plan->single, $singleArr);

                $yls[] = ['id'=>$id, 'yl'=>$yl, 'codes'=>$codes, 'old_yl'=>$key, 'current_single'=>$plan->single];
            }
            /*
            $yls = [
                ['id'=>3, 'yl'=>6, 'old_yl'=>5, 'current_single'=>6],
                ['id'=>4, 'yl'=>0, 'old_yl'=>0, 'current_single'=>1],
            ];
            */
            //p($yls,0);

            # 比如：02579头14683头，每盘两组都买,不中哪组就翻倍买;翻倍那组中了就从头两组都买1块 或 连续翻倍不中8盘,两组就重新开始1块钱起步
            if(
                ($yls[0]['yl']>=8 OR $yls[1]['yl']>=8) # 连续翻倍不中8盘,两组就重新开始1块钱起步
                OR ( ($yls[0]['yl']==0 && $yls[0]['old_yl']>0) OR ($yls[1]['yl']==0 && $yls[1]['old_yl']>0) ) # 翻倍那组中了就从头两组都买1块
            ){
                //p('sklfjadslf');
                $rows = [];
                foreach ($yls as $key=>$yl8){
                    $rows[] = ['id'=>$yl8['id'], 'single'=>$singleArr[0]];
                }
            //}elseif (($yls[0]['yl']==0 && $yls[0]['old_yl']>0) OR ($yls[1]['yl']==0 && $yls[1]['old_yl']>0)){ # 翻倍那组中了就从头两组都买1块
            }elseif ($yls[0]['yl']==0 && $yls[1]['yl']>=1){
                //p('yyyyyyyyyyy');
                //$key = array_search($yls[1]['current_single'], $singleArr);
                $key_s = $yls[1]['yl'];
                $single = $singleArr[$key_s];
                $rows = [
                    ['id'=>$yls[0]['id'], 'single'=>$singleArr[0]],
                    ['id'=>$yls[1]['id'], 'single'=>$yls[0]['old_yl']>=1?1:$single],
                ];
            }elseif ($yls[1]['yl']==0 && $yls[0]['yl']>=1) {
                //p('lllllllllllllll');
                //$key = array_search($yls[0]['current_single'], $singleArr);
                //p([$yls[1]['current_single'], $key,$singleArr], 0);
                $key_s = $yls[0]['yl'];
                $single = $singleArr[$key_s];
                $rows = [
                    ['id' => $yls[0]['id'], 'single'=>$yls[1]['old_yl']>=1?1:$single],
                    ['id' => $yls[1]['id'], 'single'=>$singleArr[0]],
                ];
            }else{
                //p('kkkkkkkkkk',0);
                $rows = [
                    ['id' => $yls[0]['id'], 'single'=>$singleArr[0]],
                    ['id' => $yls[1]['id'], 'single'=>$singleArr[0]],
                ];
            }
            //p($rows,0);

            $hz_Arr_str = '';
            foreach ($rows as $row){
                $plan = UserSysPlans::findOne($row['id']);
                $plan->single = $row['single'];
                $hz_Arr_str .= $plan->hz_Arr.':'.$plan->single.'|';
                $plan->save();
            }

            $UserSysPlan->hz_Arr = rtrim($hz_Arr_str, '|');
            $UserSysPlan->save();
        }
        $rst['rows'] = $rows;
        $m->set($mkey, 1, 60);

        //p($rows);
        return $rst;
    }

}