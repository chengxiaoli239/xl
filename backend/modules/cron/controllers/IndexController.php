<?php
/**
 * Created by PhpStorm.
 *  
 * Date: 18/02/04
 * Time: 下午23:55
 */

namespace backend\modules\cron\controllers;

use backend\models\SystemConfig;
use backend\models\TzSystemsUsers;
use backend\service\baota\BaoTaService;
use backend\service\BaseService;
use backend\service\datas\DatasClearService;
use backend\service\Juhua\JuHuaBaseService;
use backend\service\sports\TennisSportsService;
use backend\service\SscDataService;
use backend\service\WxService;
use common\service\CommonService;
use common\service\index\CrontabIndexService;
use common\service\proxy\ProxyBaseService;
use Yii;
use backend\service\OpKjService;
use common\tools\KjDataGet;
use yii\web\Controller;
use common\tools\Tool_Common;
use backend\service\BetService;
use backend\service\StaticService;


class IndexController extends Controller
{
    private static $staticStatus = 0;
    private static function _init()
    {
        self::$staticStatus = SystemConfig::findOne(['key'=>'static_status'])->value;
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    /**
     * @desc 号码和值投注记录
     * @return array
     */
    public function actionGrabTzHzList(){
        self::_init();
        $lottery_types = StaticService::getLotteryTypes();
        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1, 'tz_system_id'=>[1, 2]]);
        foreach ($lottery_types as $lottery_type){

            foreach ($TzSystemsUsers as $TzSystemsUser){
                switch ($TzSystemsUser->tz_system_id){
                    case 1: # 0898
                    case 2: # 99 彩票网
                        if(in_array($lottery_type, [5, 6]))
                            //$rst = NineNineBaseService::getRemoteHzRecords($TzSystemsUser->uid, $TzSystemsUser->tz_system_id, $lottery_type); # 抓取号码
                        break;
                    case 3:
                        break;
                    case 4: # 北京快乐8
                        break;
                }
            }
        }
        return $rst;
    }

    /**
     * @desc 用户计划投注
     * @return array
     */
    public function actionBet(){
        self::_init();
        $time = date("H:i");
        /*
        if(\Yii::$app->params['ssc_kj_time_start'] <= $time && $time <= \Yii::$app->params['ssc_kj_time_end'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        */
        $rst['bet'] = BetService::bet(); // 用户新计划投注，可正买可反买

        return $rst;
    }

    /**
     * @desc 访问首页
     * @return mixed
     */
    public function actionVisitHomePage(){
        self::_init();
        $lottery_types = StaticService::getLotteryTypes();
        $TzSystemsUsers = TzSystemsUsers::findAll(['status'=>1]);

        Tool_Common::log('actionVisitHomePage', 'INFO', '访问首页',['lottery_types'=>$lottery_types]);
        foreach ($lottery_types as $lottery_type){
            foreach ($TzSystemsUsers as $tzSystemsUser){

                if($tzSystemsUser->tz_system_id == 11){
                    if($lottery_type != 9) continue;
                    # 是否有激活的计划
                    $hasActivePlan = CommonService::hasPlansActiveSys($tzSystemsUser->tz_system_id, $tzSystemsUser->uid);
                    if(!$hasActivePlan){
                        return false;
                    }
                    $flag1 = JuHuaBaseService::getHomePage($tzSystemsUser->uid, $tzSystemsUser->tz_system_id, $lottery_type);
                    $flag2 = JuHuaBaseService::selectLottery($tzSystemsUser->uid, $tzSystemsUser->tz_system_id, $lottery_type);
                }
            }
        }

        return ['flag1'=>$flag1, 'flag2'=>$flag2];
    }

    /**
     * @desc 批量插入投注任务
     * @return array
     */
    public function actionBatchSimulateBet(){
        self::_init();
        ini_set('memory_limit','1024M'); //升级为1024M内存
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        $post = \Yii::$app->request->post();

        try {
            for ($i=0; $i<100; $i++){
                $batch_simulate_data = BetService::batchSimulateBet($post['lottery_types'], $post['uid']);
                $rst['batch_simulate_data'] = $batch_simulate_data;
                Tool_Common::log('/datas/'.__FUNCTION__, 'INFO', '批量模拟下注', ['uid'=>$post['uid'], 'batch_simulate_data'=>$batch_simulate_data]);
                sleep(1);
            }
        }catch (\Exception $e){
            Tool_Common::log('/datas/'.__FUNCTION__.'_e', 'ERR', '批量模拟下注-异常', ['uid'=>$post['uid'], 'err_msg'=>$e->getMessage()]);
        }

        return $rst;
    }

    /**
     * @desc 同步宝塔计划任务
     * @return array|bool|string
     */
    public static function actionSyncBtCrontabs(){
        self::_init();

        $rst = BaoTaService::syncBaoTaCrontabs();

        return $rst;
    }

    /**
     * @description 同步余额
     * @return array
     * @throws - NotFoundHttpException
     */
    public function actionSynBalance(){
        self::_init();
        $rst = ['status'=>200, 'msg'=>'同步余额'];
        //$TzSystemsUsers = TzSystemsUsers::find()->where(['AND', ['=', 'status', 1], ['!=', 'tz_system_id', 9]])->all(); # 9:幸运五 python已经做同步,这里不需要再重复请求
        $TzSystemsUsers = TzSystemsUsers::find()->where(['AND', ['=', 'status', 1], ['>', 'expire_time', time()]])->all(); # 9:幸运五 python已经做同步,这里不需要再重复请求

        foreach ($TzSystemsUsers as $TzSystemsUser){
            $rst['rst'][$TzSystemsUser->id] = BaseService::synBalance($TzSystemsUser->id);
        }
        return $rst;
    }

    /**
     * @desc 缓存代理IP数据
     * @demo curl http://www.lottery.com/cron/index/cache-proxy-ip
     * @return array
     */
    public function actionCacheProxyIp($is_auto=1){
        self::_init();
        for ($i=0; $i<1; $i++){
            foreach (ProxyBaseService::$proxy_types as $proxy_type){
                $rst = ProxyBaseService::preGetValidIp($proxy_type, $is_auto);
                Tool_Common::log('/proxy/'.__FUNCTION__, 'INFO', '缓存代理IP数据', ['proxy_type'=>$proxy_type, 'is_auto'=>$is_auto, 'rst'=>$rst]);
            }
            sleep(15);
        }

        return $rst;
    }

    /**
     * @desc 状态的开启跟关闭
     */
    public function actionSwitchStatus(){

    }

    /**
     * @desc 抓取网球赛事
     * @return array
     */
    public function actionGrabSportsGames(){
        self::_init();

        $rst = TennisSportsService::grabTennisSportsGame();

        return $rst;
    }

    /**
     * @return array
     */
    public function actionSetPeiShuTrueFalse(){
        self::_init();
        $post = \Yii::$app->request->post();
        for ($i=0;$i<10;$i++){
            $rst = SscDataService::staticPeiShuTrueFalse($post['lottery_types']);
            sleep(5);
        }

        return $rst;
    }

    /**
     * @desc 微信心跳检测
     * @return array
     */
    public function actionWxSyncCheck(){
        self::_init();
        $post = \Yii::$app->request->post();
        $uid = $post['uid'];

        $rst = WxService::syncCheckTask($uid);

        return $rst;
    }

}
