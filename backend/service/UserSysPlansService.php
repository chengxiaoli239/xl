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
use backend\models\Num4Type;
use backend\models\SscDsYl;
use backend\models\SscKjData;
use backend\models\SysPlansCodes;
use backend\models\TzSystemsAuth;
use backend\models\TzSystemsUsers;
use backend\models\TzTypes;
use backend\models\UserCustomPlans;
use backend\models\UserSysPlans;
use backend\tools\Tools;
use common\models\AdminModel;
use common\service\CommonService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class UserSysPlansService extends BaseService {

    /**
     * @desc 预处理表单信息
     * @param $post
     * @param int $playway
     * @param $account
     * @param $lottery_type - 彩种类型：1:1.5分 2:3分 3:5分 4:10分|希腊、5:重庆ssc 6:新疆ssc
     * @return bool
     */
    public static function preOpData(&$post, $user_id='', $id = ''){
        if(!$post OR !$user_id) return false;
        $tz_type = $post['UserSysPlans']['tz_type'];
        $playway = $post['UserSysPlans']['playway'];
        $plan_type = $post['UserSysPlans']['plan_type'];
        $code_type = NumService::$playway_to_code_type[$playway];
        $lottery_type = $post['UserSysPlans']['lottery_type'] ? : DEFAULT_LOTTERY_TYPE;
        if(!$playway){
            $playway = BetService::getPlaywayByTzType($tz_type);
            $post['UserSysPlans']['playway'] = $playway;
        }
        //p(['tz_type'=>$tz_type, 'playway'=>$playway,'post'=>$post, 'user_id'=>$user_id]);

        $User = AdminModel::findOne($user_id);
        $post['UserSysPlans']['tz_sites'] = implode(',',$post['UserSysPlans']['tz_sites']);

        ################### 公共参数 - 开始 #########################
        $tmpFilter = []; # hz_Arr
        # 一、位置
        # 10、第1位
        $UserSysPlans = $post['UserSysPlans'];
        if(isset($UserSysPlans['p1']) && $UserSysPlans['p1'] !== ''){
            $tmpFilter['p1'] = (string)trim($UserSysPlans['p1']);
        }
        unset($post['UserSysPlans']['p1']);
        # 11、第2位
        if(isset($UserSysPlans['p2']) && $UserSysPlans['p2'] !== ''){
            $tmpFilter['p2'] = (string)trim($UserSysPlans['p2']);
        }
        unset($post['UserSysPlans']['p2']);
        # 12、第3位
        if(isset($UserSysPlans['p3']) && $UserSysPlans['p3'] !== ''){
            $tmpFilter['p3'] = (string)trim($UserSysPlans['p3']);
        }
        unset($post['UserSysPlans']['p3']);
        # 13、第4位
        if(isset($UserSysPlans['p4']) && $UserSysPlans['p4'] !== ''){
            $tmpFilter['p4'] = (string)trim($UserSysPlans['p4']);
        }
        unset($post['UserSysPlans']['p4']);
        # 14、第5位
        if(isset($UserSysPlans['p5']) && $UserSysPlans['p5'] !== ''){
            $tmpFilter['p5'] = trim($UserSysPlans['p5']);
        }
        unset($post['UserSysPlans']['p5']);

        # 15、配数1
        if(isset($UserSysPlans['ps_1']) && $UserSysPlans['ps_1'] !== ''){
            $tmpFilter['ps_1'] = trim($UserSysPlans['ps_1']);
        }
        unset($post['UserSysPlans']['ps_1']);
        # 15、配数1
        if(isset($UserSysPlans['ps_2']) && $UserSysPlans['ps_2'] !== ''){
            $tmpFilter['ps_2'] = trim($UserSysPlans['ps_2']);
        }
        unset($post['UserSysPlans']['pei_shu_2']);

        # 二、类型：双重:type_2、三重:type_3、四重:type_4、双双重:type_22、两兄弟:type_2b、三兄弟:type_3b、四兄弟:type_4b
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
        # 7、三现：双重+两兄
        if($UserSysPlans['type_3n_2b'] && count($UserSysPlans['type_3n_2b']) == 1){
            $tmpFilter['type_3n_2b'] = $UserSysPlans['type_3n_2b'][0];
        }
        unset($post['UserSysPlans']['type_3n_2b']);
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
        # 14、对数
        if($UserSysPlans['type_log'] && count($UserSysPlans['type_log']) == 1){
            $tmpFilter['type_log'] = $UserSysPlans['type_log'][0];
        }
        unset($post['UserSysPlans']['type_log']);
        # 15.1、单双类型:两双两单，四单，四双
        if(isset($UserSysPlans['type_4ds']) && $UserSysPlans['type_4ds']){
            $tmpFilter['type_4ds'] = $UserSysPlans['type_4ds'];
        }
        unset($post['UserSysPlans']['type_4ds']);
        # 15.2、单双类型:1122,2121,1222 等
        if(isset($UserSysPlans['type_ds_details']) && $UserSysPlans['type_ds_details']){
            $tmpFilter['type_ds_details'] = $UserSysPlans['type_ds_details'];
        }
        unset($post['UserSysPlans']['type_4ds']);

        # 16、遗漏投
        if(isset($UserSysPlans['bet_while_miss']) && $UserSysPlans['bet_while_miss']){
            $tmpFilter['bet_while_miss'] = $UserSysPlans['bet_while_miss'];
        }
        unset($post['UserSysPlans']['bet_while_miss']);
        # 17、每期轮换
        if(isset($UserSysPlans['change_per'][0]) && $UserSysPlans['change_per'][0]){
            $tmpFilter['change_per'] = $UserSysPlans['change_per'][0]; # 是否每期轮换
            $turn_key = trim($UserSysPlans['turn_key']);
            $tmpFilter['turn_key'] = (int)$turn_key; # 每次保存都从第一组开始
        }
        unset($post['UserSysPlans']['bet_while_miss']);
        # 16、双两兄弟
        if($UserSysPlans['type_22b'] && count($UserSysPlans['type_22b']) == 1){
            $tmpFilter['type_22b'] = $UserSysPlans['type_22b'][0];
        }
        unset($post['UserSysPlans']['type_22b']);

        # 排除前xx期号码
        if($UserSysPlans['is_filter_history'] && count($UserSysPlans['is_filter_history']) == 1){
            if(isset($UserSysPlans['filter_history_nums']) && $UserSysPlans['filter_history_nums']>0){
                $tmpFilter['is_filter_history'] = $UserSysPlans['is_filter_history'][0];
                $tmpFilter['filter_history_nums'] = $UserSysPlans['filter_history_nums'];
            }
        }
        unset($post['UserSysPlans']['type_log']);

        # 17、区间遗漏投 start
        if(!empty($UserSysPlans['area_all_qishus'])){
            $tmpFilter['area_all_qishus'] = $UserSysPlans['area_all_qishus'];
        }
        unset($post['UserSysPlans']['area_all_qishus']);
        if(!empty($UserSysPlans['area_yl_qishus'])){
            $tmpFilter['area_yl_qishus'] = $UserSysPlans['area_yl_qishus'];
        }
        unset($post['UserSysPlans']['area_yl_qishus']);
        if(!empty($UserSysPlans['area_profits'])){
            $tmpFilter['area_profits'] = $UserSysPlans['area_profits'];
        }
        unset($post['UserSysPlans']['area_profits']);
        if(!empty($UserSysPlans['area_loss'])){
            $tmpFilter['area_loss'] = $UserSysPlans['area_loss'];
        }
        unset($post['UserSysPlans']['area_loss']);
        # 17、区间遗漏投 end

        # 16.2、动态过滤 - 是否模拟
        if($UserSysPlans['is_batch_simulate'] && count($UserSysPlans['is_batch_simulate']) == 1){
            $post['UserSysPlans']['is_batch_simulate'] = (int)$UserSysPlans['is_batch_simulate'][0];
            if(in_array($tz_type, \Yii::$app->params['IMPORT_CODES_TYPES']) && empty($post['UserSysPlans']['import_codes_txts'][0])) { # 导入号码保存
                if($post['UserSysPlans']['filter_type'] == 1){ # filter_type:1排除同期
                    $diff_poses = array_diff(NumService::$ALL_POSES, $post['UserSysPlans']['filter_poses']);
                    $query = Num4Type::find()->select(['code']);
                    $t_where = [];
                    foreach ($diff_poses as $pos){
                        $t_where[] = ['OR', ['=', 'code_'.$pos, 'X'], ['=', 'code_'.$pos, '']];
                    }
                    $query->where(array_merge(['AND', ['=', 'code_type', $code_type]], $t_where));
                    $Num4Type = $query->asArray()->all();
                    $filter_codes = ArrayHelper::getColumn($Num4Type, 'code');
                    $post['UserSysPlans']['import_codes_txts'][0] = implode(' ', str_replace(',', '', $filter_codes));
                }else{
                    #pass
                }
            }
        }else{
            $post['UserSysPlans']['is_batch_simulate'] = 0;
        }

        # 17、任意位置 是否包含
        if($UserSysPlans['arb_pos_isbaohan'] && count($UserSysPlans['arb_pos_isbaohan']) == 1){
            $tmpFilter['arb_pos_isbaohan'] = $UserSysPlans['arb_pos_isbaohan'][0];
        }
        unset($post['UserSysPlans']['arb_pos_isbaohan']);
        # 18、任意数字
        if(isset($post['UserSysPlans']['arb_pos_nums']) && $post['UserSysPlans']['arb_pos_nums']){
            $tmpFilter['arb_pos_nums'] = $post['UserSysPlans']['arb_pos_nums'];
        }
        unset($post['UserSysPlans']['arb_pos_nums']);
        # 19、任意数字 至少x个
        if(isset($post['UserSysPlans']['arb_pos_codes']) && $post['UserSysPlans']['arb_pos_codes']){
            $tmpFilter['arb_pos_codes'] = $post['UserSysPlans']['arb_pos_codes'];
        }
        unset($post['UserSysPlans']['arb_pos_codes']);

        # 20.1、号码类型：取
        if(isset($post['UserSysPlans']['get_types']) && $post['UserSysPlans']['get_types']){
            $tmpFilter['get_types'] = $post['UserSysPlans']['get_types'];
        }
        unset($post['UserSysPlans']['get_types']);
        # 20.2、号码类型：除
        if(isset($post['UserSysPlans']['remove_types']) && $post['UserSysPlans']['remove_types']){
            $tmpFilter['remove_types'] = $post['UserSysPlans']['remove_types'];
        }
        unset($post['UserSysPlans']['remove_types']);

        # 21.1、A_x_arise_B_yarise_bet_B
        if(isset($post['UserSysPlans']['arise_A_times'])){
            $tmpFilter['arise_A_times'] = $post['UserSysPlans']['arise_A_times'] ? (int)$post['UserSysPlans']['arise_A_times'] : 0;
        }
        unset($post['UserSysPlans']['arise_A_times']);
        # 21.2、A_x_arise_B_yarise_bet_B
        if(isset($post['UserSysPlans']['arise_B_times'])){
            $tmpFilter['arise_B_times'] = $post['UserSysPlans']['arise_B_times'] ? (int)$post['UserSysPlans']['arise_B_times'] : 0;
        }
        unset($post['UserSysPlans']['arise_B_times']);

        ################### 公共参数 - 结束 #########################

        if($playway == 6) {
            $tmpFilter['codes'] = str_replace('，', ',', $post['UserSysPlans']['hz_Arr']);
            unset($post['UserSysPlans']['hz_Arr']);
        }elseif (in_array($tz_type, [29, 32])){ # 三定-快选 、三定快译切换
            # 三定-快选过滤
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
            $p_Arrs = ['p1', 'p2', 'p3', 'p4'];
            # 二定-快选过滤
            $UserSysPlans = $post['UserSysPlans'];

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

            # 二定补 'X'
            $pi = 0;
            $Null_ps = [];
            foreach ($p_Arrs as $p){
                if(!empty($UserSysPlans[$p])){
                    $pi++;
                }else{
                    $Null_ps[] = $p;
                }
            }
            if($pi > 1){
                foreach ($Null_ps as $pos){
                    $tmpFilter[$pos] = 'X';
                }
            }
        }elseif (in_array($tz_type, [25, 20])){
            # 四定-快选过滤

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

        if(!in_array($tz_type, [23]) && in_array($plan_type, [2,3,4,5,9, 12])){ # 翻倍计划
            if($id && $plan = UserSysPlans::findOne($id)){
                $tmpHzArr = json_decode($plan->hz_Arr, true);
                $singles_key = isset($tmpHzArr['singles_key']) ? $tmpHzArr['singles_key'] : 0;
                $current_miss = isset($tmpHzArr['current_miss']) ? $tmpHzArr['current_miss'] : 0;
            }else{
                $singles_key = 0;
                $current_miss = 0;
            }
            if($plan->plan_type == 12){ # A_x_B_y_status
                $tmpHzArr = json_decode($plan->hz_Arr, true);
                $A_x_B_y_status = isset($tmpHzArr['A_x_B_y_status']) ? $tmpHzArr['A_x_B_y_status'] : 0;
                $A_x_B_y_start_time = (isset($tmpHzArr['A_x_B_y_start_time']) && $tmpHzArr['A_x_B_y_start_time']) ? $tmpHzArr['A_x_B_y_start_time'] : date('Y-m-d H:i:s');
                $tmpFilter['start_bet_yl_nums'] = 0;
                $tmpFilter['current_arise_A_times'] = 0;
                $tmpFilter['current_arise_B_times'] = 0;
                $tmpFilter['current_yl_desc'] = '';

                $tmpFilter['A_x_B_y_status'] = $A_x_B_y_status;
                $tmpFilter['A_x_B_y_start_time'] = $A_x_B_y_start_time;
            }

            $tmpFilter['singles_key'] = $singles_key;
            $tmpFilter['current_miss'] = $current_miss;

            if(in_array($plan_type, [4, 5])){ # 号码切换,当前组1或者组2
                $tmpFilter['status_val'] = ($post['UserSysPlans']['status_val'] == 2) ? 2 : 1;
                unset($post['UserSysPlans']['status_val']);
            }
        }

        if($post['UserSysPlans']['bet_while_miss']>0){
            $tmpFilter['current_miss'] = 0;
        }

        ###################################################### 排除参数开始 2021.05.24 ######################################################
        # 1、排除前x期
        $filters = [];
        if(isset($UserSysPlans['is_filter'][0]) && $UserSysPlans['is_filter'][0]==1){
            $filter_xQ_before = '';
            isset($UserSysPlans['filter_xQ_before']) && ($filter_xQ_before = $UserSysPlans['filter_xQ_before']);
            $filter_xQ_before = str_replace('；', ';', str_replace('，', ',', $filter_xQ_before));
            $filters = array_merge($filters, [
                'is_filter' => 1,
                'filter_xQ_before' => (!empty($filter_xQ_before))? trim($filter_xQ_before):'',
                'filter_pos1' => (isset($UserSysPlans['filter_pos1']) && !empty($UserSysPlans['filter_pos1']))? $UserSysPlans['filter_pos1']:[],
                'filter_pos2' => (isset($UserSysPlans['filter_pos2']) && !empty($UserSysPlans['filter_pos2']))? $UserSysPlans['filter_pos2']:[],
            ]);
        }

        # 2、动态过滤
        if(isset($UserSysPlans['filter_type']) && !empty($UserSysPlans['filter_type'])){
            $filters = array_merge($filters, [
                'filter_type' => $UserSysPlans['filter_type'],
                'filter_nums' => $UserSysPlans['filter_nums'],
                'test_period_days' => (int)trim($UserSysPlans['test_period_days']),
                'playway' => $playway,
                'filter_poses' => $UserSysPlans['filter_poses'],
                'start_qihao' => $UserSysPlans['start_qihao'],
                'lottery_type' => $lottery_type,
            ]);
        }
        $tmpFilter['filters'] = $filters;
        unset($UserSysPlans['is_filter'], $UserSysPlans['filter_xQ_before'], $UserSysPlans['filter_pos1'], $UserSysPlans['filter_pos2']);

        # 2、排除前x天同期
        $filter_dates = [];
        if(isset($UserSysPlans['is_filter_date'][0]) && $UserSysPlans['is_filter_date'][0]==1){
            $filter_xD_before = '';
            isset($UserSysPlans['filter_xD_before']) && ($filter_xD_before = $UserSysPlans['filter_xD_before']);
            $filter_xD_before = str_replace('；', ';', str_replace('，', ',', $filter_xD_before));
            $filter_dates = array_merge($filter_dates, [
                'is_filter_date' => 1,
                'filter_xD_before' => (!empty($filter_xD_before))? trim($filter_xD_before):'',
                'filter_date_pos1' => (isset($UserSysPlans['filter_date_pos1']) && !empty($UserSysPlans['filter_date_pos1']))? $UserSysPlans['filter_date_pos1']:[],
                'filter_date_pos2' => (isset($UserSysPlans['filter_date_pos2']) && !empty($UserSysPlans['filter_date_pos2']))? $UserSysPlans['filter_date_pos2']:[],
            ]);
        }
        $tmpFilter['filter_dates'] = $filter_dates;
        unset($UserSysPlans['is_filter_date'], $UserSysPlans['filter_xD_before'], $UserSysPlans['filter_date_pos1'], $UserSysPlans['filter_date_pos2']);

        # 3、排除期号，比如：058期，二定则排除：58XX
        $filter_qihaos = [];
        if(isset($UserSysPlans['is_filter_qihao'][0]) && $UserSysPlans['is_filter_qihao'][0]==1){
            $filter_qihaos = array_merge($filter_qihaos, [
                'is_filter_qihao' => 1,
            ]);
        }
        $tmpFilter['filter_qihaos'] = $filter_qihaos;
        unset($UserSysPlans['is_filter_qihao']);
        ###################################################### 排除参数结束 2021.05.24 ######################################################

        if(!in_array($tz_type, [22, 23, 24])){ # 四定和值、上奖全倒、直码
            $hz_Arr = json_encode($tmpFilter, 320);
            $post['UserSysPlans']['hz_Arr'] = !empty($hz_Arr) ? $hz_Arr : '';
        }
        $post['UserSysPlans']['hz_Arr'] = !$post['UserSysPlans']['hz_Arr'] ? '' : $post['UserSysPlans']['hz_Arr'];

        $post['UserSysPlans']['uid'] = $user_id;
        $post['UserSysPlans']['account'] = $User->username;
        $post['UserSysPlans']['updated_at'] = time();

        if(!$post['UserSysPlans']['id']){
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$user_id, 'tz_system_id'=>$post['UserSysPlans']['tz_sites'][0], 'status'=>1]);
            $tz_sort = 99;
            if($TzSystemsUsers){
                $TzSystemsUsers->tz_sort && $tz_sort = $TzSystemsUsers->tz_sort;
            }
            $post['UserSysPlans']['tz_sort'] = $tz_sort;
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
    public static function saveImportCodesTxt($plan_id, $codes, $change_per = 0, $uid = ''){
        $setData = [];

        $flag = false;
        if(empty($plan_id)) return $flag;
        $isolationLevel = \yii\db\Transaction::REPEATABLE_READ;
        $transaction = Yii::$app->db->beginTransaction($isolationLevel);
        try {
            foreach ($codes as $key=>$code){
                $code = trim($code);
                if($change_per==1 && strpos($key, 'arise') !== false){
                    continue;
                }
                $key = (string)$key;
                $status = ($key == 0 OR $change_per == 1) ? 1 : 0;
                $status = empty($code) ? 0 : $status;

                if(!$ImportPlanCodes = ImportPlanCodes::find()->where(['uid'=>$uid, 'plan_id'=>$plan_id, 'plan_id_sort_key'=>$key])->one()){
                    $ImportPlanCodes = new ImportPlanCodes();
                    $setData = array_merge($setData, [
                        'created_at' => time(),
                        'uid' => $uid,
                        'plan_id' => $plan_id,
                        'plan_id_sort_key' => $key,
                    ]);
                }
                $codesData = $code;
                $codesData = preg_replace( '#\s+#', ' ', $codesData);
                $codesData = str_replace(' ', ',', $codesData);
                $codesArr = !empty($codesData) ? explode(',', $codesData) : [];
                $insertCodes = [];
                if(!empty($codesArr)) foreach ($codesArr as $tmpCodes){
                    if(strlen($tmpCodes) == 5){
                        $insertCodes[] = strtoupper($tmpCodes[0]).','.strtoupper($tmpCodes[1]).','.strtoupper($tmpCodes[2]).','.strtoupper($tmpCodes[3]).','.strtoupper($tmpCodes[4]);
                    }else{
                        $insertCodes[] = strtoupper($tmpCodes[0]).','.strtoupper($tmpCodes[1]).','.strtoupper($tmpCodes[2]).','.strtoupper($tmpCodes[3]);
                    }
                }

                $insertCodesData = implode('@', $insertCodes);
                $setData = array_merge($setData, [
                    'updated_at' => time(),
                    'status' => $status,
                    'codes' => $insertCodesData,
                ]);
                $ImportPlanCodes->setAttributes($setData);
                //p($ImportPlanCodes->attributes);
                $saveFlag = $ImportPlanCodes->save();
                if(!$saveFlag){
                    $transaction->rollBack();
                    Tool_Common::log('/error/'.__FUNCTION__, 'ERR', '保存错误', ['msg'=>$ImportPlanCodes->getErrors()]);
                    return false;
                }
            }
        }catch (\Exception $exception){
            $transaction->rollBack();
            $msg = $exception->getMessage();
            Tool_Common::log('/error/'.__FUNCTION__, 'ERR', '保存导入方案号码', ['plan_id'=>$plan_id, 'msg'=>$msg]);
            return false;
        }
        $transaction->commit();

        return $flag;
    }

    /**
     * @desc 导入方案号码表数据
     * @param $plan_id
     * @param int $code_type 1一定2二定3三定4四定
     * @return string
     */
    public static function getImportCodes($plan_id, $code_type=''){
        $plan = UserSysPlans::findOne($plan_id);
        $hzArr = json_decode($plan->hz_Arr, true);
        $key = ($hzArr['change_per']==0 OR $hzArr['turn_key']==0) ? 0 : $hzArr['turn_key'];
        $data = ImportPlanCodes::find()->where(['plan_id'=>$plan_id, 'plan_id_sort_key'=>$key])->one();

        $code_types = [1=>2, 2=>3, 3=>4]; # playway:code_type
        $codes = explode('@',$data->codes);
        if(
            (isset($hzArr['filters']) && isset($hzArr['filters']['is_filter']) && $hzArr['filters']['is_filter']==1) OR # 1、排除前x期
            (isset($hzArr['filter_dates']) && isset($hzArr['filter_dates']['is_filter_date']) && $hzArr['filter_dates']['is_filter_date']==1) OR # 2、排除前x天同期
            (isset($hzArr['filter_qihaos']) && isset($hzArr['filter_qihaos']['is_filter_qihao']) && $hzArr['filter_qihaos']['is_filter_qihao']==1) # 3、排除期号定位
        ){
            $codes = NumService::getCodesKuaiXuan($hzArr, $code_types[$plan->playway], $codes, $plan->lottery_type);
        }

        # A出x次B出y次投B
        if(in_array($plan->plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types)){
            $where_codes = ['plan_id'=>$plan_id, 'plan_id_sort_key'=>['arise_A_codes', 'arise_B_codes']];
            $datas = ImportPlanCodes::find()->where($where_codes)->limit(2)->all();
            $codes_arises = [];
            foreach ($datas as $d){
                $codes_arises[$d->plan_id_sort_key] = $d->codes;
            }
            # {"arise_A_times":3,"arise_B_times":1,"current_arise_A_times":4, "current_arise_B_times":4, "A_x_B_y_start_time":"2022-03-06 14:00:00","filters":[],"filter_dates":[],"filter_qihaos":[]}
            $arise_codes = $codes_arises['arise_A_codes'];
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '号码获取', ['current_arise_A_times'=>$hzArr["current_arise_A_times"], 'arise_A_times'=>$hzArr['arise_A_times'], 'current_arise_B_times'=>$hzArr["current_arise_B_times"], 'arise_B_times'=>$hzArr['arise_B_times']]);
            if($hzArr["current_arise_A_times"]>=$hzArr['arise_A_times'] && $hzArr["current_arise_B_times"]==$hzArr['arise_B_times']){
                # 符合下注条件 投B组号码
                $arise_codes = $codes_arises['arise_B_codes'];
            }
            $codes = explode('@', $arise_codes);
        }


        return $codes;
    }

    /**
     * @desc 添加投注类型对应表单数据
     * @param int $playway
     * @param $tz_type
     * @return array
     */
    public static function getSysPlansTypeDatas($playway = 3, $tz_type='', $uid=''){
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
            }elseif ($tz_type == 22){ # 四字定单双
                $SscDsYls = SscDsYl::find()->select(['id', 'positions', 'zhi'])->where(['type'=>4, 'LENGTH(zhi)'=>4])->asArray()->all();
                $hzArr = ArrayHelper::getColumn($SscDsYls, 'zhi', false);
                $tmpData = [];
                foreach ($hzArr as $zhi){
                    $tmpData[$zhi] = $zhi;
                }
                $data['hzArr'] = $tmpData;
            }elseif (in_array($tz_type, [20, 25, 28, 38])){ # 20四定和值 25快选 28系统快捷 38新快打
                $hzArr = [];
                for ($i=0; $i<=36; $i++){
                    $hzArr[$i] = $i;
                }
                $data['hzArr'] = $hzArr;
                if(in_array($tz_type, [25, 28, 38])){ # 系统快捷、过滤快打
                    $data['code_types'] = UserSysPlansService::getCodeTypes();
                }
                $data['type_4ds_Arr'] = UserSysPlansService::getCodeTypes($flag = 2); # 单双类型：两单两双、四单、四双
                $data['type_ds_details_Arr'] = UserSysPlansService::getCodeTypes($flag = 3); # 单双类型：1122,2121 等
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
    public static function getMyLotteryTypes($uid=0){

        $m = \Yii::$app->cache;
        $mkey = 'getMyLotteryTypes_data_'.$uid;
        //if($typeDatas = $m->get($mkey)) return $typeDatas;
        if($uid>1){
            $where = ['uid'=>$uid];
        }else{
            $where = ['uid'=>1];
        }

        if($uid == 1){
            $lottery_types = ArrayHelper::getColumn(LotteryType::find()->select(['lottery_type'])->where(['enable'=>1])->asArray()->all(), 'lottery_type');
        }else{
            $lottery_types = explode(',', TzSystemsAuth::find()->where($where)->one()->lottery_types);
        }

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
    public static function getCodeTypes($flag = 1){
        $m = \Yii::$app->cache;
        $mkey = 'getCodeTypes_03_'.$flag;

        if(true OR !$data = $m->get($mkey)){
            $codeTypes = CodeTypes::find()->where(['status'=>1, 'flag'=>$flag])->asArray()->All();
            foreach ($codeTypes as $codeType){
                $data[$codeType['type']] = $codeType['type_name'];
            }
            $m->set($mkey, $data, \Yii::$app->params['BASE_DATA_CACHE_TIME']*2);
        }

        return $data;
    }

    /**
     * @desc 号码类型key
     * @return array
     */
    public static function getCodeTypeKeys($flag = 1){
        $m = \Yii::$app->cache;
        $mkey = 'getCodeTypeKeys_03_'.$flag;

        if(true OR !$data = $m->get($mkey)){
            $codeTypes = CodeTypes::find()->where(['status'=>1, 'flag'=>$flag])->asArray()->All();
            foreach ($codeTypes as $codeType){
                $data[$codeType['type']] = $codeType['type_key'];
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

    public static function getYLByPlanId($plan_id = 934){
        $yl = 0;
        $UserSysPlans = UserSysPlans::findOne($plan_id);
        //p($UserSysPlans);
        $lottery_type = $UserSysPlans->lottery_type;
        $code_type = self::getCodeTypeByTzType($UserSysPlans->tz_type);
        $codeArr = NumService::getCodesKuaiXuan(json_decode($UserSysPlans->hz_Arr, 320), $code_type);
        p($codeArr);

    }

    /**
     * @desc 获取code_type 用于筛选号码
     * @param int $tz_type
     * @return int|mixed
     */
    public static function getCodeTypeByTzType($tz_type = 25){
        $code_type = 4;
        $tz_types = [
            30 => 2,
            31 => 5,
            29 => 3,
            33 => 2,
        ];

        if(isset($tz_types[$tz_type])) $code_type = $tz_types[$tz_type];

        return $code_type;
    }

    /**
     * @desc 部分投注类型切换反买处理，目前处理：四定单双
     * @param string $plan_id
     * @return array
     */
    public static function switchBuyType($plan_id = ''){
        $rst = ['status'=>300, 'msg'=>'操作成功'];
        $UserSysPlans = UserSysPlans::findOne($plan_id);
        if(!empty($UserSysPlans) && in_array($UserSysPlans->tz_type, \Yii::$app->params['can_change_buy_type'])){
            if($UserSysPlans->tz_type == 22){
                $vals = explode(',', $UserSysPlans->hz_Arr);
                $allVals = explode(',', \Yii::$app->params['ALL_DS']);
                $val = array_diff($allVals, $vals);
                $val_str = implode(',', $val);
                if(!empty($val_str)){
                    $UserSysPlans->hz_Arr = $val_str;
                    $UserSysPlans->save();
                }
            }
        }

        return $rst;
    }

    /**
     * @param $id
     * @param string $_user_id
     * @return array
     */
    public static function openPlanBetStatus($id, $_user_id=''){
        if($_user_id != 1){
            $rst = ['status'=>300, 'msg'=>'没操作权限'];
        }else{
            $plan = UserSysPlans::findOne($id);
            if(empty($plan)){
                $rst = ['status'=>301, 'msg'=>'找不到对应记录【'.$id.'】'];
            }else{
                $qihao = HN0898Service::getQihao($plan->lottery_type);
                $tz_system_id = trim($plan->tz_sites);
                BetService::beforeBetNow($plan->account, $tz_system_id, $plan->lottery_type, $qihao, $plan->id, $plan->uid); # 手动下注时，先删除缓存
            }
        }

        return $rst;
    }

    /**
     * @desc 个人规则配置
     * @param int $uid
     * @return array
     */
    public static function getUserSetConfigs($uid=11){
        $configs = [
            11 => [
                'start_hz' => 0, # 起始和值
                'end_hz' => 36, # 结束和值
                'remove_type_ds_Arr' => [], # 单双 '1111', '2222'
                'type_3b' => [0], # 三兄
                'type_3n_2b' => [0], # 三现：双重+两兄
            ]
        ];
        if(empty($configs[$uid])) return $configs;

        return $configs[$uid];
    }

    /**
     * @return array
     */
    public static function getAllHz($start_hz=0, $end_hz=36){
        $hzArr = [];
        for ($i=$start_hz; $i<=$end_hz; $i++){
            $hzArr[$i] = $i;
        }
        return $hzArr;
    }

    /**
     * @param $tz_type
     * @param $model
     * @param $lottery_type - 彩种
     * @param string $uid
     * @param int $isShort 精简
     * @return bool
     */
    public static function newKuaiDa($tz_type, &$model, $lottery_type, $uid='', $isShort = 0){
        $flag = true;
        $config = self::getUserSetConfigs($uid);
        $newKjDatas = SscKjData::find()->where(['lottery_type'=>$lottery_type])->orderBy(['id'=>SORT_DESC])->limit(3)->all();
        $all_nums = [0,1,2,3,4,5,6,7,8,9];
        $newOneKjData = $newKjDatas[0]; # 倒数第一期，最新
        $newTwoKjData = $newKjDatas[1]; # 倒数第二期
        $newThreeKjData = $newKjDatas[2]; # 倒数第三期
        $kj_nums = explode(',', $newOneKjData->code_4n_str);
        $getNums = array_diff($all_nums, $kj_nums); # 数组1跟数组2的差集， 主要是排除最近一期的开奖号码
        $allHz = self::getAllHz($config['start_hz'], $config['end_hz']); # 用户默认和值
        //p([$allHz, $all_nums, $kj_nums, $getNums]);

        if($tz_type==38){ # 过滤快打
            $model->arb_pos_isbaohan = 1; # 是否包含
            $model->arb_pos_codes = implode('', $getNums); # 排除掉上期开奖号码
            $model->arb_pos_nums = 2; # 排除掉上期开奖号码至少上两个
            //p($newOneKjData->attributes);
            $newHz = $newOneKjData->codes_4nums_hz;
            $newHz_t = 36 - $newHz;
            foreach ([$newHz, $newHz_t] as $hz){
                if(in_array($hz, $allHz)){
                    $k = array_search($hz, $allHz);
                    unset($allHz[$k]);
                }
            }
            //p([$allHz]);
            $model->hz = $allHz;
            $model->type_3b = $config['type_3b']; # 排除三兄弟
            $model->type_3n_2b = $config['type_3n_2b']; # 三现：双重+两兄

            $all_type_ds_Arr = UserSysPlansService::getCodeTypes($flag = 3); # 单双类型：1122,2121 等
            //$model->type_ds_details = array_diff($all_type_ds_Arr, [$newOneKjData->code_1_2_3_4, '1111', '2222']);
            $remove_type_ds_Arr = $config['remove_type_ds_Arr']?array_merge([$newOneKjData->code_1_2_3_4], $config['remove_type_ds_Arr']):[$newOneKjData->code_1_2_3_4];
            $model->type_ds_details = array_diff($all_type_ds_Arr, $remove_type_ds_Arr);

            ############################  位置号码过滤 start  #################################
            # 1、千位 过滤的号码
            $p1_remove_codes = [$newOneKjData->code4, ($newOneKjData->code2+$newTwoKjData->code3)%10]; # 斜线相加除余10
            if($isShort && count($p1_remove_codes)<2 && $newOneKjData->code1 == $newTwoKjData->code1){
                #$p1_remove_codes = array_merge($p1_remove_codes, self::removeBnums($newTwoKjData->code1)); # 除左右边数
                $p1_remove_codes = array_merge($p1_remove_codes, self::removeBnums($newTwoKjData->code1)); #
            }
            if($isShort && count($p1_remove_codes)<2 && $newTwoKjData->code3 == $newOneKjData->code2){ # 斜对
                $p1_remove_codes = array_merge($p1_remove_codes, self::removeBnums($newTwoKjData->code3));
            }
            $v1 = $newOneKjData->code1 + $newTwoKjData->code1; # 两个位置对着，是单双或者双单
            if(($v1%2) == 1){
                //$p1_remove_codes = array_merge($p1_remove_codes, self::removeBnums($newOneKjData->code1));
            }
            if($isShort && count($p1_remove_codes)<2){
                $p1_remove_codes = array_merge($p1_remove_codes, [$newOneKjData->code2]);
            }

            # 2、百位
            $p2_remove_codes = [];
            $v2 = $newOneKjData->code2 + $newTwoKjData->code2; # 两个位置对着，是单双或者双单
            //if(($v2%2) == 1){
            if($isShort && ($newThreeKjData->code2>$newTwoKjData->code2 && $newTwoKjData->code2>$newOneKjData->code2) OR ($newThreeKjData->code2<$newTwoKjData->code2 && $newTwoKjData->code2<$newOneKjData->code2)){
                $p2_remove_codes = array_merge($p2_remove_codes, self::removeBnums($newOneKjData->code2));
            }
            if(count($p2_remove_codes)<2){
                $p2_remove_codes = array_merge($p2_remove_codes, [($newTwoKjData->code4+$newOneKjData->code3)%10]); # （倒数第二期个位+倒数第一期十位）%10   斜对角相加求余10
            }
            if($isShort && count($p2_remove_codes)<2){
                $p2_remove_codes = array_merge($p2_remove_codes, [$newOneKjData->code3]);
            }

            # 3、个位
            $p3_remove_codes = [];
            //$v3 = $newOneKjData->code3 + $newTwoKjData->code3; # 两个位置对着，是单双或者双单
            if($isShort && ($newThreeKjData->code3>$newTwoKjData->code3 && $newTwoKjData->code3>$newOneKjData->code3) OR ($newThreeKjData->code3<$newTwoKjData->code3 && $newTwoKjData->code3<$newOneKjData->code3)){
                #if(($v3%2) == 1){
                $p3_remove_codes = array_merge($p3_remove_codes, self::removeBnums($newOneKjData->code3));
            }
            if(count($p3_remove_codes)<2){
                $p3_remove_codes = array_merge($p3_remove_codes, [($newTwoKjData->code1+$newOneKjData->code2)%10]); # （倒数第二期千位+倒数第一期百位）%10   斜对角相加求余10
            }
            if($isShort && count($p3_remove_codes)<2){
                $p3_remove_codes = array_merge($p3_remove_codes, [$newOneKjData->code2]);
            }

            # 4、个位排除
            $p4_remove_codes = [$newOneKjData->code1, ($newOneKjData->code3+$newTwoKjData->code2)%10]; # 斜线相加除余10
            if($isShort && count($p4_remove_codes)<2 && $newOneKjData->code4 == $newTwoKjData->code4){
                $p4_remove_codes = array_merge($p4_remove_codes, self::removeBnums($newTwoKjData->code1));
            }
            if($isShort && count($p4_remove_codes)<2 && $newTwoKjData->code2 == $newOneKjData->code3){
                $p4_remove_codes = array_merge($p4_remove_codes, self::removeBnums($newTwoKjData->code3));
            }
            $v4 = $newOneKjData->code4 + $newTwoKjData->code4; # 两个位置对着，是单双或者双单
            if(($v4%2) == 1){
                //$p4_remove_codes = array_merge($p4_remove_codes, self::removeBnums($newOneKjData->code4));
            }
            if(count($p4_remove_codes)<2){
                $p4_remove_codes = array_merge($p4_remove_codes, [$newOneKjData->code3]);
            }

            $model->p1 = implode('', array_diff($all_nums, $p1_remove_codes)); # 千位排除个位号码
            $model->p2 = implode('', array_diff($all_nums, $p2_remove_codes)); # 百位排除十位号码
            $model->p3 = implode('', array_diff($all_nums, $p3_remove_codes)); # 十位排除百位号码
            $model->p4 = implode('', array_diff($all_nums, $p4_remove_codes)); # 个位排除千位号码
            ############################  位置号码过滤 start  #################################
        }

        return $flag;
    }

    /**
     * @desc 去除相邻号码
     * @param $code
     * @return int[]
     */
    public static function removeBnums($code){
        $reomveCodes = [];
        if($code === 0) {
            $reomveCodes = [1, 9];
        }elseif ($code == 9) {
            $reomveCodes = [0, 8];
        }else{
            $reomveCodes = [$code-1, $code+1];
        }
        /*
        if(true OR !in_array($code, [0,1,9])){
            $reomveCodes = [$code-1, $code+1];
        }
        */
        return $reomveCodes;
    }

    /**
     * @desc 定制化下注、模拟
     * @param array $data
     * @param int $uid
     * @return array
     */
    public static function newQuitBetType($data=[], $uid=0){

        $lottery_type = $data['UserSysPlans']['lottery_type'];
        if(in_array($lottery_type, [22, 23, 24, 25])){
            $rst = UserSysPlansService::newQuickBet($data, $uid);
        }else{
            return ['status'=>300, 'msg'=>'未知彩种'];
        }

        return $rst;
    }



    /**
     * @desc 新快打
     * @param $data
     * @param $uid
     * @return array
     */
    public static function newQuickBet($data, $uid=''){
        $rst = ['status'=>200, 'msg'=>'操作成功', 'data'=>['push_data'=>[], 'push_rst'=>['code'=>200, 'msg'=>'操作成功']]];
        //p($data);
        $lottery_type = $data['UserSysPlans']['lottery_type'];
        $playway = $data['UserSysPlans']['playway'];
        $single = $data['UserSysPlans']['single'];
        $tz_system_id = $data['UserSysPlans']['tz_sites'][0];
        $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$uid, 'tz_system_id'=>$tz_system_id, 'status'=>1]);
        $account = $TzSystemsUsers->account;
        if(!$TzSystemsUsers){
            $msg = '账号已被禁用不能下注';
            Tool_Common::log('tzByPlanId_isLogin','INFO','投注记录tzByPlanId', ['uid'=>$uid,'account'=>$account, 'msg'=>$msg]);
            return ['status'=>400, 'msg'=>$msg];
        }
        $code_types = [
            1 => 2, # 二定
            2 => 3, # 三定
            3 => 4, # 四定
        ];
        $code_type = $code_types[$data['UserSysPlans']['playway']];
        $tz_type = $data['UserSysPlans']['tz_type'];
        $is_test = $data['UserSysPlans']['is_test']; # 是否模拟
        //p([$uid, $tz_system_id, $lottery_type]);
        $activeQihao = BetService::getActiveQihao($uid, $tz_system_id, $lottery_type);
        //p([$tz_type, $activeQihao]);
        $model = new UserSysPlans();
        UserSysPlansService::preOpData($data, $user_id=1);
        $model->load($data);
        $codes_hz = json_decode($model->hz_Arr, true);
        $codes = NumService::getCodesKuaiXuan($codes_hz, $code_type);
        //$codes = ['0,0,6,8', '0,6,0,9'];

        $rst = BetService::synBalance($uid,$tz_system_id, $is_auto=2); # 同步余额

        # 5、投注请求
        $BetService = BetService::getBetObj($uid, $tz_system_id, $lottery_type);
        # 下注操作
        $tmpRst = $BetService->betByCodes($activeQihao, $codes, $uid, $single, $playway, $lottery_type);
        $rstData = $tmpRst['rstData'];
        $xCsrf = $tmpRst['xCsrf'];
        $m = \Yii::$app->cache;
        if(isset($xCsrf['Token']) && !empty($xCsrf['Token'])){
            $xCsrf_key = CommonService::buildXCsrfTokenKey($uid, $tz_system_id);
            $m->set($xCsrf_key, $xCsrf, 120);
        }
        $logArr = ['account'=>$account, 'tz_sites'=>$tz_system_id,'codes'=>$codes, 'postRst'=>$tmpRst];
        Tool_Common::log('plan_bet_new','INFO','0898投注记录', $logArr);
        if($tmpRst === false){
            Tool_Common::log('/tz_err/tzByPlanId','INFO','投注记录 异常', $logArr);
            return ['status'=>301, 'msg'=>'投注异常', 'tmpRst'=>false];
        }
        if(!$is_test && $rstData['code'] != 200){
            $rst['status'] = $rstData['code'];
            $rst['msg'] = $rstData['msg'];
        }else{
            $totalmoney = count($codes)*$single;
            $post_desc = \backend\service\NumService::getDescByKuaixuan($codes_hz);
            # 下注成功记录：
            $insertData = [
                'playway'=> $playway,  // 投注方式
                'tz_type'=> $tz_type,  // 投注类型
                'buy_type'=> 1,  // 购买方向类型
                'uid'=> $uid,  // 投注账号id
                'lottery_type' => $lottery_type, # 彩种
                'account' => $account,
                'post_desc' => $post_desc,
                'codes' => implode('@', $codes),  // 投注号码
                'qihao' => $activeQihao,  // 投注期号
                'plan_id' => '',  // 计划id
                'tz_system_id' => $TzSystemsUsers->tz_system_id,  // 投注系统tz_systems .id
                'sn'=>trim($sn, ','),
                'snid'=>trim($snid, ','),
                'order_type'=>3, # 单双三字定
                'is_simulate' => 0,  // 是否模拟投注
                'single' => $single,  // 投注倍数
                'betting_money'=> $totalmoney,  // 投注金额
            ];
            $insertRst = BetService::_logRecords($insertData);
            Tool_Common::log('/quick_bet/'.__FUNCTION__, 'INFO', '新过滤快打', ['account'=>$account, 'qihao'=>$activeQihao, 'playway'=>$playway, 'counts'=>count($codes), 'insertRst'=>$insertRst]);

        }
        $rst['data']['push_rst'] = $rstData;

        $rst['data']['push_data']['qihao'] = $activeQihao;
        $rst['data']['push_data']['nums'] = count($codes);
        $rst['data']['push_data']['money'] = round($totalmoney,2);
        $rst['data']['push_data']['code_desc'] = $post_desc;
        $rst['data']['push_data']['codes'] = str_replace('@', ',',str_replace(',', '', implode('@', $codes)));

        return $rst;
    }

}