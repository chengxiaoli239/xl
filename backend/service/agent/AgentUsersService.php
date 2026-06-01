<?php
namespace backend\service\agent;
use backend\models\AgentUsers;
use backend\models\AgentUsersBalanceFlows;
use backend\models\open\PlatformRobot;
use backend\models\statics\Static3dUserProfitsDayAll;
use backend\models\TzSystemsUsers;
use backend\service\BaseService;
use backend\service\UserService;
use common\helpers\Platform;
use common\models\wechat\WechatUser;
use common\service\CommonService;
use common\service\jobs\robots\message\WechatPrivateMsgReceiveJobs;
use common\service\jobs\statics_3d\UserDayStaticsJobs;
use common\service\jobs\telegram\MessageReceiveJobs;
use common\service\message\Send;
use common\service\thirdD\CommonBaseService;
use common\service\wechat\WechatUserService;
use common\tools\Tool_Common;
use yii\helpers\ArrayHelper;
use  yii;
use yii\helpers\Json;

class AgentUsersService extends BaseService {

    public static function opPreData(&$post, $agent_id=''){
        if(!$post OR !$agent_id) return false;

        $fields = ['is_tuo', 'is_cha', 'is_chi', 'is_bind'];
        $post = CommonService::opPreStatusFields($post, $fields, $model = 'AgentUsers');

        $data = $post[$model];
        if(!$id = $data['id']){
            $post[$model]['created_at'] = time();
            $post[$model]['token'] = self::getUserToken($post['name'], $agent_id);
        }

        $post[$model]['agent_id'] = $agent_id;
        $post[$model]['updated_at'] = time();

        return $post;
    }

    /**
     * @desc 获取用户token，用于聊天窗口识别用户
     * @param string $name
     * @param string $agent_id
     * @return string
     */
    public static function getUserToken($name = '', $agent_id = '', $randKey = 'MLGB'){
        return substr(md5($name.'_'.$agent_id.'_'.$randKey), 0,12);
    }

    /**
     * @description 更新计划表状态
     * @param $id
     * @param $account
     * @return array
     */
    public static function updateAgentUsersStatus($id, $status, $uid = '', $field='')
    {
        if(!$uid) return ['status'=>300, 'msg'=>'用户id为空'];
        $model = AgentUsers::findOne(['agent_id'=>$uid, 'id'=>$id]);
        if(!$model) return ['status'=>301, 'msg'=>'找不到记录'];
        $m = \Yii::$app->cache;
        $mkey = 'updateSysPlansStatus_'.$field.'_'.$id.'_'.$status;
        //if($rst = $m->get($mkey)) return ['status'=>302, 'msg'=>'正在修改'];
        //p([$id, $status, $uid , $field]);

        $model->$field = (int)$status;
        $model->updated_at = time();
        $model->token = $model->token ? $model->token : self::getUserToken($model->name, $uid);

        $m->set($mkey, 1, 10);

        $rst = $model->save(false);

        $rstData = ['rst'=>$rst];

        return $rstData;
    }

    public static function actUpUserData($post, $agent_id = ''){
        if(!$agent_id) return ['status'=>301, 'msg'=>'不是代理账号，不能修改用户信息'];
        $act = $post['act'];
        $id = $post['id'];

        $rst = ['status'=>200];
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            if(in_array($act, ['act-up-balance', 'act-down-balance'])){
                if(!$balance = trim($post['balance'])){
                    throw_info('积分不能为空');
                }else{
                    $where = ['id'=>$id];
                    $is3dAdmin = UserService::is3dAdmin(\Yii::$app->user->identity);
                    if(\Yii::$app->user->id != 1 && !$is3dAdmin){
                        $where['user_id'] = $agent_id;
                    }
                    $WechatUser = WechatUser::findOne($where);
                    if(empty($WechatUser)) {
                        throw_info('未找到用户记录1');
                    }
                    if($act == 'act-up-balance'){
                        $f = '加';
                        $WechatUser->balance = $WechatUser->balance + floatval($balance);
                    }elseif ($act == 'act-down-balance'){
                        $f = '减';
                        $WechatUser->balance = $WechatUser->balance - $balance;
                    }

                    if(!$WechatUser->save()){
                        //$rst = ['status'=>303, 'msg'=>$AgentUsers->getFirstError()];
                        throw_info('用户['.$WechatUser->nickName.']'.$f.'分：'.$balance.', 结果：失败');
                    }
                    $operateDesc1 = $f.'分：'.$balance.', 成功&nbsp;<font color="green"><strong>√</strong></font> 当前积分：'.floatval($WechatUser->balance);
                    $operateDesc2 = $f.'分：'.$balance.', 成功 √ 当前积分：'.floatval($WechatUser->balance);
                    $rst['msg'] = '用户['.$WechatUser->nickName.']'.$operateDesc1;
                    $rst['balance_now'] = $WechatUser->balance;
                }
                //todo 1、上下分之后给用户和管理员同时发消息
                (new Send($WechatUser->user_id))->replyAfterAction($WechatUser, [$operateDesc2, '后台操作：给['.$WechatUser->nickName.']'.$operateDesc2], Send::ACTION_BALANCE);
            }elseif ($act == 'act-user-edit'){
                if(empty($post['name'])){
                    throw_info('用户名不能为空');
                }
                if(!$WechatUser = WechatUser::findOne(['user_id'=>$agent_id, 'id'=>$post['id']])){
                    throw_info('未找到用户记录2');
                }
                $WechatUser->nickName = trim($post['name']);
                $WechatUser->token = trim($post['token']);
                $flag = $WechatUser->save();
                if(!$flag) {
                    throw_info('保存失败，用户名：'.$WechatUser->nickName);
                }
                $rst['msg'] = '成功修改用户名：'.$WechatUser->nickName;
                $rst['name_now'] = $WechatUser->nickName;
            }elseif ($act == 'act-user-del'){
                $flag = 0;
                if($WechatUser = WechatUser::findOne(['agent_id'=>$agent_id, 'id'=>$post['id']])){
                    $flag = $WechatUser->delete();
                }
                if(!$flag) {
                    throw_info('删除失败，用户名：'.$WechatUser->nickName);
                }

                $rst['msg'] = '成功删除用户：'.$WechatUser->nickName;
            }
            $transaction->commit();
        }catch (\Exception $e){
            $transaction->rollBack();
            return ['status'=>302, 'msg'=>$e->getMessage()];
        }

