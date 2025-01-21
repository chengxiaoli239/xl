<?php
namespace console\modules\data\controllers;

use backend\models\SystemConfig;
use backend\models\thirdD\BetsBackend;
use backend\models\TzSystemsUsers;
use backend\service\baota\BaoTaService;
use backend\service\BaseService;
use backend\service\datas\DatasClearService;
use backend\service\SscDataService;
use common\service\index\CrontabIndexService;
use common\service\jobs\kj_data\UserBetJob;
use common\service\proxy\ProxyBaseService;
use common\service\thirdD\CommonBaseService;
use common\service\thirdD\OperateLotteryService;
use Yii;
use backend\service\OpKjService;
use common\tools\KjDataGet;
use yii\base\Controller;
use common\tools\Tool_Common;
use backend\service\BetService;
use backend\service\StaticService;


class IndexController extends Controller
{
    private static int $staticStatus = 0;
    private static function _init()
    {
        self::$staticStatus = SystemConfig::findOne(['key'=>'static_status'])->value;
        //\Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
    }

    /**
     * @desc 逐期获取开奖数据
     * /www/server/php/74/bin/php yii data/index/grab-kj-data
     * @return array
     */
    public function actionGrabKjData(): array
    {
        self::_init();
        for($i=0; $i<3; $i++){
            $rst['kj'] = KjDataGet::grabKjData();
            sleep(15);
        }

        return $rst;
    }

    /**
     * /www/server/php/74/bin/php yii data/index/run-lottery
     */
    public function actionRunLottery(): bool
    {
        $dateHI = date('H:i');
        if('00:00'<$dateHI && $dateHI<'21:00'){
            var_dump('未在开奖时间区间，暂不处理');
            return false;
        }
        foreach (CommonBaseService::THIRDD_LOTTERY_TYPES as $lottery_type){
            $rst = OperateLotteryService::operate($lottery_type);
            var_dump($rst);
        }
        return true;
    }


    /**
     * @desc 统计
     * @return array
     */
    public function actionOpStatic(): array
    {
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];
        $start_time = microtime(true);
        $rst[] = StaticService::staticAll2NumsYl(); # 统计所有二字现遗漏
        $log_time = microtime(true);

        $rst['consume_time'] = ($log_time - $start_time).'s';

        return $rst;
    }

    /**
     * @desc 四定单双利润统计
     * @return array
     */
    public function actionStaticSdProfits(): array
    {
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];

        $rst = StaticService::opAllStaticProfits();

        return $rst;
    }

    /**
     * @desc 四定和值利润统计
     * @return array
     */
    public function actionStaticHzProfits(): array
    {
        self::_init();
        if(!self::$staticStatus) return ['status'=> 300, 'msg'=>'数据统计开关已关闭'];
        $post = \Yii::$app->request->post();
        $rst = StaticService::opStatic($post['lottery_types']); # 和值、四定利润统计

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
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii data/index/bet-by-uid
     * @desc 多线程跑用户计划
     * @return mixed
     */
    public static function actionBetByUid(){
        $tzStatus = SystemConfig::findOne(['key'=>'tz_status'])->value;
        if(!$tzStatus) return ['status'=>300, 'msg'=>'投注开关未开启'];
        self::_init();
        set_time_limit(0);
        $for_times = 8;
        $sleep_time = rand(4, 8);
        $where = [
            'AND',
            ['=', 'is_local_bet', BetsBackend::BET_TYPE_SERVER_API],
            ['=', 'status', 1],
            ['>=', 'expire_time',  time()]
        ];
        $userIds = TzSystemsUsers::find()->select(['uid'])->where($where)->asArray()->column();
        for($i=0; $i<$for_times; $i++){
            foreach ($userIds as $userId){
                push_queue(UserBetJob::class, ['user_id'=>$userId]);
            }
            sleep($sleep_time);
            $rst[$i]['sleep_time'] = $sleep_time;
        }

        return $rst;
    }


    /**
     * @desc 批量插入投注任务
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii data/index/insert-plans-task
     * @return array
     */
    public function actionInsertPlansTask(){
        self::_init();
        $rst = ['status'=>200, 'msg'=>'操作成功'];
        #$post = \Yii::$app->request->post();
        $lottery_types = [1, 8, 17];

        for ($i=0; $i<7; $i++){
            $rst['data'] = BetService::insertPlansTask($lottery_types);
            //$rst['batch_simulate_data'] = BetService::batchSimulateBet($post['lottery_types']);
            sleep(7);
        }

        return $rst;
    }

    /**
     * @desc 批量插入投注任务
     * @return array
     */
    public function actionBatchSimulateBet(): array
    {
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
     * @description 本地处理开奖数据
     * /www/server/php/74/bin/php /www/wwwroot/lottery_xl/yii data/index/op-kj
     * @return array
     */
    public function actionOpKj($lotteryType='8'): array
    {
        self::_init();
        $time = date("H:i");
        if(\Yii::$app->params['ssc_kj_time_start'] < $time && $time < \Yii::$app->params['ssc_kj_time_start'] ){
            $rst = ['status'=>300, 'msg'=>'当前时间暂停投注~'.date("Y-m-d H:i:s")];
            return $rst;
        }
        if(!empty($lotteryType)){
            $lottery_types = explode(',', $lotteryType);
        }else{
            $lottery_types = StaticService::getLotteryTypes();
        }

        foreach ($lottery_types as $lottery_type) {
            for ($i=0; $i<2; $i++){
                $rst = OpKjService::opSscKjData($lottery_type); # 在抓取完开奖数据已经调用 KjDataGet::grabOne

                sleep(25);
            }
        }

        return $rst??[];
    }

    /**
     * @description 同步余额
     * @return array
     * @throws - NotFoundHttpException
     */
    public function actionSynBalance(): array
    {
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
    public function actionCacheProxyIp($is_auto=1): array
    {
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
     * @return array
     */
    public function actionSetPeiShuTrueFalse(): array
    {
        self::_init();
        $post = \Yii::$app->request->post();
        for ($i=0;$i<10;$i++){
            $rst = SscDataService::staticPeiShuTrueFalse($post['lottery_types']);
            sleep(5);
        }

        return $rst;
    }

    /**
     * @desc 保留最近x天的记录，默认两天
     * @return array
     */
    public function actionDeleteLatestRecords(): array
    {
        self::_init();
        $rst = DatasClearService::deleteLatestRecords();

        return $rst;
    }

}
