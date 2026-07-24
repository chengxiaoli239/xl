<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\CodeTypes;
use backend\models\CodeTypesQuery;
use backend\models\ImportPlanCodes;
use backend\models\LotteryType;
use backend\models\Num4Type;
use backend\models\PlanStaticProfits;
use backend\models\SscDsYl;
use backend\models\SscKjData;
use backend\models\SysPlansCodes;
use backend\models\TzSystemsAuth;
use backend\models\TzSystemsUsers;
use backend\models\TzTypes;
use backend\models\UserCustomPlans;
use backend\models\UserSysPlans;
use common\models\AdminModel;
use common\models\base\BaseModel;
use common\service\cache\CacheKeyService;
use common\service\CommonService;
use common\service\ssc\QihaoService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;

class UserSysPlansService extends BaseService {

    public static function enableAutoLoginForRealPlan($plan): int
    {
        if(!$plan
            || (int)$plan->status !== 1
            || (int)$plan->is_test !== 0
            || (int)$plan->is_batch_simulate !== 0){
            return 0;
        }

        $tzSystemIds = array_values(array_filter(array_map('intval', explode(',', (string)$plan->tz_sites))));
        if(empty($tzSystemIds)){
            return 0;
        }

        return TzSystemsUsers::updateAll([
            'is_auto_login'=>1,
            'updated_at'=>time(),
        ], [
            'uid'=>(int)$plan->uid,
            'tz_system_id'=>$tzSystemIds,
            'status'=>1,
        ]);
    }

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

        $post['UserSysPlans']['singles'] = preg_replace( '#\s+#', '', trim($post['UserSysPlans']['singles']));
        $post['UserSysPlans']['start_qihao'] = str_replace(' ', '', $post['UserSysPlans']['start_qihao']);

