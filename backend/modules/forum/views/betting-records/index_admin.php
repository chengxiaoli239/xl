<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\BaseStringHelper;
use backend\models\SscKjData;
$newRecord = SscKjData::find()->select(['qihao','code_str'])->where(['lottery_type'=>$lottery_type])->orderBy('id DESC')->asArray()->limit(1)->one();
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BettingRecords */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Betting Records');
$this->params['breadcrumbs'][] = $this->title;
$sys_profits_3d = \backend\models\VPerdateProfits::findOne(['playway'=>2, 'tz_date'=>date('Y-m-d')])->profits;
$sys_profits_3d = $sys_profits_3d ? $sys_profits_3d : 0.00;
$profits_desc = date('Y-m-d').'系统投注利润：三定 : '.$sys_profits_3d;
?>
<section class="betting-records-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title);echo '['.$newRecord['qihao'].':'.$newRecord['code_str'].']   ['.$profits_desc.']'; ?>
            <?= Html::a(Yii::t('app', 'playway 2 Plans'), ['index', 'BettingRecords[playway]'=>2], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
            <?= Html::a(Yii::t('app', 'playway 3 Plans'), ['index', 'BettingRecords[playway]'=>3], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Betting Records', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'pager' => [
                        'firstPageLabel' => '首页',
                        'lastPageLabel' => '尾页',
                    ],
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'qihao',
                        ['attribute' => 'qihao','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->qihao;
                            }
                        ],
                        ['attribute' => 'codes',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->codes,25);
                                return Html::a($txt, '#', ['title' => $model->codes,'alt'=>$model->codes]);
                            }
                        ],
                        //'betting_money',
                        ['attribute' => 'betting_money','label'=>'投注','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->betting_money;
                            }
                        ],
                        //'bonus',
                        ['attribute' => 'bonus','label'=>'中奖','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->bonus>0 ? '<font color="green">'.$model->bonus.'</font>' : ' ';
                            }
                        ],
                        //'single',
                        ['attribute' => 'single','label'=>'倍',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->single;
                            }
                        ],
                        'profits',
                        /*
                        ['attribute' => 'profits','headerOptions' => ['width' => '5%'],
                            'value' => function($model) {
                                return $model->profits;
                            }
                        ],
                        */
                        //'kj_codes',
                        ['attribute' => 'kj_codes','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->kj_codes ? $model->kj_codes : '待开奖';
                            }
                        ],
                        //'position',
                        //'status',
                        //'sn',
                        //'snid',
                        //'account',
                        ['attribute' => 'account','label'=>'账号','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::a($model->account, '/forum/betting-records/index?BettingRecords[account]='.$model->account);
                            }
                        ],
                        //'plan_id',
                        ['attribute' => 'plan_id','label'=>'planid','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::a($model->plan_id, '/forum/betting-records/index?BettingRecords[plan_id]='.$model->plan_id);
                            }
                        ],
                        ['attribute'=>'snid', 'label'=>'操作',
                            'format'=>'raw',
                            'value'=>function($model){
                                $url1 = "/forum/betting-records/cancel-order?bet_id=".$model->id;
                                $url2 = "/forum/betting-records/tz-now?id=".$model->id; # 追加下注
                                $url3 = "/forum/betting-records/reverse-tz-now?id=".$model->id; # 反买
                                if($model->is_simulate){
                                    $str = '模拟'.$model->plan_id.'_'.$model->sn;
                                    //$str .= ' | '.Html::a('下注', $url2, ['title' => '下注'.$model->id,'alt'=>$model->id]);
                                    //$str .= ' | '.Html::a('反买', $url3, ['title' => '反买'.$model->snid,'alt'=>$model->snid]);
                                    return $str;
                                }
                                if($model->cancel_status == 1){
                                    $rst = '<font color="red">已撤单</font>';
                                    if($model->playway == 2){
                                        //$rst .= ' | '.Html::a('点击反买', $url3, ['title' => '点击反买:'.$model->snid,'alt'=>$model->snid]) ;
                                    }
                                    if(\Yii::$app->user->id == 1) $rst = '已撤单';
                                    return $rst;
                                }
                                if(!$model->status){
                                    $str = Html::a('点击撤单', $url1, ['title' => '点击撤单:'.$model->snid,'alt'=>$model->snid]);
                                    $str .= ' | '.Html::a('点击下注', $url2, ['title' => '点击下注:'.$model->id,'alt'=>$model->id]);
                                    if(\Yii::$app->user->id == 1) $str = '<font color="green">等待开奖</font>';
                                    return $str;
                                }
                                return BaseStringHelper::truncate($model->snid,15);
                            }
                        ],
                        //'playway',
                        //'playway_name',
                        ['attribute' => 'playway_name','label'=>'方式',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $str = $model->playway_name;
                                if($model->playway == 2 && $model->tz_type > 0){
                                    $buy_type_Arr = [0=>'反买', 1=>'正买'];
                                    $str .= '['.$buy_type_Arr[$model->buy_type].']';
                                }
                                if($model->playway == 3 && $model->tz_type > 0){
                                    //$str .= '['.\backend\service\StaticService::$kArr[$model->tz_type].']';
                                    $str .= '['.\backend\service\BetService::getTypeNameByTzType($model->tz_type).']';
                                }
                                return $str;
                            }
                        ],
                        //'is_simulate',
                        //'lotteryclass',
                        //'createtime:datetime',
                        //'create_time',
                        ['attribute' => 'create_time','label'=>'时间','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->create_time;
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn','headerOptions' => ['width' => '5%'],'template'=>'{view}  {delete}'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