        return $rst;
    }

    /**
     * @desc 审核用积分流水
     * @param $data
     * @param $user_id
     * @return array
     */
    public static function userFlowsCheck($data, $user_id = '', $desc = '代理操作'): array
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            Tool_Common::log('/agent_user/'.__FUNCTION__, 'INFO', '代理审核或拒绝用户的申请', ['data'=>$data, 'user_id'=>$user_id, 'desc'=>$desc]);
            if(!$data['id']) {
                throw_info('缺少参数id');
            }

            if(empty($user_id)){
                throw_info('不是代理,无权限');
            }

            if(!$flows = AgentUsersBalanceFlows::findOne(['id'=>$data['id'], 'agent_id'=>$user_id])){
                throw_info('未找到记录');
            }

            $status = $data['status']; # 审核状态 0未审核1审核通过2拒绝
            $balanceType = $flows['type'];
            // todo 此处要改成wechat_user表model
            $WechatUser = WechatUser::findOne(['id'=>$flows->member_id, 'user_id'=>$user_id]);
            if($status == AgentUsersBalanceService::FLOW_CHECK_STATUS_PASS){
                if($balanceType == WechatUserService::TYPE_BALANCE_UP){
                    $before_balance = $WechatUser->balance;
                    $after_balance = $before_balance + $flows->balance; # 1 上分，积分增加
                }elseif($balanceType == WechatUserService::TYPE_BALANCE_DOWN){
                    $before_balance = $flows->balance_now;
                    $after_balance = $WechatUser->balance; # 下分审核成功则等待打款，这里不在做扣款处理（审核时已经扣减）
                }
                $WechatUser->balance = $after_balance; # 审核后的积分，
                $WechatUser->updated_at = time();
                if(!$WechatUser->save()){
                    throw_info(current($WechatUser->getErrors()));
                }
            }elseif($status == AgentUsersBalanceService::FLOW_CHECK_STATUS_REFUSE){ # 审核拒绝
                if($balanceType == WechatUserService::TYPE_BALANCE_UP){
                    $before_balance = $WechatUser->balance;
                    $after_balance = $WechatUser->balance;
                }elseif($balanceType == WechatUserService::TYPE_BALANCE_DOWN){
                    $before_balance = $WechatUser->balance;
                    $after_balance = $WechatUser->balance + $flows->balance; # 2 下分拒绝，积分回退
                }

                $WechatUser->balance = $after_balance; # 审核后的积分，
                $WechatUser->updated_at = time();
                if(!$WechatUser->save()){
                    throw_info(current($WechatUser->getErrors()));
                }
            }

            $flows->balance_after = $after_balance;
            $flows->status = $status;
            $flows->desc = $flows->desc.'=>'.$desc;
            $flows->check_time = (string)time();
            if(!$flows->save()){
                throw_info(current($flows->getErrors()));
            }
            $transaction->commit();

            $replyTxt = '申请'.WechatUserService::$s['balance_type'][$balanceType]. $flows->balance .
                "\n【结果】".AgentUsersBalanceService::$s['status'][$status].
                "\n【操作前】".floatval($before_balance).
                "\n【盛鱼】".floatval($after_balance);
            $d = Json::decode($flows->message);
            (new Send($user_id))->replyAfterAction($WechatUser, [$replyTxt, '审核通过：'.$WechatUser->nickName . $replyTxt], Send::ACTION_BALANCE); # 发送消息
            push_queue_fast(UserDayStaticsJobs::class, ['user_id'=>$user_id, 'type'=>$balanceType, 'msg'=>'上下分后报表计算', 'wechat_user_id'=>$WechatUser->id]);
        }catch (\Exception $e){
            $transaction->rollBack();
            Tool_Common::log('/agent_user/'.__FUNCTION__, 'ERR', '审核异常', ['data'=>$data, 'err_msg'=>$e->getMessage()]);
            return ['status'=>303, 'msg'=>$e->getMessage()];
        }

        return ['status'=>200, 'msg'=>'操作成功', 'data'=>['status'=>200, 'msg'=>$desc]];
    }


    /**
     * @desc 审核用积分流水
     * @param $data
     * @param $user_id
     * @return array
     */
    public static function userGetInfo(array $wechatUser=[]): array
    {
        try {
            $replyTxt = "【余分】".$wechatUser['balance'];
            $member_id = $wechatUser['id'];
            $date = date('Y-m-d');
            $where = [
                'AND',
                ['=', 'wechat_user_id', $member_id],
                ['between', 'created_at', strtotime($date.' 00:00:00'), strtotime($date.' 00:00:00')],
            ];
            $statics = Static3dUserProfitsDayAll::findOne($where);
            if(!empty($statics)){
                if($statics->up_money>0){
                    $replyTxt .= "\n【上】".$statics->up_money;
                }
                if($statics->down_money>0){
                    $replyTxt .= "\n【下】".$statics->down_money;
                }
                if($statics->bet_money>0){
                    $replyTxt .= "\n【投】".$statics->bet_money;
                }
                if($statics->bonus>0){
                    $replyTxt .= "\n【中】".$statics->bonus;
                }
            }
        }catch (\Exception $e){
            Tool_Common::log('/agent_user/'.__FUNCTION__, 'ERR', '查询异常', ['wechatUser'=>$wechatUser, 'err_msg'=>$e->getMessage()]);
            return ['status'=>303, 'msg'=>$e->getMessage()];
        }

        return [CommonBaseService::CODE_FOR_USER, [], $replyTxt];
    }

    /**
     * @desc 类型名称
     * @param $type
     * @return mixed
     */
    public static function getFlowTypeTxt($type){
        $types = WechatUserService::$s['balance_type'];

        return $types[$type];
    }

    public static function getFlowtypes(){

        return [1=> '上分', 2=>'扣分'];
    }

    /**
     * @desc 随机获取用户头像
     * @param $agent_id
     * @return string
     */
    public static function getImages($agent_id){
        $num = rand(2, 13);
        $img = 'static/images/avatar/f1/f_'.$num.'.jpg';

        return $img;
    }

    /**
     * @desc 检测网络是否通，切换网盘线路
     * @return array
     */
    public static function pingTzSystemUsersDomain(){
        $rst = ['status'=>200, 'msg'=>'操作成功'];

        return $rst;
    }

    /**
     * @desc 是否为代理
     * @param string $admin_id
     * @return bool
     */
    public static function isAgent($admin_id = ''){
        $flag = false;
        //if(empty($admin_id)) $flag = false;
        $m = \Yii::$app->cache;
        $mkey = 'IS_AGENT_UID_'.$admin_id;
        if(true OR !$m->get($mkey)){
            $where = ['AND', ['=', 'uid', $admin_id], ['=', 'status', 1]];
            $TzSystemsUsers = TzSystemsUsers::find()->where($where)->one();
            if(!empty($TzSystemsUsers) && $TzSystemsUsers->is_agent){
                $flag = true;
            }
            $m->set($mkey, $flag, 3600);
        }
        return $flag;
    }


    /**
     * 获取跟买倍数
     * @param object $TzSystemsUsers
     * @param float $single
     * @param int $buy_type
     * @return array
     */
    public static function getFlowSingle(object $TzSystemsUsers, $single=0.1, $buy_type=0, $playway=3){

        if($buy_type == 1){
            $bet_single = $single * $TzSystemsUsers->flow_wp_player_bs;
        }else{
            $bet_single = $single * $TzSystemsUsers->flow_op_player_bs;
        }
        $bet_single = floor($bet_single * 10)/10;  # bet_single 向下保留一位小数
        if(in_array($playway, [2, 3]) && $bet_single<0.1){
            $bet_single = 0.1;
        }
        if(in_array($playway, [1])){
            if($bet_single<1){
                $bet_single = 1;
            }else{
                $bet_single = (int)$bet_single;
            }
        }

        return [0, $bet_single];
    }
}