        $User = AdminModel::findOne($user_id);
        $tzSites = $post['UserSysPlans']['tz_sites'] ?? [];
        if(!is_array($tzSites)){
            $tzSites = trim((string)$tzSites) === '' ? [] : explode(',', (string)$tzSites);
        }
        $tzSites = array_values(array_filter(array_map('trim', $tzSites), 'strlen'));
        if(empty($tzSites) && $id){
            $oldPlan = UserSysPlans::findOne($id);
            if($oldPlan && trim((string)$oldPlan->tz_sites) !== ''){
                $tzSites = array_values(array_filter(array_map('trim', explode(',', $oldPlan->tz_sites)), 'strlen'));
            }
        }
        if(empty($tzSites)){
            $tzSites = TzSystemsUsers::find()
                ->select(['tz_system_id'])
                ->where(['uid'=>(int)$user_id, 'status'=>1])
                ->andWhere(['>', 'tz_system_id', 0])
                ->orderBy(['id'=>SORT_ASC])
                ->column();
        }
        if(empty($tzSites)){
            $auth = TzSystemsAuth::findOne(['uid'=>(int)$user_id]);
            if($auth && trim((string)$auth->tz_systems_ids) !== ''){
                $tzSites = array_values(array_filter(array_map('trim', explode(',', $auth->tz_systems_ids)), 'strlen'));
            }
        }
        $post['UserSysPlans']['tz_sites'] = implode(',', $tzSites);

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
            $tmpFilter['p2'] = trim($UserSysPlans['p2']);
        }
        unset($post['UserSysPlans']['p2']);
        # 12、第3位
        if(isset($UserSysPlans['p3']) && $UserSysPlans['p3'] !== ''){
            $tmpFilter['p3'] = trim($UserSysPlans['p3']);
        }
        unset($post['UserSysPlans']['p3']);
        # 13、第4位
        if(isset($UserSysPlans['p4']) && $UserSysPlans['p4'] !== ''){
            $tmpFilter['p4'] = trim($UserSysPlans['p4']);
        }
        unset($post['UserSysPlans']['p4']);
        # 14、第5位
        if(isset($UserSysPlans['p5']) && $UserSysPlans['p5'] !== ''){
            $tmpFilter['p5'] = trim($UserSysPlans['p5']);
        }
        unset($post['UserSysPlans']['p5']);
        if(empty($post['UserSysPlans']['fixed_pos_sel']) && (!empty($UserSysPlans['p1']) OR !empty($UserSysPlans['p2']) OR !empty($UserSysPlans['p3']) OR !empty($UserSysPlans['p4']) OR !empty($UserSysPlans['p5']))){
            $tmpFilter['fixed_pos_sel'] = NumService::OBTAIN;
        }

        # 每天初始化
        if($UserSysPlans['is_init_perdate'] && count($UserSysPlans['is_init_perdate']) == 1){
            $post['UserSysPlans']['is_init_perdate'] = (int)$UserSysPlans['is_init_perdate'][0];
        }
        unset($UserSysPlans['is_init_perdate']);

        # 切换下方向
        if($UserSysPlans['bet_direct'] && count($UserSysPlans['bet_direct']) == 1){
            $post['UserSysPlans']['bet_direct'] = (int)$UserSysPlans['bet_direct'][0];
        }else{
            $post['UserSysPlans']['bet_direct'] = UserSysPlans::BET_DIRECT_Z;
        }
        unset($UserSysPlans['bet_direct']);

        # 定位置：千、百、十、个
        if($UserSysPlans['fixed_pos_sel'] && count($UserSysPlans['fixed_pos_sel']) == 1){
            $tmpFilter['fixed_pos_sel'] = (int)$UserSysPlans['fixed_pos_sel'][0];
        }
        # 配数
        if($UserSysPlans['ps_sel'] && count($UserSysPlans['ps_sel']) == 1){
            $tmpFilter['ps_sel'] = (int)$UserSysPlans['ps_sel'][0];
        }
        unset($post['UserSysPlans']['ps_sel']);
        # 15、配数1
        if(isset($UserSysPlans['ps_1']) && $UserSysPlans['ps_1'] !== ''){
            $tmpFilter['ps_1'] = trim($UserSysPlans['ps_1']);
        }
        unset($post['UserSysPlans']['ps_1']);
        # 15、配数2
        if(isset($UserSysPlans['ps_2']) && $UserSysPlans['ps_2'] !== ''){
            $tmpFilter['ps_2'] = trim($UserSysPlans['ps_2']);
        }
        unset($post['UserSysPlans']['ps_2']);
        # 15、配数3
        if(isset($UserSysPlans['ps_3']) && $UserSysPlans['ps_3'] !== ''){
            $tmpFilter['ps_3'] = trim($UserSysPlans['ps_3']);
        }
        unset($post['UserSysPlans']['ps_3']);
        # 15、配数4
        if(isset($UserSysPlans['ps_4']) && $UserSysPlans['ps_4'] !== ''){
            $tmpFilter['ps_4'] = trim($UserSysPlans['ps_4']);
        }
        unset($post['UserSysPlans']['ps_4']);
        # 定位置
        if($UserSysPlans['fixed_sel_pos'] && count($UserSysPlans['fixed_sel_pos']) > 0){
            $tmpFilter['fixed_sel_pos'] = implode(',', $post['UserSysPlans']['fixed_sel_pos']); # 合分位置
        }
        unset($post['UserSysPlans']['fixed_sel_pos']);

        # 对数
        if($UserSysPlans['log_sel'] && count($UserSysPlans['log_sel']) == 1){
            $tmpFilter['log_sel'] = (int)$UserSysPlans['log_sel'][0];
        }
        unset($post['UserSysPlans']['log_sel']);
        # 对数1
        if(isset($UserSysPlans['log_1']) && $UserSysPlans['log_1'] !== ''){
            $tmpFilter['log_1'] = trim($UserSysPlans['log_1']);
        }
        unset($post['UserSysPlans']['log_1']);
        # 对数2
        if(isset($UserSysPlans['log_2']) && $UserSysPlans['log_2'] !== ''){
            $tmpFilter['log_2'] = trim($UserSysPlans['log_2']);
        }
        unset($post['UserSysPlans']['log_2']);
        # 对数3
        if(isset($UserSysPlans['log_3']) && $UserSysPlans['log_3'] !== ''){
            $tmpFilter['log_3'] = trim($UserSysPlans['log_3']);
        }
        unset($post['UserSysPlans']['log_3']);

        # 筛选位置：单
        if($UserSysPlans['odd_sel'] && count($UserSysPlans['odd_sel']) == 1){
            $tmpFilter['odd_sel'] = (int)$UserSysPlans['odd_sel'][0];
        }
        unset($post['UserSysPlans']['odd_sel']);
        if($UserSysPlans['odd_pos'] && count($UserSysPlans['odd_pos']) > 0){
            $tmpFilter['odd_pos'] = implode(',', $post['UserSysPlans']['odd_pos']); # 合分位置
        }
        unset($post['UserSysPlans']['odd_pos']);
        # 筛选位置：双
        if($UserSysPlans['even_sel'] && count($UserSysPlans['even_sel']) == 1){
            $tmpFilter['even_sel'] = (int)$UserSysPlans['even_sel'][0];
        }
        unset($post['UserSysPlans']['even_sel']);
        if($UserSysPlans['even_pos'] && count($UserSysPlans['even_pos']) > 0){
            $tmpFilter['even_pos'] = implode(',', $post['UserSysPlans']['even_pos']); # 合分位置
        }
        unset($post['UserSysPlans']['even_pos']);
        # 筛选位置：大
        if($UserSysPlans['big_sel'] && count($UserSysPlans['big_sel']) == 1){
            $tmpFilter['big_sel'] = (int)$UserSysPlans['big_sel'][0];
        }
        unset($post['UserSysPlans']['big_sel']);
        if($UserSysPlans['big_pos'] && count($UserSysPlans['big_pos']) > 0){
            $tmpFilter['big_pos'] = implode(',', $post['UserSysPlans']['big_pos']); # 合分位置
        }
        unset($post['UserSysPlans']['big_pos']);
        # 筛选位置：小
        if($UserSysPlans['small_sel'] && count($UserSysPlans['small_sel']) == 1){
            $tmpFilter['small_sel'] = (int)$UserSysPlans['small_sel'][0];
        }
        unset($post['UserSysPlans']['small_sel']);
        if($UserSysPlans['small_pos'] && count($UserSysPlans['small_pos']) > 0){
            $tmpFilter['small_pos'] = implode(',', $post['UserSysPlans']['small_pos']); # 合分位置
        }
        unset($post['UserSysPlans']['small_pos']);

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
        # 10、排除
        if(isset($UserSysPlans['exclude_codes']) && $UserSysPlans['exclude_codes'] !== '' && ($UserSysPlans['exclude_codes'] OR $UserSysPlans['exclude_codes'] == 0)){
            $tmpFilter['exclude_codes'] = trim($UserSysPlans['exclude_codes']);
        }
        unset($post['UserSysPlans']['arise']);
        # 14、对数
        if($UserSysPlans['type_log'] && count($UserSysPlans['type_log']) == 1){
            $tmpFilter['type_log'] = $UserSysPlans['type_log'][0];
        }
        unset($post['UserSysPlans']['type_log']);
        # 14.1、双对数
        if($UserSysPlans['type_2log'] && count($UserSysPlans['type_2log']) == 1){
            $tmpFilter['type_2log'] = $UserSysPlans['type_2log'][0];
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
        unset($post['UserSysPlans']['type_ds_details']);

        # 16、遗漏投
        if(isset($UserSysPlans['bet_while_miss']) && $UserSysPlans['bet_while_miss']){
            $tmpFilter['bet_while_miss'] = (int)$UserSysPlans['bet_while_miss'];
        }
        unset($post['UserSysPlans']['bet_while_miss']);

        # 17、每期轮换
        if(isset($UserSysPlans['change_per'][0]) && $UserSysPlans['change_per'][0]){
            $tmpFilter['change_per'] = $UserSysPlans['change_per'][0]; # 是否每期轮换
            $turn_key = trim($UserSysPlans['turn_key']);
            $tmpFilter['turn_key'] = (int)$turn_key; # 每次保存都从第一组开始
            if(!empty($UserSysPlans['change_turn_pos']) && count($UserSysPlans['change_turn_pos'])==1){
                $tmpFilter['change_turn_pos'] = (int)$UserSysPlans['change_turn_pos'][0]; # 按照位置号码数指定第x组数
            }
        }
        # 17.1、每期指定号码轮换

        unset($post['UserSysPlans']['bet_while_miss']);
        # 16、双两兄弟
        if($UserSysPlans['type_22b'] && count($UserSysPlans['type_22b']) == 1){
            $tmpFilter['type_22b'] = $UserSysPlans['type_22b'][0];
        }
        unset($post['UserSysPlans']['type_22b']);

        # 反向打盘口
        if($UserSysPlans['bet_op_to_wp'] && count($UserSysPlans['bet_op_to_wp']) == 1){
            $tmpFilter['bet_op_to_wp'] = $UserSysPlans['bet_op_to_wp'][0];
            $tmpFilter['bet_op_to_wp_singles'] = $UserSysPlans['bet_op_to_wp_singles']??0.1;
        }
        unset($post['UserSysPlans']['bet_op_to_wp']);

        # 18、两合上1
        if($UserSysPlans['hsAndCf_twoFone']){
            $tmpFilter['hsAndCf_twoFone'] = $UserSysPlans['hsAndCf_twoFone'];
        }
        unset($post['UserSysPlans']['hsAndCf_twoFone']);

        # 排除前xx期号码
        if($UserSysPlans['is_filter_history'] && count($UserSysPlans['is_filter_history']) == 1){
            if(isset($UserSysPlans['filter_history_nums']) && $UserSysPlans['filter_history_nums']>0){
                $tmpFilter['is_filter_history'] = $UserSysPlans['is_filter_history'][0];
                $tmpFilter['filter_history_nums'] = $UserSysPlans['filter_history_nums'];
            }
        }
        unset($post['UserSysPlans']['is_filter_history'], $post['UserSysPlans']['filter_history_nums']);

        # 动态过滤1
        if(!empty($UserSysPlans['filter_dynamic_types']) && count($UserSysPlans['filter_dynamic_types'])>0){
            $tmpFilter['is_filter_dynamic'] = 1;
            $tmpFilter['filter_dynamic_types'] = $UserSysPlans['filter_dynamic_types'];
        }
        unset($post['UserSysPlans']['is_filter_dynamic'], $post['UserSysPlans']['filter_dynamic_types']);
        # 动态过滤2
        if(!empty($UserSysPlans['filter_dynamic_types2'])){
            foreach ($UserSysPlans['filter_dynamic_types2'] as $k=>$filter_dynamic_type2){
                if($filter_dynamic_type2['type'] == 0){
                    unset($UserSysPlans['filter_dynamic_types2'][$k]);
                }
            }
            $tmpFilter['filter_dynamic_types2'] = $UserSysPlans['filter_dynamic_types2'];
        }
        unset($post['UserSysPlans']['filter_dynamic_types2']);


        # 17、区间遗漏投 start
        if(!empty($UserSysPlans['area_all_qishus'])){ // 1、统计期数
            $tmpFilter['area_all_qishus'] = $UserSysPlans['area_all_qishus'];
        }
        unset($post['UserSysPlans']['area_all_qishus']);
        if(!empty($UserSysPlans['area_yl_qishus'])){ // 1、区间统计期数
            $tmpFilter['area_yl_qishus'] = $UserSysPlans['area_yl_qishus'];
        }
        unset($post['UserSysPlans']['area_yl_qishus']);
        if(!empty($UserSysPlans['area_loss_start'])){ // 2、亏损x元起投
            $tmpFilter['area_loss_start'] = $UserSysPlans['area_loss_start'];
        }
        unset($post['UserSysPlans']['area_loss_start']);

        if(!empty($UserSysPlans['area_profits'])){ // a.区间止盈金额
            $tmpFilter['area_profits'] = $UserSysPlans['area_profits'];
        }
        unset($post['UserSysPlans']['area_profits']);
        if(!empty($UserSysPlans['area_loss'])){ // a.区间止损金额
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
        if((int)$post['UserSysPlans']['is_batch_simulate'] === 1){
            $post['UserSysPlans']['is_test'] = 1;
        }

        # 17、任意位置 是否包含
        if($UserSysPlans['arb_pos_isbaohan'] && count($UserSysPlans['arb_pos_isbaohan']) == 1){
            $tmpFilter['arb_pos_isbaohan'] = $UserSysPlans['arb_pos_isbaohan'][0];
        }
        unset($post['UserSysPlans']['arb_pos_isbaohan']);
        # 18、任意数字
        if($UserSysPlans['arb_pos_isbaohan'] && isset($post['UserSysPlans']['arb_pos_nums']) && $post['UserSysPlans']['arb_pos_nums']){
            $tmpFilter['arb_pos_nums'] = $post['UserSysPlans']['arb_pos_nums'];
        }
        unset($post['UserSysPlans']['arb_pos_nums']);
        # 19、任意数字 至少x个
        if($UserSysPlans['arb_pos_isbaohan'] && isset($post['UserSysPlans']['arb_pos_codes']) && $post['UserSysPlans']['arb_pos_codes']){
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
            $ariseATimes = $post['UserSysPlans']['arise_A_times'];
            if(in_array($plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types) && (trim((string)$ariseATimes) === '' || (int)$ariseATimes < 1)){
                throw_info('A出次数必须大于等于1');
            }
            $tmpFilter['arise_A_times'] = $ariseATimes !== '' ? (int)$ariseATimes : 0;
        }elseif(in_array($plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types)){
            throw_info('A出次数必须大于等于1');
        }
        unset($post['UserSysPlans']['arise_A_times']);
        # 21.2、A_x_arise_B_yarise_bet_B
        if(isset($post['UserSysPlans']['arise_B_times'])){
            $ariseBTimes = $post['UserSysPlans']['arise_B_times'];
            if(in_array($plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types) && (trim((string)$ariseBTimes) !== '' && (int)$ariseBTimes < 0)){
                throw_info('B出次数不能小于0');
            }
            $tmpFilter['arise_B_times'] = $ariseBTimes !== '' ? (int)$ariseBTimes : 0;
        }elseif(in_array($plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types)){
            $tmpFilter['arise_B_times'] = 0;
        }
        unset($post['UserSysPlans']['arise_B_times']);

        # 定位合分 -------  start  ------------
        if($UserSysPlans['fixed_pos_hefen_sel'] && count($UserSysPlans['fixed_pos_hefen_sel']) == 1){
            $tmpFilter['fixed_pos_hefen_sel'] = (int)$UserSysPlans['fixed_pos_hefen_sel'][0];
        }
        if($UserSysPlans['hefen_pos1'] && count($UserSysPlans['hefen_pos1'])>0){
            $tmpFilter['hefen_pos1'] = implode(',', $post['UserSysPlans']['hefen_pos1']); # 合分位置
        }
        unset($post['UserSysPlans']['hefen_pos1']);
        # 15.1.2、合分值
        if(isset($post['UserSysPlans']['hefen1']) && ($UserSysPlans['hefen1'] !== '' && ($UserSysPlans['hefen1'] OR $UserSysPlans['hefen1'] == 0))){
            $tmpFilter['hefen1'] = trim($post['UserSysPlans']['hefen1']); # 合分
        }
        unset($post['UserSysPlans']['hefen1']);
        # 15.2.1、合分位置
        if($UserSysPlans['hefen_pos2'] && count($UserSysPlans['hefen_pos2']) > 0){
            $tmpFilter['hefen_pos2'] = implode(',', $post['UserSysPlans']['hefen_pos2']); # 合分位置
        }
        unset($post['UserSysPlans']['hefen_pos2']);
        # 15.2.2、合分值
        if(isset($post['UserSysPlans']['hefen2']) && ($UserSysPlans['hefen2'] !== '' && ($UserSysPlans['hefen2'] OR $UserSysPlans['hefen2'] == 0))){
            $tmpFilter['hefen2'] = trim($post['UserSysPlans']['hefen2']); # 合分
        }
        unset($post['UserSysPlans']['hefen2']);
        # 15.3.1、合分位置
        if($UserSysPlans['hefen_pos3'] && count($UserSysPlans['hefen_pos3']) > 0){
            $tmpFilter['hefen_pos3'] = implode(',', $post['UserSysPlans']['hefen_pos3']); # 合分位置
        }
        unset($post['UserSysPlans']['hefen_pos3']);
        # 15.3.2、合分值
        if(isset($post['UserSysPlans']['hefen3']) && ($UserSysPlans['hefen3'] !== '' && ($UserSysPlans['hefen3'] OR $UserSysPlans['hefen3'] == 0))){
            $tmpFilter['hefen3'] = trim($post['UserSysPlans']['hefen3']); # 合分
        }
        unset($post['UserSysPlans']['hefen3']);
        # 15.4.1、合分位置
        if($UserSysPlans['hefen_pos4'] && count($UserSysPlans['hefen_pos4']) > 0){
            $tmpFilter['hefen_pos4'] = implode(',', $post['UserSysPlans']['hefen_pos4']); # 合分位置
        }
        unset($post['UserSysPlans']['hefen_pos4']);
        # 15.4.2、合分值
        if(isset($post['UserSysPlans']['hefen4']) && ($UserSysPlans['hefen4'] !== '' && ($UserSysPlans['hefen4'] OR $UserSysPlans['hefen4'] == 0))){
            $tmpFilter['hefen4'] = trim($post['UserSysPlans']['hefen4']); # 合分
        }
        unset($post['UserSysPlans']['hefen4']);
        # 定位合分 -------  end  ----------

        # 17.1、三定含：除、取
        if(!empty($UserSysPlans['arise_in_sel']) && count($UserSysPlans['arise_in_sel']) == 1){
            $tmpFilter['arise_in_sel'] = (int)$UserSysPlans['arise_in_sel'][0];
            # 17.2、含
            $arise_in = trim($UserSysPlans['arise_in']);
            if(!empty($arise_in) OR $arise_in===0 OR $arise_in==='0'){
                $tmpFilter['arise_in'] = $arise_in;
            }
        }
        unset($post['UserSysPlans']['arise_in_sel']);
        unset($post['UserSysPlans']['arise_in']);

        # 分离数
        if(!empty($UserSysPlans['fenli_shu_code'])){
            foreach ($UserSysPlans['fenli_shu_code'] as $index=>$flCode){
                $type = $UserSysPlans['fenli_shu_sel_'.$index][0]??'';
                if($type==='') continue;
                $fenli_shu[] = ['type'=>(int)$type, 'code'=>$flCode];
            }
            /*
            $flsDatas = $UserSysPlans['fenli_shu_sel'];
            $fenli_shu = [];
            foreach ($flsDatas as $k=>$flsSel){
                if(empty($UserSysPlans['fenli_shu_code'][$k])) continue;
                $fenli_shu[] = ['type'=>(int)$flsSel, 'code'=>$UserSysPlans['fenli_shu_code'][$k]];
            }
            */
            if(!empty($fenli_shu)){
                $tmpFilter['fenli_shu'] = $fenli_shu;
            }
        }
        unset($post['UserSysPlans']['fenli_shu_sel']);
        unset($post['UserSysPlans']['fenli_shu_code']);

        ################### 公共参数 - 结束 #########################
        # 16.3、两数不定位合分:位置
        if($UserSysPlans['no_fix_hefen_pos_2'] && count($UserSysPlans['no_fix_hefen_pos_2']) == 1){
            $tmpFilter['no_fix_hefen_pos_2'] = implode(',', $post['UserSysPlans']['no_fix_hefen_pos_2']); # 合分位置
        }
        unset($post['UserSysPlans']['no_fix_hefen_pos_2']);
        # 16.3、两数不定位合分:值
        if(isset($post['UserSysPlans']['no_fix_hefen2']) && $post['UserSysPlans']['no_fix_hefen2']){
            $tmpFilter['no_fix_hefen2'] = trim($post['UserSysPlans']['no_fix_hefen2']); # 合分
        }
        unset($post['UserSysPlans']['no_fix_hefen2']);
        # 16.4、三数不定位合分:位置
        if($UserSysPlans['no_fix_hefen_pos_3'] && count($UserSysPlans['no_fix_hefen_pos_3']) == 1){
            $tmpFilter['no_fix_hefen_pos_3'] = implode(',', $post['UserSysPlans']['no_fix_hefen_pos_3']); # 合分位置
        }
        unset($post['UserSysPlans']['no_fix_hefen_pos_3']);
        # 16.4、三数不定位合分:值
        if(isset($post['UserSysPlans']['no_fix_hefen3']) && $post['UserSysPlans']['no_fix_hefen3']){
            $tmpFilter['no_fix_hefen3'] = trim($post['UserSysPlans']['no_fix_hefen3']); # 合分
        }
        unset($post['UserSysPlans']['no_fix_hefen3']);

        if($playway == 6) {
            $tmpFilter['codes'] = str_replace('，', ',', $post['UserSysPlans']['hz_Arr']);
            unset($post['UserSysPlans']['hz_Arr']);
        }elseif (in_array($tz_type, [29, 32])){ # 三定-快选 、三定快译切换
            # 三定-快选过滤
            ## 15.1、合分位置
            #if($UserSysPlans['hefen_pos1'] && count($UserSysPlans['hefen_pos1']) > 0){
            #    $tmpFilter['hefen_pos1'] = implode(',', $post['UserSysPlans']['hefen_pos1']); # 合分位置
            #}
            #unset($post['UserSysPlans']['hefen_pos1']);
            ## 15.2、合分值
            #if(isset($post['UserSysPlans']['hefen1']) && $post['UserSysPlans']['hefen1']){
            #    $tmpFilter['hefen1'] = $post['UserSysPlans']['hefen1']; # 合分
            #}
            #unset($post['UserSysPlans']['hefen1']);

            # 16.1、不定位合分:位置
            if($UserSysPlans['no_fix_hefen_pos'] && count($UserSysPlans['no_fix_hefen_pos']) == 1){
                $tmpFilter['no_fix_hefen_pos'] = implode(',', $post['UserSysPlans']['no_fix_hefen_pos']); # 合分位置
            }
            unset($post['UserSysPlans']['no_fix_hefen_pos']);
            # 16.2、不定位合分:值
            if(isset($post['UserSysPlans']['no_fix_hefen']) && $post['UserSysPlans']['no_fix_hefen']){
                $tmpFilter['no_fix_hefen'] = trim($post['UserSysPlans']['no_fix_hefen']); # 合分
            }
            unset($post['UserSysPlans']['no_fix_hefen']);

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
            # 16.1、不定位合分:位置
            if($UserSysPlans['no_fix_hefen_pos'] && count($UserSysPlans['no_fix_hefen_pos']) == 1){
                $tmpFilter['no_fix_hefen_pos'] = implode(',', $post['UserSysPlans']['no_fix_hefen_pos']); # 合分位置
            }
            unset($post['UserSysPlans']['no_fix_hefen_pos']);
            # 16.2、不定位合分:值
            if(isset($post['UserSysPlans']['no_fix_hefen']) && $post['UserSysPlans']['no_fix_hefen']){
                $tmpFilter['no_fix_hefen'] = trim($post['UserSysPlans']['no_fix_hefen']); # 合分
            }
            unset($post['UserSysPlans']['no_fix_hefen']);

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
            if(!empty($post['UserSysPlans']['hefen1'])){
                $tmpFilter['hefen1'] = $post['UserSysPlans']['hefen1']; # 合分
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

        if(!in_array($tz_type, [23]) && in_array($plan_type, [2,3,4,5,9, 12, 13])){ # 翻倍计划
            $existingPlan = null;
            if($id && ($existingPlan = UserSysPlans::findOne($id))){
                $tmpHzArr = json_decode($existingPlan->hz_Arr, true);
                $singles_key = $tmpHzArr['singles_key'] ?? 0;
                $current_miss = $tmpHzArr['current_miss'] ?? 0;
            }else{
                $singles_key = 0;
                $current_miss = 0;
            }
            if(in_array($plan_type, UserSysPlans::$A_x_arise_B_y_arise_bet_B_types)){ # A_x_B_y_status
                $tmpHzArr = $existingPlan ? json_decode($existingPlan->hz_Arr, true) : [];
                $A_x_B_y_status = $tmpHzArr['A_x_B_y_status'] ?? 0;
                $A_x_B_y_start_time = (isset($tmpHzArr['A_x_B_y_start_time']) && $tmpHzArr['A_x_B_y_start_time']) ? $tmpHzArr['A_x_B_y_start_time'] : date('Y-m-d H:i:s');
                $tmpFilter['start_bet_yl_nums'] = 0;
                $tmpFilter['current_arise_A_times'] = 0;
                $tmpFilter['current_arise_B_times'] = 0;
                $tmpFilter['current_yl_desc'] = '';

                $tmpFilter['A_x_B_y_status'] = $A_x_B_y_status;
                $tmpFilter['A_x_B_y_start_time'] = $A_x_B_y_start_time;
                $tmpFilter['type13_last_qihao'] = '';
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
                'filter_pos1' => (!empty($UserSysPlans['filter_pos1']))? $UserSysPlans['filter_pos1']:[],
                'filter_pos2' => (!empty($UserSysPlans['filter_pos2']))? $UserSysPlans['filter_pos2']:[],
            ]);
        }

        # 2、动态过滤
        if(!empty($UserSysPlans['start_qihao'])){
            $filters = array_merge($filters, [
                'playway' => $playway,
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
                'filter_date_pos1' => (!empty($UserSysPlans['filter_date_pos1']))? $UserSysPlans['filter_date_pos1']:[],
                'filter_date_pos2' => (!empty($UserSysPlans['filter_date_pos2']))? $UserSysPlans['filter_date_pos2']:[],
            ]);
            $tmpFilter['filter_dates'] = $filter_dates;
        }
        unset($UserSysPlans['is_filter_date'], $UserSysPlans['filter_xD_before'], $UserSysPlans['filter_date_pos1'], $UserSysPlans['filter_date_pos2']);

        # 3、排除期号，比如：058期，二定则排除：58XX
        $filter_qihaos = [];
        if(isset($UserSysPlans['is_filter_qihao'][0]) && $UserSysPlans['is_filter_qihao'][0]==1){
            $filter_qihaos = array_merge($filter_qihaos, [
                'is_filter_qihao' => 1,
            ]);
            $tmpFilter['filter_qihaos'] = $filter_qihaos;
        }
        unset($UserSysPlans['is_filter_qihao']);
        ###################################################### 排除参数结束 2021.05.24 ######################################################

        if(!in_array($tz_type, [22, 23, 24])){ # 四定和值、上奖全倒、直码
            $hz_Arr = json_encode($tmpFilter, 320);
            $post['UserSysPlans']['hz_Arr'] = !empty($hz_Arr) ? $hz_Arr : '';
        }
        $post['UserSysPlans']['hz_Arr'] = !$post['UserSysPlans']['hz_Arr'] ? '' : $post['UserSysPlans']['hz_Arr'];
        //p([$post, $post['UserSysPlans']]);

        $post['UserSysPlans']['uid'] = $user_id;
        $post['UserSysPlans']['account'] = $User->username;
        $post['UserSysPlans']['updated_at'] = time();

        # 翻倍计划如果翻倍梯度为空则赋值倍数
        if(in_array($plan_type, SscDataService::$fb_plan_types) && empty($post['UserSysPlans']['singles'])){
            $singles = preg_replace( '#\s+#', '', trim($post['UserSysPlans']['single']));
            $post['UserSysPlans']['singles'] = $singles;
        }

        if(empty($post['UserSysPlans']['id'])){
            $TzSystemsUsers = TzSystemsUsers::findOne(['uid'=>$user_id, 'tz_system_id'=>$tzSites[0] ?? null, 'status'=>1]);
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
        if(empty($plan_id)) return false;
        $isDeleteBefore = true;
        try {
            $transaction = Yii::$app->db->beginTransaction();
            $isDeleteBefore && ImportPlanCodes::deleteRecord(['uid'=>$uid, 'plan_id'=>$plan_id]);
            foreach ($codes as $key=>$code){
                $code = trim($code);
                if(!$code){
                    ImportPlanCodes::deleteRecord(['uid'=>$uid, 'plan_id'=>$plan_id, 'plan_id_sort_key' => $key]);
                    continue;
                }
                if($change_per==1 && strpos($key, 'arise') !== false){
                    continue;
                }
                $key = (string)$key;
                $status = ($key == 0 OR $change_per == 1) ? 1 : 0;
                $status = empty($code) ? 0 : $status;

                if($isDeleteBefore OR !$ImportPlanCodes = ImportPlanCodes::find()->where(['uid'=>$uid, 'plan_id'=>$plan_id, 'plan_id_sort_key' => $key])->limit(1)->one()){
                    $ImportPlanCodes = new ImportPlanCodes();
                    $setData = array_merge($setData, [
                        'created_at' => time(),
                        'uid' => $uid,
                        'plan_id' => $plan_id,
                        'plan_id_sort_key' => $key,
                    ]);
                }

                $codesData = $code;
                $codesData = preg_replace( '#\s+#', ' ', $codesData); # 多个空格替换成单个空格
                $codesData = str_replace(' ', ',', $codesData);
                $codesArr = !empty($codesData) ? explode(',', $codesData) : [];
                $insertCodes = [];
                if(!empty($codesArr)) foreach ($codesArr as $tmpCodes){
                    if(strlen($tmpCodes) == 5){
                        $insertCodes[] = trim(strtoupper($tmpCodes[0]).','.strtoupper($tmpCodes[1]).','.strtoupper($tmpCodes[2]).','.strtoupper($tmpCodes[3]).','.strtoupper($tmpCodes[4]));
                    }else{
                        $codesStr = trim(strtoupper($tmpCodes[0]).','.strtoupper($tmpCodes[1]).','.strtoupper($tmpCodes[2]).','.strtoupper($tmpCodes[3]));
                        $codesStr = trim($codesStr, '"');
                        $codesStr = trim($codesStr, ',');
                        $insertCodes[] = $codesStr;
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
            $transaction->commit();
        }catch (\Exception $exception){
            $transaction->rollBack();
            $msg = $exception->getMessage();
            Tool_Common::log('/error/'.__FUNCTION__, 'ERR', '保存导入方案号码', ['plan_id'=>$plan_id, 'msg'=>$msg]);
            return false;
        }

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
        $data = ImportPlanCodes::find()->where(['plan_id'=>$plan_id, 'plan_id_sort_key'=>$key, 'status'=>1])->one();
        //p(['plan_id'=>$plan_id, 'plan_id_sort_key'=>$key, 'status'=>1, $data]);
        if(empty($data) || empty($data->codes)){
            Tool_Common::log('/error/'.__FUNCTION__, 'ERR', '导入方案号码为空', ['plan_id'=>$plan_id, 'plan_id_sort_key'=>$key, 'status'=>1]);
            return '';
        }

        $code_types = [1=>2, 2=>3, 3=>4]; # playway:code_type
        $codes = explode('@',$data->codes);
        if(
            (isset($hzArr['filters']['is_filter']) && $hzArr['filters']['is_filter'] == 1) OR # 1、排除前x期
            (isset($hzArr['filter_dates']['is_filter_date']) && $hzArr['filter_dates']['is_filter_date'] == 1) OR # 2、排除前x天同期
            (isset($hzArr['filter_qihaos']['is_filter_qihao']) && $hzArr['filter_qihaos']['is_filter_qihao'] == 1) # 3、排除期号定位
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
            if($plan->plan_type == 13 && (int)($hzArr['A_x_B_y_status'] ?? 0) === \backend\service\PlanType13Service::STATUS_BET){
                # 类型13由独立状态机决定下期是否投B
                $arise_codes = $codes_arises['arise_B_codes'];
            }elseif($hzArr["current_arise_A_times"]>=$hzArr['arise_A_times'] && $hzArr["current_arise_B_times"]==$hzArr['arise_B_times']){
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
    public static function getSysPlansTypeDatas($playway = 3, $tz_type='', $uid=''): array
    {
        $data = [];
        $data['sel_pos'] = [1=>'',2=>'',3=>'',4=>''];
        $data['hefen_pos'] = [1=>'',2=>'',3=>'',4=>''];
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
        }elseif($playway ==3){
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
    public static function getMyLotteryTypes($uid=0, $useCache=true){

        $mkey = CacheKeyService::userLotteryTypes($uid);
        if($useCache && $typeDatas = commonRedis()->get($mkey)){
            return $typeDatas;
        }
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

        commonRedis()->setex($mkey, \Yii::$app->params['BASE_DATA_CACHE_TIME'], $typeDatas);

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
        //$activeQihao = BetService::getActiveQihao($uid, $tz_system_id, $lottery_type);
        list($currentKjQiHao, $activeQiHao) = QihaoService::getKjQiHao($lottery_type);
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
        $tmpRst = $BetService->betByCodes($activeQiHao, $codes, $uid, $single, $playway, $lottery_type);
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
                'qihao' => $activeQiHao,  // 投注期号
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
            Tool_Common::log('/quick_bet/'.__FUNCTION__, 'INFO', '新过滤快打', ['account'=>$account, 'qihao'=>$activeQiHao, 'playway'=>$playway, 'counts'=>count($codes), 'insertRst'=>$insertRst]);

        }
        $rst['data']['push_rst'] = $rstData;

        $rst['data']['push_data']['qihao'] = $activeQiHao;
        $rst['data']['push_data']['nums'] = count($codes);
        $rst['data']['push_data']['money'] = round($totalmoney,2);
        $rst['data']['push_data']['code_desc'] = $post_desc;
        $rst['data']['push_data']['codes'] = str_replace('@', ',',str_replace(',', '', implode('@', $codes)));

        return $rst;
    }

    /**
     * 删除计划相关的表数据
     * @param string $plan_id
     * @param string $user_id
     * @return bool
     * @throws yii\db\Exception
     */
    public static function deleteOnePlanDatas($plan_id='', $user_id=''){

        try {
            $db = BaseModel::getDb();
            $transaction = $db->beginTransaction();

            # 1、号码导入表
            if($user_id==1){
                $sql1 = "DELETE FROM ".ImportPlanCodes::tableName()." WHERE plan_id='{$plan_id}'";
            }else{
                $sql1 = "DELETE FROM ".ImportPlanCodes::tableName()." WHERE plan_id='{$plan_id}' AND uid='{$user_id}'";
            }
            $db->createCommand($sql1)->execute();

            # 2、计划表
            if($user_id==1){
                $sql2 = "DELETE FROM ".UserSysPlans::tableName()." WHERE id='{$plan_id}'";
            }else{
                $sql2 = "DELETE FROM ".UserSysPlans::tableName()." WHERE id='{$plan_id}' AND uid='{$user_id}'";
            }
            $db->createCommand($sql2)->execute();

            # 3、利润表
            if($user_id==1){
                $sql3 = "DELETE FROM ".PlanStaticProfits::tableName()." WHERE plan_id='{$plan_id}'";
            }else{
                $sql3 = "DELETE FROM ".PlanStaticProfits::tableName()." WHERE plan_id='{$plan_id}' AND uid='{$user_id}'";
            }
            $db->createCommand($sql3)->execute();

            $transaction->commit();
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/plan/'.__FUNCTION__, 'INFO', '计划涉及到数据删除失败', ['plan_id'=>$plan_id, 'err_msg'=>$e->getMessage()]);
            return false;
        }

        return true;
    }

}
