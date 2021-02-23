<?php

/**
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/06
 * Time: 09:40
 */

namespace backend\service;
use  yii;

class UserFollowDataService extends BaseService {

    /**
     * @desc 预处理表单信息
     * @param $post
     * @param int $playway
     * @return bool
     */
    public static function preOpData(&$post,$playway = 1){
        if(!$post) return false;
        $post['UserFollowData']['playway'] = $playway;
        if(in_array($playway, [2])){    // 多选框
            $p_1 = $post['UserFollowData']['position_1'][0];
            $p_2 = $post['UserFollowData']['position_2'][0];
            $p_3 = $post['UserFollowData']['position_3'][0];
            $p_4 = $post['UserFollowData']['position_4'][0];
        }else{  // 单选框
            $p_1 = $post['UserFollowData']['position_1'];
            $p_2 = $post['UserFollowData']['position_2'];
            $p_3 = $post['UserFollowData']['position_3'];
            $p_4 = $post['UserFollowData']['position_4'];
        }
        switch ($playway){
            case 2: # 三字定
                $positionArr = [ 1=>$p_1, 2=>$p_2, 3=>$p_3, 4=>$p_4 ];
                $positionArr = array_filter($positionArr);
                $keys = array_keys($positionArr);
                $post['UserFollowData']['position'] = implode(',',$keys);
                $post['UserFollowData']['code'] = BaseNumService::dw4ZuHe($p_1,$p_2,$p_3,$p_4);

                break;
            case 3: # 四字定
                $post['UserFollowData']['position'] = '1,2,3,4';
                $post['UserFollowData']['code'] = BaseNumService::dw4ZuHe($p_1,$p_2,$p_3,$p_4);

                break;
            default:    # 默认二字定，playway 1
                $position = $post['UserFollowData']['position'];
                $zhi = $post['UserFollowData']['codes_hezhi'];
                $post['UserFollowData']['code'] = BaseNumService::dwZuHe(explode(',',$position),[$zhi]);
                break;
        }
        return $post;
    }

    public static function opDwUserFollowData(&$post){

    }

}