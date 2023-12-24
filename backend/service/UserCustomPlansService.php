<?php

/**
 * Created by PhpStorm.
 *   
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use backend\models\SscDsYl;
use backend\models\SysPlansCodes;
use backend\models\UserCustomPlans;
use  yii;

class UserCustomPlansService extends BaseService {

    /**
     * @desc 预处理表单信息
     * @param $post
     * @param int $playway
     * @param $account
     * @return bool
     */
    public static function preOpData(&$post,$playway = 1, $account=''){
        if(!$post OR !$account) return false;
        $post['UserCustomPlans']['playway'] = $playway;
        if(in_array($playway, [2])){    // 多选框
            $p_1 = $post['UserCustomPlans']['position_1'][0];
            $p_2 = $post['UserCustomPlans']['position_2'][0];
            $p_3 = $post['UserCustomPlans']['position_3'][0];
            $p_4 = $post['UserCustomPlans']['position_4'][0];
        }else{  // 单选框
            $p_1 = $post['UserCustomPlans']['position_1'];
            $p_2 = $post['UserCustomPlans']['position_2'];
            $p_3 = $post['UserCustomPlans']['position_3'];
            $p_4 = $post['UserCustomPlans']['position_4'];
        }
        switch ($playway){
            case 2: # 三字定
                $post['UserCustomPlans']['playway_type'] = 2;
                $positionArr = [ 1=>$p_1, 2=>$p_2, 3=>$p_3, 4=>$p_4 ];
                $positionArr = $pArr = array_filter($positionArr);
                $post['UserCustomPlans']['flag'] = count($positionArr) != 3 ? false : true;
                $keys = array_keys($positionArr);
                $post['UserCustomPlans']['positions'] = implode(',',$keys);
                $post['UserCustomPlans']['periods_open'] = '';
                $post['UserCustomPlans']['periods_close'] = '';
                $post['UserCustomPlans']['codes'] = BaseNumService::dw4ZuHe($p_1,$p_2,$p_3,$p_4);

                break;
            case 3: # 四字定
                $positionArr = [ 1=>$p_1, 2=>$p_2, 3=>$p_3, 4=>$p_4 ];
                $positionArr = $pArr = array_filter($positionArr);
                $positionArr = array_filter($positionArr);
                $post['UserCustomPlans']['flag'] = count($positionArr) != 4 ? false : true;
                $post['UserCustomPlans']['positions'] = '1,2,3,4';
                $post['UserCustomPlans']['codes'] = BaseNumService::dw4ZuHe($p_1,$p_2,$p_3,$p_4);
                //$post['UserCustomPlans']['flag'] = SscDataService::just3DwRight();
                if(in_array($playway, [2,3])){
                    foreach ($pArr as $k=>$p){
                        if(!in_array($p, [3,4])) return '';
                        $tmpZhi[] =  ($p % 2 == 0) ? 2 : 1;
                    }
                    $post['UserCustomPlans']['hezhis'] = implode('',$pArr); // zhi:1211
                }

                break;
            default:    # 默认二字定，playway 1
                //p($post,0);
                $positions = $post['UserCustomPlans']['positions'];
                $post['UserCustomPlans']['positions'] = implode('|', $positions);
                $hezhis = $post['UserCustomPlans']['hezhis'];
                $post['UserCustomPlans']['hezhis'] = implode(',',$hezhis);
                $post['UserCustomPlans']['flag'] = !TzService::customPlanIsExist($account,$positions, $hezhis);
                $codes = '';
                //p([$positions,$hezhis],0);
                foreach ($hezhis as $hezhi){
                    foreach ($positions as $position){
                        $zu = explode(',',$position);
                        $codes .= BaseNumService::dwZuHe($zu,[$hezhi]).'@';
                    }
                }
                //p($codes);
                $post['UserCustomPlans']['codes'] = trim($codes, '@');
                break;
        }

        if(in_array($playway, [2,3])){  // 三定四定单双 zhi
            foreach ($pArr as $k=>$p){
                if(!in_array($p, [3,4])) return '';
                $tmpZhi[] =  ($p % 2 == 0) ? 2 : 1;
            }
            $post['UserCustomPlans']['hezhis'] = implode('',$tmpZhi); // zhi:122 zhi:1211
        }
        //p($post);
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
        $codeTypes = StaticService::$typeArr;
        unset($codeTypes[0], $codeTypes[10], $codeTypes[11]);
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


}
